<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/** 
 * @package KeyValueCoding
 * @subpackage Bindings
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/**
 * At present, we don't implement full, real-time KVO. In the web world, I didn't think we needed it, as you can just pull the data right when you need it.
 * Also, because we cannot modify the runtime environment like Cocoa does, it makes it hard for direct accessor calls to trigger change events since we have 
 * no easy way to detect the calls to setKey($value) and propagate the changes to the observers.
 *
 * So instead, we have a simpler paradigm of SETTING UP the bindings at config time, then loading the values from the FORM onto their objects based on bindings (via pushBindings()).
 * For the other direction, pullBindings() is called by the observer just before it wants to use bound values.
 *
 * While the implementation of pullBindings() in WFWidget is all that's needed to pull bound properties into the objects, for pushBindings(), it's a little more complicated.
 * Only each widget knows WHICH of its bound properties even should be pushed back. For instance, likely the "value" properties will be pushed back, but attributes like size, maxLength,
 * etc. would not be pushed back to the bound object. Additionally, the WFWidgets do some additional magic such as performing validation and maintaining error lists for each widget.
 * Thus, each widget should implement pushBindings() and for each binding that should have its value propagated back to the bound object, it should use the propagateValueToBinding() of
 * the WFWidget subclass to help with this.
 *
 * The only method subclasses are likely to need to overload is {@link processBindingOptions}.
 */
interface WFKeyValueObserving
{
    /**
     * Update the values of all bound properties for the object.
     *
     * Goes through each property, applying the value transformers and giving subclasses a chance to munge values based on bindings options.
     *
     * The default implementation of this function (presently in WFWidget) should be sufficient.
     *
     */
    function pullBindings();

    /**
     * Update the values of all objects bound via bindings.
     *
     * This is the mechanism that is used to "push" the values from the bound properties BACK to their originators to keep changes in sync.
     *
     * The default implementation of this function (presently in WFWidget) should be sufficient.
     */
    function pushBindings();

    /**
      * This is essentially a callback that allows subclasses to munge the bound value based on bindings options.
      * 
      * The default implementation in WFWidget has no bindings. This is a method many subclasses will overload.
      *
      * All subclasses should call the super method first, like so:
      * 
      *     parent::processBindingOptions($boundProperty, $options, $boundValue);
      *     // make changes to the boundValue as appropriate for the current property.
      *
      * @param string The name of the bound property being processed.
      * @param assoc_array The binding options for this binding. These values have already been coalesced with the default values manifested in {@link setupExposedBindings}.
      * @param reference A reference to the bound value, so that you can change it based on options as needed.
      */
    function processBindingOptions($boundProperty, $options, &$boundValue);
}

/**
 * The WFBindingSetup object contains all of the static information about available bindings for a class.
 *
 * This information can be used for documentation, to get the default values for certain options at runtime, etc.
 *
 * These instances are typically created in the {@link setupExposedBindings} callback of the WFKeyValueBindingCreation protocol.
 *
 */
class WFBindingSetup extends WFObject
{
    // All bindings are one of these types
    const WFBINDINGTYPE_SINGLE = 0;
    const WFBINDINGTYPE_MULTIPLE_BOOLEAN = 1;
    const WFBINDINGTYPE_MULTIPLE_PATTERN = 2;

    // options for BOOLEAN bindings
    const WFBINDINGTYPE_MULTIPLE_BOOLEAN_AND = 1;
    const WFBINDINGTYPE_MULTIPLE_BOOLEAN_OR = 2;

    // info for PATTERN bindings
    const WFBINDINGSETUP_PATTERN_OPTION_NAME = 'ValuePattern';
    const WFBINDINGSETUP_PATTERN_OPTION_VALUE = '%1%';

    /**
     * @var string The name of the bound proprety.
     */
    protected $boundProperty;
    /**
     * @var int The type of the binding. One of WFBINDINGTYPE_SINGLE, WFBINDINGTYPE_MULTIPLE_BOOLEAN, WFBINDINGTYPE_MULTIPLE_PATTERN.
     */
    protected $bindingType;
    /**
     * @var int The boolean mode for WFBINDINGTYPE_MULTIPLE_BOOLEAN bindings.
     */
    protected $booleanMode;
    /**
     * @var string A brief description of the binding's purpose.
     */
    protected $description;
    /**
     * @var assoc_array A list of all supported option keys for this binding, along with the default value.
     */
    protected $options;
    /**
     * @var boolean Is the binding read-only?
     */
    protected $readOnly;

    function __construct($boundPropName, $boundPropDescription = NULL, $options = NULL)
    {
        parent::__construct();
        if (empty($boundPropName)) throw( new Exception("You must supply a name for the bound property.") );
        $this->boundProperty = $boundPropName;

        $this->description = $boundPropDescription;
        $this->readOnly = false;
        $this->bindingType = WFBindingSetup::WFBINDINGTYPE_SINGLE;
        $this->booleanMode = WFBindingSetup::WFBINDINGTYPE_MULTIPLE_BOOLEAN_AND;

        if (is_null($options))
        {
            $options = array();
        }
        $this->options = $options;
    }

    /**
     *  Get the boolean mode of a boolean binding.
     *
     *  @return int One of WFBindingSetup::WFBINDINGTYPE_MULTIPLE_BOOLEAN_AND or WFBindingSetup::WFBINDINGTYPE_MULTIPLE_BOOLEAN_OR
     */
    function booleanMode()
    {
        return $this->booleanMode;
    }

    /**
     *  Set the boolean mode of a boolean binding.
     *
     *  Boolean bindings will combine multiple values by logical AND or logical OR depending on the booleanMode.
     *
     *  @param int One of WFBindingSetup::WFBINDINGTYPE_MULTIPLE_BOOLEAN_AND or WFBindingSetup::WFBINDINGTYPE_MULTIPLE_BOOLEAN_OR.
     *  @throws Exception if the passed mode is not valid.
     */
    function setBooleanMode($mode)
    {
        if ($mode !== WFBindingSetup::WFBINDINGTYPE_MULTIPLE_BOOLEAN_AND and $mode !== WFBindingSetup::WFBINDINGTYPE_MULTIPLE_BOOLEAN_OR) throw( new Exception("Invalid booleanMode: $mode.") );
        $this->booleanMode = $mode;
    }

    /**
     *  Get the bindingType.
     *
     *  @return int One of the binding types.
     *  @see WFBindingSetup::setBindingType()
     */
    function bindingType()
    {
        return $this->bindingType;
    }

    /**
     *  Set the type of this binding.
     *
     *  There are multiple binding types, each of which provides certain behaviors for the binding.
     *  1. WFBINDINGTYPE_SINGLE Is the simplest type; this binding type maps data from an object to the UI and potentially vice-versa (if it is not {@link WFBindingSetup::$readOnly read only).
     *  2. WFBINDINGTYPE_MULTIPLE_BOOLEAN Links multiple values together, combined by {@link WFBindingSetup::$booleanMode booleanMode}.
     *  3. WFBINDINGTYPE_MULTIPLE_PATTERN Allows for the creation of a string based on a patter, filled with multiple values.
     *
     *  All of the multiple binding types are read-only by definition because the combination is not reversible.
     *
     *  @param int One of the allowed bindingTypes.
     *  @throws Exception if an illegal type is passed.
     */
    function setBindingType($type)
    {
        switch ($type) {
            case WFBindingSetup::WFBINDINGTYPE_MULTIPLE_BOOLEAN:
            case WFBindingSetup::WFBINDINGTYPE_MULTIPLE_PATTERN:
                $this->setReadOnly(true);
                break;

            case WFBindingSetup::WFBINDINGTYPE_SINGLE:
                break;

            default:
                throw( new Exception("Unknown binding type: $type.") );
        }
        $this->bindingType = $type;
    }

    /**
     *  Is this binding read-only?
     *
     *  @return boolean
     */
    function readOnly()
    {
        return $this->readOnly;
    }

    /**
     *  Set whether this binding is read-only.
     *
     *  Read-only bindings will not propagate data from the UI to the bound object.
     *
     *  @param boolean TRUE to set the binding as read-only.
     */
    function setReadOnly($ro)
    {
        $this->readOnly = $ro;
    }

    /**
     *  The name of the property of the object that this binding setup applies to.
     *
     *  @return string
     */
    function boundProperty()
    {
        return $this->boundProperty;
    }

    /**
     *  A human-readable description of the binding.
     *
     *  @return string
     */
    function description()
    {
        return $this->description;
    }

    /**
     * Get the default option for the given binding option name.
     * @param string The name of the option.
     * @return mixed The default value for the option.
     */
    function getOptionDefault($optName)
    {
        if (!isset($this->options[$optName])) throw( new Exception("Option {$optName} does not exist for property {$this->boundProperty}.") );
        return $this->options[$optName];
    }
    
    function options()
    {
        return $this->options;
    }
}

/** 
 * The binding object.
 *
 * The binding object encapsulates all information about a particular bound property of an object. These instances are created at runtime each time the
 * {@link bind} function of the WFKeyValueBindingCreation protocol is used.
 * 
 * @todo Incorporate commonly used binding options here??? In addition to valueTransformer?
 * http://developer.apple.com/documentation/Cocoa/Reference/CocoaBindingsRef/Concepts/BindingsOptions.html
 */
class WFBinding extends WFObject
{
    // commonly used binding options available globally
    const VALUE_TRANSFORMER_NAME = "valueTransformer";

    /**
     * @var object The object that this property is bound to.
     */
    protected $bindToObject;
    /**
     * @var string The keyPath on the {@link $bindToObject} that this binding is bound to.
     */
    protected $bindToKeyPath;

    /**
     * @var boolean Should an exception be raised if the binding cannot be resolved? If false, the bindings system may log the exception.
     */
    protected $raisesForNotApplicableKeys;

    /**
     * @var assoc_array All of the various options that the binding supports. Each binding may have extra properties that it needs to know, and 
     *                  can be configured.
     */
    protected $options;

    /**
     * @var object WFBindingSetup A reference to the binding setup for this binding.
     */
    protected $bindingSetup;
    /**
     * @var string The name of the local property of the object being bound.
     */
    protected $bindLocalProperty;
    
    function __construct()
    {
        parent::__construct();
        $this->bindToObject = NULL;
        $this->bindToKeyPath = NULL;
        $this->valueTransformer = NULL;
        $this->placeholders = array();
        $this->raisesForNotApplicableKeys = true;
        $this->options = array();
        $this->bindingSetup = NULL;
        $this->bindLocalProperty = NULL;
    }

    /**
     *  The {@link WFBindingSetup} object that this binding instance is for.
     *
     *  @return object WFBindingSetup
     */
    function bindingSetup()
    {
        return $this->bindingSetup;
    }

    /**
     *  Set the {@link WFBindingSetup} object that this binding instance is for.
     *
     *  In the case of Multi-Value bindings, each binding instance will point to the same "base" WFBindingSetup.
     *
     *  @param object WFBindingSetup
     */
    function setBindingSetup($bs)
    {
        if ( !($bs instanceof WFBindingSetup) ) throw( new Exception("The value passed to setBindingSetup is not a WFBindingSetup instance.") );
        $this->bindingSetup = $bs;
    }

    /**
     *  The property of the object that this binding applies to.
     *
     *  @return string
     */
    function bindLocalProperty()
    {
        return $this->bindLocalProperty;
    }
    
    /**
     *  Set the property of the object that this binding applies to.
     *
     *  @param string
     */
    function setBindLocalProperty($localPropName)
    {
        $this->bindLocalProperty = $localPropName;
    }

    function raisesForNotApplicableKeys()
    {
        return $this->raisesForNotApplicableKeys;
    }

    function bindToKeyPath()
    {
        return $this->bindToKeyPath;
    }
    function setBindToKeyPath($keyPath)
    {
        $this->bindToKeyPath = $keyPath;
    }

    function bindToObject()
    {
        return $this->bindToObject;
    }
    function setBindToObject($obj)
    {
        $this->bindToObject = $obj;
    }

    function options()
    {
        return $this->options;
    }

    function setOption($name, $value)
    {
        $this->options[$name] = $value;
    }
    function setOptions($options)
    {
        $this->options = $options;
    }

    function valueTransformerName()
    {
        if (isset($this->options[WFBinding::VALUE_TRANSFORMER_NAME]))
        {
            return $this->options[WFBinding::VALUE_TRANSFORMER_NAME];
        }
        else
        {
            return NULL;
        }
    }

}
?>
