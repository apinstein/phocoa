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
 * - {@link WFWidget::$value value} An array of WFYUITreeNode objects. If {@link WFYUITree::$dynamicallyLoadData}, then value should be just the top level items, otherwise it should be the entire tree.
 *
 * @todo For dynamic loading, add support to tell the system that a node definitely has no kids, so that the [+] sign isn't displayed, which is confusing.
 * @todo Add capability for multi-selection of tree items. This one is gonna be tricky! Esp. with dynamic data; need to keep track of checked items even if they never become visisble.
 */
class WFYUITree extends WFWidget
{
    /**
     * @var boolean Is the tree data loaded statically (all at once) or dynamically (using AJAX callbacks)
     */
	protected $dynamicallyLoadData;
    /**
     * @var string An http URL to serve as the "root" callback URL for loading dynamic data. The "path" of the node to load data for will be passed in as the first parameter.
     */
    protected $dynamicCallback;
    /**
     * @var string The YAHOO! NodeType to use for the tree nodes. Originally I thought this would be user-selectable, but I don't think it needs to be now.
     */
    private $nodeType;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->value = array();
        $this->dynamicallyLoadData = false;
        $this->dynamicCallback = NULL;
        $this->nodeType = 'HTMLNode';
    }

    /**
     *  Set the base URL used for the dynamic data loading.
     *
     *  The format of the callback is that WFYUITree will add one parameter to the end of that URL which contains the "path" to the tree node that child data is needed for.
     *  For example, a parameter of "GA|Atlanta Metro|Decatur" means that the child data for the node at "GA > Atlanta Metro > Decatur" is needed.
     *
     *  The URL should not have a trailing slash.
     *
     *  The URL should perform the following action:
     *
     *  1. Determine the child data for the passed path, and build an array of WFYUITreeNode objects representing the children of that node. ONLY 1 LEVEL DEEP OF CHILDREN!
     *  2. Call WFYUITree::sendTree() with an array of WFYUITreeNode objects.
     *
     *  @param string The base URL for node data loading.
     */
    function setDynamicCallback($url)
    {
        $this->dynamicCallback = $url;
        $this->dynamicallyLoadData = true;
    }

    function render($blockContent = NULL)
    {
        if ($this->hidden)
        {
            return NULL;
        }
        else
        {
            $yuiPath = WFWebApplication::webDirPath(WFWebApplication::WWW_DIR_BASE) . '/framework/yui';

            // set up basic HTML
            $html = "<div id=\"{$this->id}\"></div>\n";
            $tv = "treeView_{$this->id}";
            $script = "
<script type=\"text/javascript\">
//<![CDATA[
// add js and css needed for this widget
PHOCOA.importJS('{$yuiPath}/yahoo/yahoo.js');
PHOCOA.importJS('{$yuiPath}/treeview/treeview.js');
PHOCOA.importJS('{$yuiPath}/dom/dom.js');
PHOCOA.importJS('{$yuiPath}/connection/connection.js');
PHOCOA.importCSS('{$yuiPath}/treeview/assets/tree.css');
</script>

<script type=\"text/javascript\">
//<![CDATA[

WFYUITree_{$tv} = {};
WFYUITree_{$tv}.{$tv}_loadData = function(node, fnLoadComplete)
{
    var tNode = node;
    var pathParts = new Array();
    while ( true ) {
        pathParts.push(tNode.nodeId);
        tNode = tNode.parent;
        if (!tNode || tNode.nodeId == null) break;
    }
    pathParts.reverse();
    path = pathParts.join('|');
    var url = '{$this->dynamicCallback}/' + path;
    var callback = {
        success: WFYUITree_{$tv}.{$tv}_loadDataHandleSuccess,
        failure: WFYUITree_{$tv}.{$tv}_loadDataHandleFailure,
        argument: { loadComplete: fnLoadComplete, node: node }
    };
    var transaction = YAHOO.util.Connect.asyncRequest('GET', url, callback);
}

WFYUITree_{$tv}.{$tv}_loadDataHandleSuccess = function(o)
{
    // process XML data - this is the only x-browser way I could find since Safari doesn't support XPath yet
    var xml = o.responseXML.documentElement;
    var items = xml.getElementsByTagName('item');
    for (var i = 0; i < items.length; i++)
    {
        var nodeData = items[i].firstChild.nodeValue;
        var nodeId = items[i].getAttribute('nodeId');
        var newNode = new YAHOO.widget.{$this->nodeType}(nodeData, o.argument.node, false, true);
        newNode.nodeId = nodeId;
    }

    // redraw
    o.argument.loadComplete();
}

WFYUITree_{$tv}.{$tv}_loadDataHandleFailure = function(o)
{
    alert('failed to load data');
}

WFYUITree_{$tv}.{$tv}_treeInit = function()
{
    var {$tv} = new YAHOO.widget.TreeView('{$this->id}');
    var root = {$tv}.getRoot();
    var nodes = new Array();
";

            // add items
            // iterative algorithm for travesing nested list and creating JS to add all nodes in proper order
            // initailze itemStack with first level
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
                if (!($currentItem instanceof WFYUITreeNode)) throw( new Exception("Items in tree data must be WFYUITreeNode instances.") );
                $currentParentPath = array_pop($itemParentPaths);

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
                $script .= "    nodes['{$labelPath}'] = new YAHOO.widget.{$this->nodeType}('" . $currentItem->data() . "', {$parentNode}, false, true);\n";
                $script .= "    nodes['{$labelPath}'].nodeId = '" . addslashes($currentItem->id()) . "';\n";

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
            if ($this->dynamicallyLoadData)
            {
                $script .= "{$tv}.setDynamicLoad(WFYUITree_{$tv}.{$tv}_loadData, 1);\n";
                //throw( new WFException("dynamic loading not yet implemented"));
            }
            
            // finish script init function
            $script .= "
    {$tv}.draw();
}
WFYUITree_{$tv}.{$tv}_treeInit();
//]]>
</script>";
            // output script
            $html .= "\n{$script}\n";
            return $html;
        }
    }

    /**
     *  Helper function for the dynamicCallback page to use to send the data back to the WFYUITree via AJAX.
     *
     *  This function will create the XML data representation of the node data and pass it back to the client.
     *
     *  NOTE: Script execution stops inside of this function.
     *
     *  @param array An array of WFYUITreeNode objects representing the children of the node passed in to the dynamicCallback URL.
     */
    static function sendTree($items)
    {
        // sanitize inputs
        if (is_null($items))
        {
            $items = array();
        }

        $html = "<items>\n";
        foreach ($items as $item)
        {
            if (!($item instanceof WFYUITreeNode)) throw( new Exception("Items in tree data must be WFYUITreeNode instances.") );
            $html .= $item->toXML() . "\n";
        }
        $html .= "</items>";
        header('Content-Type: text/xml');
        die($html);
    }


    function canPushValueBinding() { return false; }
}

/**
 * Helper object for WFYUITree.
 *
 * WFYUITreeNode is a minimal PHP representation of the YAHOO.widget.node javascript object.
 *
 * It contains the "id" and the "data" to be displayed in the tree, as well as helper functions for building and accessing the tree.
 */
class WFYUITreeNode extends WFObject
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
     * @var array An array of child WFYUITreeNode objects for this node.
     */
    protected $children;
    
    /**
     *  To create a node, the ID and DATA are required.
     *
     *  @param string {@link WFYUITreeNode::$id id}
     *  @param string {@link WFYUITreeNode::$data data}
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
    }
    
    /**
     *  Add a child node.
     *
     *  @param object WFYUITreeNode The child object.
     *  @throws object Exception If the node does not have a unique ID or isn't a WFYUITreeNode object.
     */
    function addChild($c)
    {
        if (!($c instanceof WFYUITreeNode)) throw( new Exception("Items in tree data must be WFYUITreeNode instances.") );
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
     *  @return array An array of WFYUITreeNode objects.
     */
    function children()
    {
        return $this->children;
    }

    /**
     *  Get the child node for the specified ID.
     *
     *  @param string The ID of the desired child.
     *  @return object WFYUITreeNode The node with the given id.
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
     *  Helper function used by {@link WFYUITree::sendTree() sendTree}.
     *
     *  @return string The XML representation of the node.
     */
    function toXML()
    {
        return '<item nodeId="' . $this->id . '">' . htmlentities($this->data()) . '</item>';
    }
}

?>
