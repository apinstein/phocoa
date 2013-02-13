<h2>Uncaught Exception</h2>
{foreach from=$exceptions item=exception}
   <table>
    <tr>
        <td>Class:</td>
        <td>{get_class($exception)}</td>
    </tr>
    <tr>
        <td>Message:</td>
        <td>{$exception->getMessage()}</td>
    </tr>
    <tr>
        <td>Code:</td>
        <td>{$exception->getCode()}</td>
    </tr>
    <tr>
        <td>Location:</td>
        <td>
            {$exception->getFile()}:{$exception->getLine()}
            <pre>Trace:\n{$exceptions[0]->getTraceAsString()}</pre>
        </td>
    </tr>
   </table>
{/foreach}

{assign var=error value=error_get_last()}
<h3>error_get_last() output</h3>
<p><em>may or may not be relevant to the Exception</em></p>
<table>
<tr>
    <td>Type:</td>
    <td>{$error['type']}</td>
</tr>
<tr>
    <td>Message:</td>
    <td>{$error['message']}</td>
</tr>
<tr>
    <td>Location:</td>
    <td>{$error['file']}:{$error['line']}</td>
</tr>
</table>

<h3>Misc Data</h3>
<table>
    <tr>
        <td nowrap>URL</td>
        <td>{$location}</td>
    </tr>
    <tr>
        <td nowrap>Referrer</td>
        <td>{$smarty.server.HTTP_REFERER|default:'(none)'}</td>
    </tr>
    <tr>
        <td nowrap valign="top">Request Scope</td>
        <td><pre>{$smarty.request|@print_r:true}</pre></td>
    </tr>
    <tr>
        <td nowrap valign="top">Session Scope</td>
        <td><pre>{$smarty.session|@print_r:true}</pre></td>
    </tr>
    <tr>
        <td nowrap valign="top">Server Scope</td>
        <td><pre>{$smarty.server|@print_r:true}</pre></td>
    </tr>
</table>

<script>
console.error("Error at {$location}\n{$headline}", {$standardErrorDataJSON});
</script>
