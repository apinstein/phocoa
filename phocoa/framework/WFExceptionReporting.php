<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package framework-base
 * @subpackage Error
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>
 */

/**
 * The WFExceptionReporting class provides some static helper methods for dealing with exceptions.
 */
class WFExceptionReporting extends WFObject
{
    /**
     * Helper function to translate a PHP integer error code into the equivalent string.
     *
     * @param int PHP Error code (one of E_*)
     * @return string
     */
    public static function phpErrorAsString($e)
    {
        // didn't use E_ERROR etc since they aren't all defined on all versions. it is a hard-coded hack, yes, but it works and won't change.
        $el = array(
            1     => 'E_ERROR',
            2     => 'E_WARNING',
            4     => 'E_PARSE',
            8     => 'E_NOTICE',
            16    => 'E_CORE_ERROR',
            32    => 'E_CORE_WARNING',
            64    => 'E_COMPILE_ERROR',
            128   => 'E_COMPILE_WARNING',
            256   => 'E_USER_ERROR',
            512   => 'E_USER_WARNING',
            1024  => 'E_USER_NOTICE',
            2048  => 'E_STRICT',
            4096  => 'E_RECOVERABLE_ERROR',
            8192  => 'E_DEPRECATED',
            16384 => 'E_USER_DEPRECATED',
        );
        return $el[$e];
    }

    /**
      * Log the passed exception to the framework's log folder.
      *
      * @param array Data from WFExceptionReporting::generatedStandardizedErrorDataFromException()
      */
    public static function log($standardErrorData)
    {
        $logfile = WFWebApplication::appDirPath(WFWebApplication::DIR_LOG) . '/framework_exceptions.log';

        $smarty = new WFSmarty();
        $smarty->assign('standardErrorData', $standardErrorData);
        $smarty->setTemplate(WFWebApplication::appDirPath(WFWebApplication::DIR_SMARTY) . '/app_error_log.tpl');
        $errText = $smarty->render(false);

        // append info to log
        $fs = fopen($logfile, 'a');
        fputs($fs, $errText);
        fclose($fs);
    }

    /**
     * Utility function to produce a standardized error reporting format.
     *
     * This function will walk up the entire Exception tree and also append the most recent error_get_last().
     *
     * @param object Exception
     * @return array A standardized array structure of all errors:
     *         [
     *              {
     *                    'title'         => '',
     *                    'message'       => '',
     *                    'code'          => '',
     *                    'traceAsString' => ''
     *                    'trace'         => array( ... )
     *              }, ...
     *         ]
     */
    public static function generatedStandardizedErrorDataFromException(Exception $e)
    {
        // grab error_get_last ASAP so that it cannot get adulterated by other things.
        $lastPhpError = error_get_last();

        // build stack of errors (php 5.3+)
        if (method_exists($e, 'getPrevious'))
        {
            $allExceptions = array();
            do {
                $allExceptions[] = $e;
            } while ($e = $e->getPrevious());
        }
        else
        {
            $allExceptions = array($e);
        }

        $errorData = array();
        foreach ($allExceptions as $e) {
            $errorData[] = array(
                'title'         => get_class($e),
                'message'       => $e->getMessage(),
                'code'          => $e->getCode(),
                'traceAsString' => "At {$e->getFile()}:{$e->getLine()}\n" . preg_replace('/\(([0-9]+)\):/', ':\1', $e->getTraceAsString()),
                'trace'         => array_merge(
                    array("At {$e->getFile()}:{$e->getLine()}"),
                    explode("\n", $e->getTraceAsString())
                )
            );
        }
        if ($lastPhpError)
        {
            $errorData[] = array(
                'title'         => "error_get_last() at time error occurred; may or may not be relevant.",
                'code'          => self::phpErrorAsString($lastPhpError['type']),
                'message'       => $lastPhpError['message'],
                'traceAsString' => "{$lastPhpError['file']}:{$lastPhpError['line']}",
                'trace'         => array("{$lastPhpError['file']}:{$lastPhpError['line']}")
            );
        }

        return $errorData;
    }

    /**
      * Mail the passed exception to the framework's log folder.
      * @todo Not yet implemented! Where to get email address from? probably WFWebApplicationDelegate, overridable by param
      *       Also need to write WFSmartyMail class?
      */
    function mail(Exception $e, $email = NULL)
    {
    }
}
