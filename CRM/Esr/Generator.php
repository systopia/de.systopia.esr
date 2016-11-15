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
class CRM_Esr_Generator {

  protected $header     = NULL;
  protected $id2country = NULL;
  protected $id2prefix  = NULL;


  public function __construct() {
    // fill header
    $this->header = array(
        'Personennummer',
        'Mailcode',
        'Adresszeile1',
        'Adresszeile2',
        'Adresszeile3',
        'Adresszeile4',
        'Adresszeile5',
        'Adresszeile6',
        'Strasse',
        'Hausnummer',
        'Plz',
        'Ort',
        'Land',
        'Anrede',
        'Briefanrede',
        'Titel',
        'Vorname',
        'Nachname',
        'Name2',
        'VESRNummer',
        'ESR1',
        'ESR1RefZeile',
        'ESR1RefZeileGrp',
        'ESR1Identity',
        'DataMatrixCodeTyp20abStelle37',
        'DataMatrixCode',
        'TextBaustein',
        'Paketnummer',
        );

    // fill prefix lookup
    $prefixes = civicrm_api3('OptionValue', 'get', array('option_group_id' => 'individual_prefix', 'return' => 'value,label'));
    $this->id2prefix = array('' => '', NULL => '');
    foreach ($prefixes['values'] as $prefix) {
      $this->id2prefix[$prefix['value']] = $prefix['label'];
    }

    // fill country (localised)
    $this->id2country = CRM_Core_PseudoConstant::country();
    // $I18nParams = array('context' => 'country');
    // $i18n = CRM_Core_I18n::singleton();
    // $i18n->localizeArray($this->id2country, $I18nParams);
  }


  /**
   * Will generate a CSV file and write into a file or the HTTP stream
   */
  public function generate($contact_ids, $params, $out = 'php://output') {
    if ($out == 'php://output') {
      // we want to write into the outstream
      $filename = "ESR-" . date('YmdHis') . '.csv';
      header('Content-Description: File Transfer');
      header('Content-Type: application/csv');
      header('Content-Disposition: attachment; filename=' . $filename);
      // 4.7:
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
    $sql = $this->generateSQL($contact_ids, $params);
    $query = CRM_Core_DAO::executeQuery($sql);
    while ($query->fetch()) {
      $record = $this->generateRecord($query, $params);
      $csv_line = array();
      foreach ($this->header as $field) {
        if (isset($record[$field])) {
          $csv_line[] = $record[$field];
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
  protected function generateSQL($contact_ids, $params) {
    $filtered_contact_ids = array();
    foreach ($contact_ids as $contact_id) {
      $filtered_contact_id = (int) $contact_id;
      if ($filtered_contact_id) {
        $filtered_contact_ids[] = $filtered_contact_id;
      }
    }

    if (empty($filtered_contact_ids)) {
      $where_clause = 'FALSE';
    } else {
      $contact_id_list = implode(',', $filtered_contact_ids);
      $where_clause = "civicrm_contact.id IN ({$contact_id_list})";
    }

    $sql = "
SELECT    civicrm_contact.id                        AS contact_id,
          civicrm_contact.prefix_id                 AS prefix_id,
          civicrm_contact.display_name              AS display_name,
          civicrm_contact.first_name                AS first_name,
          civicrm_contact.last_name                 AS last_name,
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
WHERE     {$where_clause}
GROUP BY  civicrm_contact.id";
    return $sql;
  }

  /**
   * generate a new record on the next line of the query result
   */
  protected function generateRecord($query, $params) {
    $record = array();

    // basic information
    $record['Personennummer'] = $query->contact_id;
    $record['Mailcode']       = $params['mailcode'];

    // address lines
    $record['Adresszeile1'] = $this->id2prefix[$query->prefix_id];
    $record['Adresszeile2'] = $query->addressee_display;
    $record['Adresszeile3'] = $query->street_address;
    $record['Adresszeile4'] = "{$query->postal_code} {$query->city}";
    $record['Adresszeile5'] = $query->supplemental_address_1;
    $record['Adresszeile6'] = $query->supplemental_address_2;

    // parsed address
    // TODO: PARSE
    $record['Strasse']    = '';
    $record['Hausnummer'] = '';
    $record['Plz']        = $query->postal_code;
    $record['Ort']        = $query->city;
    $record['Land']       = $this->id2country[$query->country_id];

    // personalised data
    $record['Anrede']       = $this->id2prefix[$query->prefix_id];
    $record['Briefanrede']  = $query->postal_greeting_display;
    $record['Titel']        = $query->formal_title;
    $record['Vorname']      = $query->first_name;
    $record['Nachname']     = $query->last_name;
    $record['Name2']        = '';  // unused

    // codes
    $record['VESRNummer']       = $params['tn_number'];
    $record['ESR1']             = $params['mailcode'];
    $record['ESR1RefZeile']     = '1234567890';  // TODO
    $record['ESR1RefZeileGrp']  = '1234 5678 90';  // TODO
    // unused: ESR1Identity, DataMatrixCodeTyp20abStelle37, DataMatrixCode, TextBaustein, Paketnummer

    return $record;
  }

}