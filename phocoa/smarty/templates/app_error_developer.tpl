<p>An uncaught exception has occurred:</p>
<pre>
{foreach from=$exceptions item=exception}
{php}
    print "Exception: " . get_class($this->_tpl_vars['exception']) .
          "\n\nMessage: {$this->_tpl_vars['exception']}" .
          "\n\nTrace:\n" . $this->_tpl_vars['exceptions'][0]->getTraceAsString();
{/php}
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
