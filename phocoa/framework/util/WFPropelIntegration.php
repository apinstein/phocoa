<?php

// @todo Refactor all Propel-related classes into this file.

// A wrapper for PropelException that acts as WFErrorCollection as well so that the phocoa controllers can catch propel errors directly.
class WFPropelException extends PropelException implements WFErrorCollection
{
    protected $errors;

    public function __construct(WFErrorArray $errors, $p1, $p2 = null)
    {
        parent::__construct($p1, $p2);
        $this->errors = $errors;
    }

    /***************** WFErrorCollection Interface Pass-Thru ********************/
    public function errors()
    {
        return $this->errors;
    }

    public function generalErrors()
    {
        return $this->errors->generalErrors();
    }

    public function allErrors()
    {
        return $this->errors->allErrors();
    }

    public function errorsForKey($key)
    {
        return $this->errors->errorsForKey($key);
    }

    public function hasErrors()
    {
        return $this->errors->hasErrors();
    }

    public function hasErrorsForKey($key)
    {
        return $this->errors->hasErrorsForKey($key);
    }

    public function hasErrorWithCode($code)
    {
        return $this->errors->hasErrorWithCode($code);
    }

    public function hasErrorWithCodeForKey($code, $key)
    {
        return $this->errors->hasErrorWithCodeForKey($code, $key);
    }
}

// A subclass of WFObject to add support for VirtualColumns and other dynamic elements of Propel for KVC.
class WFObject_Propel extends WFObject
{
    function valueForUndefinedKey($key)
    {
        if ($this->hasVirtualColumn($key))
        {
            return $this->getVirtualColumn($key);
        }
        // default implementation will throw
        parent::valueForUndefinedKey($key);
    }
}
