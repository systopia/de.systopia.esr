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
 * ESR Number generator logic
 */
class CRM_Esr_Generator {

  const COLUMN_PERSONAL_NUMBER = 0;
  const COLUMN_MAIL_CODE = 1;
  const COLUMN_ADDRESS_1 = 2;
  const COLUMN_ADDRESS_2 = 3;
  const COLUMN_ADDRESS_3 = 4;
  const COLUMN_ADDRESS_4 = 5;
  const COLUMN_ADDRESS_5 = 6;
  const COLUMN_ADDRESS_6 = 7;
  const COLUMN_STREET = 8;
  const COLUMN_STREET_NUMBER = 9;
  const COLUMN_POSTAL_CODE = 10;
  const COLUMN_CITY = 11;
  const COLUMN_COUNTRY = 12;
  const COLUMN_SALUTATION = 13;
  const COLUMN_POSTAL_MAILING_SALUTATION = 14;
  const COLUMN_FORMAL_TITLE = 15;
  const COLUMN_FIRST_NAME = 16;
  const COLUMN_LAST_NAME = 17;
  const COLUMN_NAME_2 = 18;
  const COLUMN_VESR_NUMBER = 19;
  const COLUMN_ESR1 = 20;
  const COLUMN_ESR_1_REF_ROW = 21;
  const COLUMN_ESR_1_REF_ROW_GROUP = 22;
  const COLUMN_ESR_1_IDENTITY = 23;
  const COLUMN_DATA_MATRIX_CODE_TYPE_20_DIGIT_37 = 24;
  const COLUMN_DATA_MATRIX_CODE = 25;
  const COLUMN_TEXT_MODULE = 26;
  const COLUMN_PACKET_NUMBER = 27;

  // ESR TYPES BC (Belegartcode), defined by standard
  public static $BC_ESR_CHF      = '01';
  public static $BC_ESR__N_CHF   = '03';
  public static $BC_ESR_PLUS_CHF = '04';
  public static $BC_ESR_EUR      = '21';
  public static $BC_ESR_PLUS_EUR = '31';

  // Reference type indicators (self defined)
  public static $REFTYPE_BULK_SIMPLE  = '01';
  public static $REFTYPE_MEMBERSHIP   = '02';


  protected $header     = NULL;
  protected $id2country = NULL;
  protected $id2prefix  = NULL;
  protected $street_parser  = '#^(?P<street>.*) +(?P<number>[\d\/-]+( ?[A-Za-z]+)?)$#';
  protected $checksum_table = '0946827135'; // used by calculate_checksum


  public function __construct() {
    // fill header
    $this->header = array(
        self::COLUMN_PERSONAL_NUMBER                   => E::ts('Registration number'),
        self::COLUMN_MAIL_CODE                         => E::ts('Mail code'),
        self::COLUMN_ADDRESS_1                         => E::ts('Address line 1'),
        self::COLUMN_ADDRESS_2                         => E::ts('Address line 2'),
        self::COLUMN_ADDRESS_3                         => E::ts('Address line 3'),
        self::COLUMN_ADDRESS_4                         => E::ts('Address line 4'),
        self::COLUMN_ADDRESS_5                         => E::ts('Address line 5'),
        self::COLUMN_ADDRESS_6                         => E::ts('Address line 6'),
        self::COLUMN_STREET                            => E::ts('Street'),
        self::COLUMN_STREET_NUMBER                     => E::ts('Street number'),
        self::COLUMN_POSTAL_CODE                       => E::ts('Postal code'),
        self::COLUMN_CITY                              => E::ts('City'),
        self::COLUMN_COUNTRY                           => E::ts('Country'),
        self::COLUMN_SALUTATION                        => E::ts('Salutation'),
        self::COLUMN_POSTAL_MAILING_SALUTATION         => E::ts('Postal mailing salutation'),
        self::COLUMN_FORMAL_TITLE                      => E::ts('Formal title'),
        self::COLUMN_FIRST_NAME                        => E::ts('First name'),
        self::COLUMN_LAST_NAME                         => E::ts('Last name'),
        self::COLUMN_NAME_2                            => E::ts('Name 2'),
        self::COLUMN_VESR_NUMBER                       => E::ts('VESR number'),
        self::COLUMN_ESR1                              => E::ts('ESR 1'),
        self::COLUMN_ESR_1_REF_ROW                     => E::ts('ESR 1 Ref Row'),
        self::COLUMN_ESR_1_REF_ROW_GROUP               => E::ts('ESR 1 Ref Row Grp'),
        self::COLUMN_ESR_1_IDENTITY                    => E::ts('ESR 1 Identity'),
        self::COLUMN_DATA_MATRIX_CODE_TYPE_20_DIGIT_37 => E::ts('Data Matrix Code Type 20 from digit 37'),
        self::COLUMN_DATA_MATRIX_CODE                  => E::ts('Data Matrix Code'),
        self::COLUMN_TEXT_MODULE                       => E::ts('Text module'),
        self::COLUMN_PACKET_NUMBER                     => E::ts('Packet number'),
    );

    // fill prefix lookup
    $prefixes = civicrm_api3('OptionValue', 'get', array('option_group_id' => 'individual_prefix', 'return' => 'value,label'));
    $this->id2prefix = array('' => '', NULL => '');
    foreach ($prefixes['values'] as $prefix) {
      $this->id2prefix[$prefix['value']] = $prefix['label'];
    }

    // fill country (localised)
    $this->id2country = CRM_Core_PseudoConstant::country();
    $this->id2country[''] = '';
    $this->id2country[NULL] = '';

    // $this->test_calculate_checksum();
  }


  /**
   * Will generate a CSV file and write into a file or the HTTP stream
   *
   * @param $type        String reference type, e.g. self::$REFTYPE_BULK_SIMPLE
   * @param $entity_ids  array  list of entity ids, e.g. contact IDs or membership IDs
   * @param $params      array  list of additional parameters
   */
  public function generate($type, $entity_ids, $params, $out = 'php://output') {
    if ($out == 'php://output') {
      // we want to write into the outstream
      $filename = "ESR-" . date('YmdHis') . '.csv';
      header('Content-Description: File Transfer');
      header('Content-Type: application/csv');
      header('Content-Disposition: attachment; filename=' . $filename);
      // CiviCRM 4.7:
      // CRM_Utils_System::setHttpHeader('Content-Description', 'File Transfer');
      // CRM_Utils_System::setHttpHeader('Content-Type', 'application/csv');
      // CRM_Utils_System::setHttpHeader('Content-Disposition', 'attachment; filename=' . $filename);

      $output_stream = fopen('php://output', 'w');
      ob_clean();
      flush();

    } else {
      // user submitted a file
      $output_stream = fopen($out, 'w');
    }

    // write header line
    fputcsv($output_stream, $this->header);

    // create the query and go through the lines
    $sql = $this->generateSQL($type, $entity_ids , $params);
    $query = CRM_Core_DAO::executeQuery($sql);
    while ($query->fetch()) {
      $record = $this->generateRecord($type, $query, $params);
      $csv_line = array();
      foreach ($this->header as $field_index => $field) {
        if (isset($record[$field_index])) {
          $csv_line[] = $record[$field_index];
        } else {
          $csv_line[] = '';
        }
      }
      fputcsv($output_stream, $csv_line);
    }

    // wrap it up
    fclose($output_stream);
    if ($out == 'php://output') {
      CRM_Utils_System::civiExit();
    }
  }



  /**
   * generate the SQL needed to collect all necessary data
   */
  protected function generateSQL($type, $entity_ids, $params) {
    // filter IDs
    $filtered_entity_ids = array();
    foreach ($entity_ids as $entity_id) {
      $filtered_entity_id = (int) $entity_id;
      if ($filtered_entity_id) {
        $filtered_entity_ids[] = $filtered_entity_id;
      }
    }

    // delegate to generator
    switch ($type) {
      case self::$REFTYPE_MEMBERSHIP:
        return $this->generateMembershipSQL($filtered_entity_ids, $params);

      default:
      case self::$REFTYPE_BULK_SIMPLE:
        return $this->generateBasicSQL($filtered_entity_ids, $params);
    }
  }

  /**
   * generate the SQL needed to collect all necessary data
   */
  protected function generateMembershipSQL($membership_ids, $params) {
    // membership IDs
    if (empty($membership_ids)) {
      $WHERE_CLAUSE = 'FALSE';
    } else {
      $membership_id_list = implode(',', $membership_ids);
      $WHERE_CLAUSE = "civicrm_contact.id IN ({$membership_id_list})";
    }

    // amount
    if (is_numeric($params['amount_option'])) {
      $field = civicrm_api3('CustomField', 'getsingle', ['id' => $params['amount_option']]);
      $group = civicrm_api3('CustomGroup', 'getsingle', ['id' => $field['custom_group_id']]);
      $AMOUNT_TERM = "COALESCE(amount_group.`{$field['column_name']}`, civicrm_membership_type.minimum_fee)";
      $AMOUNT_JOIN = "LEFT JOIN {$group['table_name']} amount_group ON amount_group.entity_id = civicrm_membership.id";
    } elseif ($params['amount_option'] == 'fixed') {
      $AMOUNT_TERM = sprintf("'%.2f'", (float) $params['amount']);
      $AMOUNT_JOIN = '';
    } elseif ($params['amount_option'] == 'type') {
      $AMOUNT_TERM = 'civicrm_membership_type.minimum_fee';
      $AMOUNT_JOIN = '';
    } else {
      throw new Exception("Unknown amount_option '{$params['amount_option']}'");
    }

    // payment term
    if (is_numeric($params['paying_contact'])) {
      $field = civicrm_api3('CustomField', 'getsingle', ['id' => $params['paying_contact']]);
      $group = civicrm_api3('CustomGroup', 'getsingle', ['id' => $field['custom_group_id']]);
      $BUYER_TERM = "COALESCE(buyer_group.`{$field['column_name']}`, civicrm_membership.contact_id)";
      $BUYER_JOIN = "LEFT JOIN {$group['table_name']} buyer_group ON buyer_group.entity_id = civicrm_membership.id";
    } elseif ($params['paying_contact'] == 'member') {
      $BUYER_TERM = "civicrm_membership.contact_id";
      $BUYER_JOIN = "";
    } else {
      throw new Exception("Unknown paying_contact option '{$params['paying_contact']}'");
    }

    $sql = "
      SELECT    civicrm_contact.id                        AS contact_id,
                civicrm_contact.contact_type              AS contact_type,
                civicrm_contact.prefix_id                 AS prefix_id,
                civicrm_contact.display_name              AS display_name,
                civicrm_contact.first_name                AS first_name,
                civicrm_contact.last_name                 AS last_name,
                civicrm_contact.household_name            AS household_name,
                civicrm_contact.organization_name         AS organization_name,
                civicrm_contact.formal_title              AS formal_title,
                civicrm_contact.addressee_display         AS addressee_display,
                civicrm_contact.postal_greeting_display   AS postal_greeting_display,
                civicrm_contact.first_name                AS first_name,
                civicrm_contact.last_name                 AS last_name,
                civicrm_address.street_address            AS street_address,
                civicrm_address.postal_code               AS postal_code,
                civicrm_address.supplemental_address_1    AS supplemental_address_1,
                civicrm_address.supplemental_address_2    AS supplemental_address_2,
                civicrm_address.city                      AS city,
                civicrm_address.country_id                AS country_id,
                civicrm_membership.id                     AS membership_id,
                {$AMOUNT_TERM}                            AS amount
      FROM      civicrm_membership
      LEFT JOIN civicrm_membership_type ON civicrm_membership.membership_type_id = civicrm_membership_type.id  
      {$BUYER_JOIN}
      LEFT JOIN civicrm_contact         ON civicrm_contact.id = {$BUYER_TERM}
      LEFT JOIN civicrm_address         ON civicrm_address.contact_id = civicrm_contact.id AND civicrm_address.is_primary = 1
      {$AMOUNT_JOIN}
      WHERE     {$WHERE_CLAUSE}
      GROUP BY  civicrm_membership.id";
    return $sql;
  }

  /**
   * generate the SQL needed to collect all necessary data
   */
  protected function generateBasicSQL($contact_ids, $params) {
    if (empty($contact_ids)) {
      $WHERE_CLAUSE = 'FALSE';
    } else {
      $contact_id_list = implode(',', $contact_ids);
      $WHERE_CLAUSE = "civicrm_contact.id IN ({$contact_id_list})";
    }

    $sql = "
      SELECT    civicrm_contact.id                        AS contact_id,
                civicrm_contact.contact_type              AS contact_type,
                civicrm_contact.prefix_id                 AS prefix_id,
                civicrm_contact.display_name              AS display_name,
                civicrm_contact.first_name                AS first_name,
                civicrm_contact.last_name                 AS last_name,
                civicrm_contact.household_name            AS household_name,
                civicrm_contact.organization_name         AS organization_name,
                civicrm_contact.formal_title              AS formal_title,
                civicrm_contact.addressee_display         AS addressee_display,
                civicrm_contact.postal_greeting_display   AS postal_greeting_display,
                civicrm_contact.first_name                AS first_name,
                civicrm_contact.last_name                 AS last_name,
                civicrm_address.street_address            AS street_address,
                civicrm_address.postal_code               AS postal_code,
                civicrm_address.supplemental_address_1    AS supplemental_address_1,
                civicrm_address.supplemental_address_2    AS supplemental_address_2,
                civicrm_address.city                      AS city,
                civicrm_address.country_id                AS country_id
      FROM      civicrm_contact
      LEFT JOIN civicrm_address ON civicrm_address.contact_id = civicrm_contact.id AND civicrm_address.is_primary = 1
      WHERE     {$WHERE_CLAUSE}
      GROUP BY  civicrm_contact.id";
    return $sql;
  }

  /**
   * generate a new record on the next line of the query result
   */
  protected function generateRecord($type, $query, $params) {
    $record = array();

    // basic information
    $record[self::COLUMN_PERSONAL_NUMBER] = $query->contact_id;
    $record[self::COLUMN_MAIL_CODE]       = isset($params['mailcode']) ? $params['mailcode'] : '';

    // address lines
    $record[self::COLUMN_ADDRESS_1] = $this->id2prefix[$query->prefix_id];
    $record[self::COLUMN_ADDRESS_2] = $this->generateName($query);
    $record[self::COLUMN_ADDRESS_3] = $query->street_address;
    $record[self::COLUMN_ADDRESS_4] = "{$query->postal_code} {$query->city}";
    $record[self::COLUMN_ADDRESS_5] = $query->supplemental_address_1;
    $record[self::COLUMN_ADDRESS_6] = $query->supplemental_address_2;

    // parsed address
    $record[self::COLUMN_POSTAL_CODE] = $query->postal_code;
    $record[self::COLUMN_CITY]        = $query->city;
    $record[self::COLUMN_COUNTRY]     = $this->id2country[$query->country_id];
    if (preg_match($this->street_parser, $query->street_address, $matches)) {
      $record[self::COLUMN_STREET]        = $matches['street'];
      $record[self::COLUMN_STREET_NUMBER] = $matches['number'];
    } else {
      $record[self::COLUMN_STREET]        = $query->street_address;
      $record[self::COLUMN_STREET_NUMBER] = '';
    }

    // personalised data
    $record[self::COLUMN_SALUTATION]                = $this->id2prefix[$query->prefix_id];
    $record[self::COLUMN_POSTAL_MAILING_SALUTATION] = $query->postal_greeting_display;
    $record[self::COLUMN_FORMAL_TITLE]              = $query->formal_title;
    $record[self::COLUMN_FIRST_NAME]                = $query->first_name;
    $record[self::COLUMN_LAST_NAME]                 = $query->last_name;
    $record[self::COLUMN_NAME_2]                    = '';  // unused

    // amount
    switch ($type) {
      case self::$REFTYPE_MEMBERSHIP:
        $amount = $query->amount;
        break;

      default:
      case self::$REFTYPE_BULK_SIMPLE:
        $amount = $this->getFullAmount($params['amount']);
        break;
    }

    // codes
    $esr_ref                                  = $this->create_reference($type, $query, $params);
    $bc_type                                  = empty($amount) ? self::$BC_ESR_PLUS_CHF : self::$BC_ESR_CHF;
    $esr1                                     = $this->create_code($bc_type, $amount, $esr_ref, $params['tn_number']);
    $record[self::COLUMN_VESR_NUMBER]         = $params['tn_number'];
    $record[self::COLUMN_ESR1]                = $esr1;
    $record[self::COLUMN_ESR_1_REF_ROW]       = $esr_ref;
    $record[self::COLUMN_ESR_1_REF_ROW_GROUP] = $this->format_code($esr_ref);

    // misc
    $record[self::COLUMN_TEXT_MODULE] = $params['custom_text'];

    // unused: ESR1Identity, DataMatrixCodeTyp20abStelle37, DataMatrixCode, Paketnummer
    return $record;
  }


  /**
   * generate an ESR code,
   *  e.g. 0100003949753>120000000000234478943216899+ 010001628>
   * @see https://www.postfinance.ch/binp/postfinance/public/dam.aw0b_Jf924M3gwLiSxkZQ_REZopMbAfPgsQR7kChnsY.spool/content/dam/pf/de/doc/consult/manual/dlserv/inpayslip_isr_man_de.pdf
   */
  protected function create_code($type, $amount, $esr_ref, $tn_number) {
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
    $code .= sprintf('%08d', $tn_number);

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
      $digit = $number_string[$i];
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

  /**
   * Generate a name: "{Titel} {Vorname} {Nachname}"
   * @see https://projekte.systopia.de/redmine/issues/3937#change-24931
   */
  protected function generateName($contact) {
    switch ($contact->contact_type) {
      case 'Organization':
        return $contact->organization_name;
        break;

      case 'Household':
        return $contact->household_name;
        break;

      default:
      case 'Individual':
        $name = "{$contact->formal_title} {$contact->first_name} {$contact->last_name}";
        $name = str_replace('  ', ' ', $name); // remove double whitespaces
        $name = trim($name); // remove leading/trailing whitespaces
        return $name;
    }
  }




  /**
   * simple test function for calculate_checksum($number_string)
   *
   * @todo: move to unit tests
   */
  protected function test_calculate_checksum() {
    $test_cases = array(
      '70004152' => '8',
      '12000000000023447894321689' => '9',
      '96111690000000660000000928' => '4',
      '21000000000313947143000901' => '7');

    foreach ($test_cases as $number => $checksum_expected) {
      $checksum_calculated = $this->calculate_checksum($number);
      if ($checksum_calculated == $checksum_expected) {
        error_log("OK: {$number}: {$checksum_expected}");
      } else {
        error_log("FAIL: {$number}: {$checksum_calculated} != {$checksum_expected}");
      }
    }
  }

}
