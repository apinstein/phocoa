<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

class module_container extends WFModule
{
    function sharedInstancesDidLoad()
    {
    }

    function defaultPage()
    {
        return 'container';
    }

    function container_PageDidLoad($page, $params)
    {
        $m = new WFYAHOO_widget_Module('module', $page);
        $m->setBody('This is a module. By default modules have no style; they are just blocks of content. <a href="#" onClick="PHOCOA.runtime.getObject(\'module\').hide();">Hide Module</a>');

        $m = new WFYAHOO_widget_Overlay('overlay', $page);
        $m->setBody('This is an overlay. By default overlays have no style; they are just absolutely positioned blocks of content. <a href="#" onClick="PHOCOA.runtime.getObject(\'overlay\').hide();">Hide Overlay</a>');

        $m = new WFYAHOO_widget_Panel('panel', $page);
        $m->setHeader('Panel');
        $m->setBody('This is a panel. By default, panels pop up over the existing content and have a "dialog-like" UI.');
        $m->setFixedCenter(true);
    }
}
?>
