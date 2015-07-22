<?php

class PhocoaControllerTestCase_PHPUnit extends PHPUnit_Framework_TestCase
{
    // Args for WFModuleInvocation
    protected $invocationPath   = NULL;
    protected $invocationParentInvocation = NULL;
    protected $invocationSkinDelegate   = NULL;

    // form handling
    protected $formId           = NULL;
    protected $formData         = array();
    protected $formSubmitButton = array();

    // data to "inject" into module
    protected $moduleData       = array();

    // internal data
    protected $invocation;
    protected $module;
    protected $pageOutput;

    // exception asserting
    protected $expectException    = NULL;
    protected $expectExceptionUrl = NULL;

    public function setExpectRedirectException($url, $class = 'WFRedirectRequestException')
    {
        $this->expectException = $class;
        $this->expectExceptionUrl = WWW_ROOT . '/' . $url;

        return $this;
    }

    public function setInvocationPath($invocationPath)
    {
        $this->invocationPath = $invocationPath;

        return $this;
    }

    /**
     * Curry the arguments that will be passed to "new WFModuleInvocation"
     * @see WFModuleInvocation
     */
    public function setInvocationArgs($invocationPath, $parentInvocation = NULL, $skinDelegate = NULL)
    {
        $this->invocationPath = $invocationPath;
        $this->invocationParentInvocation = $parentInvocation;
        $this->invocationSkinDelegate = $skinDelegate;

        return $this;
    }

    public function setFormData($formId, $formData, $formSubmitButton = NULL)
    {
        $this->formId = $formId;
        $this->formData = $formData;
        $this->formSubmitButton = $formSubmitButton;

        return $this;
    }

    public function setRequestData($data)
    {
        $_REQUEST = $data;

        return $this;
    }

    public function setModuleData($moduleData)
    {
        $this->moduleData = $moduleData;

        return $this;
    }

    /**
     * A Helper function that will execute an invocation path and store the resulting module locally for execution of asserts.
     *
     * NOTE: Use the $moduleVars to "stuff" a DB connection so your controller runs in the same DB tx as the test.
     *
     * @param string Invocation Path
     * @param array An array of data to "stuff" into the WFModule instance.
     */
    public function executeInvocationPath($invocationPath = NULL, $moduleVars = array())
    {
        // set up
        $invocationPath = ($invocationPath ? $invocationPath : $this->invocationPath);
        $moduleData = array_merge($this->moduleData, $moduleVars);

        if ($this->formId && count($this->formData))
        {
            // render the "requestPage" so we can pull out
            // setup invocation
            $formInvocation = new WFModuleInvocation($invocationPath, $this->invocationParentInvocation, $this->invocationSkinDelegate);
            $formModule = $formInvocation->module();
            // inject data
            $formModule->setValuesForKeys($moduleData);
            $formInvocation->execute();

            // stuff appropriate data into our page under test
            $requestPage = $formModule->requestPage();
            $form = $requestPage->outlet($this->formId);
            $formSubmitButtonId = ($this->formSubmitButton ? $this->formSubmitButton : $form->defaultSubmitID());
            if (!$formSubmitButtonId) throw new Exception("No form submit id was specified, and the form doesn't have a defaultSubmitID configured.");
            $formSubmitButton = $requestPage->outlet($formSubmitButtonId);

            $submitAction = $formSubmitButton->submitAction();
            $rpc = $submitAction->rpc();
            $this->formData = array_merge($this->formData, $form->phocoaFormParameters());
            $_REQUEST = array_merge($_REQUEST, $this->formData);
        }

        // set up invocation to test
        $this->invocation = new WFModuleInvocation($invocationPath, $this->invocationParentInvocation, $this->invocationSkinDelegate);
        $this->module = $this->invocation->module();

        // inject data
        $this->module->setValuesForKeys($moduleData);
        // form -- automatic from $_REQUEST
        // request -- automatic from $_REQUEST

        // execute
        try {
            $this->pageOutput = $this->invocation->execute();
        } catch (Exception $e) {
            if ($this->expectException and $e instanceof $this->expectException)
            {
                $this->assertEquals($this->expectExceptionUrl, $e->getRedirectURL(), "Unexpected url for exception of type {$this->expectException}.");
            }
            else
            {
                throw $e;
            }
        }

        return $this;
    }

    /**
     * NOTE: Makes calling any PHPUnit assertXXX magically pass thru our controller pre-processor with the following suffixes:
     * - TemplateVar        => Looks up the variable in the page template. Pass a templateVar name as the "expected" param
     * - SharedInstance     => Looks up the SharedInstance.keyPath. Pass 2 args (SharedInstance, KeyPath) in place of the "expected" param
     *
     * @method PhocoaControllerTestCase_PHPUnit::assertEqualsFromTemplateVar($expectedVal, $actualFromTemplateVarName, $msg)
     * @method PhocoaControllerTestCase_PHPUnit::assertEqualsFromSharedInstance($expectedVal, $actualSharedInstanceName, $actualSharedInstanceKeyPath, $msg)
     * @method PhocoaControllerTestCase_PHPUnit::assertRegExpFromPageOutput($expectedVal, $regex, $msg)
     * @method PhocoaControllerTestCase_PHPUnit::assertEqualsFromResponseTemplate($expectedVal, $templateFileName, $msg)
     */
    public function __call($method, $args)
    {
        if (preg_match('/^(assert.+)From(TemplateVar|SharedInstance|PageOutput|ResponseTemplate)$/', $method, $matches))
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
                case 'PageOutput':
                    $actual = $this->pageOutput;
                    return call_user_func_array(array($this, $assertComparator), $this->_buildPHPUnitAssertArgs($assertComparator, $args, $actual));
                    break;
                case 'ResponseTemplate':
                    $actual = basename($this->module->responsePage()->template()->template());
                    return call_user_func_array(array($this, $assertComparator), $this->_buildPHPUnitAssertArgs($assertComparator, $args, $actual));
                    break;
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
            $isBinaryAssert = false;
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
