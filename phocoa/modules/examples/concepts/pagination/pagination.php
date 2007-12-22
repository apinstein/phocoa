<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

class pagination extends WFModule
{
    protected $allPeople;
    protected $people;

    function __construct($inv)
    {
        parent::__construct($inv);
        $path = $this->pathToModule() . '/../../widgets/exampleClasses.php';
        require $path;
    }

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
        return array('paginatorState');
    }
    function example_PageDidLoad($page, $params)
    {
        $this->paginator->setDataDelegate(new WFPagedArray($this->allPeople));
        $this->paginator->readPaginatorStateFromParams($params);
        $this->people->setContent($this->paginator->currentItems());
        $page->assign('people', $this->people->arrangedObjects());
    }
    function example_SetupSkin($skin)
    {
        $skin->setMetaDescription('PHOCOA Pagination: Built-in paginator with automatic pagination and sorting, including data that changes based on form submission.');
    }

    function exampleWithForm_ParameterList()
    {
        return array('paginatorState');
    }
    function exampleWithForm_PageDidLoad($page, $params)
    {
        $this->paginator->setModeForm('submit');

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
}
?>
