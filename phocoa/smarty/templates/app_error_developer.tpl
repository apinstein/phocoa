<h2>Uncaught Exception</h2>
{foreach from=$exceptions item=exception}
{php}
    print "<table>
            <tr>
                <td>Class:</td>
                <td>" . get_class($this->_tpl_vars['exception']) . "</td>
            </tr>
            <tr>
                <td>Message:</td>
                <td>{$this->_tpl_vars['exception']->getMessage()}</td>
            </tr>
            <tr>
                <td>Code:</td>
                <td>{$this->_tpl_vars['exception']->getCode()}</td>
            </tr>
            <tr>
                <td>Location:</td>
                <td>
                    {$this->_tpl_vars['exception']->getFile()}:{$this->_tpl_vars['exception']->getLine()}
                    <pre>Trace:\n" . $this->_tpl_vars['exceptions'][0]->getTraceAsString() . "</pre>
                </td>
            </tr>
           </table>
          "
          ;
{/php}
{/foreach}
{php}
    extract(error_get_last());
    print "
          <h3>error_get_last() output</h3>
          <p><em>may or may not be relevant to the Exception</em></p>
          <table>
            <tr>
                <td>Type:</td>
                <td>{$type}</td>
            </tr>
            <tr>
                <td>Message:</td>
                <td>{$message}</td>
            </tr>
            <tr>
                <td>Location:</td>
                <td>{$file}:{$line}</td>
            </tr>
           </table>
          "
          ;
{/php}

<h3>Server Data</h3>
<pre>
{php}
print "
URL: http://{$_SERVER['HTTP_HOST']}{$_SERVER["REQUEST_URI"]}
Referrer: " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '(none)') . "
Server: " . print_r($_SERVER, true) . "
Request: " . print_r($_REQUEST, true) . "
Session: " . print_r($_SESSION, true);
{/php}
</pre>
<script>
console.error(
{php}
$err = "PHP Uncaught Exception
URL: http://{$_SERVER['HTTP_HOST']}{$_SERVER["REQUEST_URI"]}
Referrer: " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '(none)') . "
Server: " . print_r($_SERVER, true) . "
Request: " . print_r($_REQUEST, true) . "
Session: " . print_r($_SESSION, true);
print json_encode($err);
{/php}
);
</script>
