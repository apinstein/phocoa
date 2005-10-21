{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
{WFSelect id="city"}

{literal}
<hr>
<h3>.tpl file</h3>
<pre>
{WFSelect id="city"}
</pre>
    
<h3>.instances file</h3>
<pre>
$__instances = array(
	'city' => array('class' => 'WFSelect', 'children' => array()),
);
</pre>
    
<h3>.config file</h3>
<pre>
$__config = array(
	'city' => array(
		'bindings' => array(
			'value' => array(
				'instanceID' => 'person',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'cityID',
			),
			'contentLabels' => array(
				'instanceID' => 'cities',
				'controllerKey' => 'arrangedObjects',
				'modelKeyPath' => 'name',
			),
			'contentValues' => array(
				'instanceID' => 'cities',
				'controllerKey' => 'arrangedObjects',
				'modelKeyPath' => 'id',
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
