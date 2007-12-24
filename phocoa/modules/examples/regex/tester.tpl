{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
<style>
{literal}
#regexTarget, #regexExpression {
    width: 500px;
}
{/literal}
</style>
<h1>PHP Regular Expression Tester</h1>
<p>This demonstrates a good use of the AJAX technologies in PHOCOA.</p>
{WFForm id="regexForm"}
<table border="0">
    <tr>
        <td>Match Type:</td>
        <td>{WFView id=regexMatchTypeMatch} {WFView id="regexMatchTypeMatchAll"}</td>
    </tr>
    <tr>
        <td>Expression:</td>
        <td>{WFView id=regexExpression} {WFView id="regexRun"}</td>
    </tr>
    <tr>
        <td>Input Text:</td>
        <td>{WFView id=regexTarget}</td>
    </tr>
    <tr>
        <td colspan="2">{WFView id=regexResult}</td>
    </tr>
</table>
{/WFForm}
<script>
{literal}
Event.observe(window, 'load', function() {
        $('regexExpression').focus();
    });
{/literal}
</script>
