<?php
/*-------------------------------------------------------+
| ESR Codes Extension                                    |
| Copyright (C) 2016 SYSTOPIA                            |
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
class CRM_Esr_Form_Task_Contact extends CRM_Contact_Form_Task {

  public function buildQuickForm() {
    CRM_Utils_System::setTitle(E::ts('ESR Code Generation'));

    // // make sure we're only talking about individuals
    // if (!$this->onlyIndividuals()) {
    //   $session = CRM_Core_Session::singleton();
    //   CRM_Core_Session::setStatus("Es können ausschließlich Kontakte vom Typ 'Individual' verarbeitet werden", "Ungültige Auswahl", 'error');
    //   CRM_Utils_System::redirect($session->readUserContext());
    // }

    // register rule
    $this->registerRule('digits_only', 'callback', 'digits_only', 'CRM_Esr_Form_Task_Contact');

    // default settings for campaign mailcodes
    $this->assign('campaign_mailcode_prefix', '313');
    $this->assign('campaign_mailcode_length', 9);

    // load campaigns
    $campaigns = civicrm_api3('Campaign', 'get', array(
      'is_active' => 1,
      'option.limit' => 0,
      ));
    $campaign_list = array('0' => E::ts('No campaign'));
    foreach ($campaigns['values'] as $campaign) {
      $campaign_list[$campaign['id']] = $campaign['title'];
    }

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
      'tn_number',
      E::ts('Participant number'),
      array('class' => 'huge'),
      TRUE
    );
    $this->addRule('tn_number', E::ts('Please enter digits only'), 'digits_only');

    $this->add(
      'text',
      'mailcode',
      E::ts('Mail code'),
      array('class' => 'huge'),
      TRUE
    );
    $this->addRule('mailcode', E::ts('Please enter digits only'), 'digits_only');

    $this->add(
      'select',
      'campaign',
      E::ts('Mail code for campaign'),
      $campaign_list,
      TRUE
    );
    $this->addRule('mailcode', E::ts('Please enter digits only'), 'digits_only');

    $this->add(
      'text',
      'custom_text',
      E::ts('Text module'),
      array('class' => 'huge'),
      FALSE
    );

    $this->add(
        'text',
        'custom_field_id',
        E::ts('Organisation Name Field ID (Custom)'),
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
    $values = civicrm_api3('Setting', 'getvalue', array('name' => 'de.systopia.esr.contact', 'group' => 'de.systopia.esr'));
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
      'amount'      => CRM_Utils_Array::value('amount', $all_values),
      'tn_number'   => CRM_Utils_Array::value('tn_number', $all_values),
      'mailcode'    => CRM_Utils_Array::value('mailcode', $all_values),
      'custom_text' => CRM_Utils_Array::value('custom_text', $all_values),
      'custom_field_id' => CRM_Utils_Array::value('custom_field_id', $all_values),
    );
    civicrm_api3('Setting', 'create', array('de.systopia.esr.contact' => $values));

    if (isset($all_values['_qf_Contact_submit'])) {
      // CREATE CSV
      $generator = new CRM_Esr_Generator();
      $generator->generate(CRM_Esr_Generator::$REFTYPE_BULK_SIMPLE, $this->_contactIds, $values);

    } elseif (isset($all_values['_qf_Contact_next'])) {
      // CREATE ACTIVITY
      $result = civicrm_api3('Activity', 'create', array(
        'activity_type_id'   => (int) CRM_Core_OptionGroup::getValue('activity_type', E::ts('ESR Code Generation')),
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
}
