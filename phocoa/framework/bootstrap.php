<?php

require_once('framework/WFWebApplication.php');
require_once('framework/WFLog.php');

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


?>
