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
 * Traditional ESR Number generator logic
 */
class CRM_Esr_GeneratorQR extends CRM_Esr_Generator {

  const COLUMN_PERSONAL_NUMBER                   = 0;
  const COLUMN_MAIL_CODE                         = 1;
  const COLUMN_ADDRESS_1                         = 2;
  const COLUMN_ADDRESS_2                         = 3;
  const COLUMN_ADDRESS_3                         = 4;
  const COLUMN_ADDRESS_4                         = 5;
  const COLUMN_ADDRESS_5                         = 6;
  const COLUMN_ADDRESS_6                         = 7;
  const COLUMN_STREET                            = 9;
  const COLUMN_STREET_NUMBER                     = 10;
  const COLUMN_POSTAL_CODE                       = 11;
  const COLUMN_CITY                              = 12;
  const COLUMN_COUNTRY                           = 13;
  const COLUMN_POSTAL_MAILING_SALUTATION         = 14;
  const COLUMN_FORMAL_TITLE                      = 15;
  const COLUMN_FIRST_NAME                        = 16;
  const COLUMN_LAST_NAME                         = 17;
  const COLUMN_ESR1                              = 20;
  const COLUMN_ESR_1_REF_ROW                     = 21;
  const COLUMN_ESR_1_REF_ROW_GROUP               = 22;
  const COLUMN_DATA_MATRIX_CODE_TYPE_20_DIGIT_37 = 24;
  const COLUMN_DATA_MATRIX_CODE                  = 25;
  const COLUMN_TEXT_MODULE                       = 26;
  const COLUMN_PACKET_NUMBER                     = 27;
  const COLUMN_ORGANISATION_NAME                 = 28;
  const COLUMN_QR_TYPE                           = 31;
  const COLUMN_VERSION                           = 32;
  const COLUMN_CODING_TYPE                       = 33;
  const COLUMN_QR_ACCOUNT                        = 34;
  const COLUMN_ZE_ADDRESS_TYPE                   = 35;
  const COLUMN_ZE_NAME                           = 36;
  const COLUMN_ZE_STREET                         = 37;
  const COLUMN_ZE_STREET_NUMBER                  = 38;
  const COLUMN_ZE_POSTAL_CODE                    = 39;
  const COLUMN_ZE_CITY                           = 40;
  const COLUMN_ZE_COUTRY                         = 41;
  const COLUMN_CURRENCY                          = 42;
  const COLUMN_EZP_ADDRESS_TYPE                  = 43;
  const COLUMN_EZP_NAME                          = 44;
  const COLUMN_REF_TYPE                          = 45;

  // additional headers for membership
  const COLUMN_SECOND_CONTACT_IDENTICAL          = 29;
  const COLUMN_SECOND_PERSONAL_NUMBER            = 30;
  const SECOND_ADDRESS_OFFSET                    = +29; // offset
  const SECOND_ADDRESS_LENGTH                    = 16;
  const COLUMN_AMOUNT_INT                        = self::SECOND_ADDRESS_OFFSET + self::SECOND_ADDRESS_LENGTH + 1;
  const COLUMN_AMOUNT_CENTS                      = self::COLUMN_AMOUNT_INT + 1;



  protected $base_header    = NULL;
  protected $id2country     = NULL;
  protected $id2prefix      = NULL;
  protected $street_parser  = '#^(?P<street>.*) +(?P<number>[\d\/-]+( ?[A-Za-z]+)?)$#';


  public function __construct() {
    // fill header
    $this->base_header = array(
        self::COLUMN_QR_TYPE                           => E::ts('QR Type'),
        self::COLUMN_VERSION                           => E::ts('Version'),
        self::COLUMN_CODING_TYPE                       => E::ts('Coding Type'),
        self::COLUMN_QR_ACCOUNT                        => E::ts('QR Account'),
        self::COLUMN_ZE_ADDRESS_TYPE                   => E::ts('ZE Address Type'),
        self::COLUMN_ZE_NAME                           => E::ts('ZE Name'),
        self::COLUMN_ZE_STREET                         => E::ts('ZE Street'),
        self::COLUMN_ZE_STREET_NUMBER                  => E::ts('ZE Street number'),
        self::COLUMN_ZE_POSTAL_CODE                    => E::ts('ZE Postal Code'),
        self::COLUMN_ZE_CITY                           => E::ts('ZE City'),
        self::COLUMN_ZE_COUTRY                         => E::ts('ZE Country'),
        self::COLUMN_CURRENCY                          => E::ts('Currency'),
        self::COLUMN_EZP_ADDRESS_TYPE                  => E::ts('EZP Address Type'),
        self::COLUMN_EZP_NAME                          => E::ts('EZP Name'),
        self::COLUMN_STREET                            => E::ts('EZP Street/Address'),
        self::COLUMN_STREET_NUMBER                     => E::ts('EZP Street number'),
        self::COLUMN_POSTAL_CODE                       => E::ts('EZP Postal code'),
        self::COLUMN_CITY                              => E::ts('EZP City'),
        self::COLUMN_COUNTRY                           => E::ts('EZP Country'),
        self::COLUMN_REF_TYPE                          => E::ts('ESR Reference Type'),
        self::COLUMN_ESR_1_REF_ROW                     => E::ts('ESR Reference'),
        self::COLUMN_ESR_1_REF_ROW_GROUP               => E::ts('ESR 1 Ref Row Grp'),
        self::COLUMN_PERSONAL_NUMBER                   => E::ts('Personal number QR'),
        self::COLUMN_MAIL_CODE                         => E::ts('Mail code'),
        self::COLUMN_ADDRESS_1                         => E::ts('Address line 1'),
        self::COLUMN_ADDRESS_2                         => E::ts('Address line 2'),
        self::COLUMN_ADDRESS_3                         => E::ts('Address line 3'),
        self::COLUMN_ADDRESS_4                         => E::ts('Address line 4'),
        self::COLUMN_ADDRESS_5                         => E::ts('Address line 5'),
        self::COLUMN_ADDRESS_6                         => E::ts('Address line 6'),
        self::COLUMN_FORMAL_TITLE                      => E::ts('Formal title'),
        self::COLUMN_FIRST_NAME                        => E::ts('First name'),
        self::COLUMN_LAST_NAME                         => E::ts('Last name'),
        self::COLUMN_POSTAL_MAILING_SALUTATION         => E::ts('Postal mailing salutation'),
        self::COLUMN_DATA_MATRIX_CODE_TYPE_20_DIGIT_37 => E::ts('Data Matrix Code Type 20 from digit 37'),
        self::COLUMN_DATA_MATRIX_CODE                  => E::ts('Data Matrix Code'),
        self::COLUMN_TEXT_MODULE                       => E::ts('Text module'),
        self::COLUMN_PACKET_NUMBER                     => E::ts('Packet number'),
        self::COLUMN_ORGANISATION_NAME                 => E::ts('Organisation Name'),
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
    $headers = $this->getHeaders($type, $params);
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
    fputcsv($output_stream, $headers);

    // create the query and go through the lines
    $sql = $this->generateSQL($type, $entity_ids , $params);
    $query = CRM_Core_DAO::executeQuery($sql);
    while ($query->fetch()) {
      $record = $this->generateRecord($type, $query, $params);
      $csv_line = array();
      foreach ($headers as $field_index => $field) {
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
      $WHERE_CLAUSE = "civicrm_membership.id IN ({$membership_id_list})";
    }

    // amount
    if (is_numeric($params['amount_option'])) {
      $field = civicrm_api3('CustomField', 'getsingle', ['id' => $params['amount_option']]);
      $group = civicrm_api3('CustomGroup', 'getsingle', ['id' => $field['custom_group_id']]);
      $AMOUNT_TERM = "COALESCE(amount_group.`{$field['column_name']}`, civicrm_membership_type.minimum_fee)";
      $AMOUNT_JOIN = "LEFT JOIN {$group['table_name']} amount_group ON amount_group.entity_id = civicrm_membership.id";
    } elseif ($params['amount_option'] == 'fixed') {
      $amount = $this->getFullAmount($params['amount']);
      $AMOUNT_TERM = sprintf("'%.2f'", (float) ($amount / 100.0));
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

    // organisation name term
    if (is_numeric($params['custom_field_id'])) {
      $custom_field_id = (int) $params['custom_field_id'];
      $custom_field  = civicrm_api3('CustomField', 'getsingle', ['id' => $custom_field_id]);
      $custom_group  = civicrm_api3('CustomGroup', 'getsingle', ['id' => $custom_field['custom_group_id']]);
      $ORGNAME1_JOIN = "LEFT JOIN {$custom_group['table_name']} contact_orgname        ON contact_orgname.entity_id = civicrm_contact.id";
      $ORGNAME2_JOIN = "LEFT JOIN {$custom_group['table_name']} second_contact_orgname ON second_contact_orgname.entity_id = second_contact.id";
      $ORGNAME1_TERM = "contact_orgname.{$custom_field['column_name']}";
      $ORGNAME2_TERM = "second_contact_orgname.{$custom_field['column_name']}";
    } elseif ($params['custom_field_id'] == 'organization_name') {
      $ORGNAME1_JOIN = "";
      $ORGNAME2_JOIN = "";
      $ORGNAME1_TERM = "civicrm_contact.organization_name";
      $ORGNAME2_TERM = "second_contact.organization_name";
    } else {
      // this should be an error...
      $ORGNAME1_JOIN = "";
      $ORGNAME2_JOIN = "";
      $ORGNAME1_TERM = "ERROR";
      $ORGNAME2_TERM = "ERROR";
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
                {$ORGNAME1_TERM}                          AS organisation_name,
                civicrm_membership.id                     AS membership_id,

                second_contact.id                         AS second_contact_id,
                second_contact.contact_type               AS second_contact_type,
                second_contact.prefix_id                  AS second_prefix_id,
                second_contact.display_name               AS second_display_name,
                second_contact.first_name                 AS second_first_name,
                second_contact.last_name                  AS second_last_name,
                second_contact.household_name             AS second_household_name,
                second_contact.organization_name          AS second_organization_name,
                second_contact.formal_title               AS second_formal_title,
                second_contact.addressee_display          AS second_addressee_display,
                second_contact.postal_greeting_display    AS second_postal_greeting_display,
                second_contact.first_name                 AS second_first_name,
                second_contact.last_name                  AS second_last_name,
                second_address.street_address             AS second_street_address,
                second_address.postal_code                AS second_postal_code,
                second_address.supplemental_address_1     AS second_supplemental_address_1,
                second_address.supplemental_address_2     AS second_supplemental_address_2,
                second_address.city                       AS second_city,
                second_address.country_id                 AS second_country_id,
                {$ORGNAME2_TERM}                          AS second_organisation_name,
                {$AMOUNT_TERM}                            AS amount
      FROM      civicrm_membership
      LEFT JOIN civicrm_membership_type ON civicrm_membership.membership_type_id = civicrm_membership_type.id
      {$BUYER_JOIN}
      LEFT JOIN civicrm_contact civicrm_contact  ON civicrm_contact.id = {$BUYER_TERM}
      LEFT JOIN civicrm_address civicrm_address  ON civicrm_address.contact_id = civicrm_contact.id AND civicrm_address.is_primary = 1
      LEFT JOIN civicrm_contact second_contact   ON second_contact.id = civicrm_membership.contact_id
      LEFT JOIN civicrm_address second_address   ON second_address.contact_id = second_contact.id AND second_address.is_primary = 1
      {$AMOUNT_JOIN}
      {$ORGNAME1_JOIN}
      {$ORGNAME2_JOIN}
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

    // organisation name term
    if (is_numeric($params['custom_field_id'])) {
      $custom_field_id = (int) $params['custom_field_id'];
      $custom_field = civicrm_api3('CustomField', 'getsingle', ['id' => $custom_field_id]);
      $custom_group = civicrm_api3('CustomGroup', 'getsingle', ['id' => $custom_field['custom_group_id']]);
      $ORGNAME_TERM = "{$custom_group['table_name']}.{$custom_field['column_name']}";
      $ORGNAME_JOIN = "LEFT JOIN {$custom_group['table_name']} ON {$custom_group['table_name']}.entity_id = civicrm_contact.id";
    } elseif ($params['custom_field_id'] == 'organization_name') {
      $ORGNAME_TERM = "civicrm_contact.organization_name";
      $ORGNAME_JOIN = "";
    } else {
      // this should be an error...
      $ORGNAME_TERM = "'ERROR'";
      $ORGNAME_JOIN = "";
    }

    // compile the query
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
                civicrm_country.iso_code                  AS iso_code,
                {$ORGNAME_TERM}                           AS organisation_name
      FROM      civicrm_contact
      LEFT JOIN civicrm_address ON civicrm_address.contact_id = civicrm_contact.id AND civicrm_address.is_primary = 1
      LEFT JOIN civicrm_country ON civicrm_country.id = civicrm_address.country_id
      {$ORGNAME_JOIN}
      WHERE     {$WHERE_CLAUSE}
      GROUP BY  civicrm_contact.id";
    return $sql;
  }


  /**
   * Get the list of headers for the CSV file
   *
   * @param $type   int   format type
   * @param $params array generation parameters
   * @return array index => column name
   */
  public function getHeaders($type, $params) {
    $header = $this->base_header;

    // add more columns
    if ($type == self::$REFTYPE_MEMBERSHIP) {
      // add second contact data
      $header[self::COLUMN_SECOND_CONTACT_IDENTICAL] = E::ts('Second Contact Identical');
      $header[self::COLUMN_SECOND_PERSONAL_NUMBER]   = E::ts('Registration number') . ' - 2';
      for ($index = self::COLUMN_SECOND_PERSONAL_NUMBER + 1; $index < self::COLUMN_SECOND_CONTACT_IDENTICAL + self::SECOND_ADDRESS_LENGTH; $index++) {
        $header[$index] = $header[$index - self::SECOND_ADDRESS_OFFSET] . ' - 2';
      }
      $header[self::SECOND_ADDRESS_OFFSET + self::SECOND_ADDRESS_LENGTH] =  $header[self::COLUMN_ORGANISATION_NAME] . ' - 2';

      // add amount split
      $header[self::COLUMN_AMOUNT_INT]   = E::ts('Amount (main)');
      $header[self::COLUMN_AMOUNT_CENTS] = E::ts('Amount (cents)');
    }

    return $header;
  }


  /**
   * generate a new record on the next line of the query result
   */
  protected function generateRecord($type, $query, $params) {
    $record = array();

    // basic information
    $record[self::COLUMN_MAIL_CODE]       = isset($params['mailcode']) ? $params['mailcode'] : '';

    // add all contact data
    $this->addContactData($query, $record);

    // custom stuff
    switch ($type) {
      case self::$REFTYPE_MEMBERSHIP:
        // add second contact data
        $this->addContactData($query, $record, self::SECOND_ADDRESS_OFFSET, 'second_');

        // set the extra fields
        $contact_id        = $this->getQueryResult($query, 'contact_id');
        $second_contact_id = $this->getQueryResult($query, 'contact_id', 'second_');
        $record[self::COLUMN_SECOND_PERSONAL_NUMBER]   = $second_contact_id;
        $record[self::COLUMN_SECOND_CONTACT_IDENTICAL] = ($contact_id == $second_contact_id) ? '1' : '0';

        // organisation name doesn't fit the scheme below...
        $record[self::COLUMN_ORGANISATION_NAME] = $this->getQueryResult($query, 'organisation_name');
        $record[self::SECOND_ADDRESS_OFFSET + self::SECOND_ADDRESS_LENGTH] = $this->getQueryResult($query, 'second_organisation_name');

        // set amount
        $full_amount = $this->getQueryResult($query, 'amount');
        $record[self::COLUMN_AMOUNT_INT]   = (int) $full_amount;
        $record[self::COLUMN_AMOUNT_CENTS] = ((int) ($full_amount * 100)) % 100;
        $amount = (int) (100.0 * $full_amount);
        break;

      default:
      case self::$REFTYPE_BULK_SIMPLE:
        // organisation name doesn't fit the scheme below...
        $record[self::COLUMN_ORGANISATION_NAME] = $this->getQueryResult($query, 'organisation_name');
        // take amount from the contact data
        $amount = $this->getFullAmount($params['amount']);
        break;
    }

    // codes
    $esr_ref                                  = $this->create_reference($type, $query, $params);
    $bc_type                                  = empty($amount) ? self::$BC_ESR_PLUS_CHF : self::$BC_ESR_CHF;
    $esr1                                     = $this->create_code($bc_type, $amount, $esr_ref, $params['tn_number']);
    $record[self::COLUMN_ESR1]                = $esr1;
    $record[self::COLUMN_ESR_1_REF_ROW]       = $esr_ref;
    $record[self::COLUMN_ESR_1_REF_ROW_GROUP] = $this->format_code($esr_ref);

    // misc
    $record[self::COLUMN_TEXT_MODULE] = $params['custom_text'];
    $record[self::COLUMN_QR_TYPE] = $params['qr_type'];
    $record[self::COLUMN_VERSION] = $params['qr_version'];
    $record[self::COLUMN_CODING_TYPE] = $params['qr_coding_type'];
    $record[self::COLUMN_QR_ACCOUNT] = $params['qr_account'];
    $record[self::COLUMN_REF_TYPE] = $params['esr_reference_type'];
    $record[self::COLUMN_ZE_ADDRESS_TYPE] = $params['ze_address_type'];
    $record[self::COLUMN_ZE_NAME] = $params['ze_name'];
    $record[self::COLUMN_ZE_STREET] = $params['ze_street'];
    $record[self::COLUMN_ZE_STREET_NUMBER] = $params['ze_street_number'];
    $record[self::COLUMN_ZE_POSTAL_CODE] = $params['ze_postal_code'];
    $record[self::COLUMN_ZE_CITY] = $params['ze_city'];
    $record[self::COLUMN_ZE_COUTRY] = $params['ze_country'];
    $record[self::COLUMN_CURRENCY] = $params['ze_currency'];
    $record[self::COLUMN_EZP_ADDRESS_TYPE] = $params['ezp_address_type'];

    // custom field
    $record[self::COLUMN_ORGANISATION_NAME] = $query->organisation_name;

    // unused: ESR1Identity, DataMatrixCodeTyp20abStelle37, DataMatrixCode, Paketnummer
    return $record;
  }

  /**
   * Add the contact base data to the record
   * @param $query         CRM_Core_DAO query result
   * @param $record        array record data
   * @param $offset        int offset to the generic fields
   * @param $prefix        string prefix in the query field
   */
  protected function addContactData($query, &$record, $offset = 0, $prefix = '') {
    // address lines
    $record[$offset + self::COLUMN_PERSONAL_NUMBER] = $this->getQueryResult($query, 'contact_id', $prefix);
    $record[$offset + self::COLUMN_ADDRESS_1]       = $this->id2prefix[$this->getQueryResult($query, 'prefix_id', $prefix)];
    $record[$offset + self::COLUMN_ADDRESS_2]       = $this->generateName($query, $prefix);
    $record[$offset + self::COLUMN_ADDRESS_3]       = $this->getQueryResult($query, 'street_address', $prefix);
    $record[$offset + self::COLUMN_ADDRESS_4]       = $this->getQueryResult($query, 'supplemental_address_1', $prefix);
    $record[$offset + self::COLUMN_ADDRESS_5]       = $this->getQueryResult($query, 'postal_code', $prefix) . ' ' . $this->getQueryResult($query, 'city', $prefix);
    $record[$offset + self::COLUMN_ADDRESS_6]       = $this->getQueryResult($query, 'supplemental_address_2', $prefix);

    // parsed address
    $record[$offset + self::COLUMN_POSTAL_CODE] = $this->getQueryResult($query, 'postal_code', $prefix);
    $record[$offset + self::COLUMN_CITY]        = $this->getQueryResult($query, 'city', $prefix);
    $record[$offset + self::COLUMN_COUNTRY]     = $this->getQueryResult($query, 'iso_code', $prefix);
    if (preg_match($this->street_parser, $this->getQueryResult($query, 'street_address', $prefix), $matches)) {
      $record[$offset + self::COLUMN_STREET]        = $matches['street'];
      $record[$offset + self::COLUMN_STREET_NUMBER] = $matches['number'];
    } else {
      $record[$offset + self::COLUMN_STREET]        = $this->getQueryResult($query, 'street_address', $prefix);
      $record[$offset + self::COLUMN_STREET_NUMBER] = '';
    }

    // personalised data
    $record[$offset + self::COLUMN_POSTAL_MAILING_SALUTATION] = $this->getQueryResult($query, 'postal_greeting_display', $prefix);
    $record[$offset + self::COLUMN_FORMAL_TITLE]              = $this->getQueryResult($query, 'formal_title', $prefix);
    $record[$offset + self::COLUMN_FIRST_NAME]                = $this->getQueryResult($query, 'first_name', $prefix);
    $record[$offset + self::COLUMN_LAST_NAME]                 = $this->getQueryResult($query, 'last_name', $prefix);

    // EZP COLUMNS
    $record[$offset + self::COLUMN_EZP_NAME]       = $this->generateName($query, $prefix);
  }

  /**
   * Get the given attribute from the query result
   * @param $query  CRM_Core_DAO query result
   * @param $name   string attribute name
   * @param $prefix string name prefix
   * @return mixed attribute value
   */
  protected function getQueryResult($query, $name, $prefix = '') {
    $attribute_name = $prefix . $name;
    if (isset($query->$attribute_name)) {
      return $query->$attribute_name;
    } else {
      return NULL;
    }
  }

  /**
   * Generate a name: "{Titel} {Vorname} {Nachname}"
   * @see https://projekte.systopia.de/redmine/issues/3937#change-24931
   */
  protected function generateName($contact, $prefix = '') {
    switch ($this->getQueryResult($contact, 'contact_type', $prefix)) {
      case 'Organization':
        return $this->getQueryResult($contact, 'organization_name', $prefix);
        break;

      case 'Household':
        return $this->getQueryResult($contact, 'household_name', $prefix);
        break;

      default:
      case 'Individual':
        $name = $this->getQueryResult($contact, 'formal_title', $prefix);
        $name .= ' ' . $this->getQueryResult($contact, 'first_name', $prefix);
        $name .= ' ' . $this->getQueryResult($contact, 'last_name', $prefix);
        $name = str_replace('  ', ' ', $name); // remove double whitespaces
        $name = trim($name); // remove leading/trailing whitespaces
        return $name;
    }
  }
}
