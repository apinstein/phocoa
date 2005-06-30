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

    function __construct()
    {
        parent::__construct();
        $this->content = NULL;
        $this->class = 'WFDictionary';
        $this->automaticallyPreparesContent = true;
    }

    function setClass($class)
    {
        $this->class = $class;
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
        $this->content = $obj;
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
