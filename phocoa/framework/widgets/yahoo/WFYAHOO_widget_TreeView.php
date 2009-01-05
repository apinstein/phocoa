<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package UI
 * @subpackage Widgets
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/**
 * A tree widget for our framework. Uses Yahoo! YUI library.
 * 
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * 
 * <b>Optional:</b><br>
 * - {@link WFWidget::$value value} An array of WFYAHOO_widget_TreeViewNode objects.
 * - {@link WFYAHOO_widget_TreeView::$dynamicDataLoader}
 * - {@link WFYAHOO_widget_TreeView::$autoExpandUntilChoices}
 * - {@link WFYAHOO_widget_TreeView::$expandOnClick}
 * - {@link WFYAHOO_widget_TreeView::$queryFieldId}
 *
 * @todo Add capability for multi-selection of tree items. This one is gonna be tricky! Esp. with dynamic data; need to keep track of checked items even if they never become visisble.
 * @todo Add loading indicator
 * @todo Add indicator in case of NO ITEMS FOUND... need to say something.
 * @todo Bug; if hit enter then esc quickly, things get fouled up. no idea why. even happens with autoExpandUntilChoices = false. jwatts?
 */
class WFYAHOO_widget_TreeView extends WFYAHOO
{
    /**
     * @var string an http url to serve as the "root" callback url for loading dynamic data. the "path" of the node to load data for will be passed in as the first parameter.
     */
    protected $bcCallback;
    /**
     * @var array A PHP callback structure. See {@link setDynamicDataLoader} for details.
     */
    protected $dynamicDataLoader;
    /**
     * @var string The YAHOO! NodeType to use for the tree nodes. Originally I thought this would be user-selectable, but I don't think it needs to be now.
     */
    private $nodeType;
    /**
     * @var boolean TRUE to automatically expand any node that has exactly 1 child, FALSE to make everything manual. Default: TRUE
     */
    protected $autoExpandUntilChoices;
    /**
     * @var boolean TRUE to automatically expand any node when the label is clicked. Default: TRUE
     */
    protected $expandOnClick;
    /**
     * @var string The ID of a WFSearchField that can contain a "query" to filter the tree data on.
     */
    protected $queryFieldId;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->dynamicDataLoader = NULL;
        $this->bcCallback = NULL;
        $this->nodeType = 'HTMLNode';
        $this->autoExpandUntilChoices = true;
        $this->queryFieldId = NULL;
        $this->yuiloader()->yuiRequire('treeview,connection');
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            'dynamicDataLoader',
            ));
    }

    /**
     *  Are we in dynamic data loading mode?
     *
     *  @return boolean
     */
    function dynamicallyLoadsData()
    {
        return ($this->bcCallback or $this->dynamicDataLoader);
    }

    /**
     *  Set up a dataloader callback for dynamically loading child data.
     *
     *  The callback function prototype is:
     *
     *  (array) loadNodesCallback($path)
     *
     *  Where $path is a '|' separated list of ID's to the node whose child data is needed, and you return an array of {@link WFYAHOO_widget_TreeViewNode} objects.
     *
     *  For example, a $path of "USA|Georgia" means that the child data for the node at "USA > Georgia" is needed.
     *
     *  @param mixed Callback.
     *         string A method on the page delegate object to call to get the child nodes.
     *         array  A php callback structure.
     *  @return array An array of WFYAHOO_widget_TreeViewNode objects.
     *  @throws object WFException If the callback is invalid.
     */
    function setDynamicDataLoader($callback)
    {
        if (is_string($callback))
        {
            $callback = array($this->page()->delegate(), $callback);
        }
        if (!is_callable($callback)) throw( new WFException('Invalid callback: ' . print_r($callback,true)) );

        $this->dynamicDataLoader = $callback;
    }

    /**
     *  Convert an array of WFYAHOO_widget_TreeViewNode objects into the XML that the UI widget expects in JS.
     *
     *  @param array Array of WFYAHOO_widget_TreeViewNode objects.
     *  @return string The XML of the items.
     *  @throws object WFException On Error.
     */
    static function itemsAsXML($items)
    {
        // sanitize inputs
        if (is_null($items))
        {
            $items = array();
        }

        $xml = "<items>\n";
        foreach ($items as $item)
        {
            if (!($item instanceof WFYAHOO_widget_TreeViewNode)) throw( new WFException("Items in tree data must be WFYAHOO_widget_TreeViewNode instances.") );
            $xml .= $item->toXML() . "\n";
        }
        $xml .= "</items>";
        return $xml;
    }

    /**
     *  AJAX callback function which will be called when the tree needs more node data.
     *
     *  NOTE: must be public or is_callable fails.
     *
     *  @param object WFPage
     *  @param array Paramter hash.
     *  @param string The "path" to the node that child data is being requested for
     *  @param string The "query" that is active on the tree.
     *  @return object WFActionResponseXML.
     *  @throws object WFException On Error.
     */
    public function ajaxLoadData($page, $params, $path, $query = NULL)
    {
        $nodes = call_user_func($this->dynamicDataLoader, $path, $query);
        $xml = $this->itemsAsXML($nodes);
        return new WFActionResponseXML($xml);
    }

    function render($blockContent = NULL)
    {
        if ($this->hidden)
        {
            return NULL;
        }
        else
        {
            // set up basic HTML
            $html = parent::render($blockContent);
            $html .= "<div id=\"{$this->id}\"></div>\n";
            return $html;
        }
    }

    function initJS($blockcontent)
    {
            $script = "
PHOCOA.namespace('widgets.{$this->id}');
PHOCOA.widgets.{$this->id}.loadData = function(node, fnLoadComplete)
{
    var tNode = node;
    var pathParts = new Array();
    while ( true ) {
        pathParts.push(tNode.nodeId);
        tNode = tNode.parent;
        if (!tNode || tNode.nodeId == null) break;
    }
    pathParts.reverse();
    var path = encodeURIComponent(pathParts.join('|'));
";
            if ($this->dynamicDataLoader)
            {
                $script .= "
    var rpc = new PHOCOA.WFRPC('" . WWW_ROOT . '/' . $this->page()->module()->invocation()->invocationPath() . "', '#page#{$this->id}', 'ajaxLoadData');
    rpc.callback.success = PHOCOA.widgets.{$this->id}.loadDataHandleSuccess;
    rpc.callback.failure = PHOCOA.widgets.{$this->id}.loadDataHandleFailure;
    rpc.callback.argument = { loadComplete: fnLoadComplete, node: node };
    var qVal = PHOCOA.widgets.{$this->queryFieldId}.getValue();
    rpc.execute(path" . ($this->queryFieldId ? ", qVal" : NULL) . ");
    ";
            }
            else
            {
                // backwards compatibility
                $script .= "
    var url = '{$this->bcCallback}/' + path;
    var callback = {
        success: PHOCOA.widgets.{$this->id}.loadDataHandleSuccess,
        failure: PHOCOA.widgets.{$this->id}.loadDataHandleFailure,
        argument: { loadComplete: fnLoadComplete, node: node }
    };
    YAHOO.util.Connect.asyncRequest('GET', url, callback);
    ";
            }
            $script .= "
};

PHOCOA.widgets.{$this->id}.loadDataHandleSuccess = function(o)
{
    if (o.argument.node.isRoot())
    {
        // always clear all kids on root before adding; this solves the problem of multiple reloadTree() being called before first result rolls in (and thus merges results)
        PHOCOA.runtime.getObject('{$this->id}').removeChildren(o.argument.node);
    }

    // process XML data - this is the only x-browser way I could find since Safari doesn't support XPath yet
    var xml = o.responseXML.documentElement;
    var items = xml.getElementsByTagName('item');
    for (var i = 0; i < items.length; i++)
    {
        var nodeData = items[i].firstChild.nodeValue;
        var nodeId = items[i].getAttribute('nodeId');
        var couldHaveChildren = items[i].getAttribute('couldHaveChildren');
        var newNode = new YAHOO.widget.{$this->nodeType}(nodeData, o.argument.node, false, true);
        newNode.nodeId = nodeId;
        if (couldHaveChildren == '0')
        {
            newNode.dynamicLoadComplete = true;
        }
    }

    // complete node loading
    o.argument.loadComplete();

    if (o.argument.node.isRoot() && " . ($this->autoExpandUntilChoices ? 1 : 0) . ") PHOCOA.widgets.{$this->id}.autoExpand(o.argument.node);
};

PHOCOA.widgets.{$this->id}.loadDataHandleFailure = function(o)
{
    alert('failed to load data');
};

// utility functions not included in YUI Tree
PHOCOA.widgets.{$this->id}.reloadTree = function()
{
    var tree = PHOCOA.runtime.getObject('{$this->id}');
    var rootNode = tree.getRoot();
    tree.removeChildren(rootNode);
    rootNode.refresh();
    PHOCOA.widgets.{$this->id}.loadData(rootNode, function() { rootNode.loadComplete(); });
};
PHOCOA.widgets.{$this->id}.autoExpand = function(node) { if (node.children.length === 1) node.children[0].expand(); };
// end util funcs

PHOCOA.widgets.{$this->id}.init = function()
{
    var {$this->id} = new YAHOO.widget.TreeView('{$this->id}');
    var root = {$this->id}.getRoot();
    var nodes = new Array();
";

            // load the root data set if it hasn't been set already
            if ($this->value === NULL)
            {
                if ($this->dynamicDataLoader)
                {
                    $this->setValue( call_user_func($this->dynamicDataLoader, NULL, NULL) );
                }
                else
                {
                    $this->setValue( array() );
                }
            }

            // add items
            // iterative algorithm for travesing nested list and creating JS to add all nodes in proper order
            // initailze itemStack with first level
            $itemStack = array();
            foreach (array_reverse($this->value) as $child)
            {
                $itemStack[] = $child;
                $itemParentPaths[] = NULL;
            }

            while ( true )
            {
                // get item to work with; flow control
                $currentItem = array_pop($itemStack);
                if (!$currentItem) break;
                if (!($currentItem instanceof WFYAHOO_widget_TreeViewNode)) throw( new Exception("Items in tree data must be WFYAHOO_widget_TreeViewNode instances.") );
                $currentParentPath = array_pop($itemParentPaths);

                // if we're not in dynamic data loading mode, we can auto-calculate couldHaveChildren
                if (!$this->dynamicallyLoadsData())
                {
                    if (!$currentItem->hasChildren())
                    {
                        $currentItem->setCouldHaveChildren(false);
                    }
                }

                // add item to tree
                $labelPath = $currentParentPath . "|" . $currentItem->id();
                if ($currentParentPath)
                {
                    $parentNode = "nodes['{$currentParentPath}']";
                }
                else
                {
                    $parentNode = "root";
                }
                $script .= "    nodes['{$labelPath}'] = new YAHOO.widget.{$this->nodeType}(" . WFJSON::encode($currentItem->data()) . ", {$parentNode}, false, true);\n";
                $script .= "    nodes['{$labelPath}'].nodeId = '" . addslashes($currentItem->id()) . "';\n";
                if (!$currentItem->couldHaveChildren())
                {
                    $script .= "    nodes['{$labelPath}'].dynamicLoadComplete = true;\n";
                }

                if ($currentItem->hasChildren())
                {
                    foreach (array_reverse($currentItem->children()) as $child)
                    {
                        $itemStack[] = $child;
                        $itemParentPaths[] = $labelPath;
                    }
                }
            }

            // add dynamic loader if needed
            if ($this->dynamicallyLoadsData())
            {
                $script .= "{$this->id}.setDynamicLoad(PHOCOA.widgets.{$this->id}.loadData, 1);\n";
                //throw( new WFException("dynamic loading not yet implemented"));
            }

            if ($this->autoExpandUntilChoices)
            {
                $script .= "{$this->id}.subscribe('expandComplete', PHOCOA.widgets.{$this->id}.autoExpand);";
            }
            if ($this->expandOnClick === false)
            {
                $script .= "{$this->id}.subscribe('clickEvent', function(e) { return false; });";
            }
            
            // finish script init function
            $script .= "
    PHOCOA.runtime.addObject({$this->id}, '{$this->id}');
    {$this->id}.draw();
            ";
            if ($this->queryFieldId)
            {
                $qf = $this->page()->outlet($this->queryFieldId);
                if (!($qf instanceof WFSearchField)) throw( new WFException("queryFieldId must be the ID of a WFSearchField.") );
                $script .= "
    YAHOO.util.Event.onContentReady('{$this->queryFieldId}', function() {
        var queryField = $('{$this->queryFieldId}');
        queryField.observe('phocoa:WFSearchField:search', PHOCOA.widgets.{$this->id}.reloadTree);
        queryField.observe('phocoa:WFSearchField:clear', PHOCOA.widgets.{$this->id}.reloadTree);
    });
                ";
            }
            $script .= "
}
";
        return $script;
    }

    function canPushValueBinding() { return false; }

    /**
     *  Helper function for the dynamicCallback page to use to send the data back to the WFYAHOO_widget_TreeView via AJAX.
     *
     *  This function will create the XML data representation of the node data and pass it back to the client.
     *
     *  NOTE: Script execution stops inside of this function.
     *
     *  @param array An array of WFYAHOO_widget_TreeViewNode objects representing the children of the node passed in to the dynamicCallback URL.
     *  @deprecated
     */
    static function sendTree($items)
    {
        header('Content-Type: text/xml');
        die(self::itemsAsXML($items));
    }

    /**
     *  Set the base URL used for the dynamic data loading.
     *
     *  The format of the callback is that WFYAHOO_widget_TreeView will add one parameter to the end of that URL which contains the "path" to the tree node that child data is needed for.
     *  For example, a parameter of "GA|Atlanta Metro|Decatur" means that the child data for the node at "GA > Atlanta Metro > Decatur" is needed.
     *
     *  The URL should not have a trailing slash.
     *
     *  The URL should be urlencoded.
     *
     *  The URL should perform the following action:
     *
     *  1. Determine the child data for the passed path, and build an array of WFYAHOO_widget_TreeViewNode objects representing the children of that node. ONLY 1 LEVEL DEEP OF CHILDREN!
     *  2. Call WFYAHOO_widget_TreeView::sendTree() with an array of WFYAHOO_widget_TreeViewNode objects.
     *
     *  @param string The base URL for node data loading.
     *  @deprecated Use {WFYAHOO_widget_TreeView::setDynamicDataLoader()}.
     */
    function setDynamicCallback($url)
    {
        $this->bcCallback = $url;
    }
}

/**
 * Helper object for WFYAHOO_widget_TreeView.
 *
 * WFYAHOO_widget_TreeViewNode is a minimal PHP representation of the YAHOO.widget.node javascript object.
 *
 * It contains the "id" and the "data" to be displayed in the tree, as well as helper functions for building and accessing the tree.
 */
class WFYAHOO_widget_TreeViewNode extends WFObject
{
    /**
     * @var string The ID of the node. ID's must be unique among children of the same node.
     */
    protected $id;
    /**
     * @var string The raw HTML code to be shown as the item at that level of the tree.
     */
    protected $data;
    /**
     * @var array An array of child WFYAHOO_widget_TreeViewNode objects for this node.
     */
    protected $children;
    /**
     * @var boolean TRUE if the node does/could have children. If true, the node will be "expandable". FALSE if the node definitely doesn't have kids; it will be a leaf node.
     */
    protected $couldHaveChildren;

    /**
     *  To create a node, the ID and DATA are required.
     *
     *  @param string {@link WFYAHOO_widget_TreeViewNode::$id id}
     *  @param string {@link WFYAHOO_widget_TreeViewNode::$data data}
     */
    function __construct($id, $data = NULL)
    {
        $this->id = $id;
        if (is_null($data))
        {
            $this->data = $id;
        }
        else
        {
            $this->data = $data;
        }
        $this->children = array();
        $this->couldHaveChildren = true;
    }

    /**
     *  By default, it is assumed that all nodes *could* have children, and that a dynamic callback must be made to see.
     *
     *  Thus, all nodes will be "+" expandable icons. If you know for sure that a given node doesn't have children,
     *  setCouldHaveChildren(true) to have the node be a leaf node by default.
     *
     *  @param boolean TRUE if the node does/could have children. If true, the node will be "expandable". FALSE if the node definitely doesn't have kids; it will be a leaf node.
     */
    function setCouldHaveChildren($could)
    {
        $this->couldHaveChildren = $could;
    }

    /**
     *  Is it possible that this node has children?
     *
     *  @return boolean 
     *  @see setCouldHaveChildren()
     */
    function couldHaveChildren()
    {
        return $this->couldHaveChildren;
    }
    
    /**
     *  Add a child node.
     *
     *  @param object WFYAHOO_widget_TreeViewNode The child object.
     *  @throws object Exception If the node does not have a unique ID or isn't a WFYAHOO_widget_TreeViewNode object.
     */
    function addChild($c)
    {
        if (!($c instanceof WFYAHOO_widget_TreeViewNode)) throw( new Exception("Items in tree data must be WFYAHOO_widget_TreeViewNode instances.") );
        if (isset($this->children[$c->id()])) throw( new Exception("Children at the same level must have unique ID's. Duplicate found: " . $c->id()) );
        $this->children[$c->id()] = $c;
    }

    /**
     *  Does this node have children?
     *
     *  @return boolean
     */
    function hasChildren()
    {
        return (count($this->children) > 0);
    }

    /**
     *  Get the children of this node as an array.
     *
     *  @return array An array of WFYAHOO_widget_TreeViewNode objects.
     */
    function children()
    {
        return $this->children;
    }

    /**
     *  Get the child node for the specified ID.
     *
     *  @param string The ID of the desired child.
     *  @return object WFYAHOO_widget_TreeViewNode The node with the given id.
     *  @throws object Exception if there is no node with that id.
     */
    function childWithId($i)
    {
        if (!isset($this->children[$i])) throw( new Exception("No child with id: '{$i}'.") );
        return $this->children[$i];
    }

    /**
     *  Get the ID for the node.
     *
     *  @return string The ID.
     */
    function id()
    {
        return $this->id;
    }

    /**
     *  Get the node data (the HTML).
     *
     *  @return string The node data / html.
     */
    function data()
    {
        return $this->data;
    }

    /**
     *  Convert this node into XML format.
     *
     *  Helper function used by {@link WFYAHOO_widget_TreeView::sendTree() sendTree}.
     *
     *  @return string The XML representation of the node.
     */
    function toXML()
    {
        return '<item nodeId="' . $this->id . '" couldHaveChildren="' . ($this->couldHaveChildren ? '1' : '0') . '">' . htmlentities($this->data()) . '</item>';
    }
}

?>
