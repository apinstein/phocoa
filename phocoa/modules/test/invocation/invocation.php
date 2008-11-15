<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * This class is a test rig for testing WFModule. See framework/test/Invocation.php
 */

class module_invocation extends WFModule
{
    function defaultPage()
    {
        return 'display';
    }
}

class module_invocation_display
{
    public function parameterList()
    {
        return array('parameter1');
    }
    public function parametersDidLoad($page, $params)
    {
        $page->assign('parameter1',  $params['parameter1']);
    }
}
class module_invocation_anotherPage
{
    public function parameterList()
    {
        return array('parameter1');
    }
    public function parametersDidLoad($page, $params)
    {
        $page->assign('parameter1',  $params['parameter1']);
    }
}
