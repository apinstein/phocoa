{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}

<p>WFTextField is a simple text field.</p>

{WFTextField id="name"}

{literal}
<hr>
<h3>.tpl file</h3>
<pre>
{WFTextField id="name"}
</pre>

<h3>.instances file</h3>
<pre>
$__instances = array(
	'name' => array('class' => 'WFTextField', 'children' => array()),
);
</pre>

<h3>.config file</h3>

<pre>
$__config = array(
	'name' => array(
		'bindings' => array(
			'value' => array(
				'instanceID' => 'person',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'name',
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

