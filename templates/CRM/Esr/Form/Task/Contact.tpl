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
  <div class="label">{$form.exporter_class.label} <a onclick='CRM.help("{ts domain="de.systopia.esr"}Generator{/ts}", {literal}{"id":"id-exporter_class","file":"CRM\/Esr\/Form\/Task\/Contact"}{/literal}); return false;' href="#" title="Help" class="helpicon">&nbsp;</a></div>
  <div class="content">{$form.exporter_class.html}</div>
  <div class="clear"></div>
</div>
<div class="crm-section">
  <div class="label">{$form.amount.label} <a onclick='CRM.help("{ts domain="de.systopia.esr"}Amount{/ts}", {literal}{"id":"id-amount","file":"CRM\/Esr\/Form\/Task\/Contact"}{/literal}); return false;' href="#" title="Help" class="helpicon">&nbsp;</a></div>
  <div class="content">{$form.amount.html}&nbsp;CHF</div>
  <div class="clear"></div>
</div>
<div class="crm-section">
  <div class="label">{$form.tn_number.label} <a onclick='CRM.help("{ts domain="de.systopia.esr"}ESR Number{/ts}", {literal}{"id":"id-number","file":"CRM\/Esr\/Form\/Task\/Contact"}{/literal}); return false;' href="#" title="Help" class="helpicon">&nbsp;</a></div>
  <div class="content">{$form.tn_number.html}</div>
  <div class="clear"></div>
</div>
<div class="crm-section">
  <div class="label">{$form.mailcode.label}</div>
  <div class="content">{$form.mailcode.html}</div>
  <div class="clear"></div>
</div>
<div class="crm-section">
  <div class="label">{$form.campaign.label}</div>
  <div class="content">{$form.campaign.html}</div>
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
var campaign_mailcode_prefix = "{$campaign_mailcode_prefix}";
var campaign_mailcode_length = {$campaign_mailcode_length};
{literal}

cj("#campaign").change(function(e) {
  var campaign_id = cj(e.target).val();
  if (campaign_id == '0') {
    cj("#mailcode").val('');
  } else {
    // compile mailcode
    var mailcode = campaign_mailcode_prefix;
    while (mailcode.length + campaign_id.length < campaign_mailcode_length) {
      mailcode = mailcode + '0';
    }
    mailcode = mailcode + campaign_id;
    cj("#mailcode").val(mailcode);
  }
});

cj("#mailcode").change(function(e) {
  cj("#campaign").val('0');
});

{/literal}
</script>
