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
    /**
     * @var string The name of the bound proprety.
     */
    protected $boundProperty;
    /**
     * @var string A brief description of the binding's purpose.
     */
    protected $description;
    /**
     * @var assoc_array A list of all supported option keys for this binding, along with the default value.
     */
    protected $options;

    function __construct($boundPropName, $boundPropDescription = NULL, $options = NULL)
    {
        parent::__construct();
        if (empty($boundPropName)) throw( new Exception("You must supply a name for the bound property.") );
        $this->boundProperty = $boundPropName;

        $this->description = $boundPropDescription;

        if (is_null($options))
        {
            $options = array();
        }
        $this->options = $options;
    }

    function boundProperty()
    {
        return $this->boundProperty;
    }

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
 */
class WFBinding extends WFObject
{
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
    
    function __construct()
    {
        parent::__construct();
        $this->bindToObject = NULL;
        $this->bindToKeyPath = NULL;
        $this->valueTransformer = NULL;
        $this->placeholders = array();
        $this->raisesForNotApplicableKeys = true;
        $this->options = array();
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
