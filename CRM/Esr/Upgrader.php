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

use CRM_Esr_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Esr_Upgrader extends CRM_Esr_Upgrader_Base {
  /**
   * Update menu for configuration page
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0150() {
    $this->ctx->log->info('Applying update 0150');
    CRM_Core_Invoke::rebuildMenuAndCaches();
    return TRUE;
  }
}
