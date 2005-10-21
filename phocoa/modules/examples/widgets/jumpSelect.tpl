{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}

<p>The WFJumpSelect is a subclass of WFSelect that adds "redirect on select" functionality via javascript.</p>

{WFJumpSelect id="city"}

{literal}
<hr>
<h3>.tpl file</h3>
<pre>
{WFJumpSelect id="city"}
</pre>
    
<h3>.instances file</h3>
<pre>
$__instances = array(
	'city' => array('class' => 'WFJumpSelect', 'children' => array()),
);
</pre>
    
<h3>.config file</h3>
<pre>
$__config = array(
	'city' => array(
		'properties' => array(
			'baseURL' => 'http://google.com/search?q=',
		),
		'bindings' => array(
			'contentLabels' => array(
				'instanceID' => 'cities',
				'controllerKey' => 'arrangedObjects',
				'modelKeyPath' => 'name',
				'options' => array(
					'NullPlaceholder' => 'Select a city to search for...',
					'InsertsNullPlaceholder' => 'true',
				),
			),
			'contentValues' => array(
				'instanceID' => 'cities',
				'controllerKey' => 'arrangedObjects',
				'modelKeyPath' => 'name',
				'options' => array(
					'InsertsNullPlaceholder' => 'true',
				),
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

