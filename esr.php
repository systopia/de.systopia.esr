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

require_once 'esr.civix.php';

use CRM_Esr_ExtensionUtil as E;

/**
* Add an action for creating donation receipts after doing a search
*
* @param string $objectType specifies the component
* @param array $tasks the list of actions
*
* @access public
*/
function esr_civicrm_searchTasks($objectType, &$tasks) {
  // add ESR generation tasks
  if ($objectType == 'contact') {
    // this object is only available for the 'merge' mode
    $tasks['generate_esr'] = array(
        'title'  => E::ts('ESR Generation'),
        'class'  => 'CRM_Esr_Form_Task_Contact',
        'result' => false);
  } elseif ($objectType == 'membership') {
    // this object is only available for the 'merge' mode
    $tasks['generate_esr'] = array(
        'title'  => E::ts('ESR Generation'),
        'class'  => 'CRM_Esr_Form_Task_Membership',
        'result' => false);
  }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function esr_civicrm_config(&$config) {
  _esr_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function esr_civicrm_install() {
  _esr_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function esr_civicrm_enable() {
  _esr_civix_civicrm_enable();

  $customData = new CRM_Esr_CustomData('de.systopia.esr');
  $customData->syncOptionGroup(__DIR__ . '/resources/formats_option_group.json');
}
