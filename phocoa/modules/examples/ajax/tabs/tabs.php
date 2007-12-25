<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

class module_tabs extends WFModule
{
    function defaultPage()
    {
        return 'example';
    }

}

class module_tabs_example
{
    function setupSkin($page, $params, $skin)
    {
        $skin->setTitle('YUI TabView and Tab Widget Example - PHOCOA AJAX Integration');
        $skin->setMetaDescription('YUI TabView and Tab Widget Example - PHOCOA AJAX Integration');
        $skin->addMetaKeywords( array('yui tabs', 'yui tab view', 'yui tabview', 'yui tab', 'yui tab example') );
    }
}
?>
