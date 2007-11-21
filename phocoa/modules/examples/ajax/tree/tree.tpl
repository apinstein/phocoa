{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
<h1>YUI TreeView</h1>

<p>YUI TreeView is a Javascript tree widget, which is part of the <a href="http://developer.yahoo.com/yui" target="_blank">Yahoo! YUI platform</a> (<a href="http://developer.yahoo.com/yui/treeview" target="_blank">YAHOO.widget.TreeView</a>). PHOCOA has a wrapper widget class (<a href="http://phocoa.com/docs/UI/Widgets/WFYAHOO_widget_TreeView.html">WFYAHOO_widget_TreeView</a>) that makes it very easy to build either a static Javascript tree view or dynamic AJAX tree view (dynamic data loading).</p>
<h2>Static Javascript TreeView</h2>
<p>All data in this example is available for initial page output and the data is included in Javascript as the page is built.</p>
<p>This is the ideal way to create fast-loading treeview widgets when the amount of data is small and can be built quickly.</p>
{WFView id="yuiTreeStatic"}

<h2>Dynamic AJAX TreeView</h2>
<p>In the dynamic example, only the "top level" of items is loaded with the initial page data. All other data is loaded dynamically (via AJAX) when the node is "expanded".</p>
<p>This is the ideal way to build a tree view interface when the tree represents a large amount of data or the time to build all data is longer than desired.</p>
{WFView id="yuiTreeDynamic"}

<h3>PHOCOA Wrapper for YAHOO.widget.TreeView</h3>
<p>With PHOCOA, you don't need to write any Javascript to use the Yahoo TreeView. All you need to do is add a TreeView widget to your page, and provide it with data.</p>
<p>The data for the tree must be an array of <a href="http://phocoa.com/docs/UI/Widgets/WFYAHOO_widget_TreeViewNode.html" target="_blank">WFYAHOO_widget_TreeViewNode</a> objects. It is simple to set up this data structure. Then PHOCOA handles the rest.</p>
<h3>tree.php file</h3>
<p>The code below is the entire code used to make the above page.</p>
<pre>
{php}
echo htmlentities(file_get_contents(FRAMEWORK_DIR . '/modules/examples/ajax/tree/tree.php'));
{/php}
</pre>
