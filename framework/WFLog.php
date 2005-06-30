<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package framework-base
 * @subpackage Log
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/**
 * Includes
 */
require_once('WFObject.php');
require_once('Log.php');

/**
 * The WFLog class provides some static helper methods for logging things.
 * Uses {@link http://pear.php.net/manual/en/package.logging.log.php PEAR::Log}.
 */
class WFLog extends WFObject
{
    // the TRACE log ident
    const TRACE_LOG = 'trace';
    const WARN_LOG = 'warn';

    /**
      * Log the passed exception to the framework's log folder.
      * @param string Log message to log.
      * @param string The "ident" of the message to log.
      * @param level The PEAR log level (PEAR_LOG_EMERG, PEAR_LOG_ALERT, PEAR_LOG_CRIT, PEAR_LOG_ERR, PEAR_LOG_WARNING, PEAR_LOG_NOTICE, PEAR_LOG_INFO, and PEAR_LOG_DEBUG)
      *
      * The message will be logged to the wf.log file in the web application's log directory, if the log level is less than or equal to the WF_LOG_LEVEL.
      */
    function log($message, $ident = 'general', $level = PEAR_LOG_DEBUG)
    {
        $logFileDir = WFWebApplication::sharedWebApplication()->appDirPath(WFWebApplication::DIR_LOG);
        $logger = Log::singleton('file', $logFileDir . '/wf.log', $ident, NULL, WF_LOG_LEVEL);
        $logger->log($message, $level);
    }
}
