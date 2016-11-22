{*-------------------------------------------------------+
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
+-------------------------------------------------------*}

<h3>Parameter</h3>

<div class="crm-section">
  <div class="label">{$form.amount.label} <a onclick='CRM.help("Betrag", {literal}{"id":"id-amount","file":"CRM\/Esr\/Form\/Task\/Contact"}{/literal}); return false;' href="#" title="Help" class="helpicon">&nbsp;</a></div>
  <div class="content">{$form.amount.html}&nbsp;CHF</div>
  <div class="clear"></div>
</div>
<div class="crm-section">
  <div class="label">{$form.tn_number.label}</div>
  <div class="content">{$form.tn_number.html}</div>
  <div class="clear"></div>
</div>
<div class="crm-section">
  <div class="label">{$form.mailcode.label}</div>
  <div class="content">{$form.mailcode.html}</div>
  <div class="clear"></div>
</div>
<div class="crm-section">
  <div class="label">{$form.custom_text.label}</div>
  <div class="content">{$form.custom_text.html}</div>
  <div class="clear"></div>
</div>

<br/>
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
