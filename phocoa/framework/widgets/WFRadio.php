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
 * A Radio Button widget for our framework.
 *
 * Radio buttons are a set of 1 or more items that are mutually exclusive. They are similar in logic to a popup menu where only one item can be selected.
 * Typically, NULL selection is not allowed.
 *
 * Unlike a popup menu, however, each choice is represented by a distinct radio button.
 *
 * For Phocoa, we implement this by creating whichever radio buttons are desired, and making them part of a {@link WFRadioGroup}, which acts as the interface
 * to the selected radio button.
 *
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * - {@link WFRadio::$selectedValue selectedValue} The value to use if this radio button is selected.
 * 
 * <b>Optional:</b><br>
 * - {@link WFRadio::$label label}
 *
 * NOTE: The "value" property is not really used for WFRadio... that instead is handled by the WFRadioGroup.
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
            if ($_REQUEST[$this->parent()->name()] == $this->selectedValue())
            {
                $this->parent()->setSelectedRadio($this);
            }
        }
    }

    /**
     * A WFRadio can only determine if it's the selected one after all of its config has been loaded.
     */
    function allConfigFinishedLoading()
    {
        if (!$this->parent()) throw( new Exception("All WFRadio's must be children of a WFRadioGroup.") );
        $this->parent()->checkRadioForDefault($this);
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
