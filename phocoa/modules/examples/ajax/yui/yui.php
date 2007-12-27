<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

class module_yui extends WFModule
{
    function defaultPage()
    {
        return 'examples';
    }

}

class module_yui_examples
{
    function setupSkin($page, $params, $skin)
    {
        $skin->setTitle('YUI Examples - PHOCOA PHP Framework - YUI Integration');
        $skin->setMetaDescription('YUI Examples - examples of menu, tabs, treeview, containers, autocomplete, and more. Example are of YUI integrated with PHP via the PHOCOA php framework.');
        $skin->addMetaKeywords( array('yui examples', 'yui example') );
    }
}
?>
