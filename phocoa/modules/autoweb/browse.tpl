{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
<h2>{$tableMap->getName()}</h2>

{WFPaginatorPageInfo id="pageInfo"}<br />
{WFPaginatorNavigation id="pageNav"}<br />

<table border="1" cellspacing="0" cellpadding="3">
{section name=currentRecord loop=$objects}
    {if $smarty.section.currentRecord.first}
    <tr>
        {foreach from=$dynamicWidgetIDs key=colName item=colWidgets}
            <th>{WFPaginatorSortLink id=$colWidgets.sortLink}</th>
        {/foreach}
    </tr>
    {/if}
    <tr>
        {foreach from=$dynamicWidgetIDs key=colName item=colWidgets}
            <td valign="top">{WFDynamic id=$colWidgets.label}</td>
        {/foreach}
    {*
        {foreach from=$columns key=colDBName item=colPHPName}
            <td valign="top">{$objects[currentRecord]->valueForKey($colPHPName)}</td>
        {/foreach}
        *}
    </tr>
{sectionelse}
    <tr><td>No records found in {$tableMap->getName()}.</td></tr>
{/section}
</table>
