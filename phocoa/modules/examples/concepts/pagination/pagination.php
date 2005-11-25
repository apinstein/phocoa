<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

require_once APP_ROOT . '/modules/examples/widgets/exampleClasses.php';
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
        return array('paginatorState');
    }
    function example_PageDidLoad($page, $params)
    {
        $this->paginator->setDataDelegate(new WFPagedArray($this->allPeople));
        $this->paginator->readPaginatorStateFromParams($params);
        $this->people->setContent($this->paginator->currentItems());
        $page->assign('people', $this->people->arrangedObjects());
    }

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
}
?>
