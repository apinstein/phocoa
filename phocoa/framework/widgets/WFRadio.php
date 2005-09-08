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
 * A Checkbox widget for our framework.
 *
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * - {@link WFRadioGroup::$value Values} of radio controls are managed through WFRadioGroup
 * 
 * <b>Optional:</b><br>
 * - {@link WFRadio::$selectedValue selectedValue}
 * - {@link WFRadio::$label label}
 */
class WFRadio extends WFWidget
{
    /**
     * @var boolean Whether or not the radio is selected.
     */
    protected $selected;
    /**
     * @var string The value of the radio field when it's selected.
     */
    protected $selectedValue;
    /**
     * @var string The label to show next to the radio. This will be shown to the RIGHT of the radio, and will use the HTML <label> tag to link the radio to the label.
     */
    protected $label;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->setSelectedValue($id);
        $this->setSelected(false);
        $this->setLabel('');
    }

    function setSelectedValue($v)
    {
        $this->selectedValue = $v;
    }
    function selectedValue()
    {
        return $this->selectedValue;
    }

    function selected()
    {
        return $this->selected;
    }

    function setSelected($selected)
    {
        $this->selected = $selected;
    }

    function label()
    {
        return $this->label;
    }

    function setLabel($label)
    {
        $this->label = $label;
    }

    function setupExposedBindings()
    {
        $myBindings = parent::setupExposedBindings();
        $myBindings[] = new WFBindingSetup('label', 'The label for the radio -- Text to label the radio with, or empty.');
        $myBindings[] = new WFBindingSetup('selectedValue', 'The value of the radio to use if it is selected.');
        return $myBindings;
    }

    function restoreState()
    {
        //  must call super
        parent::restoreState();

        if (isset($_REQUEST[$this->parent()->name()]))
        {
            WFLog::log("Restoring state of {$this->id} (" . $this->selectedValue() . ")... " . $_REQUEST[$this->parent()->name()] . "<br>\n");
            if ($_REQUEST[$this->parent()->name()] == $this->selectedValue())
            {
                WFLog::log( "State match! {$this->id} == " . $_REQUEST[$this->parent()->name()] . "<br>" );
                $this->setSelected(true);
            }
        }
    }

    function render($blockContent = NULL)
    {
        if ($this->hidden) return NULL;
        if (is_null($this->parent()) or !($this->parent() instanceof WFRadioGroup)) throw( new Exception("Radio item '{$this->id}' does not have a parent. This is illegal. Create a WFRadioGroup for the WFRadios.") );

        // get there reference to the named item
        // set the name / value
        // render
        return '<input type="radio" ' .
                    'name="' . $this->parent()->name() . '" ' .
                    'id="' . $this->id() . '" ' .
                    'value="' . $this->selectedValue() . '" ' .
                    ($this->selected() ? ' checked="checked" ' : '') .
                    ' />' . 
                    ($this->label() !== '' ? " <label for=\"{$this->id}\">{$this->label}</label>" : '')
                    ;
     
    }

    function canPushValueBinding() { return false; }
}
?>
