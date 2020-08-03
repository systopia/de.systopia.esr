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
      unset($settings['qfKey'], $settings['entryURL']);
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
   * Format the creditor (TN) number according to the settings
   *
   * @param $creditor_id integer creditor (TN) number
   * @param $context     string  indicator of where it's being used
   * @return string formatted number
   */
  public static function formatCreditorID($creditor_id, $context) {
    $format = CRM_Utils_Array::value('esr_tn_format', self::getSettings(), '8');
    switch ($format) {
      case '9': # 9 digits
        return sprintf('%09d', $creditor_id);

      case 'X': # do not format
        return $creditor_id;

      default:  # 8 digits
      case '8':
        return sprintf('%08d', $creditor_id);
    }
  }

  /**
   * Value for TA875 file format:
   */
  public function get_ta875_ESR_TN() {
    return CRM_Utils_Array::value('esr_tn', self::getSettings(), '010000000');
  }

  /**
   * Value for TA875 file format: the LSV-ID
   *
   * @param integer $creditor_id
   *   the creditor's ID for an override
   *
   * @return string LSV ID
   *    the 6 digit LSV id, usually 9xxxxx
   */
  public function get_ta875_LSV_ID($creditor_id) {
    // first: see if there's an override in the creditor identifier
    $identifier = civicrm_api3('SepaCreditor', 'getvalue', [
        'id'     => $creditor_id,
        'return' => 'identifier'
    ]);
    if (strstr($identifier, '/')) {
      $lsv_id = explode('/', $identifier, 2)[1];
      if (preg_match('/^[0-9]{6}$/', $lsv_id)) {
        // this seems to check out
        return $lsv_id;
      } else {
        Civi::log()->warning("Badly formatted LSV-ID override '{$identifier}'. Should be 6 digits. Override ignored.");
      }
    }

    // no override: simply return the standard
    return CRM_Utils_Array::value('lsv_id', self::getSettings(), '900000');
  }

  /**
   * Prefix for TA875 files:
   */
  public function get_ta875_ESR_Prefix() {
    return CRM_Utils_Array::value('lsv_prefix', self::getSettings(), 'AVNC-');
  }


}
