<?php

require_once('testclass.php');

class examplemodule extends WFModule
{
    protected $person;
    protected $people;
    protected $someChoices = array(1,2,3,4,5,6,7);
    protected $checkboxGroupDefaultSelections = array(1,3);

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
        for ($i = 100; $i < 110; $i++) {
            $fields[$i] = new Person;
            $fields[$i]->setValueForKey("Johnny #$i", "name");
            $fields[$i]->setValueForKey("$i", "id");
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

        // set up tabs
        $page->outlet('tabs')->addTab('stat_tab', 'Statically Declared Widgets', 'tab1.tpl');
        $page->outlet('tabs')->addTab('dyn_tab', 'Dynamically Declared Widgets', 'tab2.tpl');

        $page->assign('itemCount', $this->people->arrangedObjectCount());
    }

    function selectedPeople_PageDidLoad($page)
    {
        // set up selection manager -- this needs to be done here rather than the sharedInstancesDidLoad because we need the "selection" of the people arrayController to have been restored
        // before it will be meaningful here...
        $this->selectedPeople->setContent($this->people->selectedObjects());

        $page->assign('selectedPeople', $this->selectedPeople->arrangedObjects());
    }

    function selectedPeople_SetupSkin($skin)
    {
        $skin->setTitle('View selected people.');
    }

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

    function radios_submit_Action($page)
    {
        $page->assign('selectedValue', $page->outlet('radioGroup')->value());
    }
}

?>
