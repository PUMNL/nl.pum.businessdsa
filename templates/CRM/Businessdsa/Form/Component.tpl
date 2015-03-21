{* HEADER *}
<h3>{$formHeader}</h3>

<div class="crm-block crm-form-block">
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="top"}
  </div>
  {* view action *}
  {if $action eq 4}
    {foreach from=$elementNames item=elementName}
      <div class="crm-section">
        <div class="label">{$form.$elementName.label}</div>
        {if $elementName eq 'dsa_amount'}
          <div class="content">{$form.$elementName.value|crmMoney}</div>
        {elseif $elementName eq 'is_active'}
          {if $form.$elementName.value eq 1}
            <div class="content">{ts}Yes{/ts}</div>
          {else}
            <div class="content">{ts}No{/ts}</div>
          {/if}
        {elseif $elementName eq 'accountable_advance'}
          {if $form.$elementName.value eq 1}
            <div class="content">{ts}Yes{/ts}</div>
          {else}
            <div class="content">{ts}No{/ts}</div>
          {/if}
        {else}
          <div class="content">{$form.$elementName.value}</div>
        {/if}
        <div class="clear"></div>
      </div>
    {/foreach}

  {else}
  { *add or update action *}
    {foreach from=$elementNames item=elementName}
      <div class="crm-section">
        <div class="label">{$form.$elementName.label}</div>
        <div class="content">{$form.$elementName.html}</div>
        <div class="clear"></div>
      </div>
    {/foreach}
  {/if}
  {* FOOTER *}
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>
