<?php

class PhocoaControllerTestCase_PHPUnit extends PHPUnit_Framework_TestCase
{
    protected $invocation;
    protected $module;

    /**
     * A Helper function that will execute an invocation path and store the resulting module locally for execution of asserts.
     *
     * NOTE: Use the $moduleVars to "stuff" a DB connection so your controller runs in the same DB tx as the test.
     *
     * @param string Invocation Path
     * @param array An array of data to "stuff" into the WFModule instance.
     */
    public function executeInvocationPath($invocationPath, $moduleVars = array())
    {
        $this->invocation = new WFModuleInvocation($invocationPath, NULL, NULL);
        $this->module = $this->invocation->module();
        $this->module->setValuesForKeys($moduleVars);
        $result = $this->invocation->execute();
    }

    /**
     * NOTE: Makes calling any PHPUnit assertXXX magically pass thru our controller pre-processor with the following suffixes:
     * - TemplateVar        => Looks up the variable in the page template. Pass a templateVar name as the "expected" param
     * - SharedInstance     => Looks up the SharedInstance.keyPath. Pass 2 args (SharedInstance, KeyPath) in place of the "expected" param
     *
     * @method PhocoaControllerTestCase_PHPUnit::assertEqualsFromTemplateVar($expectedVal, $actualFromTemplateVarName, $msg)
     * @method PhocoaControllerTestCase_PHPUnit::assertEqualsFromSharedInstance($expectedVal, $actualSharedInstanceName, $actualSharedInstanceKeyPath, $msg)
     */
    public function __call($method, $args)
    {
        if (preg_match('/^(assert.+)From(TemplateVar|SharedInstance)$/', $method, $matches))
        {
            $assertComparator = $matches[1];
            $varGetterType = $matches[2];
            switch ($varGetterType) {
                case 'TemplateVar':
                    $actualFromTemplateVarName = $args[1];
                    $actual = $this->_getTemplateVar($actualFromTemplateVarName);
                    return call_user_func_array(array($this, $assertComparator), $this->_buildPHPUnitAssertArgs($assertComparator, $args, $actual));
                case 'SharedInstance':
                    $sharedInstanceName = $args[1];
                    $sharedInstanceKeyPath = (isset($args[2]) ? $args[2] : NULL);
                    $actual = $this->_getSharedInstanceValue($sharedInstanceName, $sharedInstanceKeyPath);
                    return call_user_func_array(array($this, $assertComparator), $this->_buildPHPUnitAssertArgs($assertComparator, $args, $actual, 2));
            }
        }

        // seems a little odd, dies HERE if parent doesn't define __call, but doesn't matter b/c it dies when an unexpected method is encountered
        return parent::__call($method, $args);
    }

    protected function _buildPHPUnitAssertArgs($assert, $origArgs, $actual, $numExpectArguments = 1)
    {
        $isBinaryAssert = true;
        if (in_array($assert, array('assertTrue', 'assertFalse', 'assertNull', 'assertNotNull')))
        {
            $isBinaryAssert = true;
        }
        $numRequiredArgs = ($isBinaryAssert ? 1 : 0) + $numExpectArguments;

        $assertArgs = $isBinaryAssert ? array($origArgs[0], $actual) : array($actual);
        if (count($origArgs) > $numRequiredArgs)
        {
            $assertArgs = array_merge($assertArgs, array_slice($origArgs, $numRequiredArgs+1));
        }

        return $assertArgs;
    }

    protected function _getTemplateVar($templateVarName)
    {
        $page = $this->module->responsePage();
        if (!$page)
        {
            $this->fail("No response page...");
        }
        return $page->template()->get($templateVarName);
    }

    protected function _getSharedInstanceValue($sharedInstanceName, $keyPath)
    {
        $sharedInstance = $this->module->outlet($sharedInstanceName);
        if (!$sharedInstance)
        {
            $this->fail("Shared Instance {$sharedInstanceName} does not exist.");
        }
        if ($keyPath === NULL)
        {
            return $sharedInstance;
        }
        else
        {
            return $sharedInstance->valueForKeyPath($keyPath);
        }
    }
}
