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
        $page->outlet('module')->addEffect('YAHOO.widget.ContainerEffect.FADE', 1); // has no effect :(
        $page->outlet('overlay')->addEffect('YAHOO.widget.ContainerEffect.FADE', 1);
        $page->outlet('panel')->addEffect('YAHOO.widget.ContainerEffect.SLIDE', 1);
        $page->outlet('panel')->addEffect('YAHOO.widget.ContainerEffect.FADE', 1);
    }
}
?>
