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

    function tree_PageDidLoad($page, $params)
    {
        $treeView = new WFYAHOO_widget_TreeView('yuiTree', $page);
        $treeView->setDynamicCallback( WFRequestController::WFURL($this->invocation()->modulePath(), 'ajax') );
        $treeView->setValue($this->treeItemsTop);
    }

    function ajax_ParameterList()
    {
        return array('node');
    }
    function ajax_PageDidLoad($page, $params)
    {
        $parts = explode('|', $params['node']);
        $node = NULL;
        $first = true;
        foreach ($parts as $i)
        {
            try {
                if ($first)
                {
                    $first = false;
                    if (isset($this->treeItemsAll[$i]))
                    {
                        $node = $this->treeItemsAll[$i];
                    }
                    else
                    {
                        throw( new Exception() );   // will send no items
                    }
                }
                else
                {
                    $node = $node->childWithId($i);
                }
            } catch (Exception $e) {
                WFYAHOO_widget_TreeView::sendTree(NULL);
            }
        }
        WFYAHOO_widget_TreeView::sendTree($node->children());
    }

    function tree_SetupSkin($skin)
    {
        $skin->setTitle('YUI Tree View Example');
        $skin->addMetaKeywords(array('yui treeview', 'yui tree', 'yahoo tree view example', 'yui treeview example', 'yui ajax tree', 'ajax tree view', 'yui example'));
        $skin->addHeadString('<script type="text/javascript" src="' . WFWebApplication::webDirPath(WFWebApplication::WWW_DIR_FRAMEWORK) . '/js/prototype.js"></script>');
        $skin->addHeadString('<script type="text/javascript" src="' . WFWebApplication::webDirPath(WFWebApplication::WWW_DIR_FRAMEWORK) . '/js/phocoa.js"></script>');
    }
}
?>
