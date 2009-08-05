<?php

require_once(getenv('PHOCOA_PROJECT_CONF'));
error_reporting(E_ALL);

class WFErrorsExceptionTest extends PHPUnit_Framework_TestCase
{
    function testErrorsForKey()
    {
        $errors = array('blah' => array(new WFError("1", 1)));
        $ex = new WFErrorsException($errors);
        $this->assertEquals($errors['blah'], $ex->errorsForKey('blah'));
    }
}
