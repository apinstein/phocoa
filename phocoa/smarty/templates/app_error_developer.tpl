<h2>Uncaught Exception</h2>

<table>
{foreach from=$standardErrorData item=error key=errorIndex}
      <tr>
        <th colspan=2>{$errorIndex+1}. {$error.title}</th>
      </tr>
      <tr>
          <td>Message:</td>
          <td>{$error.message}</td>
      </tr>
      <tr>
          <td>Code:</td>
          <td>{$error.code}</td>
      </tr>
      <tr>
          <td>Location:</td>
          <td>
            <div class="phocoaStackTrace">{$error.traceAsString}</div>
          </td>
      </tr>
{/foreach}
</table>


<h2>Server Data</h2>
<div class="phocoaStackTrace">{php}
print "URL: http://{$_SERVER['HTTP_HOST']}{$_SERVER["REQUEST_URI"]}
Referrer: " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '(none)') . "
Server: " . print_r($_SERVER, true) . "
Request: " . print_r($_REQUEST, true) . "
Session: " . print_r($_SESSION, true);
{/php}
</pre>
<script>
console.error("Error at {$location}\n{$headline}", {$standardErrorDataJSON});
</script>
