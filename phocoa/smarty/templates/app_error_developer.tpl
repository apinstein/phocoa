{foreach from=$exceptions item=exception}
    <p>An uncaught exception has occurred:</p>
    <p>({php} print get_class($this->_tpl_vars['exception']); {/php}) {$exception->getMessage()}</p>
    <pre>
    {php}
    print "
    (" . get_class($this->_tpl_vars['exception']) . ")
    Error: " . $this->_tpl_vars['exception']->getMessage() . "
    Trace: " . $this->_tpl_vars['exceptions'][0]->getTraceAsString();
    {/php}
</pre>
{/foreach}


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
