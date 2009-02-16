<p>An uncaught exception ({$exceptionClass}) has occurred:</p>
<p>{$exception->getMessage()}</p>
<p>Stack Trace:</p>
<pre>
{php}
print "
Exception: " . get_class($this->_tpl_vars['exception']) . "
Error: " . $this->_tpl_vars['exception']->getMessage() . "
URL: http://{$_SERVER['HTTP_HOST']}{$_SERVER["REQUEST_URI"]}
Referrer: " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '(none)') . "
Trace: " . $this->_tpl_vars['exception']->getTraceAsString() . "
Server: " . print_r($_SERVER, true) . "
Request: " . print_r($_REQUEST, true) . "
Session: " . print_r($_SESSION, true);
{/php}
</pre>
