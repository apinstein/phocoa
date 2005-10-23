{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
Raw creation DTS: {WFLabel id="creationDTS"}<br />
Formatted creation DTS: {WFLabel id="formattedCreationDTS"}<br />
Raw wealth: ${WFLabel id="wealth"}<br />
Formatted wealth: ${WFLabel id="formattedWealth"}<br />

{capture name="tplData"}
{literal}
Raw creation DTS: {WFLabel id="creationDTS"}<br />
Formatted creation DTS: {WFLabel id="formattedCreationDTS"}<br />
Raw wealth: ${WFLabel id="wealth"}<br />
Formatted wealth: ${WFLabel id="formattedWealth"}<br />
{/literal}
{/capture}

<h3>.tpl File</h3>
<pre>
{$smarty.capture.tplData|escape:'html'}
</pre>

{literal}
<h3>shared.instances File</h3>
<pre>
$__instances = array(
	'unixDateFormatter' => 'WFUNIXDateFormatter',
	'currencyFormatter' => 'WFNumberFormatter',
	'person' => 'WFObjectController',
);
</pre>

<h3>shared.config File</h3>
<pre>
$__config = array(
	'unixDateFormatter' => array(
		'properties' => array(
			'formatString' => 'Y M d',
		),
	),
	'currencyFormatter' => array(
		'properties' => array(
			'decimalPlaces' => '0',
		),
	),
	'person' => array(
		'properties' => array(
			'class' => 'Person',
		),
	),
);
</pre>

<h3>.instances File</h3>
<pre>
$__instances = array(
	'creationDTS' => array('class' => 'WFLabel', 'children' => array()),
	'wealth' => array('class' => 'WFLabel', 'children' => array()),
	'formattedCreationDTS' => array('class' => 'WFLabel', 'children' => array()),
	'formattedWealth' => array('class' => 'WFLabel', 'children' => array()),
);
</pre>

<h3>.config File</h3>
<pre>
$__config = array(
	'creationDTS' => array(
		'bindings' => array(
			'value' => array(
				'instanceID' => 'person',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'creationDTS',
			),
		),
	),
	'wealth' => array(
		'bindings' => array(
			'value' => array(
				'instanceID' => 'person',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'wealth',
			),
		),
	),
	'formattedCreationDTS' => array(
		'properties' => array(
			'formatter' => '#module#unixDateFormatter',
		),
		'bindings' => array(
			'value' => array(
				'instanceID' => 'person',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'creationDTS',
			),
		),
	),
	'formattedWealth' => array(
		'properties' => array(
			'formatter' => '#module#currencyFormatter',
		),
		'bindings' => array(
			'value' => array(
				'instanceID' => 'person',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'wealth',
			),
		),
	),
);
</pre>

<h3>Module Code</h3>
<pre>
class formatters extends WFModule
{
    function defaultPage()
    {
        return 'example';
    }

    function sharedInstancesDidLoad()
    {
        $this->person->setContent(Person::personByID(1));
    }
}
</pre>
{/literal}
