<?php

/*
 * @package test
 */
 

error_reporting(E_ALL);
//require_once('/Users/alanpinstein/dev/sandbox/phocoadev/phocoadev/conf/webapp.conf');
require_once getenv('PHOCOA_PROJECT_CONF');

class WFYamlTest extends PHPUnit_Framework_TestCase
{
    protected $fixtureShouldBe = array(
        'anArray'   => array('arrayVal1', 'arrayVal2'),
        'aHash'     => array('key1' => 'value1', 'key2' => 'value2')
    );

    function testLoadFile()
    {
        $this->assertEquals($this->fixtureShouldBe, WFYaml::loadFile('./WFYamlTest.yaml'));
    }
    function testLoadString()
    {
        $this->assertEquals($this->fixtureShouldBe, WFYaml::loadString(file_get_contents('./WFYamlTest.yaml')));
    }
}
