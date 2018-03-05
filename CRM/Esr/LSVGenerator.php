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
 * Generates ESRs for CiviSEPA's LSV+ format 
 *  via end2end ID
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

    // format is as follows
    // [6 LSV-ID][0850][10 contribution ID][6 creditor id][1 checksum]
    
    // gather data

  }

  /**
   * Check whether this creditor uses the LSV TA875 format
   */
  public static function isLSVCreditor($creditor) {
    if (self::$lsv_creditor_ids === NULL) {
      self::$lsv_creditor_ids = array();

      // find LSVs
      $option_values = civicrm_api3('OptionValue', 'get', array(
        'name'            => 'ta875',
        'option_group_id' => 'sepa_file_format',
        'return'          => 'value'));
      
      foreach ($option_values['values'] as $option_value) {
        self::$lsv_creditor_ids[] = $option_value['value'];
      }
    }

    return in_array($creditor['id'], self::$lsv_creditor_ids);
  }
}