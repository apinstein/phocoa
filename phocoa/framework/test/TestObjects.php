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

    function childrenAsWFArray()
    {
        return new WFArray($this->children());
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

// Has properties firstName, lastName, uid, child. Has method get/set FirstName, no accessors for others. Has KVV method validateFirstName.
class Person extends WFObject
{
    public $firstName;
    public $lastName;
    public $uid;
    public $child;
    public $alwaysNull = NULL;

    public static $gid = 0;

    function __construct($first = NULL, $last = NULL, $id = NULL)
    {
        $this->firstName = $first;
        $this->lastName = $last;
        $this->uid = $id;
        $this->child = NULL;

        if ($this->uid === NULL) $this->uid = self::$gid++;
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

    function getFirstName()
    {
        return $this->firstName;
    }
    function setFirstName($first)
    {
        $this->firstName = $first;
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

class StaticPerson extends WFObject
{
    // KVC only works with public methods
    public static $staticVar = array(1,2,3);
    public static $anotherStaticVar = array(1,2,3);
    
    public static $staticVarWithNoStaticAccessor = array(1,2,3);

    protected static $kvcInaccessible = array(1,2,3);

    // method with same name as class var
    public static function staticVar()
    {
        return self::$staticVar;
    }
    // method with different name as class var (get*)
    public static function getAnotherStaticVar()
    {
        return self::$anotherStaticVar;
    }
    public static function people()
    {
        return array(
                new Person('John', 'Doe'),
                new Person('Jane', 'Doe')
            );
    }
}
