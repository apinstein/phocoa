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

        // something doesn't work with dialog.. not sure what yet
        $page->outlet('dialog')->addButton('YUI Submit Button', 'clickMeHandler', false);
        $page->outlet('dialog')->setCallbackSuccess('dialogSuccessHandler');
        $page->outlet('dialog')->setCallbackFailure('dialogFailureHandler');

        new WFYAHOO_widget_Logger('logger', $page);
    }
    function dialogFormSubmit($page)
    {
        if (strtolower($page->outlet('pickACity')->value()) == "atlanta") echo("You're right!");
        echo("you're wrong!");
        exit;
    }
    function setupSkin($page, $params, $skin)
    {
        $skin->setMetaDescription('YUI Container Examples: Module, Overlay, Panel, and PHOCOA Dialog.');
    }
}
?>
