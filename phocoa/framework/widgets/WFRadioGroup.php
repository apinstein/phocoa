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
 * If you want to use WFRadio's in your page, they must be contained by a WFRadioGroup.
 *
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * - {@link WFWidget::$value value} The value of the WFRadio that should be selected.
 */
class WFRadioGroup extends WFWidget
{
    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
    }

    function setupExposedBindings()
    {
        $myBindings = parent::setupExposedBindings();
        $myBindings[] = new WFBindingSetup('value', 'The value of the WFRadio that should be selected.');
        return $myBindings;
    }

    /**
     *  Set which radio in this group is selected.
     *
     *  The way this works is to unselect ALL managed WFRadio's, and just select the one whose {@link WFRadio::$selectedValue selectedValue} matches the passed value.
     *
     *  @param mixed The value of the WFRadio (selectedValue) which should be selected.
     */
    function setValue($val)
    {
        parent::setValue($val);
        $val = $this->value;    // this is now the formatted value

        WFLog::log( "Setting radio value to: $val<br>");
        foreach ($this->children() as $radio) {
            $radio->setSelected(false);
            if ($radio->selectedValue() == $val)
            {
                $radio->setSelected(true);
            }
        }
    }
    
    /**
     *  Update the value of the selected radio button managed by this radio group.
     *
     *  The value for the RadioGroup is the selectedValue of the only selected radio, or NULL if there is no selected radio.
     */
    function updateGroupValue()
    {
        $value = NULL;

        WFLog::log( "getting radio group value... managed radios: " . count($this->children()) . '<br>' );
        foreach ($this->children() as $radio) {
            WFLog::log( "Checking if " . $radio->id() . " is selected: " . ( $radio->selected() ? 'YES' : 'NO') );
            if ($radio->selected())
            {
                $value = $radio->selectedValue();
                break;
            }
        }
        $this->value = $value;
    }

    function restoreState()
    {
        parent::restoreState();

        WFLog::log("RadioGroup restoreState()<bR>");
        // restore state of all children
        foreach ($this->children() as $radio) {
            $radio->restoreState();
        }
        // rebuild our meta-value
        $this->updateGroupValue();
        WFLog::log("RadioGroup restoreState() done, value now:" . $this->value . "<bR>");
    }

    function render($blockContent = NULL)
    {
        return "\n<!-- Radio Group Manager -->\n";
    }

    function canPushValueBinding() { return true; }

}

?>
