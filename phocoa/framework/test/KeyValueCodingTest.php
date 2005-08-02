<?php

/*
 * @package test
 */
 

error_reporting(E_ALL);
require_once('../../conf/webapp.conf');
require_once('framework/WFWebApplication.php');
require_once('framework/WFObject.php');
require_once('framework/WFError.php');

require_once "TestObjects.php";

require_once "PHPUnit2/Framework/TestCase.php";

class KeyValueCodingTest extends PHPUnit2_Framework_TestCase
{
    protected $parent;
    protected $child;

    function setUp()
    {
        $this->parent = new Person('John', 'Doe', 1);
        $this->child = new Person('John', 'Doe, Jr.', 2);
    }

    function testSetValueForKey()
    {
        $this->parent->setValueForKey('My Last Name', 'lastName');
        self::assertTrue($this->parent->lastName == 'My Last Name');

        $this->parent->setValueForKey($this->child, 'child');
        self::assertTrue($this->parent->child->uid == 2);
    }

    function testKeyValueValidationGeneratesErrorForBadValue()
    {
        $badvalue = 'badfirstname';
        $edited = false;
        $errors = array();
        $valid = $this->parent->validateValueForKey($badvalue, 'firstName', $edited, $errors);
        self::assertTrue($valid === false and count($errors) == 1);
    }
}

?>
