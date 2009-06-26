{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}

<p>WFTextField is a simple text field.</p>

{WFView id="name"}
{WFView id="fancyField"}

{literal}
<hr>
<h3>.tpl file</h3>
<pre>
{WFView id="name"}
{WFView id="fancyField"}
</pre>

{/literal}
<pre>{php}echo htmlentities(file_get_contents(FRAMEWORK_DIR . '/modules/examples/widgets/textField.yaml'));{/php}</pre>

<h3>Module Code</h3>
<pre>
none
</pre>
