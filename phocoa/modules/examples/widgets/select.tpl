{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
{WFViewBlock id="cityForm"}
<h2>Select</h2>
{WFSelect id="city"}

<h2>Multiple Select</h2>
{WFSelect id="cityMultiple"}

<br/>{WFView id="submitForm"}
{/WFViewBlock}

{literal}
<hr>
<h3>.tpl file</h3>
<pre>
{WFView id="city"}
{WFView id="cityMultiple"}
</pre>
{/literal}
    
<h3>.yaml file</h3>
<pre>
{php}
echo htmlentities(file_get_contents(FRAMEWORK_DIR . '/modules/examples/widgets/select.yaml'));
{/php}
</pre>
    
