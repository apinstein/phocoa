<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * This class is a test rig for testing WFModule. See framework/test/Invocation.php
 */

class module_invocationAllPages extends WFModule
{
    function defaultPage()
    {
        return 'display';
    }

    function allPages() { return array('display', 'anotherPage'); }
}

class module_invocationAllPages_display
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
class module_invocationAllPages_anotherPage
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
