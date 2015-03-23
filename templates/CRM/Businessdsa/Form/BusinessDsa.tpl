{* HEADER *}
<h3>{$formHeader}</h3>
<div class="messages status no-popup">
  <div class="icon inform-icon"></div>
  {ts}The base business DSA amount is {$baseAmount|crmMoney}, the accountable amount is
    {$accountableAmount|crmMoney} (per person per day){/ts}
</div>

<div class="crm-block crm-form-block">

  <div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="top"}
  </div>

  <div class="crm-section">
    <div class="label">{$form.noOfPersons.label}</div>
    <div class="content">{$form.noOfPersons.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.noOfDays.label}</div>
    <div class="content">{$form.noOfDays.html}</div>
    <div class="clear"></div>
  </div>

  {* FOOTER *}
  <div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>
