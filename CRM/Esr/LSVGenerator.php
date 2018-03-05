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


/**
 * Generates ESRs for CiviSEPA's LSV+ format
 *  via end2end ID. Format is
 *   [6 LSV-ID][0850][10 contribution ID][6 creditor id][1 checksum]
 */
class CRM_Esr_LSVGenerator {

  /** cache for creditor IDs with the TA875 file format */
  protected static $lsv_creditor_ids = NULL;
  protected static $generator        = NULL;

  /**
   * If the TA875 format is selected as the creditor's file format
   *  this will generate a 27 digit ESR number
   */
  public static function generateESR_E2EID(&$end2endID, $contribution, $creditor) {
    // only applies if creditor is an LSV creditor
    if (!self::isLSVCreditor($creditor)) {
      return;
    }

    $config    = new CRM_Esr_Config();
    $generator = self::getGenerator();


    // lead with 6-digit LSV-ID
    $reference = sprintf('%06d', $config->get_ta875_LSV_ID());

    // add fixed '0850' string
    $reference .= '0850';

    // add 10-digit contribution ID
    $reference .= sprintf('%010d', $end2endID);

    // add 6-digit creditor ID
    $reference .= sprintf('%06d', $creditor['id']);

    // add checksum
    $reference .= $generator->calculate_checksum($reference);

    // add store as end2end
    $end2endID = $reference;
  }

  /**
   * get the (cached) generator instance
   */
  protected static function getGenerator() {
    if (self::$generator === NULL) {
      self::$generator = new CRM_Esr_Generator();
    }
    return self::$generator;
  }

  /**
   * Check whether this creditor uses the LSV TA875 format
   */
  public static function isLSVCreditor($creditor) {
    if (self::$lsv_creditor_ids === NULL) {
      self::$lsv_creditor_ids = array();

      // find LSVs
      $ta875_values  = array();
      $option_values = civicrm_api3('OptionValue', 'get', array(
        'name'            => 'ta875',
        'option_group_id' => 'sepa_file_format',
        'return'          => 'value'));
      foreach ($option_values['values'] as $option_value) {
        $ta875_values[] = $option_value['value'];
      }

      if (!empty($ta875_values)) {
        $creditors = civicrm_api3('SepaCreditor', 'get', array(
          'sepa_file_format_id' => array('IN' => $ta875_values),
          'return'              => 'id'
        ));
        foreach ($creditors['values'] as $eligible_creditor) {
          self::$lsv_creditor_ids[] = $eligible_creditor['id'];
        }
      }
    }

    return in_array($creditor['id'], self::$lsv_creditor_ids);
  }
}