<?php

// @todo Refactor all Propel-related classes into this file.

// A wrapper for PropelException that acts as WFErrorCollection as well so that the phocoa controllers can catch propel errors directly.
class WFPropelException extends PropelException implements WFErrorCollection
{
    protected $errors;

    public function __construct($p1, $p2 = NULL)
    {
        parent::__construct($p1, $p2);
        $this->errors = new WFErrorArray;
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

    /**
     * Add an error that is not associated with a key.
     *
     * @param object WFError A WFError object to add.
     * @return WFErrorArray This error collection object (for fluid interface).
     */
    public function addGeneralError($error)
    {
        $this->errors->addGeneralError($error);
        return $this;
    }

    public function allErrors()
    {
        return $this->errors->allErrors();
    }

    public function errorsForKey($key)
    {
        return $this->errors->errorsForKey($key);
    }

    /**
     * Add an error for a particular key
     *
     * Note for implementers: Make sure to check whether
     * the key exists in the error collection.
     *
     * @param object WFError A WFError object to add.
     * @param string The key to add the error to.
     * @return WFErrorArray This error collection object (for fluid interface).
     */
    public function addErrorForKey($error, $key)
    {
        $this->errors->addErrorForKey($error, $key);
        return $this;
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

    public function setErrors($errors)
    {
        // Make sure we have the right type of object
        if (!$errors instanceof WFErrorCollection && !is_array($errors))
        {
            throw new Exception("Invalid error collection passed to WFPropelException");
        }

        // Convert array of errors into a WFErrorCollection
        if (is_array($errors))
        {
            $errors = new WFErrorArray($errors);
            //TODO: Do we need to loop through the errors
            //to make sure they're in the right format?
        }

        $this->errors = $errors;
        return $this;
    }

    /**
     * Convenience method to allow for a fluent interface.
     * e.g...
     *
     * WFConcreteErrorCollection::create()
     *      ->addGeneralError(...);
     */
    public static function create($p1, $p2 = NULL)
    {
        return new self($p1, $p2);
    }

    public static function createFromErrorCollection($errors, $p1, $p2 = NULL)
    {
        $e = new self($p1, $p2);
        $e->setErrors($errors);
        return $e;
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
