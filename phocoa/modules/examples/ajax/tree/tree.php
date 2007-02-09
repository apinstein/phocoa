<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

class module_tree extends WFModule
{
    protected $treeItemsAll;

    function sharedInstancesDidLoad()
    {
        $this->treeItemsAll = array( 
                    'Portals' => new WFYAHOO_widget_TreeViewNode('Portals'),
                    'Search Engines' => new WFYAHOO_widget_TreeViewNode('Search Engines'),
                );
        $this->treeItemsAll['Portals']->addChild(new WFYAHOO_widget_TreeViewNode('Yahoo', '<a href="http://www.yahoo.com">Yahoo</a>'));
        $this->treeItemsAll['Portals']->addChild(new WFYAHOO_widget_TreeViewNode('MSN', '<a href="http://www.msn.com">MSN</a>'));
        $n31 = new WFYAHOO_widget_TreeViewNode('Google', '<a href="http://www.Google.com">Google</a>');
        $this->treeItemsAll['Search Engines']->addChild($n31);
        $n31->addChild(new WFYAHOO_widget_TreeViewNode('Local', '<a href="http://www.google.com/local">Google Local</a>'));
        $n31->addChild(new WFYAHOO_widget_TreeViewNode('Froogle', '<a href="http://froogle.google.com">Google Froogle</a>'));
        $this->treeItemsAll['Search Engines']->addChild(new WFYAHOO_widget_TreeViewNode('Ask Jeeves', '<a href="http://www.ask.com">Ask Jeeves</a>'));
    }

    function defaultPage()
    {
        return 'tree';
    }

    function tree_PageDidLoad($page, $params)
    {
        // static
        $treeView = new WFYAHOO_widget_TreeView('yuiTreeStatic', $page);
        $treeView->setValue($this->treeItemsAll);

        // dynamic
        $treeView = new WFYAHOO_widget_TreeView('yuiTreeDynamic', $page);
        $treeView->setDynamicCallback( WFRequestController::WFURL($this->invocation()->modulePath(), 'ajax') );
        $treeView->setValue($this->getRandomNodes(5));
    }

    function ajax_ParameterList()
    {
        return array('node');
    }

    function getRandomNodes($min = 1)
    {
        // create between 0 and 5 random nodes
        $nkids = rand($min,5);
        $nodes = array();
        for ($i = 0; $i < $nkids; $i++)
        {
            $node = new WFYAHOO_widget_TreeViewNode("Random node: " . ($i+1));
            $node->setCouldHaveChildren( $i % 2 );
            $nodes[] = $node;
        }
        return $nodes;
    }
    function ajax_PageDidLoad($page, $params)
    {
        WFYAHOO_widget_TreeView::sendTree($this->getRandomNodes());
    }

    function tree_SetupSkin($skin)
    {
        $skin->setTitle('YUI Tree View Example');
        $skin->addMetaKeywords(array('yui treeview', 'yui tree', 'yahoo tree view example', 'yui treeview example', 'yui ajax tree', 'ajax tree view', 'yui example'));
    }
}
?>
