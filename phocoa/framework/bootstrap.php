<?php

/**
 * PHOCOA has its own autload infrastructure that is handled by WFWebApplication. So, this is the only require_once() we need to use in all PHOCOA framework code outside of bootstrapping this file.
 */
require('framework/util/WFIncluding.php');
require('framework/WFWebApplication.php'); // need this because it decleare the WFWebApplicationMain() entry point that is used to get things going.
require('framework/WFLog.php');    // need this for the PEAR_LOG_* constants below.

// This version number should be updated with each release - this version number, among other things, is used to construct version-unique URLs for static resources
// thus anytime anything in wwwroot/www/framework changes, this should be bumped. This version string should match [0-9\.]*
define('PHOCOA_VERSION', '0.2.1');

if (IS_PRODUCTION)
{
    error_reporting(E_ALL);
    ini_set('display_errors', false);
    if (!defined('WF_LOG_LEVEL'))
    {
        define('WF_LOG_LEVEL', PEAR_LOG_ERR);
    }
}
else
{
    error_reporting(E_ALL); // | E_STRICT);
    ini_set('display_errors', true);
    if (!defined('WF_LOG_LEVEL'))
    {
        define('WF_LOG_LEVEL', PEAR_LOG_DEBUG);
    }
}

// load the WFWebApplication so that it is initialized() before __autoload() is called for the first time.
// if we don't do this, classes attempted to autoload from initialized() will cause a fatal error.
WFWebApplication::sharedWebApplication();

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
    //print "autoload: $className<BR>";

    $loaded = WFIncluding::autoload($className);
    if (!$loaded)
    {
        $webapp = WFWebApplication::sharedWebApplication();
        $loaded = $webapp->autoload($className);
    }
}

// register the autoloader on higher PHP versions for compatibility with other systems.
if (function_exists('spl_autoload_register'))
{
    spl_autoload_register('__autoload');
}

?>
