<?php

require_once('testclass.php');

class examplemodule extends WFModule
{
    protected $person;
    protected $someChoices = array(1,2,3,4,5,6,7);

    function examplemodule($invocation)
    {
        parent::__construct($invocation);

        // can easily set skin parameters for ALL views in this module in the constructor
        $skin = WFRequestController::sharedSkin();
        $skin->addMetaKeywords(array('example', 'web', 'page'));
    }

    /**
      * Tell system which page to show if none specified.
      */
    function defaultPage() { return 'staticPage'; }

    /**
      * Actions for staticPage
      */
    function staticPage_action2_Action($requestPage)
    {
        // if this action is taken, simply show the resulting object in a non-editable form.
        // since we use bindings to populate the data in the UI, all we have to do here is tell the system which page to display as the response.
        $this->setupResponsePage('showPerson');
    }

    function staticPage_action1_Action($requestPage)
    {
        // no need to do anything! Form will be re-displayed with state preserved... of course, you'll want to do something probably.
        // this just demostrates the power of the framework to let you focus on YOUR code.
        // see action2 for an example of doing something for real.
    }

    /**
      * PageDidLoad callback for staticPage
      */
    function staticPage_PageDidLoad($page, $parameters)
    {
        $page->outlet('selectMultiple')->setContentValues(array(1,2,3,4,5,6));
    }

    function staticPage_ParameterList()
    {
        return array('itemID' ,'name');
    }

    function sharedInstancesDidLoad()
    {
        // initialize data used for dynamic fields
        $fields = array();
        for ($i = 0; $i < 10; $i++) {
            $fields[$i] = new Person;
            $fields[$i]->setValueForKey("Person: $i", "name");
        }
        $this->people->setContent($fields);
    }

    /**
      * Actions for allWidgets page
      */
    function allWidgets_action1_Action($requestPage) { /* noop */ }
    function allWidgets_showSelection_Action($requestPage)
    {
        $this->setupResponsePage('selectedPeople');
    }

    /**
      * PageDidLoad callback for allWidgets page
      */
    function allWidgets_PageDidLoad($page)
    {
        $page->outlet('selectOne')->setContentValues(array(1,2,3));
        $page->outlet('selectMultiple')->setContentValues(array(1,2,3,4,5,6));

        $config = array(
                'value' => array(
                    'bind' => array(
                        'instanceID' => '#current#',
                        'controllerKey' => '',
                        'modelKeyPath' => 'name'
                        )
                    )
                );
        $page->outlet('lotsOFields')->setWidgetConfig($config);

        // set up tabs
        $page->outlet('tabs')->addTab('stat_tab', 'Statically Declared Widgets', 'tab1.tpl');
        $page->outlet('tabs')->addTab('dyn_tab', 'Dynamically Declared Widgets', 'tab2.tpl');
    }

    function selectedPeople_PageDidLoad($page)
    {
        // set up selection manager -- this needs to be done here rather than the sharedInstancesDidLoad because we need the "selection" of the people arrayController to have been restored
        // before it will be meaningful here...
        $this->selectedPeople->setContent($this->people->selectedObjects());

        $page->assign('selectedPeople', $this->selectedPeople->arrangedObjects());
        $config = array(
                'value' => array(
                    'bind' => array(
                        'instanceID' => '#current#',
                        'controllerKey' => '',
                        'modelKeyPath' => 'name'
                        )
                    )
                );
        $page->outlet('selectedPeople')->setWidgetConfig($config);
    }

    function selectedPeople_SetupSkin($skin)
    {
        $skin->setTitle('View selected people.');
    }
}

?>
