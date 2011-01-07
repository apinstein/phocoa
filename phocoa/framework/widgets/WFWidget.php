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
 * Set up built-in value transformers.
 */
WFValueTransformer::setValueTransformerForName(new WFNegateBooleanTransformer, 'WFNegateBoolean');
WFValueTransformer::setValueTransformerForName(new WFIsEmptyTransformer, 'WFIsEmpty');
WFValueTransformer::setValueTransformerForName(new WFIsNotEmptyTransformer, 'WFIsNotEmpty');
WFValueTransformer::setValueTransformerForName(new WFUrlencodeTransformer, 'WFUrlencode');
WFValueTransformer::setValueTransformerForName(new WFEmptyToNullTransformer, 'WFEmptyToNull');

/**
 * The base "widget" class. In our framework, all html form widgets are necessarily WFWidget subclasses.
 *
 * Widgets are {@link WFView} subclasses that add capabilities such as state maintainance/restoration, error tracking, formatters, and editability.
 *
 * SUBCLASSING GUIDE:
 * Very important instructions on subclassing WFWidget should be put here.
 * The basics for now, if your widget is simple, you get bindings for free. Just write restoreState() if needed, and implement canPushValueBinding().
 * Oh yeah, and one more thing, ALWAYS get/set the value directly via $this->value or $this->value = $newValue. If you call value() or setValue($newValue),
 * the formatters will run again and probably goof up your data.
 *
 * Each subclass can also set up a list of its bindable properties and binding options. This is done by implementing the {@link setupExposedBindings()} method.
 * It's quite easy to view the binding setup for a widget, just cd to the framework/ directory and run:
 *
 * php showBindings.php WFWidgetSubclass
 *
 * And you will see a nicely formatted list of all available bindings and options.
 *
 * Formatter support: Using {@link value(), setValue()} will provide automatic formatter support. Generally, the idea is that setValue() will take the passed "raw" value and convert it into the "formatted" value. The {@link $value} member thus should always contain the "formatted" representation if the widget is using a formatter. Conversely, value() will take the "formatted" representation stored internally and turn it back into a "raw" value.
 *
 * @todo Should the class property be moved up to WFView? Probably yes
 *
 * Implements:
 *  {@link WFKeyValueBindingCreation} - Provides a base implementation of bindings support.
 */
abstract class WFWidget extends WFView
{
    /**
      * @var string The name of the widget. Used as the HTML name.
      */
    protected $name;
    /**
      * @var array All of the bound properties for the widget.
      */
    protected $bindings;
    /**
     * @var array An array of WFError objects containing a list of all errors in the form.
     */
    protected $errors;
    /**
     * @var boolean Has the widget restored its state from the form data yet?
     */
    protected $hasRestoredState;
    /**
      * The WFFormatter subclass to use to format the data, or NULL to not use one.
      */
    protected $formatter;
    /**
      * @var mixed The VALUE of the widget. Various subclasses will have different meanings for it, but it is part
      *            of our WFWidget so that we can automatically deal with formatters.
      */
    protected $value;
    /**
      * @var boolean Whether or not the widget is hidden. Hidden controls WILL NOT BE IN THE HTML OUTPUT.
      *
      * If you want to hide content related to a widget that should also be hidden if the widget is hidden, see {@link smarty_block_WFViewHiddenHelper}.
      *
      * If you are looking for the Hidden HTML input type, see {@link WFHidden}.
      *
      * @todo Should this be moved up into WFView? If so, make sure block.WFViewHiddenHelper.php still works.
      */
    protected $hidden;
    /**
     * @var string The css class to use for the item.
     */
    protected $class;
    /**
     * @var string The label for this widget.
     * @see WFWidgets::setWidgetLabel()
     */
    protected $widgetLabel;
    /**
     * @var integer The tabindex of the control (defaults to NULL)
     */
    protected $tabIndex;

    /**
      * Constructor.
      *
      * Sets up the smarty object for this module.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);

        $this->name = $id;
        $this->bindings = array();
        $this->errors = array();
        $this->hasRestoredState = false;
        $this->formatter = NULL;
        $this->value = NULL;
        $this->hidden = false;
        $this->class = NULL;
        $this->widgetLabel = NULL;
        $this->tabIndex = NULL;
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array('value', 'formatter', 'hidden', 'class'));
    }

    /**
      * Set the value used by the widget.
      *
      * Subclasses that use {@link value, setValue} automatically get formatter support.
      *
      * @param mixed The value used by the widget.
      */
    function setValue($value)
    {
        $theString = $value;

        if ($this->formatter)
        {
            $theString = $this->formatter->stringForValue($theString);
        }

        $this->value = $theString;
    }

    /**
      * Get the value for the widget.
      *
      * Subclasses that use {@link value, setValue} automatically get formatter support.
      *
      * @return mixed The value returned by the widget.
      */
    function value()
    {
        return $this->formattedValue($this->value);
    }

    /**
      * Set whether or not the widget should be hidden.
      *
      * Hidden widgets simply do not appear in the HTML output.
      *
      * @param boolean Whether or not to hide the widget.
      */
    function setHidden($hidden)
    {
        $this->hidden = $hidden;
    }

    /**
      * Get whether or not the widget should be hidden.
      *
      * Hidden widgets simply do not appear in the HTML output.
      *
      * @return boolean Whether or not to hide the widget.
      */
    function hidden()
    {
        return $this->hidden;
    }

    /**
     *  Set the "label" used for this widget field.
     *
     *  The label is the "field label" that describes the widget. Used primarly by {@link WFAutoForm}.
     *
     *  @param string The label for this widget.
     */
    function setWidgetLabel($widgetLabel)
    {
        $this->widgetLabel = $widgetLabel;
    }

    /**
     *  The widget's label.
     *
     *  @return *  string The label for this widget.
     */
    function widgetLabel()
    {
        return $this->widgetLabel;
    }

    /**
      * Get the formatted value for the passed string.
      *
      * If there was an error in the formatting, we return NULL as a flag so the caller knows that no value could be determined.
      *
      * @param string The string from the UI.
      * @return mixed The value from the formatter, if there is one, or the original otherwise.
      */
    protected function formattedValue($string)
    {
        $theValue = $string;
        if ($this->formatter)
        {
            $error = new WFError;
            $theValue = $this->formatter->valueForString($string, $error);
            if ($error->errorMessage() or $error->errorCode())
            {
                $this->addError($error);
                return NULL;
            }
        }
        return $theValue;
    }
    
    protected function classHTML()
    {
        if ($this->class)
        {
            return ' class="' . $this->class . '" ';
        }
        return NULL;
    }

    /**
      * Attach a formatter to the widget.
      *
      * @param object A WFFormatter object.
      */
    function setFormatter($formatter)
    {
        if ($formatter !== NULL and !($formatter instanceof WFFormatter)) throw( new Exception("You must pass a WFFormatter subclass to setFormatter().") );
        $this->formatter = $formatter;
    }

    /**
      * Retreive the formatter for this widget.
      *
      * @return object A {@link WFFormatter} object.
      */
    function formatter()
    {
        return $this->formatter;
    }

    /**
     * Get a list of all exposed bindings. If a subclass has overridden a bindable property, the definition from the subclass will be used.
     * @see WFKeyValueBindingCreation
     */
    function exposedBindings()
    {
        static $exposedBindings = NULL;
        if (is_null($exposedBindings)) {
            $allBindings = $this->setupExposedBindings();

            // make assoc array out of it
            $exposedBindings = array();
            foreach ($allBindings as $bindingInfo) {
                $exposedBindings[$bindingInfo->boundProperty()] = $bindingInfo;
            }
        }
        return $exposedBindings;
    }

    /**
      * Set up all exposed bindings for this widget.
      *
      * The default implementation sets up bindings available for all WFWidgets. Subclasses must call super method.
      *
      * @return array An array of {@link WFBindingSetup} objects.
      */
    function setupExposedBindings()
    {
        $myBindings[] = new WFBindingSetup('value', 'The value of the widget.');
        $hidSetup = new WFBindingSetup('hidden', 'Whether or not the widget is hidden (included in the HTML output).');
        $hidSetup->setBindingType(WFBindingSetup::WFBINDINGTYPE_MULTIPLE_BOOLEAN);
        $hidSetup->setBooleanMode(WFBindingSetup::WFBINDINGTYPE_MULTIPLE_BOOLEAN_OR);
        $myBindings[] = $hidSetup;
        $enSetup = new WFBindingSetup('enabled', 'Whether or not the widget is enabled.');
        $enSetup->setBindingType(WFBindingSetup::WFBINDINGTYPE_MULTIPLE_BOOLEAN);
        $enSetup->setBooleanMode(WFBindingSetup::WFBINDINGTYPE_MULTIPLE_BOOLEAN_AND);
        $myBindings[] = $enSetup;
        $tabIndexSetup = new WFBindingSetup('tabIndex', 'The current tabIndex.');
        $myBindings[] = $tabIndexSetup;
        return $myBindings;
    }

    /**
      * Set up a binding.
      * @see WFKeyValueBindingCreation::bind()
      */
    function bind($bindLocalProperty, $bindToObject, $bindToKeyPath, $options = NULL)
    {
        // determine if $bindLocalProperty is a multi-value binding and, if so, figure out the "base" property
        $baseLocalProperty = $bindLocalProperty;
        $matches = array();
        if (preg_match('/(.*)[0-9]+$/', $bindLocalProperty, $matches) == 1)
        {
            $baseLocalProperty = $matches[1];
        }
        // does this property exist? Easy to test as valueForKey will THROW if DNE...
        $exposedBindings = $this->exposedBindings();
        if (!isset($exposedBindings[$baseLocalProperty])) throw( new WFException("Cannot bind property '{$bindLocalProperty}' because it is not a property of object '" . get_class($this) . "' (instanceId: " . $this->id() . ")."));

        // is the bindToObject an object? ideally we'd check to be sure it's KVC compliant, but for now just make sure it's an object.
        if (!is_object($bindToObject)) throw( new WFException("The bindToObject (for '" . get_class($this) . "::{$bindLocalProperty}') must be a Key-Value Coding Compliant object.") );

        // is the property already bound?
        if (isset($this->bindings[$bindLocalProperty])) throw( new WFException("Cannot bind property '$bindLocalProperty' because it is already bound to " . get_class($this->bindings[$bindLocalProperty]->bindToObject()) . " with keyPath '" . $this->bindings[$bindLocalProperty]->bindToKeyPath() . "'.") );

        $binding = new WFBinding();
        $binding->setBindToObject($bindToObject);
        $binding->setBindToKeyPath($bindToKeyPath);
        $binding->setBindLocalProperty($bindLocalProperty);

        $binding->setBindingSetup($exposedBindings[$baseLocalProperty]);

        // Save options
        if (is_null($options))
        {
            $options = array();
        }
        $binding->setOptions($options);

        // add to bindings list for this object
        $this->bindings[$bindLocalProperty] = $binding;
    }

    /**
      * Set up a binding.
      * @see WFKeyValueBindingCreation::unbind()
      */
    function unbind($bindLocalProperty)
    {
        if (!isset($this->bindings[$bindLocalProperty])) throw( new Exception("Attempt to unbind '$bindLocalProperty' failed because it is not bound.") );
        unset($this->bindings[$bindLocalProperty]);
    }

    /**
     * Returns the value determined by the binding.
     *
     * @return mixed The value to use as determined by resolving the binding.
     * @throws Exception under various circumstances if the value cannot be determined.
     */ 
    final function valueForBinding($prop, $binding)
    {
        $exposedBindings = $this->exposedBindings();

        // Get a list of all options, coalesced with default value from the binding setup for this property.
        // the lack of documenting (ie exposing) a binding setup should simply assume that there are no options.
        // @todo can this block be relplaced with $binding->coalescedOptions()?
        $optionDefaults = array();
        $optionDefaults = $binding->bindingSetup()->options();
        $coalescedOptions = array_merge($optionDefaults, $binding->options());

        try {
            // get original value
            if (strpos($binding->bindToKeyPath(), '::') === false)
            {
                $boundValue = $binding->bindToObject()->valueForKeyPath($binding->bindToKeyPath());
            }
            else
            {
                $boundValue = $binding->bindToObject()->valueForStaticKeyPath($binding->bindToKeyPath());
            }
        } catch (WFUndefinedKeyException $e) {
            if ($binding->raisesForNotApplicableKeys()) throw $e;
            WFLog::log("undefined key: {$binding->bindToKeyPath()}, substituting " . var_export($binding->notApplicablePlaceholder(), true), WFLog::TRACE_LOG);
            $boundValue = $binding->notApplicablePlaceholder();
        }

        // let class apply options to value
        $this->processBindingOptions($prop, $coalescedOptions, $boundValue);

        // process value transformer
        if ($binding->valueTransformerName())
        {
            WFLog::log("Transforming value " . print_r($boundValue, true) . " with " . $binding->valueTransformerName(), WFLog::TRACE_LOG);
            $vt = WFValueTransformer::valueTransformerForName($binding->valueTransformerName());
            $boundValue = $vt->transformedValue($boundValue);
            WFLog::log("Transformed value: " . print_r($boundValue, true), WFLog::TRACE_LOG);
        }

        if ($binding->formatter())
        {
            WFLog::log("Formatting value " . print_r($boundValue, true) . " with " . $binding->formatter(), WFLog::TRACE_LOG);
            $formatter = $this->page()->module()->valueForKey($binding->formatter());
            // automatically handle formatting of arrays of objects
            if (is_array($boundValue))
            {
                // using foreach since for some f'd up reason array_walk($boundValue, array($formatter,"stringForValue")) didn't munge the values.
                foreach (array_keys($boundValue) as $k) {
                    $boundValue[$k] = $formatter->stringForValue($boundValue[$k]);
                }
            }
            else
            {
                $boundValue = $formatter->stringForValue($boundValue);
            }
            WFLog::log("Formatted value: " . print_r($boundValue, true), WFLog::TRACE_LOG);
        }

        WFLog::log("Using value " . print_r($boundValue, true) . " for binding '$prop'", WFLog::TRACE_LOG);
        return $boundValue;
    }

    /**
     * Go through all bindings and pull the values into our widget.
     * Will give the class an option to modify the bound value based on all binding options.
     */
    final function pullBindings()
    {
        $skipReadWriteBindings = (count($this->errors) > 0);
        foreach ($this->bindings as $prop => $binding) {
            if ($prop != $binding->bindingSetup()->boundProperty())
            {
                WFLog::log("pullBindings() -- skipping meta-binding '$prop'.", WFLog::TRACE_LOG);
                continue;
            }
            WFLog::log("pullBindings() -- processing binding for widget '{$this->id}', local property '$prop', to keyPath " . $binding->bindToKeyPath(), WFLog::TRACE_LOG);
            // DO NOT RE-BIND IF THE BOUND VALUE WAS AN ERROR! WANT TO SHOW THE BAD VALUE!
            // Of course, R/O bindings cannot have errors, so we will still bind them...
            if ($skipReadWriteBindings and !$binding->bindingSetup()->readOnly())
            {
                WFLog::log("skipping pullBindings for {$this->id} / $prop because the value is an error.", WFLog::TRACE_LOG);
                continue;
            }
            // process readwrite mode option
            if (!$binding->canReadBoundValue())
            {
                WFLog::log("skipping pullBindings for {$this->id} / $prop because the binding option for ReadWriteMode is set to writeonly.", WFLog::TRACE_LOG);
                continue;
            }
        
            try {
                $bindingSetup = $binding->bindingSetup();
                switch ($bindingSetup->bindingType()) {
                    case WFBindingSetup::WFBINDINGTYPE_SINGLE:
                        $boundValue = $this->valueForBinding($prop, $binding);
                        break;
                    case WFBindingSetup::WFBINDINGTYPE_MULTIPLE_BOOLEAN:
                        // find all bindings in the pattern of <prop>, <prop2>, <propN>
                        $boundValueParts = array($this->valueForBinding($prop, $binding));
                        $partIndex = 2;
                        while (true) {
                            $partName = $prop . $partIndex;
                            if (!isset($this->bindings[$partName])) break;

                            $boundValueParts[] = $this->valueForBinding($partName, $this->bindings[$partName]);
                            $partIndex++;
                        }
                        // determine combo of all values; seed value with value of first one.
                        $boundValue = $boundValueParts[0];
                        for ($i = 1; $i < count($boundValueParts); $i++) {
                            switch ($binding->bindingSetup()->booleanMode()) {
                                case WFBindingSetup::WFBINDINGTYPE_MULTIPLE_BOOLEAN_OR:
                                    $boundValue = ($boundValue or $boundValueParts[$i]);
                                    break;
                                case WFBindingSetup::WFBINDINGTYPE_MULTIPLE_BOOLEAN_AND:
                                    $boundValue = ($boundValue and $boundValueParts[$i]);
                                    break;
                                default:
                                    throw( new Exception("Illegal booleanMode for '$prop'.") );
                            }
                        }
                        break;
                    case WFBindingSetup::WFBINDINGTYPE_MULTIPLE_PATTERN:
                        // find all bindings in the pattern of <prop>, <prop2>, <propN> and assemble in format of prop's FormatString binding option.
                        $boundValueParts = array(WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_VALUE => $this->valueForBinding($prop, $binding));
                        $partIndex = 2;
                        while (true) {
                            $partName = $prop . $partIndex;
                            if (!isset($this->bindings[$partName])) break;

                            $partPattern = "%{$partIndex}%";
                            $boundValueParts[$partPattern] = $this->valueForBinding($partName, $this->bindings[$partName]);
                            $partIndex++;
                        }
                        $defaultPropertyOptions = $binding->bindingSetup()->options();
                        $valuePattern = $defaultPropertyOptions[WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_NAME];

                        $basePropertyOptions = $binding->options();
                        if (isset($basePropertyOptions[WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_NAME]))
                        {
                            $valuePattern = $basePropertyOptions[WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_NAME];
                        }
                        // need to support mutli-value bindings for "magic" array mode bindings too
                        if (is_array($boundValueParts['%1%']))
                        {
                            $boundValues = array();
                            // special handling for InsertsNullPlaceholder - this way we can set up a single null placeholder like "Select..." and it won't look goofy when we process '%1%, %2%' ValuePatterns
                            if (isset($basePropertyOptions[WFBindingSetup::WFBINDINGSETUP_INSERTS_NULL_PLACEHOLDER]))
                            {
                                // use NullPlaceholder as the entire value, then remove it from the array so all parts are same array length
                                $boundValues[] = array_shift($boundValueParts['%1%']);
                            }
                            $itemCount = count($boundValueParts['%1%']);
                            for ($i = 0; $i < $itemCount; $i++) {
                                $boundValueArrayParts = array();
                                foreach (array_keys($boundValueParts) as $partPattern) {
                                    $boundValueArrayParts[] = $boundValueParts[$partPattern][$i];
                                }
                                $boundValues[] = str_replace(array_keys($boundValueParts), $boundValueArrayParts, $valuePattern);
                            }
                            $boundValue = $boundValues;
                        }
                        else
                        {
                            // special-case handling for NULL_PLACEHOLDER and multi-value bindings
                            // if *any* boundValueParts.values is NULL then boundValue should be NULL entirely
                            // otherwise do normal str substitution
                            $anyNulls = false;
                            if (isset($basePropertyOptions[WFBindingSetup::WFBINDINGSETUP_NULL_PLACEHOLDER]))
                            {
                                foreach (array_values($boundValueParts) as $checkForNull) {
                                    if ($checkForNull === NULL)
                                    {
                                        $anyNulls = true;
                                        break;
                                    }
                                }
                            }

                            if ($anyNulls)
                            {
                                $boundValue = NULL;
                            }
                            else
                            {
                                $boundValue = str_replace(array_keys($boundValueParts), array_values($boundValueParts), $valuePattern);
                            }
                        }
                        break;
                    default:
                        throw( new Exception("Support for bindingType " . $bindingSetup->bindingType() . " used by '$prop' is not yet implemented.") );
                }
                // adjust for NullPlaceholder
                if ( ($boundValue === NULL or $boundValue === '') and $binding->coalescedOption(WFBindingSetup::WFBINDINGSETUP_NULL_PLACEHOLDER) )
                {
                    $boundValue = $binding->coalescedOption(WFBindingSetup::WFBINDINGSETUP_NULL_PLACEHOLDER);
                }
                WFLog::log("FINAL value " . print_r($boundValue, true) . " for binding {$this->id} / $prop...", WFLog::TRACE_LOG);
                $this->setValueForKey($boundValue, $prop);  // must do this to allow accessors to be called!
            } catch (Exception $e) {
                WFLog::log("Skipping pullBindings for {$this->id} / {$prop} due to exception: {$e->getMessage()}", WFLog::WARN_LOG);
                continue;
            }
        }
    }

    /**
      * Pass the list of binding options to the subclass for modification of the value.
      *
      * The default implementation currently makes NO changes. In the future, if there are any bindings options shared by all, they will be processed here.
      *
      * @param string The name of the bound property being processed.
      * @param assoc_array The binding options for this binding.
      * @param reference A referene to the bound value, so that you can change it based on options as needed.
      */
    function processBindingOptions($boundProperty, $options, &$boundValue)
    {
    }

    /**
      * Our WFWidgets can keep track of all errors that occurred when processing their values. This allows you to add an error to the widget.
      *
      * @param object A WFError object describing the error.
      */
    function addError(WFError $theError)
    {
        if (!($theError instanceof WFError)) throw( new Exception("All errors must be of type WFError.") );

        // add the error to our local list of errors
        $this->errors[] = $theError;

        // add the error to the page object
        $this->page->addError($theError);
    }

    function addErrors($arr)
    {
        foreach ($arr as $err) {
            $this->addError($err);
        }
    }

    /**
      * Retrieve all errors for this widget.
      *
      * @return array An array of WFError objects.
      */
    function errors()
    {
        return $this->errors;
    }


    /**
      * Are there any errors recorded for this widget?
      *
      * @return boolean
      */
    function hasErrors()
    {
        return count($this->errors());
    }

    /**
      * Set the HTML 'name' of the widget.
      *
      * @param string The name of the HTML widgets. Where appropriate, subclasses should use the name in their output.
      */
    function setName($name)
    {
        $this->name = $name;
    }

    /**
      * Get the name of the widget.
      *
      * @return string The name of the widget.
      */
    function name()
    {
        return $this->name;
    }

    /**
     * Restore the UI state of this widget from the $_REQUEST data.
     *
     * Subclasses should impement this to make their widgets stateful.
     * Subclasses should call the SUPER method.
     *
     * IMPORTANT!!! This method is ONLY FOR RESTORING THE VALUE OF THE WIDGET FROM THE FORM DATA!!!
     * @see pushBindings For information about how bound values are propagated back to the bound objects.
     */
    function restoreState()
    {
        $this->hasRestoredState = true;
    }

    /**
     * Returns whether or not the control has already restored its UI state with restoreState().
     *
     * If the value is TRUE, means that the control has already ATTEMPTED to restore state. It may not have found any state, though.
     *
     * @return boolean TRUE if it has already restored the state, FALSE otherwise.
     */
    function hasRestoredState()
    {
        return $this->hasRestoredState;
    }

    /**
     * By default HTML doesn't submit the data for disabled controls; phocoa hence skips pushBindings for disabled controls to prevent "null" data
     * from being pushed back onto the models.
     *
     * This function allows WFWidget subclasses that "fake" submission of data for disabled controls via hidden fields to "make themselves work"
     * by re-enabling pushBindings for not enabled widgets.
     *
     * Subclasses that fake their own data should override this function and return true.
     * 
     * @return boolean
     */
    function pushBindingsIfNotEnabled()
    {
        return false;
    }

    /**
     * Each widget should implement this callback if there are any bindable properties whose values are to be propagated back to the bound objects.
     * For each bound property, use the {@link propagateValueToBinding} method to perform validation and propagate the value back to the bound object.
     * Don't forget that the value may be cleaned up by propagateValueToBinding and that you should update the value(s) of your widget so that it will
     * reflect the the new value if it was edited.
     *
     * The default implementation of pushBindings() only works for "value". If a widget needs to push a value via bindings to other properties,
     * he should override pushBindings().
     */
    function pushBindings()
    {
        if (!$this->canPushValueBinding()) return;
        if ($this->bindingByName('value') and !$this->bindingByName('value')->canWriteBoundValue()) return;
        if (!$this->enabled() and !$this->pushBindingsIfNotEnabled()) return;  // disabled HTML controls do not submit data, thus they'll be empty! Thus don't push data  or we'll blow away valid data.


        WFLog::log("pushBindings() for for widget id '{$this->id}'", WFLog::TRACE_LOG);

        // get the cleaned value from the formatter first, and of course check for errors there.
        $fmtV = $this->value;
        if ($this->formatter)
        {
            $fmtV = $this->formattedValue($this->value);
            // null signifies ERROR! cannot proceed to normal validate/push routine (ie propagateValueToBinding);
            // @todo NOTE: This chunk of logic (null == error, doesn't push) seems at odds with WFFormatter.php:22, which says:
            //       >>> Now that valueForString() can return NULL as a legitimate value, need to look at existing formatters to make sure they're compatible.
            //       Since some formatters do return NULL w/o an error, and formattedValue() itself seems to be OK with that, why on earth is this is_null() / return below?
            if (is_null($fmtV))
            {
                //print "skinpipng pb b/c of null value for {$this->value}<BR>";
                return;
            }
        }

        // use propagateValueToBinding() to call validator and propagate new value to binding.
        $cleanValue = $this->propagateValueToBinding('value', $fmtV);
        // update UI to cleaned-up value
        $this->value = $cleanValue;
    }

    /**
      * Does this widget use the "value" binding to WRITE data back to the bindings?
      *
      * If the control is non-editable, client should return FALSE.
      *
      * If the control is editable, and you only use the built-in value property, then subclasses should return TRUE.
      * 
      * If the control is editable, and the subclass does not make use of the built-in value property, then should return FALSE.
      *
      * NOTE: this callback is used *instead* of the read-only setting of the binding setup for the "value" binding only. This is a special case
      * for the "value" property of WFWidget so that WFWidget subclasses can easily make themselves read-only without looking for their binding setup and editing
      * the read-only attribute.
      *
      * NOTE: contrast this with the {@link WFView::$enabled} setting. The canPushValueBinding setting is an inherent property of the widget class; enabled is a setting
      * that is toggleable at runtime.
      * 
      * @return boolean Return TRUE to have the base WFWidget class automatically push your 'value' binding. FALSE to skip pushing bindings for the "value" property.
      */
    abstract function canPushValueBinding();

    /**
     * Propagate the value from the UI widget to the bound object.
     *
     * This method will automatically call the validator for the binding, if it exists.
     * If the value is valid, it will also push the value onto the bound object with setValueForKeyPath.
     *
     * Any validation errors are stored in the widget's error list.
     *
     * If the value of the widget is equivalent to an empty string ($value === '') then value is converted into PHP NULL.
     * Since all values in widgets come from the UI, and there is no distinction in the UI world b/w "" and NULL, we normalize all "" values to NULL.
     * It is left up to objects to then distinguish between NULL and "" (via normalization in Key-Value Validation).
     *
     * @param string The name of the binding to propagate back to the bound object.
     * @param mixed The value from the UI widget (submitted by the form).
     * @return mixed The cleaned-up value from validateValueForKeyPath()
     */
    function propagateValueToBinding($bindingName, $value)
    {
        // the bindable property may not be bound! The is legitimate, so be graceful about it.
        $binding = $this->bindingByName($bindingName);
        if (is_null($binding)) return $value;

        // check OPTION_DO_NOT_PUSH_VALUE_SEMAPHORE
        if ($binding->hasCoalescedOption(WFBinding::OPTION_DO_NOT_PUSH_VALUE_SEMAPHORE) and $binding->coalescedOption(WFBinding::OPTION_DO_NOT_PUSH_VALUE_SEMAPHORE) === $value)
        {
            WFLog::log("propagateValueToBinding() skipping push for {$bindingName} since value matched OPTION_DO_NOT_PUSH_VALUE_SEMAPHORE for for widget id '{$this->id}'", WFLog::TRACE_LOG);
            return $value;
        }

        // assert for r/o bindings.
        if ($binding->bindingSetup()->readOnly()) throw( new Exception("Attempt to propagateValueToBinding for a read-only binding: {$this->id} / $bindingName.") );
        if (!$binding->canWriteBoundValue()) throw( new Exception("Attempt to propagateValueToBinding for a binding with that doesn't allow writing.") );

        // normalize "" string values to NULL. Do this pre-validation; that function can do normalization etc.
        // we simply cover the case of TOTALLY EMPTY STRING is equivalent to NULL here.
        if ($value === '') $value = NULL;
        
        $edited = false;
        WFLog::log("propagateValueToBinding() validating value '$value' for bound object '" . get_class($this) . "' for widget id '{$this->id}' binding: '{$bindingName}'", WFLog::TRACE_LOG);
        $errors = array();
        $valid = $binding->bindToObject()->validateValueForKeyPath($value, $binding->bindToKeyPath(), $edited, $errors);
        if ($valid)
        {
            WFLog::log("propagateValueToBinding() Pushing value '$value' for bound object '" . get_class($this) . "' for widget id '{$this->id}' binding: '{$bindingName}'", WFLog::TRACE_LOG);
            $binding->bindToObject()->setValueForKeyPath($value, $binding->bindToKeyPath());
        }
        else
        {
            WFLog::log("propagateValueToBinding() WILL NOT (did not validate) push value '$value' for bound object '" . get_class($this) . "' for widget id '{$this->id}' binding: '{$bindingName}'", WFLog::TRACE_LOG);

            // keep all returned errors
            $this->addErrors($errors);
        }

        // return cleaned-up value so UI can update
        return $value;
    }

    /**
     *  Get all bindings for this object instance.
     *
     *  @return array An array of attached {@link WFBinding bindings} for this instance.
     */
    function bindings()
    {
        return $this->bindings;
    }

    /**
     *  Get the binding for a particular local property on this object instance.
     *
     *  @param string The local property to get the binding for.
     *  @return object WFBinding The {@link WFBinding} for this instance on the local property passed, or NULL if there is no binding.
     */
    function bindingByName($bindingName)
    {
        if (isset($this->bindings[$bindingName]))
        {
            return $this->bindings[$bindingName];
        }
        return NULL;
    }
}

?>
