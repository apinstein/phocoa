<?php

/*
 * @package test
 */

class Person extends WFObject
{
    public $firstName;
    public $lastName;
    public $uid;
    public $child;

    function __construct($first, $last, $id)
    {
        $this->first = $first;
        $this->last = $last;
        $this->uid = $id;
        $this->child = NULL;
    }

    function validateFirstName(&$value, &$edited, &$errors)
    {
        if ($value == 'badfirstname')
        {
            $errors[] = new WFError('Name cannot be "badfirstname".');
            return false;
        }
        return true;
    }
}

// test class useful for when you need a two-key unique ID (issuingState and idNumber).
class PersonID extends WFObject
{
    public $issuingState;
    public $idNumber;

    function __construct($issuingState = NULL, $idNumber = NULL)
    {
        $this->issuingState = $issuingState;
        $this->idNumber = $idNumber;
    }
}

?>
