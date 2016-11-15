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

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Esr_Form_Task_Contact extends CRM_Contact_Form_Task {

  public function buildQuickForm() {
    CRM_Utils_System::setTitle("ESR Code Generierung");

    $this->add(
      'text',
      'tn_number',
      "Teilnehmer-Nummer",
      array('class' => 'huge'),
      false
    );
  
    $this->add(
      'text',
      'mailcode',
      "Mailcode",
      array('class' => 'huge'),
      false
    );

    $this->add(
      'text',
      'custom_text',
      "Textbausteine",
      array('class' => 'huge'),
      false
    );

    parent::buildQuickForm();

    $this->addButtons(array(
      array(
        'type' => 'next',
        'name' => "Abschließen",
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'submit',
        'name' => "CSV Erstellen",
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'cancel',
        'name' => "Abbrechen",
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
      'tn_number'   => CRM_Utils_Array::value('tn_number', $all_values),
      'mailcode'    => CRM_Utils_Array::value('mailcode', $all_values),
      'custom_text' => CRM_Utils_Array::value('custom_text', $all_values),
    );
    civicrm_api3('Setting', 'create', array('de.systopia.esr.contact' => $values));

    if (isset($all_values['_qf_Contact_submit'])) {
      // CREATE CSV
      $generator = new CRM_Esr_Generator();
      $generator->generate($this->_contactIds, $values);

    } elseif (isset($all_values['_qf_Contact_next'])) {
      // CREATE ACTIVITIES
      error_log("CREATE ACTIVITIES");

    }

    parent::postProcess();
  }

}
