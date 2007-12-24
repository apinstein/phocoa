<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

class module_general extends WFModule
{
    function defaultPage()
    {
        return 'general';
    }

    function sayHi($page, $params, $senderId, $eventName)
    {
        return WFActionResponsePhocoaUIUpdater::WFActionResponsePhocoaUIUpdater()
            ->addRunScript('alert("Hi from a module function!");');
    }
}

class module_general_general
{
    function sayHi($page, $params, $senderId, $eventName)
    {
        return $this->eventClickHandleClick($page, $params, $senderId, $eventName);
    }
    function eventClickHandleClick($page, $params, $senderId, $eventName)
    {
        return WFActionResponsePhocoaUIUpdater::WFActionResponsePhocoaUIUpdater()
            ->addRunScript('alert("HI FROM SERVER!");');
    }

    function rpcPageDelegateServerHandleClick($page, $params, $senderId, $eventName)
    {
        if (WFRequestController::sharedRequestController()->isAjax())
        {
            return WFActionResponsePhocoaUIUpdater::WFActionResponsePhocoaUIUpdater()
                ->addUpdateHTML('ajaxTarget', 'I am the server and this is my random number: ' . rand());
        }
        else
        {
            $page->outlet('ajaxTarget')->setValue('I am the server and this is my random number: ' . rand());
        }
    }

    function ajaxFormSubmitNormal($page, $params, $senderId, $eventName)
    {
        $result = 'You said: "' . $page->outlet('textField')->value() . '" and "' . $page->outlet('textField2')->value() . '".';
        if (WFRequestController::sharedRequestController()->isAjax())
        {
            return WFActionResponsePhocoaUIUpdater::WFActionResponsePhocoaUIUpdater()
                ->addUpdateHTML('ajaxFormResult', $result);
        }
        else
        {
            $page->assign('formResult', $result);
        }
    }

}

class TestClass extends WFObject
{
    protected $value;
    protected $value2;

    public function validateValue2(&$value, &$edited, &$errors)
    {
        $value = trim($value);
        if (!$value)
        {
            $errors[] = new WFError("Other text cannot be blank.");
            return false;
        }
        if ($value == 'worse') 
        {
            $errors[] = new WFError("You typed 'worse'.");
            return false;
        }
        return true;
    }
    public function validateValue(&$value, &$edited, &$errors)
    {
        $value = trim($value);
        if (!$value)
        {
            $errors[] = new WFError("Text cannot be blank.");
            return false;
        }
        if ($value == 'bad') 
        {
            $errors[] = new WFError("You typed 'bad'.");
            return false;
        }
        return true;
    }
}
?>
