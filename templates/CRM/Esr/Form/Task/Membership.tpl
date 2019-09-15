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

<h3>{ts domain="de.systopia.esr"}Parameters{/ts}</h3>

<div class="crm-section">
  <div class="label">{$form.tn_number.label} <a onclick='CRM.help("{ts domain="de.systopia.esr"}ESR Registration{/ts}", {literal}{"id":"id-number","file":"CRM\/Esr\/Form\/Task\/Membership"}{/literal}); return false;' href="#" title="Help" class="helpicon">&nbsp;</a></div>
  <div class="content">{$form.tn_number.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.paying_contact.label} <a onclick='CRM.help("{ts domain="de.systopia.esr"}Buyer Options{/ts}", {literal}{"id":"id-buyer","file":"CRM\/Esr\/Form\/Task\/Membership"}{/literal}); return false;' href="#" title="Help" class="helpicon">&nbsp;</a></div>
  <div class="content">{$form.paying_contact.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.amount_option.label} <a onclick='CRM.help("{ts domain="de.systopia.esr"}Amount Options{/ts}", {literal}{"id":"id-amount","file":"CRM\/Esr\/Form\/Task\/Membership"}{/literal}); return false;' href="#" title="Help" class="helpicon">&nbsp;</a></div>
  <div class="content">{$form.amount_option.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.amount.label}</div>
  <div class="content">{$form.amount.html}&nbsp;CHF</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.custom_text.label}</div>
  <div class="content">{$form.custom_text.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.custom_field_id.label}</div>
  <div class="content">{$form.custom_field_id.html}</div>
  <div class="clear"></div>
</div>

<br/>
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

<script type="text/javascript">
{literal}
function esr_show_amount() {
  let current_value = cj("#amount_option").val();
  console.log(current_value);
  if (current_value == 'fixed') {
    cj("#amount").parent().parent().show();
  } else {
    cj("#amount").parent().parent().hide();
  }
}

cj("#amount_option").change(esr_show_amount);
esr_show_amount();
{/literal}
</script>
