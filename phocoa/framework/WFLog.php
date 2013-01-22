<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package framework-base
 * @subpackage Log
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

# legacy compatibilty
define('PEAR_LOG_EMERG',    Logger::EMERGENCY);
define('PEAR_LOG_ALERT',    Logger::ALERT);
define('PEAR_LOG_CRIT',     Logger::CRITICAL);
define('PEAR_LOG_ERR',      Logger::ERROR);
define('PEAR_LOG_WARNING',  Logger::WARNING);
define('PEAR_LOG_NOTICE',   Logger::NOTICE);
define('PEAR_LOG_INFO',     Logger::INFO);
define('PEAR_LOG_DEBUG',    Logger::DEBUG);

/**
 * The WFLog class provides some static helper methods for logging things.
 * Uses {@link https://github.com/Seldaek/monolog monolog}
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
      * @param level The Monolog log level (Logger::EMERGENCY, Logger::ALERT, Logger::CRITICAL, Logger::ERROR, Logger::WARNING, Logger::NOTICE, Logger::INFO, and Logger::DEBUG)
      *
      * The message will be logged to the wf.log file in the web application's log directory, if the log level is less than or equal to the WF_LOG_LEVEL.
      */
    public static function log($message, $ident = 'general', $level = Logger::DEBUG)
    {
        if (!WFLog::logif($level)) return;   // bail as early as possible if we aren't gonna log this line
        $logFileDir = WFWebApplication::sharedWebApplication()->appDirPath(WFWebApplication::DIR_LOG);
        $logger = new Logger('name');
        $logger->pushHandler(new StreamHandler($logFileDir . '/wf.log', WF_LOG_LEVEL));
        $logger->addRecord($level, self::buildLogMessage($message));
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
        $logFileDir = WFWebApplication::sharedWebApplication()->appDirPath(WFWebApplication::DIR_LOG);
        $logger = new Logger('name');
        $logger->pushHandler(new StreamHandler($logFileDir . '/' . $fileName, WF_LOG_LEVEL));
        $logger->addInfo(self::buildLogMessage($message));
    }

    public static function logif($level = Logger::DEBUG)
    {
        static $mask = NULL;
        if ($mask === NULL) $mask = -1; // @todo need monolog version of this: Log::UPTO(WF_LOG_LEVEL);
        return ((1 << $level) & $mask) ? true : false;
    }

    public function deprecated($message)
    {
        // intersects with E_DEPRECATED? http://php.net/manual/en/errorfunc.constants.php
        WFLog::log($message, 'deprecated', Logger::NOTICE);
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
