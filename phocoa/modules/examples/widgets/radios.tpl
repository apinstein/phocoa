{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
<p>This page demonstrates the use of radio buttons in conjunction with WFDynamic.</p>
<p>A WFRadioGroup is a meta-widget and is never used directly in template files. The WFRadioGroup is solely to provide a single interface to the programmer for accessing the value represented by an arbitrary number of WFRadio's. In this example, the WFRadio's are created dynamically by the WFDynamic widget.</p>
<p>The use of this setup allows for the dynamic, automatic creation of radio buttons based on items in an array in your model. It is a powerful capability, requiring minimal setup. See the WFDynamic docs to read about all of the options.</p>
<p>You will notice that some minimal coding is required for this setup. This is required to tell the WFDynamic where to get the values for the each radio's label and value represented.</p>
<p>NOTE: the parentFormID property is currently named poorly. It really just means "parent widget" and will be changed in the future. Don't let it confuse you.</p>

{WFDynamic id="radios"}

{literal}
<hr>
<h3>.tpl file</h3>
<pre>
{WFDynamic id="radios"}
</pre>
    
<h3>.instances file</h3>
<pre>
$__instances = array(
	'radios' => array('class' => 'WFDynamic', 'children' => array()),
	'radioGroup' => array('class' => 'WFRadioGroup', 'children' => array()),
);
</pre>
    
<h3>.config file</h3>
<pre>
$__config = array(
	'radios' => array(
		'properties' => array(
			'arrayController' => '#module#cities',
			'parentFormID' => 'radioGroup',
			'oneShotMode' => true,
			'widgetClass' => 'WFRadio',
		),
	),
	'radioGroup' => array(
		'properties' => array(
			'value' => '4',
		),
		'bindings' => array(
			'value' => array(
				'instanceID' => 'person',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'cityID',
			),
		),
	),
);
</pre>

<h3>Module Code</h3>
<pre>
    function radios_PageDidLoad($page, $params)
    {
        $radioDynamicOptions = array(
                                    'label' => array(
                                        'bind' => array(
                                            'instanceID' => '#current#',
                                            'controllerKey' => '',
                                            'modelKeyPath' => 'name'
                                            )
                                        ),
                                    'selectedValue' => array(
                                        'custom' => array(
                                            'iterate' => true,
                                            'keyPath' => '#identifier#'
                                            )
                                        )
                                    );
        $page->outlet('radios')->setWidgetConfig($radioDynamicOptions);
    }
</pre>
{/literal}

