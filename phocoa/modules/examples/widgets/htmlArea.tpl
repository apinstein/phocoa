{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
<p>Our WYSIWYG control is <a href="http://fckeditor.com" target="_blank">FCKeditor</a>. It works in IE 6+ and Mozilla 7+ and FireFox. In other browsers it degrades nicely to a text area.</p>
{WFHTMLArea id="bio"}

{literal}
<hr>
<h3>.tpl file</h3>
<pre>
{WFHTMLArea id="bio"}
</pre>
    
<h3>.instances file</h3>
<pre>
$__instances = array(
	'bio' => array('class' => 'WFHTMLArea', 'children' => array()),
);
</pre>
    
<h3>.config file</h3>
<pre>
$__config = array(
	'bio' => array(
		'bindings' => array(
			'value' => array(
				'instanceID' => 'person',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'bio',
			),
		),
	),
);
</pre>

<h3>Module Code</h3>
<pre>
none
</pre>
{/literal}

