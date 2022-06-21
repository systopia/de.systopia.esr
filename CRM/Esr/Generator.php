<?php
/*-------------------------------------------------------+
| ESR Codes Extension                                    |
| Copyright (C) 2016-2022 SYSTOPIA                       |
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
 * ESR Number generator logic
 */
abstract class CRM_Esr_Generator {

  // ESR TYPES BC (Belegartcode), defined by standard
  public static $BC_ESR_CHF      = '01';
  public static $BC_ESR__N_CHF   = '03';
  public static $BC_ESR_PLUS_CHF = '04';
  public static $BC_ESR_EUR      = '21';
  public static $BC_ESR_PLUS_EUR = '31';

  // Reference type indicators (self defined)
  public static $REFTYPE_BULK_SIMPLE  = '01';
  public static $REFTYPE_MEMBERSHIP   = '02';

  protected $checksum_table = '0946827135'; // used by calculate_checksum

  /**
   * Get the list of Generator implementations
   * @return string[]
   */
  public static function getGeneratorOptions() {
    return [
        'CRM_Esr_GeneratorClassic' => E::ts('Legacy Format'),
        'CRM_Esr_GeneratorQR' => E::ts('QR Code Format'),
    ];
  }

  /**
   * Get the instance of the given class
   *
   * @param string $class_name
   *   the exporter class name
   *
   * @return CRM_Esr_Generator
   *   exporter instance
   */
  public static function getInstance($class_name)
  {
    $options = self::getGeneratorOptions();
    if (isset($options[$class_name])) {
      return new $class_name();
    } else {
      throw new Exception("Cannot instantiate {$class_name} as generator");
    }
  }

  /**
   * Will generate a CSV file and write into a file or the HTTP stream
   *
   * @param $type        String reference type, e.g. self::$REFTYPE_BULK_SIMPLE
   * @param $entity_ids  array  list of entity ids, e.g. contact IDs or membership IDs
   * @param $params      array  list of additional parameters
   */
  abstract public function generate($type, $entity_ids, $params, $out = 'php://output');

  /**
   * generate an ESR code,
   *  e.g. 0100003949753>120000000000234478943216899+ 010001628>
   * @see https://www.postfinance.ch/binp/postfinance/public/dam.aw0b_Jf924M3gwLiSxkZQ_REZopMbAfPgsQR7kChnsY.spool/content/dam/pf/de/doc/consult/manual/dlserv/inpayslip_isr_man_de.pdf
   */
  function create_code($type, $amount, $esr_ref, $tn_number) {
    // code starts with the type
    $code = $type;

    if ($amount) {
      $code .= sprintf("%010d", $amount);
    }

    // add checksum bit
    $code .= $this->calculate_checksum($code);

    // then add the '>' separator
    $code .= '>';

    // then add the reference
    $code .= $esr_ref;

    // then add the '+ ' separator (for whatever reason)
    $code .= '+ ';

    // then add the creditor id (Teilnehmernummer)
    $code .= CRM_Esr_Config::formatCreditorID($tn_number, 'generator');

    // ...and finish with '>'
    $code .= '>';

    return $code;
  }

  /**
   * generate an ESR reference
   */
  protected function create_reference($type, $query, $params) {
    switch ($type) {
      case self::$REFTYPE_BULK_SIMPLE:
        $reference = sprintf("%02d%014d%010d", $type, $params['mailcode'], $query->contact_id);
        break;

      case self::$REFTYPE_MEMBERSHIP:
        $reference = sprintf("%02d%010d%09d09999", $type, $query->contact_id, $query->membership_id);
        break;

      default:
        throw new Exception("Unknown reference type: '{$type}'");
    }

    $reference .= $this->calculate_checksum($reference);
    return $reference;
  }

  /**
   * converts the amount string in an integer of cents.
   */
  protected function getFullAmount($amount) {
    // check if it's even set
    if (empty($amount)) {
      return 0;
    }

    // clean the amount
    $config = CRM_Core_Config::singleton();
    $amount = str_replace(array(' ', "\t", "\n", $config->monetaryThousandSeparator), '', $amount);
    $amount = str_replace($config->monetaryDecimalPoint, '.', $amount);

    // add the amount
    return (int) ($amount * 100.0);
  }

  /**
   * simply adds spaces to separate the code into columns
   */
  protected function format_code($code) {
    $code_blocks = substr($code, 0, 2);
    for ($i=0; $i < (strlen($code)-2)/5 ; $i++) {
      $code_blocks .= ' ' . substr($code, 2+$i*5, 5);
    }
    return $code_blocks;
  }

  /**
   * calculate MOD10 checksum
   * @see https://www.postfinance.ch/binp/postfinance/public/dam.c8_wVGPa22PId2Sju8Y4fcG6nsPr4WVUrdgEgwJu5RA.spool/content/dam/pf/de/doc/consult/manual/dldata/efin_recdescr_man_de.pdf
   */
  public function calculate_checksum($number_string) {
    $number_string = (String) $number_string;
    $carry = 0;
    for ($i=0; $i < strlen($number_string); $i++) {
      $digit = (int) $number_string[$i];
      // error_log("INDEX {$i}, CARRY {$carry}, DIGIT {$digit}");
      $carry = $this->checksum_table[($carry + $digit) % 10];
    }
    return (10 - $carry) % 10;
  }

  /**
   * remove the given prefix from the string, if present
   */
  protected function stripPrefix($string, $prefix) {
    $string_prefix = substr($string, 0, strlen($prefix));
    if ($string_prefix == $prefix) {
      return trim(substr($string, strlen($prefix)));
    } else {
      return trim($string);
    }
  }

}
