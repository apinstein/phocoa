<?php

/*
 * @package test
 */

class Node extends WFObject
{
    public $name;
    public $value;
    public $children = array();

    function addChild(Node $child)
    {
        $this->children[] = $child;
    }

    function children()
    {
        return $this->children;
    }

    // basically for testing "distinctArrays"
    function childrenDuplicated()
    {
        return array_merge($this->children, $this->children);
    }

    // basically for testing distinctObjects
    function firstChild()
    {
        return $this->children[0];
    }

}

class ObjectHolder extends WFObject
{
    public $myObject;
}

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
