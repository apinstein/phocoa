<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

class module_tree extends WFModule
{
    protected $treeItemsTop;
    protected $treeItemsAll;

    function sharedInstancesDidLoad()
    {
        $this->treeItemsTop = array( 
                    'Portals' => new WFYAHOO_widget_TreeViewNode('Portals'),
                    'Search Engines' => new WFYAHOO_widget_TreeViewNode('Search Engines'),
                );
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

//    uncomment as needed
//    function tree_ParameterList()
//    {
//        return array();
//    }
//
//    function tree_SetupSkin($skin)
//    {
//    }
//
    function tree_PageDidLoad($page, $params)
    {
        $treeView = new WFYAHOO_widget_TreeView('yuiTree', $page);
        $treeView->setDynamicCallback( WFRequestController::WFURL($page->module()->invocation()->modulePath(), 'ajax') );
        $treeView->setValue($this->treeItemsTop);
    }

    function ajax_ParameterList()
    {
        return array('node');
    }
    function ajax_PageDidLoad($page, $params)
    {
        $parts = explode('|', $params['node']);
        $node = new WFYAHOO_widget_TreeViewNode('root');
        foreach ($this->treeItemsAll as $item)
        {
            $node->addChild($item);
        }
        foreach ($parts as $i)
        {
            try {
                $node = $node->childWithId($i);
            } catch (Exception $e) {
                return WFYAHOO_widget_TreeView::sendTree(NULL);
            }
        }
        return WFYAHOO_widget_TreeView::sendTree($node->children());
    }

    function tree_SetupSkin($skin)
    {
        $skin->setTitle('YUI Tree View Example');
        $skin->addHeadString('<script type="text/javascript" src="' . WFWebApplication::webDirPath(WFWebApplication::WWW_DIR_FRAMEWORK) . '/js/prototype.js"></script>');
        $skin->addHeadString('<script type="text/javascript" src="' . WFWebApplication::webDirPath(WFWebApplication::WWW_DIR_FRAMEWORK) . '/js/phocoa.js"></script>');
    }
}
?>
