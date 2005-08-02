<script language="JavaScript">
<!--
var tabList = new Array({foreach name=enum_tabs from=$__tabView->tabs() item=tab key=tabID}"{$tabID}"{if !$smarty.foreach.enum_tabs.last},{/if}{/foreach});
{literal}
function activateTab(tabMode)
{   
    for (theTabIndex in tabList) {
        theTab = tabList[theTabIndex];
        tabContentsID = 'tabDiv_' + theTab;
        tabContents = document.getElementById(tabContentsID);
        tabLinkID = 'tabLink_' + theTab;
        tabLink = document.getElementById('tabLink_' + theTab);
        if (tabMode == theTab) {
            // activate
            tabLink.className = 'active';
            tabContents.style.display = 'block';
            
            {/literal}
            onepage_mode_current_tab_fieldname = document.getElementById('{$__tabView->id()}');
            onepage_mode_current_tab_fieldname.value = theTab;
            {literal}
        } else {
            // de-activate
            tabLink.className = '';
            tabContents.style.display = 'none';
        }
    }
}
{/literal}
-->
</script>
    
<input type="hidden" id="{$__tabView->id()}" name="{$__tabView->id()}" value="{$__tabView->activeTabID()}">

{strip}
<div class="phocoaTabNav">
    <ul>
        {foreach name=tabs from=$__tabView->tabs() item=tab key=tabID}
            <li id="tabLink_{$tabID}"><a href="#" onClick="activateTab('{$tabID}'); return false;">{$tab->label()}</a></li>
        {/foreach}
    </ul>
</div>
{/strip}
{foreach name=tabs from=$__tabView->tabs() item=tab key=tabID}
    <div id="tabDiv_{$tabID}" class="phocoaTabContent" style="display: none;">
        {include file=$tab->template()}
    </div>
{/foreach}

<script language="JavaScript">
<!--
activateTab('{$__tabView->activeTabID()}');
-->
</script>

