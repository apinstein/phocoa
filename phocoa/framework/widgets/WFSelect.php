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
 * A Select widget for our framework.
 *
 * Used to select either a single, or multiple, values.
 *
 * <b>PHOCOA Builder Setup:</b>
 *
 * Properties:
 * - {@link WFSelect::$width width}
 * - {@link WFSelect::$multiple multiple}
 * - {@link WFSelect::$visibleItems visibleItems}
 * - {@link WFSelect::$labelFormatter labelFormatter}
 * - {@link WFSelect::$labelFormatterSkipFirst labelFormatterSkipFirst}
 * - {@link WFSelect::$strictValueTyping strictValueTyping}
 *
 * Bindings:
 * <b>Required:</b><br>
 * - {@link WFWidget::$value value} or {@link WFSelect::$values values}, depending on {@link WFSelect::$multiple multiple}.
 * 
 * <b>Optional:</b><br>
 * - {@link WFSelect::$contentValues contentValues} Set the values for each option specified in contentLabels.
 *  <pre>Binding Options:
 *  InsertsNullPlaceholder - boolean, true to insert an item for "NULL" value at the top of the list.
 *  NullPlaceholder - string, the value of the "Null" placeholder item.
 *  </pre>
 * 
 * - {@link WFSelect::$contentLabels contentLabels} Set the labels for each option specified in contentValues.
 *  <pre>Binding Options:
 *   InsertsNullPlaceholder - boolean, true to insert an item for "NULL" value at the top of the list.
 *   NullPlaceholder - string, the label of the "Null" placeholder item.
 *   ValuePattern - supports ValuePattern binding
 *  </pre>
 * 
 * - {@link WFSelect::setOptions() options} Set both contentValues and contentLabels at the same time, from an associative array of format 'value' => 'label'.
 *  <pre>Binding Options:
 *   InsertsNullPlaceholder - boolean, true to insert an item for "NULL" value at the top of the list.
 *   NullPlaceholder - string, the label of the "Null" placeholder item.
 *  </pre>
 *
 *  @todo Add support for OPTGROUP. I think to do this we'll need a separate way to do values/labels/options for OPTGROUP capable.
 */
class WFSelect extends WFWidget
{
    /**
     * @var array The selected values of the select list, IF multiple is enabled.
     */
    protected $values;
    /**
     * @var boolean Allow multiple selection?
     */
    protected $multiple;
    /**
     * @var array The items to allow user to select. These are the "values" that are used if the user selects the item. There should be an equal number of contentValues and contentLabels.
     */
    protected $contentValues;
    /**
     * @var array The items to allow user to select. These are the "labels" that are used if the user selects the item. There should be an equal number of contentValues and contentLabels.
     */
    protected $contentLabels;
    /**
     * @var assoc_array PLACEHOLDER so that we can use bindings for the setOptions function. Never has any real value.
     */
    protected $options;
    /**
     * @var integer The number of items to show, if MULTIPLE is enabled. Default is 5.
     */
    protected $visibleItems;
    /**
     * @var string CSS width data for the popup. Default is EMPTY. Useful to constrain width of the popup menu. Ex: 80px will yield width: 80px;.
     */
    protected $width;
    /**
     * @var object WFFormatter labelFormatter A formatter for the "label" portion of the data.
     */
    protected $labelFormatter;
    /**
     * @var boolean Should the label formatter be applied to the first choice? Handy option to use in conjunction with InsertsNullPlaceholder (when NullPlaceholder won't
     *              format correctly). Default FALSE
     */
    protected $labelFormatterSkipFirst;
    /**
     * @var boolean TRUE if the determination of whether a value is "selected" uses STRICT comparisons (===); FALSE to use relaxed comparisons (==). Default FALSE.
     */
    protected $strictValueTyping;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->values = array();
        $this->multiple = false;
        $this->visibleItems = 1;
        $this->contentValues = array();
        $this->contentLabels = array();
        $this->width = NULL;
        $this->labelFormatter = NULL;
        $this->labelFormatterSkipFirst = false;
        $this->strictValueTyping = false;
    }

    function setJSonChange($js)
    {
        $this->jsActions['onChange'] = $js;
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            'multiple' => array('true', 'false'),
            'visibleItems',
            'width',
            'labelFormatter',
            'labelFormatterSkipFirst' => array('true', 'false'),
            ));
    }

    function setupExposedBindings()
    {
        $myBindings = parent::setupExposedBindings();
        $myBindings[] = new WFBindingSetup('values', 'The selected values for multiple select boxes.');
        $newValBinding = new WFBindingSetup('contentValues', 'List of the VALUES of each item in the select box.',
                array(
                    WFBindingSetup::WFBINDINGSETUP_INSERTS_NULL_PLACEHOLDER => false,
                    WFBindingSetup::WFBINDINGSETUP_NULL_PLACEHOLDER => ''
                    )
                );
        $newValBinding->setReadOnly(true);
        $myBindings[] = $newValBinding;
        $newValBinding = new WFBindingSetup('contentLabels', 'List of the LABELS of each item in the select box.',
                array(
                    WFBindingSetup::WFBINDINGSETUP_INSERTS_NULL_PLACEHOLDER => false,
                    WFBindingSetup::WFBINDINGSETUP_NULL_PLACEHOLDER => 'Select...',
                    WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_NAME => WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_VALUE,
                    )
                );
        $newValBinding->setBindingType(WFBindingSetup::WFBINDINGTYPE_MULTIPLE_PATTERN);
        $newValBinding->setReadOnly(true);
        $myBindings[] = $newValBinding;
        $newValBinding = new WFBindingSetup('options', 'List of the options (value => label) of each item in the select box.',
                array(
                    WFBindingSetup::WFBINDINGSETUP_INSERTS_NULL_PLACEHOLDER => false,
                    WFBindingSetup::WFBINDINGSETUP_NULL_PLACEHOLDER => 'Select...'
                    )
                );
        $newValBinding->setReadOnly(true);
        $myBindings[] = $newValBinding;
        return $myBindings;
    }

    function processBindingOptions($boundProperty, $options, &$boundValue)
    {
        parent::processBindingOptions($boundProperty, $options, $boundValue);

        switch ($boundProperty) {
            case 'contentValues':
                if ($options[WFBindingSetup::WFBINDINGSETUP_INSERTS_NULL_PLACEHOLDER]) {
                    $defaultValue = $options[WFBindingSetup::WFBINDINGSETUP_NULL_PLACEHOLDER];
                    $boundValue = array_merge(array($defaultValue), $boundValue);
                }
                break;
            case 'contentLabels':
                if ($options[WFBindingSetup::WFBINDINGSETUP_INSERTS_NULL_PLACEHOLDER]) {
                    $defaultLabel = $options[WFBindingSetup::WFBINDINGSETUP_NULL_PLACEHOLDER];
                    $boundValue = array_merge(array($defaultLabel), $boundValue);
                }
                break;
            case 'options':
                if ($options[WFBindingSetup::WFBINDINGSETUP_INSERTS_NULL_PLACEHOLDER]) {
                    $defaultLabel = $options[WFBindingSetup::WFBINDINGSETUP_NULL_PLACEHOLDER];
                    WFLog::log("BEFORE: " . var_export($boundValue, true));
                    $boundValue = array('' => $defaultLabel) + $boundValue;
                    WFLog::log("AFETR: " . var_export($boundValue, true));
                }
                break;
        }
    }

    function setLabelFormatter($f)
    {
        if (!($f instanceof WFFormatter)) throw( new Exception("labelFormatter must be a WFFormatter subclass.") );
        $this->labelFormatter = $f;
    }
    function labelFormatter()
    {
        return $this->labelFormatter;
    }

    function setFormatter($f)
    {
        throw( new Exception("Formatters are not supported on WFSelect at this time. Are you looking for labelFormatter?") );
    }

    function setVisibleItems($numItems)
    {
        $this->visibleItems = $numItems;
    }
    function visibleItems()
    {
        return $this->visibleItems;
    }

    function multiple()
    {
        return $this->multiple;
    }

    function setMultiple($multiple)
    {
        if (!is_bool($multiple)) throw( new Exception("multiple must be boolean.") );
        $this->multiple = $multiple;
        // only setVisibleItems to a reasonable default for multiple=true if it's 1. We have to check b/c it visibleItems could get set BEFORE the call to setMultiple
        if ($this->visibleItems() == 1)
        {
            $this->setVisibleItems(5);
        }
    }

    function setValue($val)
    {
        $this->assertMultiple(false);
        parent::setValue($val);
    }
    function value()
    {
        $this->assertMultiple(false);
        return parent::value();
    }
    function valueLabel()
    {
        if (!$this->value) return NULL;

        for ($i = 0; $i < count($this->contentValues); $i++) {
            if ($this->contentValues[$i] == $this->value)
            {
                return $this->contentLabels[$i];
            }
        }
        throw( new Exception("Couldn't find label for value {$this->value} for select id '{$this->id}'.") );
    }

    function setValues($valArray)
    {
        $this->assertMultiple(true);
        if (!is_array($valArray)) throw( new Exception("setValues requires an array.") );
        $this->values = $valArray;
    }

    function valueIsSelected($value)
    {
        if ($this->multiple())
        {
            if (in_array($value, $this->values, $this->strictValueTyping))
            {
                return true;
            }
        }
        else
        {
            if ($this->strictValueTyping)
            {
                if ($value === $this->value)
                {
                    return true;
                }
            }
            else
            {
                if ($value == $this->value)
                {
                    return true;
                }
            }
        }
        return false;
    }

    function addValue($val)
    {
        $this->assertMultiple(true);
        $this->values[] = $val;
    }

    function values()
    {
        $this->assertMultiple(true);
        return $this->values;
    }
    function valuesLabels()
    {
        $labels = array();
        for ($i = 0; $i < count($this->contentValues); $i++) {
            if ($this->contentValues[$i] == $this->value)
            {
                array_push($labels, $this->contentLabels[$i]);
            }
        }
        return $labels;
    }

    
    function contentValues()
    {
        return $this->contentValues;
    }
    
    /**
     * A convenience function to set both values and labels simultaneously from an associative array.
     * @param assoc_array A list of value => label.
     */
    function setOptions($opts)
    {
        $this->setContentValues(array_keys($opts));
        $this->setContentLabels(array_values($opts));
    }

    function setContentValues($values)
    {
        $this->contentValues = $values;
    }

    function contentLabels()
    {
        return $this->contentLabels;
    }
    
    function setContentLabels($labels)
    {
        $this->contentLabels = $labels;
    }

    function assertMultiple($multiple)
    {
        if ($multiple !== $this->multiple()) throw( new Exception("Attempt to retrive a multiple value from a non-multiple widget, or vice-versa.") );
    }

    function pushBindings()
    {
        // our only bindable property that should be propagated back is VALUE / VALUES.
        if ($this->multiple())
        {
            // use propagateValueToBinding() to call validator and propagate new value to binding.
            $cleanValue = $this->propagateValueToBinding('values', $this->values);
            // update UI to cleaned-up value
            $this->setValues($cleanValue);
        }
        else
        {
            parent::pushBindings();
        }
    }

    function restoreState()
    {
        //  must call super
        parent::restoreState();

        if ($this->multiple())
        {
            if (isset($_REQUEST[$this->name]))
            {
                $this->setValues($_REQUEST[$this->name]);
            }
        }
        else
        {
            if (isset($_REQUEST[$this->name]))
            {
                $this->setValue($_REQUEST[$this->name]);
            }
        }
    }

    function render($blockContent = NULL)
    {
        $multiple = $this->multiple() ? ' multiple ' : NULL;
        $size = ($this->visibleItems() != 1) ? 'size="' . $this->visibleItems() . '" ' : NULL;

        $output = '<select id="' . $this->id . '" name="' . $this->name() . ($this->multiple() ? '[]' : '') . '" ' .
                    $multiple .
                    $size .
                    ($this->enabled() ? '' : ' disabled readonly ') .
                    ($this->width ? ' style="width: ' . $this->width . ';" ' : '') . 
                    $this->getJSActions() . 
                    '>';

        $values = $this->contentValues();
        $labels = $this->contentLabels();
        for ($i = 0; $i < count($values); $i++) {
            $value = $label = $values[$i];
            if (isset($labels[$i])) $label = $labels[$i];
            if ($this->labelFormatter())
            {
                if ($this->labelFormatterSkipFirst && $i == 0)
                {
                    $label = $label;
                }
                else
                {
                    $label = $this->labelFormatter->stringForValue($label);
                }
            }
            $selected = $this->valueIsSelected($value) ? 'selected' : '';
            $output .= "\n<option value=\"{$value}\" {$selected} >$label</option>";
        }

        $output .= "\n</select>";

        // when not enabled, no data will be submitted for the select, so we need to fake it with hidden fields
        if (!$this->enabled())
        {
            if ($this->multiple)
            {
                foreach ($this->values as $v) {
                    $output .= "<input type=\"hidden\" name=\"{$this->id}[]\" value=\"{$v}\" />\n";
                }
            }
            else
            {
                $output .= "<input type=\"hidden\" name=\"{$this->id}\" value=\"{$this->value}\" />\n";
            }
        }
        $output .= $this->getListenerJSInScriptTag();
        return $output;
    }

    function canPushValueBinding() { return true; }

}

?>
