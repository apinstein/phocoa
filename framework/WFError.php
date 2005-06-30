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
 * A generic error class.
 */
class WFError extends WFObject
{
    protected $errorMessage;
    protected $errorCode;

    function __construct($errorMessage = NULL, $errorCode = 0)
    {
        parent::__construct();
        $this->errorCode = $errorCode;
        $this->errorMessage = $errorMessage;
    }

    function setErrorMessage($msg)
    {
        $this->errorMessage = $msg;
    }
    function errorMessage()
    {
        return $this->errorMessage;
    }

    function setErrorCode($code)
    {
        $this->errorCode = $code;
    }
    function errorCode()
    {
        return $this->errorCode;
    }
    function __toString()
    {
        return "Error #{$this->errorCode}: {$this->errorMessage}";
    }
}
?>
