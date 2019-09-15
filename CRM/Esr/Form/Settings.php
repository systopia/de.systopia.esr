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

use CRM_Esr_ExtensionUtil as E;

/**
 * Configuration form for ESR:
 *   - TA875
 */
class CRM_Esr_Form_Settings extends CRM_Core_Form {

  public function buildQuickForm() {
    CRM_Utils_System::setTitle(E::ts("ESR Configuration"));
    $this->add(
      'text',
      'esr_tn',
      E::ts('Participant Number (ESR_TN)'),
      FALSE
    );

    $this->add(
        'text',
        'lsv_id',
        E::ts('LSV Identification (LSV_ID)'),
        FALSE
    );

    $this->addButtons([
        [
            'type'      => 'submit',
            'name'      => E::ts('Save'),
            'isDefault' => TRUE,
        ],
    ]);

    // restore settings
    $this->setDefaults(CRM_Esr_Config::getSettings());

    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues(NULL, TRUE);

    // store settings
    CRM_Esr_Config::setSettings($values);

    CRM_Core_Session::setStatus(E::ts('Settings stored'), E::ts("Success"), 'info');
    parent::postProcess();
  }
}
