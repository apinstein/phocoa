<?php

/*
 * @package test
 */
 

error_reporting(E_ALL);
require_once('/Users/alanpinstein/dev/sandbox/showcaseng/showcaseng/conf/webapp.conf');
require_once "framework/WFWebApplication.php";
require_once "framework/util/PHPArrayDumper.php";

require_once "PHPUnit/Framework/TestCase.php";

class WFErrorsExceptionTest extends PHPUnit_Framework_TestCase
{
    function testErrorsForKey()
    {
        $errors = array('blah' => array(new WFError("1", 1)));
        $ex = new WFErrorsException($errors);
        $this->assertEquals($errors['blah'], $ex->errorsForKey('blah'));
    }
}
