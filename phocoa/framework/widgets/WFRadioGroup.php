<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package UI
 * @subpackage Widgets
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/**
 * Includes
 */
require_once('framework/widgets/WFWidget.php');

/**
 * The WFRadioGroup is the "interface" object used to interact with a set of WFRadio widgets.
 * 
 * The WFRadioGroup provides a single object that you can interface with to manage a set of Radio buttons. 
 * To bind an object to a set of radio buttons, bind them to the WFRadioGroup's value, and then specify {@link WFRadio WFRadio's} as choices.
 * If you want to use WFDynamic to create the list of WFRadio choices, create a WFRadioGroup and a WFDynamic. Provide the ID of the WFRadioGroup as the parentFormID of the WFDynamic.
 *
 * If you want to use WFRadio's in your page, they must be contained by a WFRadioGroup. That is, all of the WFRadio's that are a part of the WFRadioGroup should be set up as children of the WFRadioGroup.
 *
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * - {@link WFWidget::$value value} The value of the WFRadio that should be selected.
 */
class WFRadioGroup extends WFWidget
{
    /**
     * @var object WFRadio A reference to the selected WFRadio.
     */
    private $selectedRadio;
    /**
     * @var mixed The default value for the radio group.
     */
    private $defaultValue;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->selectedRadio = NULL;
        $this->defaultValue = NULL;
    }

    function setupExposedBindings()
    {
        $myBindings = parent::setupExposedBindings();
        $myBindings[] = new WFBindingSetup('value', 'The value of the WFRadio that should be selected.');
        return $myBindings;
    }

    /**
     *  Check the passed radio to see if it should be selected.
     *
     *  The WFRadios call this from allConfigFinishedLoading so that the WFRadioGroup can make sure that the correct WFRadio is selected by default.
     *  We have to do this to support WFDynamic, since sometimes the radios don't exist when WFRadioGroup's allConfigFinishedLoading() would be called.
     *
     *  @param object WFRadio The calling WFRadio instance.
     */
    function checkRadioForDefault($radio)
    {
        // make sure default value is selected
        if (!is_null($this->defaultValue) and $radio->selectedValue() == $this->defaultValue)
        {
            $this->setSelectedRadio($radio);
        }
    }

    function setValue($val)
    {
        // we are using the assumption that the first "setValue" call will be the value that is desired as the default value. Sometimes,
        // the setValue call happens before the kids exist, so we need to remember the default value so we can select the approproate kid as the default
        // as they are added later on, see WFRadio::allConfigFinishedLoading()
        if (is_null($this->defaultValue))
        {
            $this->defaultValue = $val;
        }
        parent::setValue($val);
        foreach ($this->children() as $id => $radio) {
            if ($radio->selectedValue() == $this->value)
            {
                $this->setSelectedRadio($radio);
                break;
            }
        }
    }

    function setSelectedRadio(WFRadio $radio)
    {
        //WFLog::log( "setSelectedRadio: to " . $radio->id() . " value: " . $radio->selectedValue() );
        if ($this->selectedRadio)
        {
            $this->selectedRadio->setSelected(false);
        }
        $this->selectedRadio = $radio;
        $this->value = $this->selectedRadio->selectedValue();
        $this->selectedRadio->setSelected(true);
    }
    function selectedRadio()
    {
        return $this->selectedRadio;
    }

    function addChild($newChild)
    {
        parent::addChild($newChild);
        // make sure one child is always selected
        if (!$this->selectedRadio)
        {
            $this->setSelectedRadio($newChild);
        }
    }

    function restoreState()
    {
        parent::restoreState();

        // restore state of all children
        foreach ($this->children() as $radio) {
            $radio->restoreState();
        }
    }

    function render($blockContent = NULL)
    {
        return "\n<!-- Radio Group Manager -->\n";
    }

    function canPushValueBinding() { return true; }

}

?>
