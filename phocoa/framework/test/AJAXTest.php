<?php

require_once 'PHPUnit/Extensions/SeleniumTestCase.php';
 
// @todo Test on multiple browsers. ATM only firefox2 works. Safari doesn't click when told, and no idea how to test IE from a mac...
class AJAXTest extends PHPUnit_Extensions_SeleniumTestCase
{
    public static $browsers = array(
      array(
        'name'    => 'Internet Explorer 7 on Windows XP',
        'browser' => '*iexplore',
        'host'    => '10.0.1.204',
        'port'    => 4444,
        'timeout' => 30000,
      ),
      array(
        'name'    => 'Firefox on Mac',
        'browser' => '*firefox',
        'host'    => 'localhost',
        'port'    => 4444,
        'timeout' => 30000,
      ),
      array(
        'name'    => 'Safari on MacOS X',
        'browser' => '*safari',
        'host'    => 'localhost',
        'port'    => 4444,
        'timeout' => 30000,
      ),
    );

    protected function setUp()
    {
        //$this->setBrowser('*firefox');
        $this->setBrowserUrl('http://jwatts.dev.tourbuzz.net:8080');   // needed to load the first page; required for SC setup.
    }
 
    public function testLocalJavascriptAction()
    {
        $this->open('http://jwatts.dev.tourbuzz.net:8080/examples/ajax/general/general');
        $this->click('id=localAction');
        $this->assertTrue($this->isAlertPresent());
    }

    public function testServerJavascriptAction()
    {
        $this->open('http://jwatts.dev.tourbuzz.net:8080/examples/ajax/general/general');
        $this->click('id=rpcPageDelegateServer');
        $this->waitForPageToLoad();
        $this->assertTextPresent('ajaxTarget', 'I am the server and this is my random number');
    }

    public function testAJAXJavascriptAction()
    {
        $this->open('http://jwatts.dev.tourbuzz.net:8080/examples/ajax/general/general');
        $this->click('id=rpcPageDelegate');
        sleep(1);
        $this->assertTextPresent('ajaxTarget', 'I am the server and this is my random number');
    }

    public function testAjaxFormNormalMode()
    {
        $this->open('http://jwatts.dev.tourbuzz.net:8080/examples/ajax/general/general');
        $this->assertTextNotPresent('Text cannot be blank');
        $this->assertTextNotPresent('Other text cannot be blank');
        $this->assertElementPresent('id=ajaxFormSubmitAjax', "Couldn't find submit button");
        $this->click('id=ajaxFormSubmitNormal');
        $this->waitForPageToLoad();
        $this->assertTextPresent('Text cannot be blank');
        $this->assertTextPresent('Other text cannot be blank');
    }
    public function testAjaxFormAjaxMode()
    {
        $this->open('http://jwatts.dev.tourbuzz.net:8080/examples/ajax/general/general');
        $this->click('id=ajaxFormSubmitAjax');
        sleep(2); // wait for ajax to finish
        $this->assertTextPresent('Text cannot be blank');
        $this->assertTextPresent('Other text cannot be blank');
    }
}
