{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
{*
{WFView id="logger"}
*}

{literal}
<style type="text/css">
</style>
<script type="text/javascript">
var clickMeHandler = function()
{
    PHOCOA.runtime.getObject('dialog').submit();
}
var dialogSuccessHandler = function(o) {
    if (o.responseText.match('right'))
    {
        alert('Logically correct');
    }
    else
    {
        alert('Logically incorrect');
        PHOCOA.runtime.getObject('dialog').show();
    }
}
var dialogFailureHandler = function(o) {
    alert('Communication Error');
}
</script>
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

<br />

<a href="#" onClick="PHOCOA.runtime.getObject('phocoaDialog').show();">Show PhocoaDialog</a> - A PhocoaDialog is a custom YUI subclass that allows you to drop in any PHOCOA module as an AJAX-based workflow. This one includes the <a href="{WFURL module="examples/ajax/autocomplete/example"}">autocomplete example</a>.
{WFView id="phocoaDialog"}

<hr />

<div style="position: relative;; width: 250px; height: 300px; overflow: scroll">
<p>This block demonstrates a fix for a bug in Firefox (Mac only).</p>
<p>On Firefox mac, there are 2 bugs involving scrollbars and a higher-z-index layer (such as a YUI Overlay or subclass):</p>
<ol>
<li>If there is a scrollbar on the page BELOW the overlay, the scrollbar normally bleeds through the overlay. Notice that this doesn't happen on this page.</li>
<li>If the overlay itself has a scrollbar, when the overlay is not visible, the scrollbar will still remain visible on the page.</li>
</ol>
<p>Click on Panel or Dialog above, and you will see the bug.</p>
<br /> <br />
<br /> <br />
<br /> <br />
<br /> <br />
<br /> <br />
<br /> <br />
<br /> <br />
<br /> <br />
<br /> <br />
</div>
