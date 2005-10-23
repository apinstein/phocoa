{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
<p>This is the shared setup for all of the widget examples.</p>
<p>A brief bit of explanation on bindings... throughout the examples you will see the "controllerKey" set up to "selection" for the person object, and "arrangedObjects" for the cities object. The normative way of building PHOCOA pages that use bindings is to set up object controllers as shared instances for each part of your data model being used on the page. Object controllers are PHOCOA framework objects that are simply wrappers for different types of data. Presently there are two: WFObjectController and WFArrayController.</p>
<p>The reason that object controllers are used is to provide a level of indirection between the UI and the object. While you could directly instantiate your object as a shared instance and bind to it directly, this actually makes things more difficult when working with dynamic data. The reason is that you typically have to load your data from a database or some external source, and if the object is already instantiated, and you need to change the object, the UI would be bound to the wrong object! So, by using an object controller and binding to the data THROUGH the object controller, you can change the object managed by the object controller at any time and the system will still work as expected.</p>
<p>The "controllerKey" you see specified in the binding config simlpy specifies the way which you get at your object through the object controller. For WFObjectController, selection() and content() both provide access to the managed object, so you will see the "selection" controllerKey used. For WFArrayController, arrangedObjects() provides the array of managed objects, so you will see "arrangedObjects" as the controller key.</p>
<p>In our example below, you can see that we've set up a WFObjectController to manage our person object, and a WFArrayController to manage our list of city objects. Then you can see in the sharedInstancesDidLoad method of the module that we set the content of the object controllers.</p>

<h3>shared.instances</h3>
<pre>
{literal}
$__instances = array(
	'creationDTSFormatter' => 'WFUnixDateFormatter',
	'selectedPeople' => 'WFArrayController',
	'cities' => 'WFArrayController',
	'person' => 'WFObjectController',
);
{/literal}
</pre>
<h3>shared.config</h3>
<pre>
{literal}
$__config = array(
	'creationDTSFormatter' => array(
		'properties' => array(
			'formatString' => 'Y M D',
		),
	),
	'selectedPeople' => array(
		'properties' => array(
			'class' => 'person',
		),
	),
	'cities' => array(
		'properties' => array(
			'classIdentifiers' => 'id',
			'class' => 'City',
		),
	),
	'person' => array(
		'properties' => array(
			'class' => 'Person',
		),
	),
);
{/literal}
</pre>
<h3>Module code</h3>
<pre>
{literal}
    function sharedInstancesDidLoad()
    {
        $this->person->setContent(Person::personByID(1));
        $this->cities->setContent(City::allCities());
    }
{/literal}
</pre>
