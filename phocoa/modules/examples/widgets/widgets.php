<?php

require_once('exampleClasses.php');

class module_widgets extends WFModule
{
    protected $person;
    protected $cities;
    protected $creationDTSFormatter;
    protected $selectedPeople;

    function __construct($invocation)
    {
        parent::__construct($invocation);

        // can easily set skin parameters for ALL views in this module in the constructor
        $skin = $this->invocation()->rootSkin();
        if ($skin)
        {
            $skin->setTitle('PHOCOA Examples');
            $skin->addMetaKeywords(array('example', 'widgets', 'phocoa'));
        }
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

class module_widgets_form
{
    function normalButton1($page, $params)
    {
        $page->assign('normalButtonPressed', 'First button');
    }
    function normalButton2($page, $params)
    {
        $page->assign('normalButtonPressed', 'Second button');
    }
    function normalButton3($page, $params)
    {
        $page->assign('normalButtonPressed', 'Third button');
    }

    function ajaxButton1($page, $params)
    {
        $page->assign('ajaxButtonPressed', 'First button');
        if (WFRequestController::sharedRequestController()->isAjax())
        {
            sleep(1);   // kill some time so viewers can see the postSubmitLabel working...
            return WFActionResponsePhocoaUIUpdater::WFActionResponsePhocoaUIUpdater()
                                                        ->addUpdateHTML('ajaxButtonPressed', 'First Button')
                                                        ->addRunScript("$('ajaxButton1').setValue('" . $page->outlet('ajaxButton1')->label() . "');");
        }
    }
    function ajaxButton2($page, $params)
    {
        $page->assign('ajaxButtonPressed', 'Second button');
        if (WFRequestController::sharedRequestController()->isAjax())
        {
            sleep(1);   // kill some time so viewers can see the postSubmitLabel working...
            return WFActionResponsePhocoaUIUpdater::WFActionResponsePhocoaUIUpdater()
                                                        ->addUpdateHTML('ajaxButtonPressed', 'Second Button')
                                                        ->addRunScript("$('ajaxButton2').setValue('" . $page->outlet('ajaxButton2')->label() . "');");
        }
    }
    function ajaxButton3($page, $params)
    {
        $page->assign('ajaxButtonPressed', 'Third button');
        if (WFRequestController::sharedRequestController()->isAjax())
        {
            return WFActionResponsePhocoaUIUpdater::WFActionResponsePhocoaUIUpdater()->addUpdateHTML('ajaxButtonPressed', 'Third Button');
        }
    }
}
?>
