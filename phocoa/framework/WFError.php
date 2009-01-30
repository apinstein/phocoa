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

    function __construct($errorMessage = NULL, $errorCode = NULL)
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

/**
 * A special WFException subclass meant for carrying multiple WFError objects.
 *
 * WFPage automatically catches WFErrorsException's thrown from action methods and displays the errors.
 *
 * WFErrorsException knows how to handle the multi-level error structure used by {@link WFKeyValueCoding::validateObject()}.
 */
class WFErrorsException extends WFException
{
    protected $errors;

    function __construct($errors)
    {
        if (!is_array($errors)) throw( new WFException("WFErrorsException requires an array of WFError objects.") );
        if (count($errors) === 0) throw( new WFException("WFErrorsException must contain errors!") );

        $this->errors = $errors;

        $message = join(',', WFArray::arrayWithArray($this->allErrors())->valueForKeyPath('errorMessage'));
        parent::__construct($message);
    }

    /**
     * Get all errors in the format prescribed by {@link WFKeyValueCoding::validateObject()}
     *
     * @return array
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * Get the errors that are not mapped to specific properties.
     *
     * @return array An array of WFError objects.
     */
    public function generalErrors()
    {
        $general = array();
        foreach ($this->errors as $k => $v) {
            if (gettype($k) == 'integer')
            {
                $general[] = $v;
            }
        }
        return $general;
    }

    /**
     * Get all errors in the current exception.
     *
     * @return array An array of all WFError objects.
     */
    public function allErrors()
    {
        $flattenedErrors = array();
        foreach ($this->errors as $k => $v) {
            if (gettype($k) == 'integer')
            {
                $flattenedErrors[] = $v;
            }
            else
            {
                $flattenedErrors = array_merge($flattenedErrors, $v);
            }
        }
        return $flattenedErrors;
    }

    /**
     * Get all errors for the given key.
     *
     * @return array An array of all WFError objects.
     */
    public function errorsForKey($key)
    {
        if (isset($this->errors[$key]))
        {
            return $this->errors[$key];
        }
        return array();
    }

    /**
     * Inform a widget of all errors for the given key.
     *
     * Optionally [and by default], prune the errors that have been propagated from the current list. Since the caller will typically re-throw this exception to be caught by the WFPage,
     * the auto-pruning prevents errors from appearing twice, as the WFPage will automatically detect and report all errors as well (although not linked to widgets).
     *
     * @param string The key which generated the errors
     * @param object WFWidget The widget that the errors should be reported to.
     * @param bolean Prune errors for this key from the exception object.
     */
    public function propagateErrorsForKeyToWidget($key, $widget, $prune = true)
    {
         foreach ($this->errorsForKey($key) as $keyErr) {
             $widget->addError($keyErr);
         }
         if ($prune && isset($this->errors[$key]))
         {
             unset($this->errors[$key]);
         }
    }
}
