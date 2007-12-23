<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

class module_general extends WFModule
{
    function sharedInstancesDidLoad()
    {
    }

    function defaultPage()
    {
        return 'general';
    }
}

class module_general_general
{
    function parametersDidLoad($page, $params)
    {
        $page->outlet('localAction')->setListener( new WFClickEvent() );
        $page->outlet('eventClick')->setListener( new WFClickEvent() );
        $page->outlet('eventMouseover')->setListener( new WFMouseoverEvent() );
        $page->outlet('eventMouseout')->setListener( new WFMouseoutEvent() );
        $page->outlet('eventMousedown')->setListener( new WFMousedownEvent() );
        $page->outlet('eventMouseup')->setListener( new WFMouseupEvent() );
        $page->outlet('eventChange')->setListener( new WFChangeEvent() );
        $page->outlet('eventBlur')->setListener( new WFBlurEvent() );
        $page->outlet('eventFocus')->setListener( new WFFocusEvent() );

        $page->outlet('rpcPageDelegate')->setListener( new WFClickEvent( WFAction::ServerAction() ) );

        $page->outlet('ajaxFormSubmitAjax')->setListener( new WFClickEvent( WFAction::AjaxAction()
                                                                                ->setForm('ajaxForm')
                                                                                ->setAction('ajaxFormSubmitNormal') 
                                                                          )
                                                        );
    }

    function rpcPageDelegateHandleClick($page, $params, $senderId, $eventName)
    {
        return WFActionResponsePhocoaUIUpdater::WFActionResponsePhocoaUIUpdater()
            ->addUpdateHTML('ajaxTarget', 'I am the server and this is my random number: ' . rand());
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
