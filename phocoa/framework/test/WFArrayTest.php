<?php

require_once(getenv('PHOCOA_PROJECT_CONF'));
error_reporting(E_ALL);

/*
 * @package test
 */
class WFArrayTest extends PHPUnit_Framework_TestCase
{
    public $array;

    public function setUp()
    {
        $this->array = new WFArray();
        $this->array['one'] = 1;
        $this->array['subWFArray'] = new WFArray( array('two' => 2) );
        $this->array['subArray'] = array('two' => 2);
        $this->array['complexArray'] = array(
            'fiveNumbers' => array(1,2,3,4,5)
        );
    }

    public function testSupportsValueForKey()
    {
        $this->assertEquals(1, $this->array->valueForKey('one'));
    }
    public function testValueForKeyThrowsWFUndefinedKeyExceptionIfKeyDoesNotExist()
    {
        $this->setExpectedException('WFUndefinedKeyException');
        $this->array->valueForKey('dne');
    }
    public function testSupportsValueForKeyPath()
    {
        $this->assertEquals(1, $this->array->valueForKeyPath('one'));
        $this->assertEquals(2, $this->array->valueForKeyPath('subWFArray.two'));
        $this->assertEquals(2, $this->array->valueForKeyPath('subArray.two'));
    }
    public function testValueForKeyPathThrowsWFUndefinedKeyExceptionIfKeyDoesNotExist()
    {
        $this->setExpectedException('WFUndefinedKeyException');
        $this->array->valueForKeyPath('one.dne');
    }
    public function testSupportsValueForKeyPathMagic()
    {
        $this->assertEquals(1, $this->array->valueForKeyPath('complexArray.fiveNumbers.values.@first'));
        $this->assertEquals(15, $this->array->valueForKeyPath('complexArray.fiveNumbers.values.@sum'));
    }
}
