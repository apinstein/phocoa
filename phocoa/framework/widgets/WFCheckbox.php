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
 * A Checkbox widget for our framework.
 *
 * There are several ways to use checkboxes in your application, depending on the way you want your checkboxes to behave and interact with your data.
 *
 * 1. A single checkbox representing whether a certain option is "on" or "off".
 *    Simply use a WFCheckbox in your application. The {@link WFWidget::$value value} of the WFCheckbox is {@link WFCheckbox::$checkedValue checkedValue} or {@link WFCheckbox::$uncheckedValue uncheckedValue}, depending on the checkbox's state.
 * 2. Multiple checkboxes representing multiple "states" that should be enabled for a single property. <br>
 *    a. If you want to set up the checkboxes statically in .instances/.config, use a {@link WFCheckboxGroup} in conjuction with multiple WFCheckbox widgets.<br>
 *       When using this mode, the {@link WFCheckboxGroup::$values values} property is an array containing the {@link WFCheckbox::$checkedValue checkedValue} of each selected checkbox. With WFCheckboxGroup, the WFCheckbox uncheckedValue's are not used.<br><br>
 *    b. If you want to set up the checkboxes based on the objects in an array, use a {@link WFSelectionCheckbox} control and link it to an array of objects.<br>
 *       When using this mode, which uses {@link WFArrayController}, the array controller's selected objects will be updated to reflect the state of the checkboxes.
 *
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * - (none)
 * 
 * <b>Optional:</b><br>
 * - {@link WFWidget::$value value}
 * - {@link WFCheckbox::$checked checked}
 * - {@link WFCheckbox::$checkedValue checkedValue}
 * - {@link WFCheckbox::$uncheckedValue uncheckedValue}
 * - {@link WFCheckbox::$groupMode groupMode}
 * - {@link WFCheckbox::$label label}
 * - {@link WFCheckbox::$labelPosition labelPosition}
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
     * @var string The position of the labels in HTML. 'left' will put the <label> first; 'right' will put it after the checkbox.
     */
    protected $labelPosition;

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
        $this->labelPosition = 'right';
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
        $this->hasRestoredState = false;
    }
    function checkedValue()
    {
        return $this->checkedValue;
    }

    function setUncheckedValue($v)
    {
        $this->uncheckedValue = $v;
        $this->hasRestoredState = false;
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

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            'checked' => array('true', 'false'),
            'checkedValue',
            'uncheckedValue',
            'groupMode' => array('true', 'false'),
            'label',
            'labelPosition' => array('left', 'right'),
            ));
    }

    function setupExposedBindings()
    {
        $myBindings = parent::setupExposedBindings();
        $myBindings[] = new WFBindingSetup('value', 'The value of the checkbox -- this will be either checkedValue or uncheckedValue depending on the checked status.');
        $myBindings[] = new WFBindingSetup('label', 'The label for the checkbox -- Text to label the checkbox with, or empty.');
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
        $labelLeft = $labelRight = ($this->label() !== '' ? " <label for=\"{$this->id}\">{$this->label}</label>" : '');
        if ($this->labelPosition === 'right')
        {
            $labelLeft = NULL;
        }
        else
        {
            $labelRight = NULL;
        }
        return $labelLeft . '<input type="checkbox" ' .
                    'name="' . $this->name() . ($this->groupMode() ? '[]' : '') . '" ' .
                    ($this->class ? ' class="' . $this->class . '" ' : '') .
                    'id="' . $this->id() . '" ' .
                    'value="' . $this->checkedValue() . '" ' .
                    ($this->checked() ? ' checked="checked" ' : '') .
                    ($this->enabled() ? '' : ' disabled readonly ') .
                    ($this->tabIndex ? ' tabIndex="' . $this->tabIndex . '" ' : NULL) .
                    ' />' . 
                    $labelRight .
                    $this->getListenerJSInScriptTag();
     
    }

    function canPushValueBinding() { return true; }
}
?>
