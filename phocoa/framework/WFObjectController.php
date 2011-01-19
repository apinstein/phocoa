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
 * @see WFKeyValueCoding
 * @see WFKeyValueBindingCreation
 * @see WFDecorator
 *
 * The WFObjectController provides a controller-layer compatible container for a single object.
 *
 * The following are legitimate Controller Keys for this controller:
 *   selection
 *   selectedObjects
 *   isEditable      UNIMPLEMENTED
 *   canAdd          UNIMPLEMENTED
 *   canRemove       UNIMPLEMENTED
 *
 * The following properties of the WFObjectController can be bound:
 *   content
 *   editable        UNIMPLEMENTED
 *
 * @todo Upgrade to cocoa-compatible defaults
 *       automaticallyPreparesContent FALSE --> BC-breaking CHANGE; will require testing all apps that use it
 *       avoidsEmptySelection TRUE
 *       selectsInsertedObjects TRUE
 *       (not yet implemented)
 *       editable TRUE
 *       preservesSelection TRUE
 */
class WFObjectController extends WFObject
{
    /**
     * @var object The object being managed by this controller instance.
     */
    protected $content;
    /**
     * @var string The class name of the content.
     */
    protected $class;
    /**
     * @var boolean Does the controller create an instance of the controller class type if there is not one already?
     */
    protected $automaticallyPreparesContent;
    /**
     * @var array An array of WFDecorator objects that will be used to wrap the content object(s).
     */
    protected $decorators;

    function __construct()
    {
        parent::__construct();
        $this->content = NULL;
        $this->decorators = NULL;
        $this->class = 'WFDictionary';
        $this->automaticallyPreparesContent = true;
    }

    /**
     * Set the class name that is managed by this object controller.
     *
     * @param string Class name.
     */
    function setClass($class)
    {
        $this->class = $class;
    }

    /**
     * Set a {@link object WFDecorator} object to be used with this controller.
     *
     * @param string The name of the decorator class.
     */
    function setDecorator($decoratorClassName)
    {
        $this->decorators = array($decoratorClassName);
    }

    /**
     * Set multiple {@link object WFDecorator} objects to be used with this controller.
     *
     * @param string The name(s) of the decorator class(es) to be used to decorate objects managed by this controller. Names should be separated by commas; LAST one wins.
     */
    function setDecorators($decoratorList)
    {
        if (is_string($decoratorList))
        {
            $decoratorList = array_map('trim', explode(',', $decoratorList));
        }
        $this->decorators = $decoratorList;
    }

    /**
     * Make sure the passed object is of the type that this array controller manages.
     *
     * NOTE: This check will succeed as long as the object is a instance of the class, subclass, or implements the interface, in {@link WFArrayController::$class class}.
     * NOTE: {@link WFArrayController::insert() insert} enforces an additional check, in that {@link WFArrayController::$class class} must be a class, so that it is instantiable.
     *
     * @param object WFObject An instance of the object to check.
     * @throws object WFException If the passed object is not of the type managed by this ArrayController.
     */
    function checkObjectClass($obj)
    {
        if ($obj === NULL) throw( new WFException("NULL passed instead of object of type {$this->class}.") );
        if (!is_object($obj)) throw( new WFException("Passed parameter is not an object.") );
        $obj = $this->undecorateObject($obj);
        if (!($obj instanceof $this->class)) throw( new WFException("Object must be of type managed by this array controller.") );
    }

    /**
     * Decorate the passed object with the decorator(s) for this controller.
     */
    protected function decorateObject($o)
    {
        if (!$this->decorators) return $o;
        foreach ($this->decorators as $d) {
            $o = new $d($o);
        }
        return $o;
    }

    protected function undecorateObject($o)
    {
        while ($o instanceof WFDecorator) {
            $o = $o->decoratedObject();
        }
        return $o;
    }

    /**
     * Prepare the controller's content.
     * Basically, this will make sure that an instance exists. If one doesn't, a new instance of the class will be created and used as the content.
     */
    protected function prepareContent()
    {
        if (!$this->automaticallyPreparesContent) return;

        if (is_null($this->content))
        {
            if (!class_exists($this->class)) throw( new Exception("Class {$this->class} does not exist. ObjectController cannot automatically prepare.") );
            $this->setContent(new $this->class());
        }
    }

    /**
     * Set the content managed by this controller instance.
     *
     * If there already is a content, this will replace it.
     *
     * @param object The object to manage. This must be a WFObject subclass, or potentially another object that implements Key-Value Coding.
     */
    function setContent($obj)
    {
        if (!is_object($obj)) throw( new Exception("The passed content must be a WFObject subclass.") );
        $this->content = $this->decorateObject($obj);
    }

    /**
     * Clear the content.
     */
    function clearContent()
    {
        $this->content = NULL;
    }

    /**
     * Get an array with the selected objects.
     * @return An array containing the selected objects, or an empty array if there is no selection.
     */
    function selectedObjects()
    {
        $this->prepareContent();
        if ($this->selection())
        {
            return array($this->selection());
        }
        else
        {
            return array();
        }
    }

    function selection()
    {
        $this->prepareContent();
        return $this->content;
    }

    function content()
    {
        $this->prepareContent();
        return $this->content;
    }


    function setAutomaticallyPreparesContent($prepare)
    {
        if (!is_bool($prepare)) throw( new Exception("setAutomaticallyPreparesContent requires a boolean parameter.") );
        $this->automaticallyPreparesContent = $prepare;
    }

}
?>
