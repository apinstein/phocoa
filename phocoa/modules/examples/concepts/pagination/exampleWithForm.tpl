{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}

{WFForm id="form"}
    {WFPaginatorState id="paginatorState"}
    Select the number of people in the data set: {WFSelect id="numPeople"} {WFSubmit id="submit"}
{/WFForm}

<hr>

{WFPaginatorPageInfo id="pageInfo"}<br />
{WFPaginatorState id="pageNav"}<br />
Sort: {WFView id="pageSort"}<br />
<br />

{section name=peeps loop=$people}
{WFDynamic id="personInfo"}<br />
{/section}

<hr>
<p>This example uses MODE_FORM, which is the mode that provides pagination for data returned as the result of a form submission. <a href="{WFURL page="example}">Click here for a MODE_URL example.</a></p>
<p>This example demonstrates the PHOCOA pagination infrastructure interacting with a form. Try changing the number of items in the data set to see how the pagination infrastructure automatically handles various situations.</p>
<p>One particularly intersting thing that it does it support the notion of plural and singular forms. Try setting the data set size to 1 and see how the language changes. The language also updates automatically when the page size is set to 1.</p>

{capture name="tplData"}
{literal}
{WFForm id="form"}
    {WFPaginatorState id="paginatorState"}
    Select the number of people in the data set: {WFSelect id="numPeople"} {WFSubmit id="submit"}
{/WFForm}

<hr>

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
    'pageSort' => array('class' => 'WFPaginatorSortLink', 'children' => array()),
    'pageNav' => array('class' => 'WFPaginatorNavigation', 'children' => array()),
    'pageInfo' => array('class' => 'WFPaginatorPageInfo', 'children' => array()),
    'personInfo' => array('class' => 'WFDynamic', 'children' => array()),
    'form' => array('class' => 'WFForm', 'children' => array(
        'paginatorState' => array('class' => 'WFPaginatorState', 'children' => array()),
        'numPeople' => array('class' => 'WFSelect', 'children' => array()),
        'submit' => array('class' => 'WFSubmit', 'children' => array()),)
    ),
);
</pre>

<h3>.config File</h3>
<pre>
$__config = array(
	'pageSort' => array(
		'properties' => array(
			'paginator' => '#module#paginator',
			'value' => 'sort',
		),
	),
	'pageNav' => array(
		'properties' => array(
			'paginator' => '#module#paginator',
		),
	),
	'pageInfo' => array(
		'properties' => array(
			'paginator' => '#module#paginator',
		),
	),
	'personInfo' => array(
		'properties' => array(
			'simpleBindKeyPath' => 'name',
			'arrayController' => '#module#people',
			'widgetClass' => 'WFLabel',
		),
	),
	'paginatorState' => array(
		'properties' => array(
			'paginator' => '#module#paginator',
		),
	),
	'numPeople' => array(
		'properties' => array(
			'value' => '100',
		),
	),
);
</pre>

<h3>Module Code</h3>
<p>Note that the module code for this example is exactly the same as the other pagination example, but with this code added:</p>
<pre>
    function exampleWithForm_ParameterList()
    {
        return array('paginatorState');
    }
    function exampleWithForm_PageDidLoad($page, $params)
    {
        $this->paginator->enableModeForm('submit');

        $page->outlet('numPeople')->setContentValues(array(0,1,10,100));
        $somePeople = array_slice($this->allPeople, 0, $page->outlet('numPeople')->value());

        $this->paginator->setDataDelegate(new WFPagedArray($somePeople));
        $this->paginator->readPaginatorStateFromParams($params);

        $this->people->setContent($this->paginator->currentItems());
        $page->assign('people', $this->people->arrangedObjects());
    }
    function exampleWithForm_submit_Action($page)
    {
        // no-op
    }
</pre>
{/literal}
