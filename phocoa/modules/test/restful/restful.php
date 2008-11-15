<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * This class is a test rig for testing WFModule. See framework/test/Invocation.php
 */

class module_restful extends WFModule
{
    function defaultPage()
    {
        return 'display';
    }

    function allPages()
    {
        return array('display');
    }

    function isRESTful() { return true; }
}

class module_restful_display
{
    public function parameterList()
    {
        return array('parameter1');
    }
    public function renderHTML($page, $params)
    {
        return 'HTML:' . $params['parameter1'] . ':';
    }
    public function renderXML($page, $params)
    {
        return '<?xml version="1.0"?><parameter>XML:' . $params['parameter1'] . ':</parameter>';
    }
}
