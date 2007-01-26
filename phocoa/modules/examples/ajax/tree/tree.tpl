{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
<h1>AJAX TreeView</h1>

<p>This is an example of a AJAX tree widget (YAHOO.widget.TreeView) built using the Yahoo! YUI platform. PHOCOA has a wrapper widget class to make it very easy to build both static and dynamic (ajax data loading) TreeView controls based on the Yahoo TreeView.</p>
<p><b>Example of a working YUI TreeView with ajax data loading:</b></p>
{WFView id="yuiTree"}
{literal}
<p>With PHOCOA, you don't need to write any Javascript to use the Yahoo TreeView. All you need to do is add a TreeView widget to your page, and provide it with data:</p>
<pre>
    function tree_PageDidLoad($page, $params)
    {
        $treeView = new WFYAHOO_widget_TreeView('yuiTree', $page);
        // set up the PHOCOA module/page used for the ajax callback to load tree data dynamically
        $treeView->setDynamicCallback( WFRequestController::WFURL( $this->invocation()->modulePath(), 'ajax') );
        // load 1st level items into TreeView
        $treeView->setValue($this->treeItemsTop);
    }
</pre>
<p>The data for the tree must be an array of <a href="http://phocoa.com/docs/UI/Widgets/WFYAHOO_widget_TreeViewNode.html" target="_blank">WFYAHOO_widget_TreeViewNode</a> objects. It is simple to set up this data structure:</p>
<pre>
        // top items only
        $this-&gt;treeItemsTop = array( 
                    'Portals' =&gt; new WFYAHOO_widget_TreeViewNode('Portals'),
                    'Search Engines' =&gt; new WFYAHOO_widget_TreeViewNode('Search Engines'),
                );

        // all data
        $this-&gt;treeItemsAll = array( 
                    'Portals' =&gt; new WFYAHOO_widget_TreeViewNode('Portals'),
                    'Search Engines' =&gt; new WFYAHOO_widget_TreeViewNode('Search Engines'),
                );
        $this-&gt;treeItemsAll['Portals']-&gt;addChild(new WFYAHOO_widget_TreeViewNode('Yahoo', '&lt;a href="http://www.yahoo.com"&gt;Yahoo&lt;/a&gt;'));
        $this-&gt;treeItemsAll['Portals']-&gt;addChild(new WFYAHOO_widget_TreeViewNode('MSN', '&lt;a href="http://www.msn.com"&gt;MSN&lt;/a&gt;'));
        $n31 = new WFYAHOO_widget_TreeViewNode('Google', '&lt;a href="http://www.Google.com"&gt;Google&lt;/a&gt;');
        $this-&gt;treeItemsAll['Search Engines']-&gt;addChild($n31);
        $n31-&gt;addChild(new WFYAHOO_widget_TreeViewNode('Local', '&lt;a href="http://www.google.com/local"&gt;Google Local&lt;/a&gt;'));
        $n31-&gt;addChild(new WFYAHOO_widget_TreeViewNode('Froogle', '&lt;a href="http://froogle.google.com"&gt;Google Froogle&lt;/a&gt;'));
        $this-&gt;treeItemsAll['Search Engines']-&gt;addChild(new WFYAHOO_widget_TreeViewNode('Ask Jeeves', '&lt;a href="http://www.ask.com"&gt;Ask Jeeves&lt;/a&gt;'));
</pre>
<p>If you already have hierachical data stored in your database, you can simply loop through the top level and create WFYAHOO_widget_TreeViewNode instances for each item.</p>
<p>The way WFYAHOO_widget_TreeView works, it expects that each node on a given level has a unique ID. That's pretty much the only requirement. You can also pass a second parameter to the constructor of the actual HTML code you want used for the "label" of the node in the tree.</p>
<p>Now, let's get to the loading of the child data dynamically. PHOCOA will simply call back the module/page set up in setDynamicCallback with a single parameter, a | delimited set of node ID's that lead to the node for which child data is needed. So, examples would be: "Portals", "Portals|Yahoo", "Search Engines|Google". All your callback has to do is parse this string and send the new data back to the control:</p>
<pre>
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
</pre>
<p>This is actually a little uglier than it will be in your own code; when you have a nice data model for your hierarchical data, retrieving the data is much cleaner than the way we do it for this example. It would look more like:</p>
<pre>
    function ajax_PageDidLoad($page, $params)
    {
        $parts = explode('|', $params['node']);
        $childData = $myHierachicalData->getItemsAtPath($parts);
        $nodes = array();
        foreach ($childData as $child) {
            $nodes[] = new WFYAHOO_widget_TreeViewNode($child->id());
        }
        WFYAHOO_widget_TreeView::sendTree($nodes);
    }
</pre>
<p>This example at the top of this page is a live working example; you can view the source of this page to see the Javascript that PHOCOA builds for you to implement the YUI TreeView.</p>
{/literal}
