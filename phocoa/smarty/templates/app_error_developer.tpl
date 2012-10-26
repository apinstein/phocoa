<h2>Uncaught Exception</h2>
<pre>
{foreach from=$exceptions item=exception}
{php}
    print '<h3>' . get_class($this->_tpl_vars['exception']) . '</h3>' . 
          "<p>{$this->_tpl_vars['exception']->getMessage()}</p>" . 
          "<p>Trace:</p>" . $this->_tpl_vars['exceptions'][0]->getTraceAsString()
          ;
{/php}
<hr />
{/foreach}
</pre>

<h2>Server Data</h2>
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
