<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package KeyValueCoding
 * @subpackage ControllerLayer
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/** 
 * The main ObjectController from the Controller Layer.
 *
 * WFDecorator objects are used to transparently wrap objects managed by the ControllerLayer while allowing you to implement UI-specific object formatters without having
 * to place these functions in the core model object. WFDecorator objects are often defined in the UI Controller classes directly since the UI logic contained in them
 * is often customized for just that UI view.
 *
 * However of course you could create a decorator to be shared across many UI objects.
 *
 * In Cocoa something like this would probably be done by making a Category for your model object.
 *
 * @see WFKeyValueCoding
 */
class WFDecorator extends WFObject
{
    /**
     * @var object The object being decorated by this decorator instance.
     */
    protected $decoratedObject;

    function __construct($decoratedObject)
    {
        parent::__construct();
        if (!($decoratedObject instanceof WFObject)) throw new WFException("WFDecorator needs a WFObject subclass.");
        $this->decoratedObject = $decoratedObject;
    }

    /**
     * Get the underlying decorated object.
     *
     * return object
     */
    function decoratedObject()
    {
        return $this->decoratedObject;
    }

    /**
     * Local implementation of valueForKey() that looks for the local version in the decorator, and if that fails, passes the request through to the underlying object.
     *
     * @param string Key requested.
     * @return mixed
     * @throws object Exception
     */
    function valueForKey($k)
    {
        try {
            return parent::valueForKey($k);
        } catch (WFUndefinedKeyException $e) {
            return $this->decoratedObject->valueForKey($k);
        }
    }
    function setValueForKey($v, $k)
    {
        try {
            return parent::setValueForKey($v, $k);
        } catch (WFUndefinedKeyException $e) {
            return $this->decoratedObject->setValueForKey($v, $k);
        }
    }

    /**
     * Capture function calls and pass try to handle them locally, then pass-through. Basically same idea as {@link valueForKey()}
     *
     * @param string The method called.
     * @param array The arguments.
     * @return mixed
     */
    function __call($name, $args)
    {
        if (method_exists($this, $name))
        {
            return call_user_func_array(array($this, $name), $args);
        }
        return call_user_func_array(array($this->decoratedObject, $name), $args);
    }

    function __toString()
    {
        return "Object decorated with " . get_class($this) . ": {$this->decoratedObject}";
    }
}
?>
