<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

class module_container extends WFModule
{
    function defaultPage()
    {
        return 'container';
    }
}

class module_container_container
{
    function parametersDidLoad($page, $params)
    {
        $page->outlet('module')->addEffect('YAHOO.widget.ContainerEffect.FADE', 1); // has no effect :(
        $page->outlet('overlay')->addEffect('YAHOO.widget.ContainerEffect.FADE', 1);
        $page->outlet('panel')->addEffect('YAHOO.widget.ContainerEffect.SLIDE', 1);
        $page->outlet('panel')->addEffect('YAHOO.widget.ContainerEffect.FADE', 1);

        new WFYAHOO_widget_Logger('logger', $page);
    }
    function setupSkin($page, $params, $skin)
    {
        $skin->setTitle('YUI Container Examples: Module, Overlay, Panel, and PHOCOA Dialog');
        $skin->setMetaDescription('YUI Container Examples: Module, Overlay, Panel, and PHOCOA Dialog.');
        $skin->addMetaKeywords( array('yui container', 'yui module', 'yui panel', 'yui overlay', 'yui dialog', 'yui panel example') );
    }
}
?>
