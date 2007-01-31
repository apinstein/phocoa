{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
{literal}
<style type="text/css">
</style>
{/literal}

<a href="#" onClick="PHOCOA.runtime.getObject('module').show();">Show Module</a>
{WFViewBlock id="module"}
This is a module. By default modules have no style; they are just blocks of content. <a href="#" onClick="PHOCOA.runtime.getObject('module').hide();">Hide Module</a>
{/WFViewBlock}

<br />

<a href="#" onClick="PHOCOA.runtime.getObject('overlay').show();">Show Overlay</a>
{WFViewBlock id="overlay"}
This is an overlay. By default overlays have no style; they are just absolutely positioned blocks of content. <a href="#" onClick="PHOCOA.runtime.getObject('overlay').hide();">Hide Overlay</a>
{/WFViewBlock} 

<br />

<a href="#" onClick="PHOCOA.runtime.getObject('panel').show();">Show Panel</a>
{WFViewBlock id="panel"}
This is a panel. By default, panels pop up over the existing content and have a "dialog-like" UI.
{/WFViewBlock} 

