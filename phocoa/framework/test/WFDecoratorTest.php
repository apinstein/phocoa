<?php

/*
 * @package test
 */
 

error_reporting(E_ALL);
require_once('framework/WFWebApplication.php');
require_once('framework/WFObject.php');
require_once('framework/WFError.php');

require_once "TestObjects.php";

class MyTestObjectDecorator extends WFDecorator
{
    public function getFullName()
    {
        return $this->decoratedObject->valueForKey('firstName') . ' ' . $this->decoratedObject->valueForKey('lastName');
    }
}
class MyOtherTestObjectDecorator extends WFDecorator
{
    public function getFullName()
    {
        return $this->decoratedObject->valueForKey('lastName') . ', ' . $this->decoratedObject->valueForKey('firstName');
    }
}

class WFDecoratorTest extends PHPUnit_Framework_TestCase
{
    protected $baseObject = NULL;
    protected $decorator = NULL;
    protected $objectController1Decorator = NULL;
    protected $objectController2Decorators = NULL;
    protected $arrayController1Decorator = NULL;

    function setUp()
    {
        $this->baseObject = new Person('First', 'Last', 1);
        $this->decorator = new MyTestObjectDecorator($this->baseObject);

        $this->objectController1Decorator = new WFObjectController();
        $this->objectController1Decorator->setDecorator('MyTestObjectDecorator');
        $this->objectController1Decorator->setClass('Person');
        $this->objectController1Decorator->setContent($this->baseObject);

        $this->objectController2Decorators = new WFObjectController();
        $this->objectController2Decorators->setDecorators('MyTestObjectDecorator, MyOtherTestObjectDecorator');
        $this->objectController2Decorators->setClass('Person');
        $this->objectController2Decorators->setContent($this->baseObject);

        $this->arrayController1Decorator = new WFArrayController();
        $this->arrayController1Decorator->setDecorator('MyTestObjectDecorator');
        $this->arrayController1Decorator->setClass('Person');
        $this->arrayController1Decorator->setClassIdentifiers('uid');
        $this->arrayController1Decorator->setContent(array($this->baseObject));

    }

    function testDecoratedFullName()
    {
        self::assertEquals($this->decorator->valueForKey('fullName'), 'First Last');
    }

    function testUnderlyingGetterMethod()
    {
        self::assertEquals($this->decorator->getFirstName(), 'First');
    }

    function testUnderlyingGetterKey()
    {
        self::assertEquals($this->decorator->valueForKey('firstName'), 'First');
        self::assertEquals($this->decorator->valueForKey('lastName'), 'Last');
    }

    function testCanUseValueForKeyPath()
    {
        self::assertEquals($this->decorator->valueForKeyPath('fullName'), 'First Last');
    }

    function testObjectControllerWithDecorator()
    {
        self::assertEquals($this->objectController1Decorator->valueForKeyPath('content.fullName'), 'First Last');
    }
    function testObjectControllerWithDecorators()
    {
        self::assertEquals($this->objectController2Decorators->valueForKeyPath('content.fullName'), 'Last, First');
    }

    function testArrayControllerWithDecorator()
    {
        self::assertEquals($this->arrayController1Decorator->valueForKeyPath('selection.fullName'), 'First Last');
    }
}

?>
