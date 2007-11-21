<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

class module_tree extends WFModule
{
    function defaultPage()
    {
        return 'tree';
    }
}

class module_tree_tree
{

    function parametersDidLoad($page, $params)
    {
        new WFYAHOO_widget_Logger('logger',$page);
        // static
        $treeItemsAll = array( 
                    'Portals' => new WFYAHOO_widget_TreeViewNode('Portals'),
                    'Search Engines' => new WFYAHOO_widget_TreeViewNode('Search Engines'),
                );
        $treeItemsAll['Portals']->addChild(new WFYAHOO_widget_TreeViewNode('Yahoo', '<a href="http://www.yahoo.com">Yahoo</a>'));
        $treeItemsAll['Portals']->addChild(new WFYAHOO_widget_TreeViewNode('MSN', '<a href="http://www.msn.com">MSN</a>'));
        $n31 = new WFYAHOO_widget_TreeViewNode('Google', '<a href="http://www.Google.com">Google</a>');
        $treeItemsAll['Search Engines']->addChild($n31);
        $n31->addChild(new WFYAHOO_widget_TreeViewNode('Local', '<a href="http://www.google.com/local">Google Local</a>'));
        $n31->addChild(new WFYAHOO_widget_TreeViewNode('Froogle', '<a href="http://froogle.google.com">Google Froogle</a>'));
        $treeItemsAll['Search Engines']->addChild(new WFYAHOO_widget_TreeViewNode('Ask Jeeves', '<a href="http://www.ask.com">Ask Jeeves</a>'));
        $treeView = new WFYAHOO_widget_TreeView('yuiTreeStatic', $page);
        $treeView->setValue($treeItemsAll);

        // dynamic
        $treeView = new WFYAHOO_widget_TreeView('yuiTreeDynamic', $page);
        $treeView->setDynamicDataLoader('randomNodes');
    }

    function randomNodes($path = null)
    {
        $path = array_pop(explode('|', $path));
        $min = (empty($path) ? 5 : 1);
        $nkids = rand($min, 5);
        $nodes = array();
        for ($i = 0; $i < $nkids; $i++)
        {
            $node = new WFYAHOO_widget_TreeViewNode( ($path ? $path . "." : "") . ($i+1) );
            $node->setCouldHaveChildren( $i % 2 == 0 );
            $nodes[] = $node;
        }
        return $nodes;
    }

    function setupSkin($params, $page, $skin)
    {
        $skin->setTitle('YUI TreeView Example');
        $skin->addMetaKeywords(array('yui treeview', 'yui tree', 'yahoo tree view example', 'yui treeview example', 'yui ajax tree', 'ajax tree view', 'yui example'));
    }
}
?>
