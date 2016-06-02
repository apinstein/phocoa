<?php

/**
 * PHOCOA has its own autoload infrastructure that is handled by WFWebApplication. So, this is the only require_once() we need to use in all PHOCOA framework code outside of bootstrapping this file.
 */
require('framework/util/WFIncluding.php');
$ok = spl_autoload_register(array('WFIncluding', 'autoload'));
if (!$ok) throw new WFException("Error registering WFIncluding::autoload()");

// This version number should be updated with each release - this version number, among other things, is used to construct version-unique URLs for static resources
// thus anytime anything in wwwroot/www/framework changes, this should be bumped. This version string should match [0-9\.]*
define('PHOCOA_VERSION', '0.4.2');
// PHOCOA shows all deprecation notices by default in wf.log on non-production hosts.
if (!defined('WF_LOG_DEPRECATED'))
{
    define('WF_LOG_DEPRECATED', !IS_PRODUCTION);
}

if (!defined('WF_LOG_ENABLE_ERROR_REPORTING_AUTOCONFIGURATION') or WF_LOG_ENABLE_ERROR_REPORTING_AUTOCONFIGURATION)
{
    if (IS_PRODUCTION)
    {
        error_reporting(E_ALL);
        ini_set('display_errors', false);
    }
    else
    {
        error_reporting(E_ALL);
        ini_set('display_errors', true);
    }
}

require('framework/WFLog.php');    // need this for the PEAR_LOG_* constants below, which can't autoload.
if (!defined('WF_LOG_LEVEL'))
{
    define('WF_LOG_LEVEL', IS_PRODUCTION ? PEAR_LOG_ERR : PEAR_LOG_DEBUG);
}
// load the WFWebApplication so that it is initialized() before __autoload() is called for the first time.
// if we don't do this, classes attempted to autoload from WFWebApplication::initialize() will cause a fatal error.
require('framework/WFWebApplication.php');    // WFWebApplicationMain() can't autoload...
WFWebApplication::sharedWebApplication();
