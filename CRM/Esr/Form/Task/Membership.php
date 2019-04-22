<?php
/*-------------------------------------------------------+
| ESR Codes Extension                                    |
| Copyright (C) 2016-2019 SYSTOPIA                       |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

require_once 'CRM/Core/Form.php';

use CRM_Esr_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Esr_Form_Task_Membership extends CRM_Member_Form_Task {

  public function buildQuickForm() {
    CRM_Utils_System::setTitle(E::ts('ESR Code Generation'));

    // register rule
    $this->registerRule('digits_only', 'callback', 'digits_only', 'CRM_Esr_Form_Task_Contact');

    $this->add(
      'text',
      'tn_number',
      E::ts('Participant number'),
      array('class' => 'huge'),
      TRUE
    );
    $this->addRule('tn_number', E::ts('Please enter digits only'), 'digits_only');


    $this->add(
      'select',
      'paying_contact',
      E::ts('Paying Contact'),
      $this->getPayingOptions(),
      TRUE
    );

    $this->add(
        'select',
        'amount_option',
        E::ts('Payment Amount'),
        $this->getAmountOptions(),
        TRUE
    );

    $this->add(
        'text',
        'amount',
        E::ts('Amount'),
        array('class' => 'tiny'),
        FALSE
    );
    $this->addRule('amount', E::ts('Please enter a valid amount'), 'money');

    $this->add(
      'text',
      'custom_text',
      E::ts('Text module'),
      array('class' => 'huge'),
      FALSE
    );

    parent::buildQuickForm();

    $this->addButtons(array(
      array(
        'type' => 'next',
        'name' => E::ts('Complete'),
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'submit',
        'name' => E::ts('Create CSV'),
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'cancel',
        'name' => E::ts('Cancel'),
        'isDefault' => FALSE,
      ),
    ));
  }



  /**
   * get the last iteration's values
   */
  public function setDefaultValues() {
    $values = civicrm_api3('Setting', 'getvalue', array('name' => 'de.systopia.esr.membership', 'group' => 'de.systopia.esr'));
    if (empty($values) || !is_array($values)) {
      return array();
    } else {
      return $values;
    }
  }



  public function postProcess() {
    // store settings as default
    $all_values = $this->exportValues();

    //Contact:submit
    $values = array(
        'tn_number'      => CRM_Utils_Array::value('tn_number', $all_values),
        'paying_contact' => CRM_Utils_Array::value('paying_contact', $all_values),
        'amount'         => CRM_Utils_Array::value('amount', $all_values),
        'amount_option'  => CRM_Utils_Array::value('amount_option', $all_values),
        'custom_text'    => CRM_Utils_Array::value('custom_text', $all_values),
    );
    civicrm_api3('Setting', 'create', array('de.systopia.esr.membership' => $values));

    if (isset($all_values['_qf_Contact_submit'])) {
      // CREATE CSV
      $generator = new CRM_Esr_Generator();
      $generator->generate(CRM_Esr_Generator::$REFTYPE_MEMBERSHIP, $this->_memberIds, $values);

    } elseif (isset($all_values['_qf_Contact_next'])) {
      // CREATE ACTIVITY
      civicrm_api3('Activity', 'create', array(
        'activity_type_id'   => CRM_Esr_Config::getESRActivityTypeID(),
        'activity_date_time' => date('YmdHis'),
        'subject'            => E::ts('A ESR code has been generated'),
        'source_contact_id'  => CRM_Core_Session::getLoggedInContactID(),
        'target_id'          => $this->_contactIds,
      ));
    }

    parent::postProcess();
  }

  /**
   * check if the selected contacts are all individuals
   * @deprecated
   */
  protected function onlyIndividuals() {
    $filtered_contact_ids = array();
    foreach ($this->_contactIds as $contact_id) {
      $filtered_contact_id = (int) $contact_id;
      if ($filtered_contact_id) {
        $filtered_contact_ids[] = $filtered_contact_id;
      }
    }

    if (empty($filtered_contact_ids)) {
      return TRUE;
    }

    $contact_id_list = implode(',', $filtered_contact_ids);
    $violator = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contact WHERE contact_type != 'Individual' AND id IN ({$contact_id_list}) LIMIT 1;");
    if ($violator) {
      return FALSE;
    } else {
      return TRUE;
    }
  }

  /**
   * RULE that the value should be exlusively numbers
   */
  public static function digits_only($value) {
    return preg_match('/^\d+$/', $value);
  }

  /**
   * Get the list of options to determine who is paying for the membership
   */
  public function getPayingOptions() {
    $options = [
        'member' => E::ts("Member (itself)"),
    ];

    // add all membership custom fields that reference contacts
    $membership_group_ids = $this->getMembershipCustomGroupIDs();
    if (!empty($membership_group_ids)) {
      $fields = civicrm_api3('CustomField', 'get', [
          'custom_group_id' => ['IN' => $membership_group_ids],
          'data_type'       => "ContactReference",
          'is_active'       => 1,
          'sequential'      => 1,
          'return'          => 'id,label']);
      foreach ($fields['values'] as $field) {
        $options[$field['id']] = E::ts("Field: %1", [1 => $field['label']]);
      }
    }

    return $options;
  }

  /**
   * Get the list of options to determine who is paying for the membership
   */
  public function getAmountOptions() {
    $options = [
        'type'  => E::ts("Membership Type"),
        'fixed' => E::ts("Fixed Amount"),
    ];

    // add all membership custom fields that reference contacts
    $membership_group_ids = $this->getMembershipCustomGroupIDs();
    if (!empty($membership_group_ids)) {
      $fields = civicrm_api3('CustomField', 'get', [
          'custom_group_id' => ['IN' => $membership_group_ids],
          'data_type'       => "Money",
          'is_active'       => 1,
          'sequential'      => 1,
          'return'          => 'id,label']);
      foreach ($fields['values'] as $field) {
        $options[$field['id']] = E::ts("Field: %1", [1 => $field['label']]);
      }
    }

    return $options;
  }

  /**
   * Get the list of membership custom group IDs
   */
  public function getMembershipCustomGroupIDs() {
    $query = civicrm_api3('CustomGroup', 'get', [
        'extends'      => 'Membership',
        'is_active'    => 1,
        'sequential'   => 0,
        'option.limit' => 0,
        'return'       => 'id']);
    return array_keys($query['values']);
  }
}
