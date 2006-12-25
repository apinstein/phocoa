<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

class module_module extends WFModule
{
    function sharedInstancesDidLoad()
    {
    }

    function defaultPage()
    {
        return 'module';
    }

    function module_PageDidLoad($page, $params)
    {
        $m = new WFYAHOO_widget_Panel('test', $page);
        $m->setBody('<p>This is a test.</p>');
    }
}
?>
