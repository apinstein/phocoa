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
require_once('framework/WFError.php');
require_once('framework/widgets/WFView.php');
require_once('framework/WFBinding.php');
require_once('framework/ValueTransformers/WFValueTransformer.php');
require_once('framework/widgets/WFFormatter.php');

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
 * @todo Write a GUI editor to manage .instances and .config, using options from {@link exposedBinding()}.
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
      * If you are looking for the Hidden HTML element type, see {@link WFHidden}.
      *
      * @todo Should this be moved up into WFView?
      */
    protected $hidden;
    /**
     * @var string The css class to use for the item.
     */
    protected $class;

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
      * Get the formatted value for the passed string.
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
            }
        }
        return $theValue;
    }

    /**
      * Attach a formatter to the widget.
      *
      * @param object A WFFormatter object.
      */
    function setFormatter($formatter)
    {
        if (!($formatter instanceof WFFormatter)) throw( new Exception("You must pass a WFFormatter subclass to setFormatter().") );
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
        return $myBindings;
    }

    /**
      * Set up a binding.
      * @see WFKeyValueBindingCreation::bind()
      */
    function bind($bindLocalProperty, $bindToObject, $bindToKeyPath, $options = NULL)
    {
        // does this property exist? Easy to test as valueForKey will THROW if DNE...
        try {
            $baseLocalProperty = $bindLocalProperty;
            $matches = array();
            if (preg_match('/(.*)[0-9]+$/', $bindLocalProperty, $matches) == 1)
            {
                $baseLocalProperty = $matches[1];
            }
            $this->valueForKey($baseLocalProperty);
        } catch (Exception $e) {
            throw( new Exception("Cannot bind property '$bindLocalProperty' because it is not a property of the object '" . get_class($this) . "'.") );
        }

        // is the bindToObject an object? ideally we'd check to be sure it's KVC compliant, but for now just make sure it's an object.
        if (!is_object($bindToObject)) throw( new Exception("The bindToObject (for '" . get_class($this) . "::{$bindLocalProperty}') must be a Key-Value Coding Compliant object.") );

        // is the property already bound?
        if (isset($this->bindings[$bindLocalProperty])) throw( new Exception("Cannot bind property '$bindLocalProperty' because it is already bound to " . get_class($this->bindings[$bindLocalProperty]->bindToObject()) . " with keyPath '" . $this->bindings[$bindLocalProperty]->bindToKeyPath() . "'.") );

        $binding = new WFBinding();
        $binding->setBindToObject($bindToObject);
        $binding->setBindToKeyPath($bindToKeyPath);
        $binding->setBindLocalProperty($bindLocalProperty);

        $exposedBindings = $this->exposedBindings();
        if (!isset($exposedBindings[$baseLocalProperty])) throw( new Exception("No binding setup available for property: $baseLocalProperty.") );
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
        // get original value
        $boundValue = $binding->bindToObject()->valueForKeyPath($binding->bindToKeyPath());

        // Get a list of all options, coalesced with default value from the binding setup for this property.
        // the lack of documenting (ie exposing) a binding setup should simply assume that there are no options.
        $optionDefaults = array();
        $optionDefaults = $binding->bindingSetup()->options();
        $coalescedOptions = array_merge($optionDefaults, $binding->options());

        // let class apply options to value
        $this->processBindingOptions($prop, $coalescedOptions, $boundValue);

        // process value transformer
        if ($binding->valueTransformerName())
        {
            $vt = WFValueTransformer::valueTransformerForName($binding->valueTransformerName());
            $boundValue = $vt->transformedValue($boundValue);
        }

        WFLog::log("Using value '$boundValue' for binding '$prop'", WFLog::TRACE_LOG);
        return $boundValue;
    }

    /**
     * Go through all bindings and pull the values into our widget.
     * Will give the class an option to modify the bound value based on all binding options.
     */
    final function pullBindings()
    {
        foreach ($this->bindings as $prop => $binding) {
            if ($prop != $binding->bindingSetup()->boundProperty())
            {
                WFLog::log("pullBindings() -- skipping meta-binding '$prop'.", WFLog::TRACE_LOG);
                continue;
            }
            WFLog::log("pullBindings() -- processing binding for widget '{$this->id}', local property '$prop', to keyPath " . $binding->bindToKeyPath(), WFLog::TRACE_LOG);
            // DO NOT RE-BIND IF THE VALUE WAS AN ERROR! WANT TO SHOW THE BAD VALUE!
            if (count($this->errors) > 0)
            {
                WFLog::log("skipping pullBindings for {$this->id} / $prop because the value is an error.", WFLog::TRACE_LOG);
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
                        $boundValue = str_replace(array_keys($boundValueParts), array_values($boundValueParts), $valuePattern);
                        break;
                    default:
                        throw( new Exception("Support for bindingType " . $bindingSetup->bindingType() . " used by '$prop' is not yet implemented.") );
                }
                WFLog::log("Using value '$boundValue' for binding {$this->id} / $prop...", WFLog::TRACE_LOG);
                $this->setValueForKey($boundValue, $prop);  // must do this to allow accessors to be called!
            } catch (Exception $e) {
                if ($binding->raisesForNotApplicableKeys())
                {
                    throw($e);
                }
                else
                {
                    WFExceptionReporting::log($e);
                }
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

        WFLog::log("pushBindings() for for widget id '{$this->id}'", WFLog::TRACE_LOG);

        // get the cleaned value from the formatter first, and of course check for errors there.
        $fmtV = $this->value;
        if ($this->formatter)
        {
            $fmtV = $this->formattedValue($this->value);
            // null signifies ERROR! cannot proceed to normal validate/push routine (ie propagateValueToBinding);
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
     * @param string The name of the binding to propagate back to the bound object.
     * @param mixed The value from the UI widget (submitted by the form).
     * @return mixed The cleaned-up value from validateValueForKeyPath()
     */
    function propagateValueToBinding($bindingName, $value)
    {
        // the bindable property may not be bound! The is legitimate, so be graceful about it.
        $binding = $this->bindingByName($bindingName);
        if (is_null($binding)) return $value;

        // assert for r/o bindings.
        if ($binding->bindingSetup()->readOnly()) throw( new Exception("Attempt to propagateValueToBinding for a read-only binding: {$this->id} / $bindingName.") );

        $edited = false;
        WFLog::log("propagateValueToBinding() validating value $value for bound object for {$this->id} / $bindingName", WFLog::TRACE_LOG);
        $errors = array();
        $valid = $binding->bindToObject()->validateValueForKeyPath($value, $binding->bindToKeyPath(), $edited, $errors);
        if ($valid)
        {
            WFLog::log("propagateValueToBinding() Pushing value $value back to bound object for {$this->id} / $bindingName", WFLog::TRACE_LOG);
            $binding->bindToObject()->setValueForKeyPath($value, $binding->bindToKeyPath());
        }
        else
        {
            WFLog::log("propagateValueToBinding() WILL NOT (did not validate) push value $value back to bound object for {$this->id} / $bindingName", WFLog::TRACE_LOG);

            // keep all returned errors
            foreach ($errors as $err) {
                $this->addError($err);
            }
        }

        // return cleaned-up value so UI can update
        return $value;
    }

    function bindingByName($bindingName)
    {
        if (isset($this->bindings[$bindingName]))
        {
            return $this->bindings[$bindingName];
        }
        return NULL;
    }

    /**
     * After WFPage has completed the loading of all config for all widgets, it will call this function on each widget.
     *
     * This function is particularly useful for widgets that have children. When called, the widget can be sure that all instances and config of its children 
     * have been loaded from the .config file.
     */
    function allConfigFinishedLoading() {}
}

?>
