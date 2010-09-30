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

class module_invocation_default_parameter_values
{
    public function parameterList()
    {
        return array(
            'parameter1',
            'parameter2'    => 'parameter2DefaultValue',
            'parameter3'    => array('defaultValue' => 'parameter3DefaultValue')
        );
    }
    public function parametersDidLoad($page, $params)
    {
        $page->assign('parameters', var_export($params, true));
        $page->setTemplateFile('export_parameters.tpl');
    }
    public function setupSkin($page, $params, $skin)
    {
        $skin->setTemplateType(WFSkin::SKIN_WRAPPER_TYPE_RAW);
    }
}

class module_invocation_greedy
{
    public function parameterList()
    {
        return array(
            'parameter1' => array('greedy' => true, 'defaultValue' => 'parameter1DefaultValue')
        );
    }
    public function parametersDidLoad($page, $params)
    {
        $page->assign('parameters', var_export($params, true));
        $page->setTemplateFile('export_parameters.tpl');
    }
    public function setupSkin($page, $params, $skin)
    {
        $skin->setTemplateType(WFSkin::SKIN_WRAPPER_TYPE_RAW);
    }
}
