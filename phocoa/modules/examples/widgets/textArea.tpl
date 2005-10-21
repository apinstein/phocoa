{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}

<p>WFTextArea is a textarea widget.</p>

{WFTextArea id="bio"}

{literal}
<hr>
<h3>.tpl file</h3>
<pre>
{WFTextArea id="bio"}
</pre>
    
<h3>.instances file</h3>
<pre>
$__instances = array(
	'bio' => array('class' => 'WFTextArea', 'children' => array()),
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

