<?php

require_once('framework/WFWebApplication.php');

if (IS_PRODUCTION)
{
    error_reporting(E_ALL);
    ini_set('display_errors', false);
    define('WF_LOG_LEVEL', PEAR_LOG_ERR);
}
else
{
    error_reporting(E_ALL); // | E_STRICT);
    ini_set('display_errors', true);
    define('WF_LOG_LEVEL', PEAR_LOG_DEBUG);
}

/**
 *  Base autoload handler for PHOCOA. 
 *
 *  Implements a Chain of Responsibility pattern to allow various parts of the application to have a change to load classes.
 *
 *  1. Calls the autoload function on the Shared Web Application. This in turn will call the same function on the app delegate.
 *
 *  <code>
 *    bool autoload($className);    // return true if the class was loaded, false otherwise
 *  </code>
 *
 *  @param string The class name that needs to be loaded.
 */
function __autoload($className)
{
    $webapp = WFWebApplication::sharedWebApplication();
    $ok = $webapp->autoload($className);
    if (!$ok) WFLog::log("WARNING: unhandled autoload for class: '{$className}'", WFLog::WARN_LOG);
}
?>
