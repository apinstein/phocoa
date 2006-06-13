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
 * The WFCheckboxGroup is the "interface" object used to interact with a set of WFCheckbox widgets.
 *
 * When setting up a WFCheckboxGroup, all of the WFCheckboxes that are part of the group should be set up as children of the WFCheckboxGroup.
 * 
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Optional:</b><br>
 * - {@link WFCheckboxGroup::$values values} The values of the selected WFCheckboxes. Remember these should match the {@link WFCheckbox::$checkedValue checkedValue} of the checkboxes. Also note that arrays cannot be set up in PHOCOA Builder at this time, so you'll have to set up an array in the module and set the values to the module's var:
 * <code>
 *     protected $checkboxGroupDefaultSelections = array(1,3);
 * </code>
 * In PHOCOA builder, set the value of the "values" property to "#module#checkboxGroupDefaultSelections".
 
 * @todo UPDATE with WFDynamic support a la WFRadioGroup. Actuallym look at both methods (WFSelectionCheckbox vs. WFRadioGroup+WFDynamic) and see if there's any real difference.
 * @todo HMM.. I think WFCheckboxGroup already works just like WFRadioGroup.... need to check it out and document.
 *       Make the interface consistent for both widget types.
 */
class WFCheckboxGroup extends WFWidget
{
    /**
     * @var array A list of all selected {@link WFCheckbox::$checkedValue checkedValue's} of the managed WFCheckboxes.
     */
    protected $values;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->values = array();
    }

    function setupExposedBindings()
    {
        $myBindings = parent::setupExposedBindings();
        $myBindings[] = new WFBindingSetup('values', 'The values of the WFCheckboxes that should be selected.');
        return $myBindings;
    }

    /**
     *  Set which checkbox in this group is selected.
     *
     *  The way this works is to unselect ALL managed WFCheckbox's, and just select the one whose {@link WFCheckbox::$selectedValue selectedValue} matches the passed value.
     *
     *  @param mixed The value of the WFCheckbox (selectedValue) which should be selected.
     */
    function setValues($vals)
    {
        if (!is_array($vals)) throw( new Exception("setValues parameter must be an array.") );
        
        $this->values = $vals;

        foreach ($this->children() as $checkbox) {
            if (in_array($checkbox->checkedValue(), $this->values))
            {
                $checkbox->setChecked(true);
            }
            else
            {
                $checkbox->setChecked(false);
            }
        }
    }
    
    /**
     *  Update the value of the selected checkboxes managed by this checkbox group, based on the values of the child WFCheckboxs.
     *
     *  The value for the WFCheckboxGroup is an array of the checkedValue of the all selected checkboxes, or an empty array if there is are none.
     */
    function updateGroupValues()
    {
        $values = array();

        WFLog::log( "getting checkbox group values... managed checkboxes: " . count($this->children()));
        foreach ($this->children() as $checkbox) {
            WFLog::log( "Checking if " . $checkbox->id() . " is checked: " . ( $checkbox->checked() ? 'YES' : 'NO') );
            if ($checkbox->checked())
            {
                $values[] = $checkbox->checkedValue();
            }
        }
        $this->values = $values;
    }

    /**
     * Update the selected state of all child WFCheckboxs... select those that match the WFCheckboxGroup's values.
     */
    function updateSelectedCheckboxes()
    {
        $this->setValues($this->values);
    }

    function allConfigFinishedLoading()
    {
        $this->updateSelectedCheckboxes();
    }

    function restoreState()
    {
        parent::restoreState();

        WFLog::log("WFCheckboxGroup restoreState()<bR>");
        // restore state of all children
        foreach ($this->children() as $checkbox) {
            $checkbox->restoreState();
        }
        // rebuild our meta-value
        $this->updateGroupValues();
        WFLog::log("WFCheckboxGroup restoreState() done, value now:" . $this->values);
    }

    function render($blockContent = NULL)
    {
        return "\n<!-- Checkbox Group Manager -->\n";
    }

    function canPushValueBinding() { return false; }

    function pushBindings()
    {
        // our only bindable property that should be propagated back is VALUES.
        // use propagateValueToBinding() to call validator and propagate new values to binding.
        $cleanValue = $this->propagateValueToBinding('values', $this->values);
        // update UI to cleaned-up value
        $this->setValues($cleanValue);
    }
}

?>
