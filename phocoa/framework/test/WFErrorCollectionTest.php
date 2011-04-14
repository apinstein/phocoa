<?php

require_once(getenv('PHOCOA_PROJECT_CONF'));
error_reporting(E_ALL);

class WFErrorCollectionTest extends PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider errorCollectionClasses
     */
    public function testErrorCollectionCanBeCreatedFluently($class, $args)
    {
        // Construction
        $errorCollection = call_user_func_array(
            array($class, 'create'),
            $args
        );
        $this->assertTrue($errorCollection instanceof $class);
        $this->assertFalse($errorCollection->hasErrors());
        $this->assertEquals(0, count($errorCollection->allErrors()));

        // Add a general error
        $e1 = new WFError('e1');
        $errorCollection
            ->addGeneralError($e1)
            ;
        $this->assertTrue($errorCollection->hasErrors());
        $this->assertEquals(1, count($errorCollection->allErrors()));
        $retrievedErrors = $errorCollection->generalErrors();
        $this->assertEquals(1, count($retrievedErrors));
        $this->assertEquals($e1, $retrievedErrors[0]);

        // Add a second general error
        $e2 = new WFError('e2');
        $errorCollection
            ->addGeneralError($e2)
            ;
        $this->assertTrue($errorCollection->hasErrors());
        $this->assertEquals(2, count($errorCollection->allErrors()));
        $retrievedErrors = $errorCollection->generalErrors();
        $this->assertEquals(2, count($retrievedErrors));
        $this->assertEquals($e2, $retrievedErrors[1]);

        // Add error for key
        $e3 = new WFError('e3');
        $key = 'key1';
        $errorCollection
            ->addErrorForKey($e3, $key)
            ;
        $this->assertTrue($errorCollection->hasErrors());
        $this->assertEquals(3, count($errorCollection->allErrors()));
        $retrievedGeneralErrors = $errorCollection->generalErrors();
        $this->assertEquals(2, count($retrievedGeneralErrors));
        $retrievedErrors = $errorCollection->errorsForKey($key);
        $this->assertEquals($e3, $retrievedErrors[0]);

        // Add a second error for the same key
        $e4 = new WFError('e4');
        $errorCollection
            ->addErrorForKey($e4, $key)
            ;
        $this->assertTrue($errorCollection->hasErrors());
        $this->assertEquals(4, count($errorCollection->allErrors()));
        $retrievedGeneralErrors = $errorCollection->generalErrors();
        $this->assertEquals(2, count($retrievedGeneralErrors));
        $retrievedErrors = $errorCollection->errorsForKey($key);
        $this->assertEquals($e4, $retrievedErrors[1]);

        // Add a second error for the same key
        $e5 = new WFError('e5');
        $key2 = 'key2';
        $errorCollection
            ->addErrorForKey($e5, $key2)
            ;
        $this->assertTrue($errorCollection->hasErrors());
        $this->assertEquals(5, count($errorCollection->allErrors()));
        $retrievedGeneralErrors = $errorCollection->generalErrors();
        $this->assertEquals(2, count($retrievedGeneralErrors));
        $this->assertEquals(2, count($errorCollection->errorsForKey($key)));
        $retrievedErrors = $errorCollection->errorsForKey($key2);
        $this->assertEquals($e5, $retrievedErrors[0]);
    }

    public function errorCollectionClasses()
    {
        $exception  = new Exception("really bad exception");
        $errorArray = new WFErrorArray(new WFError("Foo!"));
        return array(
            array('WFErrorArray',       array()),
            array('WFPropelException',  array($exception)),
            array('WFErrorsException',  array()),
        );
    }

}
