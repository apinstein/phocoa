<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

class module_tree extends WFModule
{
    protected $treeItemsTop;
    protected $treeItemsAll;

    function sharedInstancesDidLoad()
    {
        $this->treeItemsTop = array( 
                    'Portals' => new WFYUITreeNode('Portals'),
                    'Search Engines' => new WFYUITreeNode('Search Engines'),
                );
        $this->treeItemsAll = array( 
                    'Portals' => new WFYUITreeNode('Portals'),
                    'Search Engines' => new WFYUITreeNode('Search Engines'),
                );
        $this->treeItemsAll['Portals']->addChild(new WFYUITreeNode('Yahoo', '<a href="http://www.yahoo.com">Yahoo</a>'));
        $this->treeItemsAll['Portals']->addChild(new WFYUITreeNode('MSN', '<a href="http://www.msn.com">MSN</a>'));
        $n31 = new WFYUITreeNode('Google', '<a href="http://www.Google.com">Google</a>');
        $this->treeItemsAll['Search Engines']->addChild($n31);
        $n31->addChild(new WFYUITreeNode('Local', '<a href="http://www.google.com/local">Google Local</a>'));
        $n31->addChild(new WFYUITreeNode('Froogle', '<a href="http://froogle.google.com">Google Froogle</a>'));
        $this->treeItemsAll['Search Engines']->addChild(new WFYUITreeNode('Ask Jeeves', '<a href="http://www.ask.com">Ask Jeeves</a>'));
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
        $treeView = new WFYUITree('yuiTree', $page);
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
        $node = new WFYUITreeNode('root');
        foreach ($this->treeItemsAll as $item)
        {
            $node->addChild($item);
        }
        foreach ($parts as $i)
        {
            try {
                $node = $node->childWithId($i);
            } catch (Exception $e) {
                return WFYUITree::sendTree(NULL);
            }
        }
        return WFYUITree::sendTree($node->children());
    }

    function tree_SetupSkin($skin)
    {
        $skin->setTitle('YUI Tree View Example');
        $skin->addHeadString('<script type="text/javascript" src="' . WFWebApplication::webDirPath(WFWebApplication::WWW_DIR_FRAMEWORK) . '/js/prototype.js"></script>');
        $skin->addHeadString('<script type="text/javascript" src="' . WFWebApplication::webDirPath(WFWebApplication::WWW_DIR_FRAMEWORK) . '/js/phocoa.js"></script>');
    }
}
?>
