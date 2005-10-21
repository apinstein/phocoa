<p>This is a statically declared form. That is, all form elements are statically declared in the template file. See staticPage.tpl.</p>

{WFForm id="form"}
<table border="1" cellpadding="5" cellspacing="0">
    <tr><td>Name</td><td>{WFTextField id="name"}</td></tr>
    <tr><td>Creation DTS</td><td>{WFLabel id="creationDTS"}</td></tr>
    <tr><td>Favorite Colors</td><td>{WFSelect id="selectMultiple"}</td></tr>
    <tr><td>City</td><td>{WFSelect id="selectOne"}</td></tr>
    <tr><td>Bio</td><td>{WFTextArea id="textarea"}</td></tr>
    <tr><td>Gender</td><td>{WFView id="radioOne"} {WFView id="radioTwo"}</td></tr>
</table>
{WFSubmit id="action1"}
{/WFForm}

<hr>
<h3>Person data</h3>
<p>Notice how the data of the Person object is updated automatically when you submit the form WITHOUT ANY CODE.</p>
<pre>
{$personData}
</pre>

{capture name="tplData"}
{literal}
{WFForm id="form"}
<table border="1" cellpadding="5" cellspacing="0">
    <tr><td>Name</td><td>{WFTextField id="name"}</td></tr>
    <tr><td>Creation DTS</td><td>{WFLabel id="creationDTS"}</td></tr>
    <tr><td>Favorite Colors</td><td>{WFSelect id="selectMultiple"}</td></tr>
    <tr><td>City</td><td>{WFSelect id="selectOne"}</td></tr>
    <tr><td>Bio</td><td>{WFTextArea id="textarea"}</td></tr>
    <tr><td>Gender</td><td>{WFView id="radioOne"} {WFView id="radioTwo"}</td></tr>
</table>
{WFSubmit id="action1"}
{/WFForm}
{/literal}
{/capture}

<h3>.tpl File</h3>
<pre>
{$smarty.capture.tplData|escape:'html'}
</pre>

{literal}
<h3>.instances File</h3>
<pre>
$__instances = array(
    'form' => array('class' => 'WFForm', 'children' => array(
        'name' => array('class' => 'WFTextField', 'children' => array()),
        'creationDTS' => array('class' => 'WFLabel', 'children' => array()),
        'selectOne' => array('class' => 'WFSelect', 'children' => array()),
        'radioButtons' => array('class' => 'WFRadioGroup', 'children' => array(
            'radioTwo' => array('class' => 'WFRadio', 'children' => array()),
            'radioOne' => array('class' => 'WFRadio', 'children' => array()),)
            ),
        'selectMultiple' => array('class' => 'WFSelect', 'children' => array()),
        'textarea' => array('class' => 'WFTextArea', 'children' => array()),
        'action1' => array('class' => 'WFSubmit', 'children' => array()),)
    ),
);
</pre>

<h3>.config File</h3>
<pre>
$__config = array(
	'form' => array(
		'properties' => array(
			'method' => 'post',
		),
	),
	'name' => array(
		'bindings' => array(
			'value' => array(
				'instanceID' => 'person',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'name',
			),
		),
	),
	'creationDTS' => array(
		'properties' => array(
			'formatter' => '#module#creationDTSFormatter',
		),
		'bindings' => array(
			'value' => array(
				'instanceID' => 'person',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'creationDTS',
			),
		),
	),
	'selectOne' => array(
		'bindings' => array(
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
			'value' => array(
				'instanceID' => 'person',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'cityID',
			),
		),
	),
	'radioButtons' => array(
		'bindings' => array(
			'value' => array(
				'instanceID' => 'person',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'gender',
			),
		),
	),
	'selectMultiple' => array(
		'properties' => array(
			'multiple' => true,
			'visibleItems' => 6,
		),
		'bindings' => array(
			'values' => array(
				'instanceID' => 'person',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'favoriteColors',
			),
			'contentValues' => array(
				'instanceID' => 'person',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'colorValues',
			),
		),
	),
	'textarea' => array(
		'bindings' => array(
			'value' => array(
				'instanceID' => 'person',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'bio',
			),
		),
	),
	'action1' => array(
		'properties' => array(
			'label' => 'Submit Form and Re-Display',
		),
	),
	'radioTwo' => array(
		'properties' => array(
			'selectedValue' => 'male',
			'label' => 'Male',
		),
	),
	'radioOne' => array(
		'properties' => array(
			'label' => 'Female',
			'selectedValue' => 'female',
		),
	),
);
</pre>

<h3>Module Code</h3>
<pre>
none
</pre>
{/literal}
