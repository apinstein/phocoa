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
        $myBindings[] = new WFBindingSetup('hidden', 'Whether or not the widget is hidden (included in the HTML output).');
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
            $this->valueForKey($bindLocalProperty);
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
     * Go through all bindings and pull the values into our widget.
     * Will give the class an option to modify the bound value based on all binding options.
     */
    function pullBindings()
    {
        foreach ($this->bindings as $prop => $binding) {
            WFLog::log("pullBindings() -- processing binding '$prop'", WFLog::TRACE_LOG);
            // DO NOT RE-BIND IF THE VALUE WAS AN ERROR! WANT TO SHOW THE BAD VALUE!
            if (count($this->errors) > 0)
            {
                WFLog::log("skipping pullBindings for {$this->id} / $prop because the value is an error.", WFLog::TRACE_LOG);
                continue;
            }

            $exposedBindings = $this->exposedBindings();
            try {
                // get original value
                $boundValue = $binding->bindToObject()->valueForKeyPath($binding->bindToKeyPath());

                // Get a list of all options, coalesced with default value from the binding setup for this property.
                // the lack of documenting (ie exposing) a binding setup should simply assume that there are no options.
                $optionDefaults = array();
                if (isset($exposedBindings[$prop]))
                {
                    $optionDefaults = $exposedBindings[$prop]->options();
                }
                $coalescedOptions = array_merge($optionDefaults, $binding->options());

                // let class apply options to value
                $this->processBindingOptions($prop, $coalescedOptions, $boundValue);

                // process value transformer
                if ($binding->valueTransformerName())
                {
                    $vt = WFValueTransformer::valueTransformerForName($binding->valueTransformerName());
                    $boundValue = $vt->transformedValue($boundValue);
                }

                // assign final value
                WFLog::log("Using value '$boundValue' for binding $prop...", WFLog::TRACE_LOG);
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
                return NULL;
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
     * A special version of {@link magicMatrix} that is used for managing SELECTION of a list of objects.
     *
     * @deprecated See {@link WFSelectionCheckbox}
     * @param object A WFArrayController instance. The array of objects to manage selection for.
     * @param object The WFForm that this widget belongs to.
     * @param string The base name of the widget.
     * @param string A keyPath of the managed object class of the arrayController that will return the identifier {@link WFArrayController} for the object.
     */
    function magicMatrixSelection(WFArrayController $arrayController, WFForm $parentForm, $widgetBaseName, $identifierKeyPath)
    {
        // add checkboxes for selection
        $options = array(
            'groupMode' => array( 'custom' => array('iterate' => false, 'value' => true) ),
            'checkedValue' => array( 'custom' => array('iterate' => true, 'keyPath' => $identifierKeyPath) ),
            'uncheckedValue' => array( 'custom' => array('iterate' => false, 'value' => '') )
        ); 
        $selectionCheckboxes = WFWidget::magicMatrix($arrayController, $parentForm, 'WFCheckbox', $widgetBaseName, false, $options);
        foreach ($selectionCheckboxes as $cb) {
            if ($cb->checked())
            {
                $arrayController->addSelectionIdentifiers(array($cb->value()));
            }
        }
        return $selectionCheckboxes;
    }


    /**
     * Function to help in the creation of dynamic widgets.
     *
     * Allows for creation of n widgets based on an WFArrayController. Various options allow you to set up nearly any type
     * of widget that is repeated for all objects in an array. Great for managing selection, editing data from joins, etc.
     *
     * Once the objects are instantiated, configured, and bound as appropriate, restoreState() will be called. You can be sure that
     * when this function exits that your widgets are in the same state as statically instantiated widgets from the page.
     *
     * @deprecated See {@link WFDynamic}
     * @static
     * @param object A WFArrayController instance. The magicMatrix will iterate over the arrangedObjects, making one widget for each item.
     * @param object The WFForm that this widget belongs to.
     * @param string The class of widget to create.
     * @param string The base name of the widget.
     *               If $useUniqueNames is true, all widgets will have this name.
     *               If $useUniqueNames is false, each widget will be named "<basename>_<itemid>".
     * @param boolean TRUE to make each widget have a unique name.
     *                FALSE to make each widget have the same name. Of course in either case, the ID's will all be uniq, "<basename>_<itemid>".
     * @param array A list of all "magic" variables for each widget. Format:
     *              array(
     *                  // enter as many widget property names as you like.
     *                  'widgetPropName' => array(
     *                                          'custom' => array(
     *                                                          'iterate' => true / false,
     *                                                          'keyPath' => 'modelKeyPath' KeyPath of the object to use for this iteration.
     *                                                                                      Only used if 'iterate', and then it's optional.
     *                                                          'value' => mixed            If iterate is true then this should be an array and it 
     *                                                                                      will use the nth item from it. if false, uses this value every time.
     *                                                          )
     *                                          // use this binding config (similar format as widget .config file)
     *                                          'bind' => array(
     *                                                          'instanceID' => 'outletName' or 
     *                                                                          '#module#' (to use the module) or
     *                                                                          '#custom# (to use object specified in 'custom' above) or
     *                                                                          '#current#' (to use the object of the current iteration)
     *                                                          'controllerKey' => 'controllerKey',
     *                                                          'modelKeyPath' => 'modelKeyPath',
     *                                                          'options' => array() // bindings options
     *                                                          )
     *                                      )
     *                  )
     *              This format lets you arbitrarily configure any property of any type of widget.
     *              For each property, you can choose to use the same 'value' for EVERY widget, or you can use a different value for each widget, the nth item in 'value' for the nth instance.
     * @return assoc_array An array of 'widgetID' => widget for all newly created widgets.
     */

    function magicMatrix(WFArrayController $arrayController, WFForm $parentForm, $widgetClass, $widgetBaseName, $useUniqueNames, $widgetValueOptions)
    {
        $newWidgets = array();

        if (!class_exists($widgetClass)) throw( new Exception("There is no widget class '$widgetClass'.") );

        $currentIndex = 0;
        foreach ($arrayController->arrangedObjects() as $object) {
            // instantiate the widget with a unique ID
            if ($arrayController->usingIndexedMode())
            {
                $id = $widgetBaseName . '_' . $currentIndex;
            }
            else
            {
                $id = $widgetBaseName . '_' . $arrayController->identifierHashForObject($object);
            }
            $widget = new $widgetClass($id, $parentForm->page());
            $parentForm->addChild($widget);

            // add to our list
            $newWidgets[$id] = $widget;
            
            // set up the name
            if (!$useUniqueNames)
            {
                $widget->setName($widgetBaseName);
            }

            WFLog::log("WFMatrix:: created $widgetClass id=$id name=" . $widget->name());

            // set up properties
            foreach ($widgetValueOptions as $propName => $propInfo) {
                // set up custom value and/or binding
                if (!isset($propInfo['custom']) and !isset($propInfo['binding']))
                {
                    throw( new Exception("You must supply either 'custom' or 'binding' information. (propName: {$propName})") );
                }

                $customValue = NULL;
                if (isset($propInfo['custom']))
                {
                    $customSettings = $propInfo['custom'];
                    // make sure it has all expected pieces
                    if (!isset($customSettings['iterate'])) throw( new Exception("An 'iterate' setting must be provided. (propName: {$propName})") );
                    
                    // use a different value for each iteration
                    if ($customSettings['iterate'])
                    {
                        // use the keyPath of the current object
                        if (isset($customSettings['keyPath']))
                        {
                            $customValue = $object->valueForKeyPath($customSettings['keyPath']);
                        }
                        // use the nth item from the value array
                        else
                        {
                            if (!is_array($customSettings['value'])) throw( new Exception('If iterate and you supply a value, value must be an array. (propName: {$propName})') );
                            $customValue = $customSettings['value'][$currentIndex];
                        }
                    }
                    // use the same value for each iteration.
                    else
                    {
                        if (!isset($customSettings['value'])) throw( new Exception("If not iterate, a 'value' must be provided. (propName: {$propName})") );
                        $customValue = $customSettings['value'];
                    }

                    WFLog::log("WFMatrix:: setting $propName to $customValue for $id", WFLog::TRACE_LOG);
                    $widget->setValueForKey($customValue, $propName);
                }
                // or are we using bindings
                if (isset($propInfo['bind']))
                {
                    $bindingInfo = $propInfo['bind'];

                    WFLog::log("WFMatrix:: Binding property '$propName' to {$bindingInfo['instanceID']} => {$bindingInfo['controllerKey']}::{$bindingInfo['modelKeyPath']}", WFLog::TRACE_LOG);

                    // determine object to bind to:
                    if (!isset($bindingInfo['instanceID'])) throw( new Exception("No instance id specified for binding property '{$propName}'.") );
                    $bindToObject = NULL;
                    if ($bindingInfo['instanceID'] == '#module#')
                    {
                        $bindToObject = $parentForm->page()->module();
                    }
                    else if ($bindingInfo['instanceID'] == '#custom#')
                    {
                        if (!is_object($customValue)) throw( new Exception("Could not determine custom bindToObject ({$bindingInfo['instanceID']}) for binding property '{$propName}'.") );
                        $bindToObject = $customValue;
                    }
                    else if ($bindingInfo['instanceID'] == '#current#')
                    {
                        $bindToObject = $object;
                    }
                    else
                    {
                        $bindToObject = $parentForm->page()->outlet($bindingInfo['instanceID']);
                    }
                    if (is_null($bindToObject)) throw( new Exception("Could not determine bindToObject ({$bindingInfo['instanceID']}) for binding property '{$propName}'.") );

                    $fullKeyPath = '';
                    if (isset($bindingInfo['controllerKey']))
                    {
                        $fullKeyPath .= $bindingInfo['controllerKey'];
                    }
                    if (isset($bindingInfo['modelKeyPath']))
                    {
                        if (!empty($fullKeyPath)) $fullKeyPath .= '.';
                        $fullKeyPath .= $bindingInfo['modelKeyPath'];
                    }
                    if (empty($fullKeyPath)) throw( new Exception("No keyPath specified for binding property '{$propName}'") );

                    // process options
                    $options = NULL;
                    if (isset($bindingInfo['options']))
                    {
                        $options = $bindingInfo['options'];
                    }

                    try {
                        $widget->bind($propName, $bindToObject, $fullKeyPath, $options);
                    } catch (Exception $e) {
                        print_r($bindingInfo);
                        throw($e);
                    }
                }
            }

            // have widget restore state, only if we've posted! otherwise it will grab improper state
            if ($parentForm->page()->submittedFormName())
            {
                $widget->restoreState();
            }

            $currentIndex++;
        }

        return $newWidgets;
    }
}

?>
