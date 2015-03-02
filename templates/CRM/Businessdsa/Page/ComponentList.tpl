<div class="crm-content-block crm-block">
  <div id="help">
    {ts}The existing business DSA components are listed below. You can edit, delete or add a new one from this screen.{/ts}
  </div>
  <div class="action-link">
    <a class="button new-option" href="{$addUrl}">
      <span><div class="icon add-icon"></div>{ts}New Component{/ts}</span>
    </a>
  </div>
  {include file='CRM/common/jsortable.tpl'}
  <div id="component_wrapper" class="dataTables_wrapper">
    <table id="component-table" class="display">
      <thead>
      <tr>
        <th>{ts}Component Name{/ts}</th>
        <th>{ts}Amount{/ts}</th>
        <th>{ts}Description{/ts}</th>
        <th>{ts}Enabled?{/ts}</th>
        <th>{ts}Date Modified{/ts}</th>
        <th>{ts}Modified By{/ts}</th>
        <th>{ts}Date Created{/ts}</th>
        <th>{ts}Created By{/ts}</th>
        <th id="nosort"></th>
      </tr>
      </thead>
      <tbody>
      {assign var="rowClass" value="odd-row"}
      {assign var="rowCount" value=0}
      {foreach from=$components key=componentId item=component}
        {assign var="rowCount" value=$rowCount+1}
        <tr id="row{$componentId}" class={$rowClass}>
          <td>{$component.name}</td>
          <td>{$component.amount|crmMoney}</td>
          <td>{$component.description}</td>
          <td>{$component.enabled}</td>
          <td>{$component.modified_date|crmDate}</td>
          <td>{$component.modified_by}</td>
          <td>{$component.created_date|crmDate}</td>
          <td>{$component.created_by}</td>
          <td>
              <span>
                {foreach from=$component.actions item=actionLink}
                  {$actionLink}
                {/foreach}
              </span>
          </td>
        </tr>
        {if $rowClass eq "odd-row"}
          {assign var="rowClass" value="even-row"}
        {else}
          {assign var="rowClass" value="odd-row"}
        {/if}
      {/foreach}
      </tbody>
    </table>
  </div>
  <div class="action-link">
    <a class="button new-option" href="{$addUrl}">
      <span><div class="icon add-icon"></div>{ts}New Component{/ts}</span>
    </a>
  </div>
</div>
