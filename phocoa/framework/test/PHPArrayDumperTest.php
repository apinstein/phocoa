<?php

/*
 * @package test
 */
 

error_reporting(E_ALL);
require_once "framework/WFWebApplication.php";
require_once "framework/util/PHPArrayDumper.php";

class PHPArrayDumperTest extends PHPUnit_Framework_TestCase
{
    // returns boolean;
    function rtTest($array)
    {
        $arraySource = PHPArrayDumper::arrayToPHPVariableSource($array, 'roundTripArray');
        if (eval($arraySource) === false)
        {
            print "Error parsing source:\n$arraySource\n---------\n";
        }
        self::assertTrue($array === $roundTripArray);
        return ($array === $roundTripArray);
    }

    function testString()
    {
        $myArray = array('String1', "String2");
        $this->rtTest($myArray);
    }
    function testNULL()
    {
        $myArray = array(NULL, NULL, NULL);
        $this->rtTest($myArray);
    }
    function testFloat()
    {
        $myArray = array(1.3845, 5.39475, 0.3498579, 200.39785);
        $this->rtTest($myArray);
    }
    function testInt()
    {
        $myArray = array(1, 5, 9, 200);
        $this->rtTest($myArray);
    }
    function testBoolean()
    {
        $myArray = array(true, false, true, false);
        $this->rtTest($myArray);
    }

    function testCraziness()
    {
        $myArray = array(
                        5,
                        "string",
                        true,
                        array(1,2),
                        "key" => array("string", 5, "tt" => NULL),
                        1.5
                        );
        $this->rtTest($myArray);
    }
    function testArray()
    {
        $myArray = array(array(1,2));
        $this->rtTest($myArray);
    }
}

?>
