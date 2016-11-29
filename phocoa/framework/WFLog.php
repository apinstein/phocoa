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
require('Log.php');    // PEAR log

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
     * Log the passed message to the framework's log folder.
     * @param mixed string Log message to log.
     *              object WFFunction A WFFunction which will be lazy-evaluated to produce the message to log. This level of decoupling allows the log infrastructure
     *                                to be much faster when a message won't be logged as the message creation won't occur at all.
     * @param string The "ident" of the message to log.
     * @param level The PEAR log level (PEAR_LOG_EMERG, PEAR_LOG_ALERT, PEAR_LOG_CRIT, PEAR_LOG_ERR, PEAR_LOG_WARNING, PEAR_LOG_NOTICE, PEAR_LOG_INFO, and PEAR_LOG_DEBUG)
     *
     * The message will be logged to the wf.log file in the web application's log directory, if the log level is less than or equal to the WF_LOG_LEVEL.
     */
    public static function log($message, $ident = 'general', $level = PEAR_LOG_DEBUG)
    {
        $logger = WFWebApplication::sharedWebApplication()->logger($ident);

        if (!$logger) {
            if (!WFLog::logif($level)) return;   // bail as early as possible if we aren't gonna log this line

            $logFileDir = WFWebApplication::sharedWebApplication()->appDirPath(WFWebApplication::DIR_LOG);
            $logger = Log::singleton('file', $logFileDir . '/wf.log', $ident, array('mode' => 0666), WF_LOG_LEVEL);
        }

        $logger->log(self::buildLogMessage($message), $level);
    }

    /**
     * Log the passed message to the framework's log folder in the filename specified.
     * @param string The filename to log the message to. The exact string will be used for the filename; no extension will be appended.
     * @param mixed string Log message to log.
     *              object WFFunction A WFFunction which will be lazy-evaluated to produce the message to log. This level of decoupling allows the log infrastructure
     *                                to be much faster when a message won't be logged as the message creation won't occur at all.
     */
    public static function logToFile($fileName, $message)
    {
        $logger = WFWebApplication::sharedWebApplication()->logger($fileName);

        if (!$logger) {
            $logFileDir = WFWebApplication::sharedWebApplication()->appDirPath(WFWebApplication::DIR_LOG);
            $logger = Log::singleton('file', $logFileDir . '/' . $fileName, 'log', array('mode' => 0666));
        }

        $logger->log(self::buildLogMessage($message));
    }

    public static function logif($level = PEAR_LOG_DEBUG)
    {
        static $mask = NULL;
        if ($mask === NULL) $mask = Log::UPTO(WF_LOG_LEVEL);
        return ((1 << $level) & $mask) ? true : false;
    }

    public function deprecated($message)
    {
        // intersects with E_DEPRECATED? http://php.net/manual/en/errorfunc.constants.php
        WFLog::log($message, 'deprecated', PEAR_LOG_NOTICE);
    }

    private static function buildLogMessage($msg)
    {
        if (is_string($msg)) return $msg;

        if ($msg instanceof WFFunction)
        {
            return $msg->call();
        }

        return "Unknown message type";
    }

}
