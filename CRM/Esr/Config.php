<?php
/*-------------------------------------------------------+
| ESR Codes Extension                                    |
| Copyright (C) 2018 SYSTOPIA                            |
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
 * ESR Module configuration
 */
class CRM_Esr_Config {

  /** @var integer $esr_activity_type ID cache */
  public static $esr_activity_type_id = NULL;

  /**
   * Get this extension's settings
   */
  public static function getSettings() {
    $settings = Civi::settings()->get('systopia_esr_settings');
    if (is_array($settings)) {
      return $settings;
    } else {
      return [];
    }
  }

  /**
   * Get this extension's settings
   */
  public static function setSettings($settings) {
    Civi::settings()->set('systopia_esr_settings', $settings);
  }

  /**
   * Get this extension's settings
   */
  public static function setSetting($key, $value) {
    $settings = self::getSettings();
    $settings[$key] = $value;
    self::setSettings($settings);
  }

  /**
   * Get the ESR code's activity type
   *
   * @param $recursion bool activates recursion protection
   *
   * @return integer activity_type_id
   */
  public static function getESRActivityTypeID($recursion = FALSE) {
    if (self::$esr_activity_type_id === NULL) {
      // look up the activity type
      $query = civicrm_api3('OptionValue', 'get', [
          'name'            => 'esr_code_generated',
          'option_group_id' => 'activity_type',
          'return'          => 'id,value']);
      if (empty($query['id'])) {
        // activity type not there yet, or not unqiue
        if (empty($query['count'])) {
          // create the type
          civicrm_api3("OptionValue", 'create', [
              'label'           => E::ts("ESR Code Generation"),
              'name'            => 'esr_code_generated',
              'option_group_id' => 'activity_type',
              'is_active'       => 1,
          ]);

          if (!$recursion) {
            // this should exist now:
            return self::getESRActivityTypeID(TRUE);
          } else {
            // we already tried this, but there's something wrong
            throw new Exception("Cannot creat ActivityType 'esr_code_generated'");
          }

        } else {
          throw new Exception("ActivityType 'esr_code_generated' is ambiguous!");
        }

      } else {
        $activity = reset($query['values']);
        self::$esr_activity_type_id = $activity['value'];
      }
    }

    return self::$esr_activity_type_id;
  }

  /**
   * Value for TA875 file format:
   */
  public function get_ta875_ESR_TN() {
    return CRM_Utils_Array::value('esr_tn', self::getSettings(), '010000000');
  }

  /**
   * Value for TA875 file format:
   */
  public function get_ta875_LSV_ID() {
    return CRM_Utils_Array::value('lsv_id', self::getSettings(), '900000');
  }
}
