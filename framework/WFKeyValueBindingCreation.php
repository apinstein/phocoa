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
 * Informal protocol for the Key Value Binding mechanism.
 *
 * The default implementation of Key Value Binding is currently in WFWidget.
 *
 * The only method subclasses are likely to need to overload is {@link setupExposedBindings}.
 */
interface WFKeyValueBindingCreation
{
    /**
     * Bind a property of the receiver's object to another object via KeyValueCoding
     *
     * The default implementation of this function (presently in WFWidget) should be sufficient.
     *
     * @param string The name of the local property to bind.
     * @param object An object to bind the property to.
     * @param string The keyPath on the object to bind the property to.
     * @param array Options for the binding. {@link exposedBindings}
     * @throws If the receiver's property does not exist, or it is already bound.
     */
    function bind($bindLocalProperty, $bindToObject, $bindToKeyPath, $options = array());
    /**
     * Remove the binding for the passed property of the receiver.
     *
     * The default implementation of this function (presently in WFWidget) should be sufficient.
     *
     * @param string The name of the local property to bind.
     * @throws If the receiver's property is not bound.
     */
    function unbind($bindLocalProperty);
    /**
     * Get a list of all exposed bindings for this class, and the options for each binding.
     *
     * Will call setupExposedBindings as a callback so subclasses can manifest all bindings options.
     *
     * The default implementation of this function (presently in WFWidget) should be sufficient.
     *
     * @internal For efficiency, since bindings setup DO NOT CHANGE AT RUNTIME, the exposed bindings are stored in a static function variable.
     *           NOTE: I would have liked to store this info in a static class variable and make this class static, but in PHP 5.0.x
     *           self::functionName in a parent class, when run on behalf of a child, cannot access the child self::callback, thus making
     *           that method infeasible. The downside is that to get the bindings you have to instantiate the class, which isn't a big deal.
     *
     * @return assoc_array of WFBindingSetup objects, 'boundProperty' => WFBindingSetup object
     */
    function exposedBindings();
    /**
     * Return an array of all bindings this object supports.
     *
     * The default implementation in WFWidget has no bindings. This is a method many subclasses will overload.
     *
     * Subclasses should call the parent, then add their own setups as needed. Example:
     * <code>
     *      $bindings = parent::setupExposedBindings();
     *      $bindings[] = new WFBindingSetup('prop', 'Description of prop', $optionsArray);
     *      return $bindings;
     * </code>
     *
     * @return array A list of all exposed bindings {@link WFBindingSetup}.
     */
    function setupExposedBindings();
}
?>
