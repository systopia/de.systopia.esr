{*-------------------------------------------------------+
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
+-------------------------------------------------------*}


<h3>{ts domain="de.systopia.esr"}TA875/LSV+ CiviSEPA Format{/ts}</h3>

<div class="crm-section">
  <div class="label">{$form.esr_tn.label}</div>
  <div class="content">{$form.esr_tn.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.lsv_id.label}</div>
  <div class="content">{$form.lsv_id.html}</div>
  <div class="clear"></div>
</div>


<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
