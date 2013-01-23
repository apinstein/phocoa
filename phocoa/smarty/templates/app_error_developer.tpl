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

<h3>Server Data</h3>
<pre>
URL: http://{$_SERVER['HTTP_HOST']}{$_SERVER["REQUEST_URI"]}
Referrer: {$_SERVER['HTTP_REFERER']|default:'(none)'}
Server: {$_SERVER|print_r}
Request: {$_REQUEST|print_r}
Session: {$_SESSION|print_r}
</pre>
<script>
console.error("Error at {$location}\n{$headline}", {$standardErrorDataJSON});
</script>
