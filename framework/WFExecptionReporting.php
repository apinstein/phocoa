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
 * Includes
 */
require_once('WFObject.php');

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
      * Mail the passed exception to the framework's log folder.
      * @todo Not yet implemented! Where to get email address from? probably WFWebApplicationDelegate, overridable by param
      *       Also need to write WFSmartyMail class?
      */
    function mail(Exception $e, $email = NULL)
    {
    }
}
