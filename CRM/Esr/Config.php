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
 * ESR Module configuration
 */
class CRM_Esr_Config {

  /**
   * Value for TA875 file format:
   *
   */
  public function get_ta875_ESR_TN() {
    // TODO: move to settings
    return '010092520';
  }

  /**
   * Value for TA875 file format:
   *
   */
  public function get_ta875_LSV_ID() {
    // TODO: move to settings
    return '900003';
  }
}
