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
      * Log the passed exception to the framework's log folder.
      */
    function log(Exception $e)
    {
        $logfile = WFWebApplication::appDirPath(WFWebApplication::DIR_LOG) . '/framework_exceptions.log';
        $smarty = new WFSmarty();
        $smarty->assign('exception', $e);
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
     *                    'title'   => '',
     *                    'message' => '',
     *                    'code'    => '',
     *                    'trace'   => ''
     *              }, ...
     *         ]
     */
    public static function generatedStandardizedErrorDataFromException(Exception $e)
    {
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
                'title'   => get_class($e),
                'message' => $e->getMessage(),
                'code'    => $e->getCode(),
                'trace'   => array_merge(
                    array("At {$e->getFile()}:{$e->getLine()}"),
                    explode("\n", $e->getTraceAsString())
                )
            );
        }
        $lastErr = error_get_last();
        if ($lastErr)
        {
            extract($lastErr);
            $errorData[] = array(
                'title'   => "error_get_last(): most recent; may or may not be relevant.",
                'message' => $message,
                'code'    => $type,
                'trace'   => "{$file}:{$line}"
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
