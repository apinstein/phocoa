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
 */
class WFCheckbox extends WFWidget
{
    /**
     * @var boolean Whether or not the checkbox is checked.
     */
    protected $checked;
    /**
     * @var string The value of the checkbox field when it's checked.
     */
    protected $checkedValue;
    /**
     * @var string The value of the checkbox field when it's not checked.
     */
    protected $uncheckedValue;
    /**
     * @var boolean Whether or not to enable GROUP mode for the checkboxes. GROUP mode checkboxes allow multiple values to be set for the same variable.
     */
    protected $groupMode;
    /**
     * @var string The label to show next to the checkbox. This will be shown to the RIGHT of the checkbox, and will use the HTML <label> tag to link the checkbox to the label.
     */
    protected $label;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->checkedValue = 1;
        $this->uncheckedValue = 0;
        $this->setChecked(false);
        $this->groupMode = false;
        $this->label = '';
    }

    function setGroupMode($enabled)
    {
        $this->groupMode = $enabled;
    }
    function groupMode()
    {
        return $this->groupMode;
    }

    function setCheckedValue($v)
    {
        $this->checkedValue = $v;
    }
    function checkedValue()
    {
        return $this->checkedValue;
    }

    function setUncheckedValue($v)
    {
        $this->uncheckedValue = $v;
    }

    function checked()
    {
        return $this->checked;
    }

    function setChecked($checked)
    {
        if ($checked)
        {
            $this->checked = true;
            $this->value = $this->checkedValue;
        }
        else
        {
            $this->checked = false;
            $this->value = $this->uncheckedValue;
        }
    }

    function label()
    {
        return $this->label;
    }

    function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * Set the value of the checkbox control. What this does basically, is that if the passed value is the same as the checkedValue, then
     * the checkbox will become checked. Otherwise, it will become unchecked.
     * @param mixed The value to set for the control.
     */
    function setValue($v)
    {
        if ($v == $this->checkedValue)
        {
            $this->setChecked(true);
        }
        else if ($v == $this->uncheckedValue)
        {
            $this->setChecked(false);
        }
        else
        {
            WFLog::log('Warning!!! Checkbox ID ' . $this->id . " checked state not restored because passed value '{$v}' is not equal to checked ({$this->checkedValue}) or unchecked ({$this->uncheckedValue}) value.");
        }
    }

    function restoreState()
    {
        //  must call super
        parent::restoreState();

        if ($this->groupMode) {
            if (isset($_REQUEST[$this->name]))
            {
                if (in_array($this->checkedValue, $_REQUEST[$this->name]))
                {
                    $this->setChecked(true);
                }
                else
                {
                    $this->setChecked(false);
                }
            }
        }
        else
        {
            if (isset($_REQUEST[$this->name]))
            {
                $this->setValue($_REQUEST[$this->name]);
            }
            else
            {
                $this->setChecked(false);
            }
        }
    }

    function setupExposedBindings()
    {
        $myBindings = parent::setupExposedBindings();
        $myBindings[] = new WFBindingSetup('value', 'The value of the checkbox -- this will be either checkedValue or uncheckedValue depending on the checked status.');
        $myBindings[] = new WFBindingSetup('checkedValue', 'The value of the checkbox to use if it is checked.');
        $myBindings[] = new WFBindingSetup('uncheckedValue', 'The value of the checkbox to use if it is not checked.');
        return $myBindings;
    }

    function render($blockContent = NULL)
    {
        if ($this->hidden) return NULL;

        // get there reference to the named item
        // set the name / value
        // render
        $checked = '';
        if ($this->checked())
        {
            $checked = ' checked ';
        }
        return '<input type="checkbox" ' .
                    'name="' . $this->name() . ($this->groupMode() ? '[]' : '') . '" ' .
                    'id="' . $this->id() . '" ' .
                    'value="' . $this->checkedValue() . '" ' .
                    ($this->checked() ? ' checked="checked" ' : '') .
                    ' />' . 
                    ($this->label() !== '' ? " <label for=\"{$this->id}\">{$this->label}</label>" : '')
                    ;
     
    }

    function canPushValueBinding() { return true; }
}
?>
