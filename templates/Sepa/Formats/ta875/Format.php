<?php
/*-------------------------------------------------------+
| Project 60 - SEPA direct debit                         |
| Copyright (C) 2016-2018                                |
| Author: @scardinius                                    |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

class CRM_Sepa_Logic_Format_ta875 extends CRM_Sepa_Logic_Format {

  /** cache for Creditor ID to IBAN mapping */
  protected $creditor_by_id = NULL;

  /** cached generator version */
  protected $generator = NULL;

  /**
   * gives the option of setting extra variables to the template
   */
  public function assignExtraVariables($template) {
    $config = new CRM_Esr_Config();

    // TODO: settings?
    // $template->assign('ta875_BC_ZP',  $config->get_ta875_BC_ZP());
    $template->assign('ta875_EDAT',   date('Ymd'));
    // $template->assign('ta875_BC_ZE',  $config->get_ta875_BC_ZE());
    $template->assign('ta875_ESR_TN', $config->get_ta875_ESR_TN());
  }

  /**
   * Get the creditor data
   * @param $creditor_id
   * @return array|null creditor data if exists
   */
  public function getCreditor($creditor_id) {
    if ($this->creditor_by_id === NULL) {
      $creditors = civicrm_api3('SepaCreditor', 'get', ['sequential' => 0, 'option.limit' => 0]);
      $this->creditor_by_id = $creditors['values'];
    }
    return CRM_Utils_Array::value($creditor_id, $this->creditor_by_id, NULL);
  }

  /**
   * proposed group prefix
   */
  public function getDDFilePrefix() {
    $config = new CRM_Esr_Config();
    return $config->get_ta875_ESR_Prefix();
  }

  /**
   * proposed file name
   */
  public function getFilename($variable_string) {
    return $variable_string.'.LSV';
  }

  /**
   * Lets the format add extra information to each individual
   *  transaction (contribution + extra data)
   */
  public function extendTransaction(&$trxn, $creditor_id) {
    // generate ESR reference
    $trxn['ta875_ESR']    = $this->generateReference($trxn, $creditor_id);

    // add debitor CH bank code (BLZ)
    $trxn['ta875_BC_ZP']  = $this->getBLZfromIBAN($trxn['iban']);

    // add creditor CH bank code (BLZ)
    $creditor_iban = $this->getIBANfromCreditor($creditor_id);
    $trxn['ta875_BC_ZE']  = $this->getBLZfromIBAN($creditor_iban);

    // add creditor address lines
    $address_lines = [];
    $creditor = $this->getCreditor($creditor_id);
    $address = explode(',', $creditor['address']);
    foreach ($address as $address_segment) {
      while (strlen($address_segment) > 34) {
        $address_lines[] = substr($address_segment, 0, 34);
        $address_segment = trim(substr($address_segment, 34));
      }
      $address_lines[] = trim($address_segment);
    }
    $trxn['creditor_address_lines'] = $address_lines;
  }



  /**
   * get the IBAN for the given creditor
   */
  protected function getIBANfromCreditor($creditor_id) {
    $creditor = $this->getCreditor($creditor_id);
    return $creditor['iban'];
  }

  /**
   * Cached lookup of BLZ via IBAN
   */
  protected function getBLZfromIBAN($iban) {
    $country = substr($iban, 0, 2);
    if ($country != 'CH') {
      return '99999'; // error
    }

    // cut out the BLZ
    $blz = substr($iban, 4, 5);

    // remove leading zeros
    while (substr($blz, 0, 1) == '0') {
      $blz = substr($blz, 1);
    }

    return $blz;
  }


  /**
   * If the TA875 format is selected as the creditor's file format
   *  this will generate a 27 digit ESR number
   */
  protected function generateReference($trxn, $creditor_id) {
    $config    = new CRM_Esr_Config();
    $generator = $this->getGenerator();


    // lead with 6-digit LSV-ID
    $reference = sprintf('%06d', $config->get_ta875_LSV_ID());

    // add fixed '0850' string
    $reference .= '0850';

    // add 10-digit contribution ID
    $reference .= sprintf('%010d', $trxn['contribution_id']);

    // add 6-digit creditor ID
    $reference .= sprintf('%06d', $creditor_id);

    // add checksum
    $reference .= $generator->calculate_checksum($reference);

    // add store as end2end
    return $reference;
  }

  /**
   * get the (cached) generator instance
   */
  protected function getGenerator() {
    if ($this->generator === NULL) {
      $this->generator = new CRM_Esr_Generator();
    }
    return $this->generator;
  }
}
