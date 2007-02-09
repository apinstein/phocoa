{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
<h1>AJAX TreeView</h1>

<p>This is an example of a AJAX tree widget (YAHOO.widget.TreeView) built using the Yahoo! YUI platform. PHOCOA has a wrapper widget class to make it very easy to build both static and dynamic (ajax data loading) TreeView controls based on the Yahoo TreeView.</p>
<p><b>Example of a working YUI TreeView with static data:</b></p>
{WFView id="yuiTreeStatic"}
<p><b>Example of a working YUI TreeView with ajax data loading:</b></p>
{WFView id="yuiTreeDynamic"}
<p>With PHOCOA, you don't need to write any Javascript to use the Yahoo TreeView. All you need to do is add a TreeView widget to your page, and provide it with data.</p>
<p>The data for the tree must be an array of <a href="http://phocoa.com/docs/UI/Widgets/WFYAHOO_widget_TreeViewNode.html" target="_blank">WFYAHOO_widget_TreeViewNode</a> objects. It is simple to set up this data structure. Then PHOCOA handles the rest.</p>
<h3>tree.php file</h3>
<p>The code below is the entire code used to make the above page.</p>
<pre>
{php}
echo htmlentities(file_get_contents(FRAMEWORK_DIR . '/modules/examples/ajax/tree/tree.php'));
{/php}
</pre>
