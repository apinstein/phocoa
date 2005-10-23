{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
<h1>Bindings and Binding Options</h1>

<h2>Overview</h2>
<p>Bindings allow you to map a property of a widget to the property of another object.<p>
<p>So, instead of having to write code like so:
<pre>
// set up form
$myTextField->setValue($myDataObject->getValue());

// put data from form back into my object
$myDataObject->setValue($myTextField->getValue());
</pre>

You can simply bind the "value" property of myTextField to the "value" property of myDataObject. With PHOCOA bindings, you bind widget properties with Key-Value Coding, so you can use any legitimate keyPath on your objects. This is very convenient for large data models, where you could bind a widget value to $myBook with the keyPath "author.name".
<pre>
$myTextField->bind('value', $myDataObject, 'author.name');
</pre>

While you can set up bindings programmatically, you usually use the PHOCOA configuration system to set up bindings:

<pre>
	'myTextField' => array(
		'bindings' => array(
			'value' => array(
				'instanceID' => 'myDataObject',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'author.name',
			),
		),
	),
</pre>

Typically this configuration is setup in the GUI PHOCOA Builder application.</p>
<p>PHOCOA's bindings go beyond simply moving data around between the Model and the View. They also automatically call your data's validation and normalization function (provided by Key-Value Validation) and keep track of the errors found by KVV. More on this <a href="{WFURL module="/examples/emailform"}">here</a>.</p>
<p>Thus, because of the PHOCOA bindings system, most of the glue code normally written to handle moving data between the Model and the View, along with data validation and error handling is instead done simlpy by configuring the application in the PHOCOA Builder tool.
</p>
<p>The bindings can be <b>one-way</b> (read-only), or <b>two-way</b>. One-way bindings read their value from the bound object/property and use it for the value of the widget property. Two-way bindings read their values just like one-way bindings, but also update the value of the bound object/property with the new value from the widget from the UI.</p> 
<p>Some bindings have additional <b>binding options</b> that you can use to modify the values. Some of these options are specific to certain bindings. For instance, you can add an extra choice to select lists via binding options. Others are generic. The generic mechanism used to modify bindings is called a <b>Value Transformer</b> and can be added to any binding. Value Transformers are used to modify the value from the bound object/property without having to write additional code. Built-in value transformers are WFNegateBoolean, WFIsEmpty, WFIsNotEmpty. You can tell by their names that these can be convenient for using an existing model method with a binding that doesn't quite return exactly what you need, but with a Value Transformer, it's perfect, and you don't have to write any additional methods.</p>
<p>Bindings can also be <b>mutli-value</b>, which means that multiple sources can be combined to deliver a single value to the widget. Multi-value bindings are automatically read-only.</p>
<p>All of these topics are explained very well by the <a href="http://developer.apple.com/documentation/Cocoa/Conceptual/CocoaBindings/index.html" target="_blank">Cocoa documentation on bindings</a>.</p>

<p>The following examples show how the various binding types work, and how to use binding options to get additional functionality out of the bindings mechanism.</p>

<style type="text/css">
{literal}
.exampleBox {
    border: 1px solid black;
    margin: 10px; 
    padding: 0 10px;
}
{/literal}
</style>

<h2>Single-Value Binding</h2>
<p>Bind a single property of a widget to single property of another object.
<div class="exampleBox">
    <h3>Example: Single-Value Binding</h3>
    <h3>Output</h3>
    {WFLabel id="simpleBinding"}
    {literal}
    <h3>Template</h3>
    <pre>
    {WFLabel id="simpleBinding"}
    </pre>
    <h3>Config</h3>
    <pre>
	'simpleBinding' => array(
		'bindings' => array(
			'value' => array(
				'instanceID' => 'person',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'name',
			),
		),
	),
    </pre>
    {/literal}
</div>

<h2>Multiple Value Bindings</h2>
<p>Multiple value bindings work just like single-value bindings, but are read-only, and have the capability of combining multiple values into a single value via various methods explained below.</p>

<p>Using mutliple-bindings capabilities is very convenient. If the logic behind whether a widget should be hidden is complex and depends on multiple parameters, you can use mutliple values via "hidden", "hidden2", "hiddenN" instead of having to write a special function to wrap up this logic. This is very convenient because it prevents having to write special logic code in your model or controller classes just to determine whether a widget should be hidden, for example.</p>

<h3>Multiple Value Boolean Bindings</h3>
<p>For multiple-boolean bindings, there are two modes. AND mode and OR mode. The mode of the binding determines how the multiple values are combined into a single value.</p>
<p>AND mode bindings are true IFF all bound values are TRUE, otherwise they are FALSE. An example of this is the <b>enabled</b> binding.</p>
<p>OR mode bindings are true if ANY bound value is TRUE, and are only FALSE if ALL values are false. An example of this is the <b>hidden</b> binding.</p>

<div class="exampleBox">
    <h3>Example: Multiple Value Binding, but using only one value - works just like Single-Value Binding</h3>
    <h3>Output</h3>
    {WFLabel id="multOneOn"}<br />
    {WFLabel id="multOneOff"}(hidden WFLabel exists, but is hidden)<br />
    {literal}
    <h3>Template</h3>
    <pre>
    {WFLabel id="multOneOn"}
    {WFLabel id="multOneOff"}(hidden WFLabel exists, but is hidden)
    </pre>
    <h3>Config</h3>
    <pre>
	'multOneOn' => array(
		'properties' => array(
			'value' => 'This should be visible.',
		),
		'bindings' => array(
			'hidden' => array(
				'instanceID' => 'person',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'returnsFalse',
			),
		),
	),
	'multOneOff' => array(
		'properties' => array(
			'value' => 'This should be hidden.',
		),
		'bindings' => array(
			'hidden' => array(
				'instanceID' => 'person',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'returnsTrue',
			),
		),
	),
    </pre>
    {/literal}
</div>

<div class="exampleBox">
    <h3>Example: Hidden (OR mode) via Multiple Value Boolean Binding</h3>
    <p>In this example, two values are bound to the "hidden" property. You can see from this example that if either value is TRUE, the item will be hidden.</p>
    <h3>Output</h3>
    {WFLabel id="multMultHiddenOn"}<br />
    {WFLabel id="multMultHiddenOff"}(hidden WFLabel exists, but is hidden)<br />
    {literal}
    <h3>Template</h3>
    <pre>
    {WFLabel id="multMultHiddenOn"}
    {WFLabel id="multMultHiddenOff"}(hidden WFLabel exists, but is hidden)
    </pre>
    <h3>Config</h3>
    <pre>
	'multMultHiddenOn' => array(
		'properties' => array(
			'value' => 'This should be visible.',
		),
		'bindings' => array(
			'hidden' => array(
				'instanceID' => 'person',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'returnsFalse',
			),
			'hidden2' => array(
				'instanceID' => 'person',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'alsoReturnsFalse',
			),
		),
	),
	'multMultHiddenOff' => array(
		'properties' => array(
			'value' => 'This should be hidden.',
		),
		'bindings' => array(
			'hidden' => array(
				'instanceID' => 'person',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'returnsFalse',
			),
			'hidden2' => array(
				'instanceID' => 'person',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'returnsTrue',
			),
		),
	),
    </pre>
    {/literal}
</div>

<div class="exampleBox">
    <h3>Example: Enabled (AND mode) via Multiple Value Boolean Binding</h3>
    <p>In this example, two values are bound to the "enabled" property. You can see from this example that both values must be TRUE for the item to be enabled.</p>
    <h3>Output</h3>
    {WFTextField id="multMultEnabledOn"}<br />
    {WFTextField id="multMultEnabledOff"}<br />
    {literal}
    <h3>Template</h3>
    <pre>
    {WFTextField id="multMultEnabledOn"}
    {WFTextField id="multMultEnabledOff"}
    </pre>
    <h3>Config</h3>
    <pre>
	'multMultEnabledOn' => array(
		'bindings' => array(
			'value' => array(
				'instanceID' => 'person',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'name',
			),
			'enabled' => array(
				'instanceID' => 'person',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'returnsTrue',
			),
			'enabled2' => array(
				'instanceID' => 'person',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'alsoReturnsTrue',
			),
		),
	),
	'multMultEnabledOff' => array(
		'bindings' => array(
			'value' => array(
				'instanceID' => 'person',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'name',
			),
			'enabled' => array(
				'instanceID' => 'person',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'returnsTrue',
			),
			'enabled2' => array(
				'instanceID' => 'person',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'returnsFalse',
			),
		),
	),
    </pre>
    {/literal}
</div>

<div class="exampleBox">
    <h3>Example: Multiple Value Pattern Binding</h3>
    <p>Pattern bindings allow you to create strings from multiple data sources. This is convenient for strings like "&lt;name&gt; has &lt;count&gt; favorite colors."</p>
    <p>To enable mutliple-value pattern binding, simply set the binding option <b>ValuePattern</b> for the <b>value</b> binding to the desired format string. For each variable you want replaced, enter "%N%" where N goes from 1 to N. The "value" binding will be used for "%1%", the "value2" for "%2%", and on through N.</p>
    <p>You will notice another great PHOCOA Key-Value Coding feature here. Notice the "@count" in the keyPath for "value2". There are a number of "array" operators in Key-Value coding that perform calculations on arrays. These are well explained in the <a href="http://developer.apple.com/documentation/Cocoa/Conceptual/KeyValueCoding/Concepts/ArrayOperators.html" target="_blank">Cocoa docs</a>, and PHOCOA supports all of them.
    <h3>Output</h3>
    {WFLabel id="valuePattern"}<br />
    {literal}
    <h3>Template</h3>
    <pre>
    {WFLabel id="valuePattern"}
    </pre>
    <h3>Config</h3>
    <pre>
	'valuePattern' => array(
		'bindings' => array(
			'value' => array(
				'instanceID' => 'person',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'name',
				'options' => array(
					'ValuePattern' => '%1% has %2% favorite colors.',
				),
			),
			'value2' => array(
				'instanceID' => 'person',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'favoriteColors.@count',
			),
		),
	),
    </pre>
    {/literal}
</div>

<h2>Value Transformers</h2>
<p>Value Transformers are simple ways to munge data received from a binding before using the value. These are very useful in that they allow you to use existing model functions to help drive the UI without having to write any additional code.</p>
<div class="exampleBox">
    <h3>Example: Value Transformer</h3>
    <h3>Output</h3>
    {WFLabel id="hideIfEmpty"}<br />
    {WFLabel id="hideIfNotEmpty"}<br />
    {literal}
    <h3>Template</h3>
    <pre>
    {WFLabel id="hideIfEmpty"}
    {WFLabel id="hideIfNotEmpty"}
    </pre>
    <h3>Config</h3>
    <pre>
	'hideIfEmpty' => array(
		'properties' => array(
			'value' => 'You have already set your favorite colors.',
		),
		'bindings' => array(
			'hidden' => array(
				'instanceID' => 'person',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'favoriteColors',
				'options' => array(
					'valueTransformer' => 'WFIsEmpty',
				),
			),
		),
	),
	'hideIfNotEmpty' => array(
		'properties' => array(
			'value' => 'You have no favorite colors on file with us.',
		),
		'bindings' => array(
			'hidden' => array(
				'instanceID' => 'person',
				'controllerKey' => 'selection',
				'modelKeyPath' => 'returnsEmptyArray',
				'options' => array(
					'valueTransformer' => 'WFIsNotEmpty',
				),
			),
		),
	),
    </pre>
    {/literal}
</div>

