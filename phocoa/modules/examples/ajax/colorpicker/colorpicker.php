<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

class module_colorpicker extends WFModule
{
    function sharedInstancesDidLoad()
    {
    }

    function defaultPage()
    {
        return 'example';
    }
}

class module_colorpicker_example
{

    function setupSkin($page, $params, $skin)
    {
        $skin->setTitle('YUI ColorPicker Example');
        $skin->addMetaKeywords(array('yui colorpicker', 'yui colorpicker example', 'yui color picker', 'javascript colorpicker', 'javascript color picker'));
    }
}
?>
