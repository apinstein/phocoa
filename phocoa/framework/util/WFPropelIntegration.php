<?php

// @todo Refactor all Propel-related classes into this file.

class WFPropelException extends PropelException implements WFErrorCollection
{
    protected $errors;

    public function __construct(WFErrorArray $errors, $p1, $p2 = null)
    {
        parent::__construct($p1, $p1);
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
