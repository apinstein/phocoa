{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}

{WFPaginatorPageInfo id="pageInfo"}<br />
{WFPaginatorState id="pageNav"}<br />
Sort: {WFView id="pageSort"}<br />
<br />

{section name=peeps loop=$people}
{WFDynamic id="personInfo"}<br />
{/section}

<hr>
<p>Pagination is quite simple with PHOCOA. The WFPaginator class provides an interface to paged, sorted data. The data access is delegated to a class implementing WFPagedData. PHOCOA ships with WFPagedData implementations to provide paging for PHP arrays, Creole queries, and Propel criteria-based queries. It takes only a few minutes to convert any list view to support paging and sorting. Typically you need add only 4-6 lines of code to your module and configure several widgets for the GUI. A variety of widgets are available that provide navigation, current page info, and sort links that provide user interface for the the pagination system. The pagination system is compatible with forms as well, so if the data you're accessing is the result of a form-based query, you can have the pagination system interface with your form (via javascript) to provide paging that works seamlessly with the results of a form submission.</p>
<p>This example uses MODE_URL, which is the non-form mode. <a href="{WFURL page="exampleWithForm}">Click here for a MODE_FORM example.</a></p>

{capture name="tplData"}
{literal}
{WFPaginatorPageInfo id="pageInfo"}<br />
{WFPaginatorState id="pageNav"}<br />
Sort: {WFView id="pageSort"}<br />
<br />

{section name=peeps loop=$people}
{WFDynamic id="personInfo"}<br />
{/section}
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
	'paginator' => 'WFPaginator',
	'people' => 'WFArrayController',
);
</pre>

<h3>shared.config File</h3>
<pre>
$__config = array(
	'paginator' => array(
		'properties' => array(
			'itemPhraseSingular' => 'Person',
			'itemPhrasePlural' => 'People',
			'pageSize' => '10',
		),
	),
	'people' => array(
		'properties' => array(
			'classIdentifiers' => 'id',
			'class' => 'Person',
		),
	),
);
</pre>

<h3>.instances File</h3>
<pre>
$__instances = array(
	'personInfo' => array('class' => 'WFDynamic', 'children' => array()),
	'pageSort' => array('class' => 'WFPaginatorSortLink', 'children' => array()),
	'pageInfo' => array('class' => 'WFPaginatorPageInfo', 'children' => array()),
	'pageNav' => array('class' => 'WFPaginatorNavigation', 'children' => array()),
);

<h3>.config File</h3>
<pre>
$__config = array(
	'personInfo' => array(
		'properties' => array(
			'arrayController' => '#module#people',
			'widgetClass' => 'WFLabel',
			'simpleBindKeyPath' => 'name',
		),
	),
	'pageSort' => array(
		'properties' => array(
			'value' => 'sort',
			'paginator' => '#module#paginator',
		),
	),
	'pageInfo' => array(
		'properties' => array(
			'paginator' => '#module#paginator',
		),
	),
	'pageNav' => array(
		'properties' => array(
			'paginator' => '#module#paginator',
		),
	),
);
</pre>

<h3>Module Code</h3>
<pre>

require_once 'framework/WFPagination.php';

class pagination extends WFModule
{
    protected $allPeople;
    protected $people;

    function defaultPage()
    {
        return 'example';
    }

    // Uncomment additional functions as needed
    function sharedInstancesDidLoad()
    {
        for ($i = 1; $i <= 100; $i++) {
            $person = new Person;
            $person->setValueForKey($i, "id");
            $person->setValueForKey("Johnny #{$i}", "name");
            $this->allPeople[] = $person;
        }
        $this->paginator->setSortOptions(array('+sort' => 'Ordered', '-sort' => 'Ordered'));
        $this->paginator->setDefaultSortKeys(array('+sort'));
    }

    function example_ParameterList()
    {
        return array('paginatorStateID');
    }
    function example_PageDidLoad($page, $params)
    {
        $this->paginator->setDataDelegate(new WFPagedArray($this->allPeople));
        $this->paginator->setPaginatorState($params['paginatorStateID']);
        $this->people->setContent($this->paginator->currentItems());
        $page->assign('people', $this->people->arrangedObjects());
    }
}
</pre>
{/literal}
