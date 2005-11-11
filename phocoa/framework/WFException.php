<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package framework-base
 * @subpackage Exception
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/**
 * The default exception type.
 */
define('WFGenericException', 'WFGenericException');

/** 
 * A helper class to easily throw named exceptions.
 *
 * @todo Eventually have this throw a WFException and have all PHOCOA stuff throw WFException's?
 */
class WFException extends Exception
{
    /**
     *  Raise an exception of the passed type with the passed message.
     *
     *  @param string The name / code of the exception. Default is WFGenericException.
     *  @throws WFException Throws a WFException of the passed type. Default is NULL.
     */
    static function raise($name = WFGenericException, $message = NULL)
    {
        throw( new Exception($message, $name) );
    }
}
?>
