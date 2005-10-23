<?php

require_once('exampleClasses.php');

class widgets extends WFModule
{
    protected $person;
    protected $cities;

    function __construct($invocation)
    {
        parent::__construct($invocation);

        // can easily set skin parameters for ALL views in this module in the constructor
        $skin = WFRequestController::sharedSkin();
        $skin->setTitle('PHOCOA Examples');
        $skin->addMetaKeywords(array('example', 'widgets', 'phocoa'));
    }

    /**
      * Tell system which page to show if none specified.
      */
    function defaultPage() { return 'toc'; }

    function sharedInstancesDidLoad()
    {
        $this->person->setContent(Person::personByID(1));
        $this->cities->setContent(City::allCities());
    }

    /**
      * Actions for simpleForm
      */
    function simpleForm_action1_Action($page)
    {
        // no need to do anything! Form will be re-displayed with state preserved... of course, you'll want to do something probably.
        // this just demostrates the power of the framework to let you focus on YOUR code.
        // see action2 for an example of doing something for real.
        $page->assign('personData', print_r($this->person->selection(), true));
    }

    /**
      * PageDidLoad callback for simpleForm
      */
    function simpleForm_PageDidLoad($page, $parameters)
    {
        $page->assign('personData', print_r($this->person->selection(), true));
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
}

?>
