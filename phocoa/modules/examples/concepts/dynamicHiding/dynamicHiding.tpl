{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
<h1>Dynamic Hiding of Elements and their Context</h1>

<p>While all UI widgets support a "hidden" attribute that causes them to not be rendered, sometimes there is supporting HTML that surrounds the widget that also needs to be hidden. This is particularly common when optionally including data in a table, where you might want to exclude an entire row if a piece of data is missing.</p>

<p>Toggle Visibility: <a href="{WFURL page="dynamicHiding"}?hide=0">Show</a> <a href="{WFURL page="dynamicHiding"}?hide=1">Hide</a></p>
<h2>Simple Example</h2>
<p>This example hides a table row if the "name" field is hidden.</p>
<table border="0" cellspacing="0" cellpadding="3">
    {WFViewHiddenHelper id="name"}
    <tr>
        <td valign="top" align="right">Name:</td>
        <td valign="top">{WFView id="name"}</td>
    </tr>
    {/WFViewHiddenHelper}
    <tr>
        <td valign="top" align="right">City:</td>
        <td valign="top">{WFView id="city"}</td>
    </tr>
</table>

<p>Code:
<pre>
{capture name="code"}
{literal}
<table border="0" cellspacing="0" cellpadding="3">
    {WFViewHiddenHelper id="name"}
    <tr>
        <td valign="top" align="right">Name:</td>
        <td valign="top">{WFView id="name"}</td>
    </tr>
    {/WFViewHiddenHelper}
    <tr>
        <td valign="top" align="right">City:</td>
        <td valign="top">{WFView id="city"}</td>
    </tr>
</table>
{/literal}
{/capture}
{$smarty.capture.code|escape}
</pre>
</p>


<h2>Dynamic Example</h2>
<p>WFViewHiddenHelper also works with WFDynamic's. This example hides "Alan" and "David".</p>

<ul>
{section name=items loop=$__module->valueForKeyPath('People.arrangedObjectCount')}
    {WFViewHiddenHelper id="listName"}<li>{WFDynamic id="listName"}</li>{/WFViewHiddenHelper}
{/section}
</ul>

<p>Code:
<pre style="width: 1000px;overflow: visible">
{capture name="code1"}
{literal}
<ul>
{section name=items loop=$__module->valueForKeyPath('People.arrangedObjectCount')}
    {WFViewHiddenHelper id="listName"}<li>{WFDynamic id="listName"}</li>{/WFViewHiddenHelper}
{/section}
</ul>
{/literal}
{/capture}
{$smarty.capture.code1|escape}
</pre>
</p>
