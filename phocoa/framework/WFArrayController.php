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
 * The ArrayController class.
 *
 * This is the Controller Layer object for managing arrays.
 *
 * In Cocoa, the NSArrayController manages selection by index. They can do this because in the desktop world, once you've set up your array, it stays around forever.
 * Thus when the user changes a textfield bound to the nth item in the array, it's simple to lookup the nth item and call the setValueForKey method on the nth item.
 * However in the HTTP world (especially stateless PHP) we have to re-build or array each request. Thus, the nth item when you DREW the textfield might not be the
 * nth item when you're pushing the new value from the text field back to the object!
 *
 * So, our WFArrayController has some extra magic to help with this problem. First off, arrays in PHP are different than NSArrays in that they are a blending of an
 * array and a dictionary. Since we always have associative arrays to work with, our WFArrayController will manage its content more as a dictionary and less
 * as an array. Also, the selection will be managed by keeping a list of selected keys rather than indexes. What key will be used? Well, the  WFArrayController
 * also has an identifier property, which allows you to control which key(s) of the managed class is used for the selected keys. By default, the identifier
 * property is WFArrayController::USE_ARRAY_INDEXES_AS_ID, which is a special flag to actually use numeric indexing. This way, the WFArrayController will act very similarly to a
 * NSArrayController.
 * 
 * However, if you want the WFArrayController to manage your objects by ID, then you can set the identifier to another list of key(s).
 *
 * @see WFKeyValueCoding
 * @see WFKeyValueBindingCreation
 * @todo Should there be a "canSelectMultiple" setting? This was if you're using it in a Master-Detail interface it'd be easier to manage the selection? In this case a combo of selectOnInsert and addObject would make it easy to managed. NO! This is an attribute of the "matrix" widget, not the controller. ArrayController should always be "on" for multiple selections.
 * @todo Normalize property names. Keep naming convention; properties should read like "class property -> WFArrayController selectsInsertedObjects"
 * @todo Upgrade to cocoa-compatible defaults
 *       DONE! avoidsEmptySelection TRUE - ADD this function
 *       DONE! selectsInsertedObjects TRUE - deprecate selectOnInsert, but keep func for BC. WE will keep our FALSE because it makes more sense.
 *       Something is wrong with #arrayIndexes# mode I think... see todo's
 *       (not yet implemented)
 *       preservesSelection TRUE
 */
class WFArrayController extends WFObjectController implements Iterator
{
    const USE_ARRAY_INDEXES_AS_ID = '#arrayIndexes#';
    const ID_DELIMITER = '|';

    /**
     * @var array An array of identifier key(s) to use on the managed class. By default, this is array(WFArrayController::USE_ARRAY_INDEXES_AS_ID). Will be array(id) or array(id1,id2).
     */
    protected $classIdentifiers;
    /**
     * @var boolean Are we using WFArrayController::USE_ARRAY_INDEXES_AS_ID?
     */
    protected $usingIndexedMode;
    /**
     * @var array An array of all selected Identifiers. Format is [idHash] => 1. This prevents accidentally having the same selection in the list twice.
     */
    protected $selectedIdentifiersHash;
    /**
     * @var boolean Does the controlled class use mutli-key ids?
     */
    protected $classIdentifiersMulti;
    /**
     * @var boolean TRUE if all objects should be added to the selection upon adding them to the content. This is useful if you want all objects to be selected by default.
     */
    protected $selectsInsertedObjects;
    /**
     * @var boolean TRUE If you want there to always be at least one selected object. Will always keep at least one object selected unless there are no arranged objects.
     */
    protected $avoidsEmptySelection;

    /**
     * @var int Iterator index, for the Iterator interface.
     */
    protected $iteratorIndex;
    /**
     * @var int Iterator object list, for the Iterator interface.
     */
    protected $iteratorObjects;

    /**
     * @var int An integer which increments with each change. Used by WFDynamic to see if it needs to re-call createWidgets. Eventually will be replaced by observing.
     */
    protected $changeCount;
    
    function __construct()
    {
        parent::__construct();

        $this->content = array();
        $this->changeCount = 0;

        $this->setClassIdentifiers(WFArrayController::USE_ARRAY_INDEXES_AS_ID);
        $this->selectedIdentifiersHash = array();
        $this->selectsInsertedObjects = false;
        $this->avoidsEmptySelection = true;
    }

    function changeCount()
    {
        return $this->changeCount;
    }

    /**
     *  If true, the array controller will automatically add all objects inserted to the selection.
     *
     *  @param boolean
     */
    function setSelectsInsertedObjects($bool)
    {
        $this->selectsInsertedObjects = $bool;
    }

    function selectsInsertedObjects()
    {
        return $this->selectsInsertedObjects;
    }

    /**
     *  @deprecated Use setSelectsInsertedObjects
     */
    function setSelectOnInsert($bool)
    {
        $this->setSelectsInsertedObjects($bool);
    }

    /**
     *  If true, the array controller will always make sure that there is at least one object selected, as long as the array controller isn't empty.
     *
     *  @param boolean
     */
    function setAvoidsEmptySelection($bool)
    {
        $this->avoidsEmptySelection = $bool;
    }

    function avoidsEmptySelection()
    {
        return $this->avoidsEmptySelection;
    }

    function classIdentifiers()
    {
        return $this->classIdentifiers;
    }

    function usingIndexedMode()
    {
        return $this->usingIndexedMode;
    }

    /**
     * Set the id key(s) used to generate Identifiers for the managed objects.
     *
     * For instance, if the WFArrayController's class is Person, maybe you'd pass 'uid'. If the WFArrayController's class is MyTwoColumnPKObject, you'd pass array('col1', 'col2').
     *
     * NOTE: calling this function resets the selection to EMPTY since the previous id's are no longer valid.
     *
     * @param mixed The ID key(s) to use. If it's an array, it should be an array of strings. The managed object should be KVC compliant for the passed keys.
     *              If it's a string, the managed object should be KVC compliant for that key.
     */
    function setClassIdentifiers($id)
    {
        if (is_array($id))
        {
            $this->classIdentifiers = $id;
            $this->usingIndexedMode = false;
            $this->classIdentifiersMulti = true;
        }
        else if (is_string($id))
        {
            $this->classIdentifiersMulti = false;
            $this->classIdentifiers = array($id);
            if ($id == WFArrayController::USE_ARRAY_INDEXES_AS_ID)
            {
                $this->usingIndexedMode = true;
            }
            else
            {
                $this->usingIndexedMode = false;
            }
        }
        else
        {
            throw( new WFException("Identifiers must be either an array of strings or a single string.") );
        }

        $this->clearSelection();
    }

    /**
     *  Is the given identifier selected in the array?
     *
     *  @param mixed ID information, either an array or a string/integer.
     *  @return boolean
     *  @throws object WFException
     */
    function identifierIsSelected($id)
    {
        $hash = $this->identifierHashForValues($id);
        return $this->hashIsSelected($hash);
    }

    /**
     * Convert the passed id or id list into a single-string-id-key.
     * @param mixed ID information, either an array or a string/integer.
     * @return Given an array, will return a string containing all Identifiers joined by WFArrayController::ID_DELIMITER. Given a string or integer, will just return the string (this is the usual case for single-key ids). 
     */
    function identifierHashForValues($id)
    {
        if ($this->classIdentifiersMulti)
        {
            if (!is_array($id)) throw( new WFException("Array of values required when there is more than one classIdentifier.") );
            return join(WFArrayController::ID_DELIMITER, $id);
        }
        else
        {
            return $id;
        }
    }

    function identifierValuesForHash($hash)
    {
        if ($this->classIdentifiersMulti)
        {
            return explode(WFArrayController::ID_DELIMITER, $hash);
        }
        else
        {
            return $hash;
        }
    }

    /**
      * Get an array of the values for the classIdentifiers for the object.
      * @param object An object of the class managed by our WFArrayController.
      * @return mixed If multi-key, return an array of values for the classIdentifiers of our object, in order. If single-key, returns the value.
      * @throws If usingIndexedMode or the object is not of the class managed by our controller.
      */
    function identifierValuesForObject($obj)
    {
        if ($this->usingIndexedMode) throw( new WFException('Cannot call identifierValuesForObject() when using WFArrayController::USE_ARRAY_INDEXES_AS_ID.') );
        $this->checkObjectClass($obj);
        //if (!is_object($obj) or !($obj instanceof $this->class)) throw( new WFException("Passed object not of class {$this->class} as expected.") );

        if ($this->classIdentifiersMulti)
        {
            $identifierValues = array();
            foreach ($this->classIdentifiers as $key) {
                $identifierValues[] = $obj->valueForKey($key);
            }
            return $identifierValues;
        }
        else
        {
            return $obj->valueForKey($this->classIdentifiers[0]);
        }
    }

    /**
     * Get a single string ID key for the passed object.
     * @param object An object instance of the class managed by this instance of WFArrayController.
     * @return string A string with the unique identifier hash for the passed object.
     */
    function identifierHashForObject($obj)
    {
        return $this->identifierHashForValues($this->identifierValuesForObject($obj));
    }

    /**
     * Prepare the controller's content.
     * Basically, this will make sure that the array contains an instance. If one doesn't, a new instance of the class will be created and added to the array.
     */
    protected function prepareContent()
    {
        if (!$this->automaticallyPreparesContent) return;

        if (count($this->content) == 0)
        {
            $this->insert();
        }
    }

    /**
     * Get the object specified by the passed identifier.
     * @param mixed The identifier for the object to retrieve. A single ID or array(id1, id2).
     * @return object NULL if the object doesn't exist, the object otherwise.
     */
    function objectForIdentifier($identifier)
    {
        if (isset($this->content[$this->identifierHashForValues($identifier)]))
        {
            return $this->content[$this->identifierHashForValues($identifier)];
        }
        return NULL;
    }

    /**
     * Insert a new instance of the managed class at the end of the contentArray.
     *
     * NOTE: insert() requires that the value of {@link WFArrayController::$class class} is a class, not an interface.
     *
     * @return object The new object.
     */
    function insert()
    {
        if (!class_exists($this->class)) throw( new WFException("Class {$this->class} does not exist. ArrayController cannot automatically prepare.") );
        $newObject = new $this->class();
        $this->addObject($newObject);
        return $newObject;
    }

    /**
     *  Remove the passed object from the array.
     *
     *  Also obvioulsy removes the item from the selection.
     *
     *  NOTE: presently this works in non-indexed mode only.
     *
     *  NOTE: UNTESTED!!!
     *
     *  @param object WFObject An object of the proper class managed by the array controller.
     *  @throws WFException For various circumstances like invalid parameter, couldn't find object, etc.
     */
    function removeObject($obj)
    {
        // check class
        $this->checkObjectClass($obj);
        //if (!class_exists($this->class)) throw( new WFException("Managed class {$this->class} does not exist.") );
        //if (!is_object($obj)) throw( new WFException("Passed object is, well, not an object!") );
        //if (!($obj instanceof $this->class)) throw( new WFException("Passed object not of class {$this->class} as expected, but instead: " . get_class($obj) . '.') );

        // record change
        $this->changeCount++;

        // need to ensure its key is correct!
        if ($this->usingIndexedMode)
        {
            // loop through looking for object
            for ($i = 0; $i < count($this->content); $i++) {
                if ($obj === $this->content[$i])
                {
                    die("removeObject() is unimplemented for usingIndexedMode presently. It's complicated b/c need to adjust all selections since indexes move'");
                    //array_slice();
                    break;
                }
            }
        }
        else
        {
            $hash = $this->identifierHashForObject($obj);
            if (!isset($this->content["$hash"]))
            {
                throw( new WFException("removeObject cannot remove passed object because it is not in the array.") );
            }
            // make sure it's unselected
            $this->removeSelectionIdentifiers(array($hash));
            unset($this->content["$hash"]);
        }

        if ($this->avoidsEmptySelection and $this->selectionCount() == 0 and $this->arrangedObjectCount() > 0)
        {
            // select first object 
            $keys = array_keys($this->content);
            $this->addSelectedObject($this->content[$keys[0]]);
        }
    }

    /**
     *  Remove all selected objects from the array controller.
     *
     *  @throws WFException
     */
    function cullSelectedObjects()
    {
        foreach ($this->selectedObjects() as $obj) {
            $this->removeObject($obj);
        }
        return;
    }

    /**
     *  Remove all unselected objects from the array controller.
     *
     *  @throws WFException
     */
    function cullUnselectedObjects()
    {
        foreach ($this->arrangedObjects() as $obj) {
            if (!$this->objectIsSelected($obj))
            {
                $this->removeObject($obj);
            }
        }
    }

    /**
     * Add an object to the array.
     * @param object An object, must be of proper class.
     * @throws If the object is not of the class managed by our controller.
     */
    function addObject($obj)
    {
        // check class
        $this->checkObjectClass($obj);
        //if (!class_exists($this->class)) throw( new WFException("Managed class {$this->class} does not exist.") );
        //if (!is_object($obj)) throw( new WFException("Passed object is, well, not an object!") );
        //if (!($obj instanceof $this->class)) throw( new WFException("Passed object not of class {$this->class} as expected, but instead: " . get_class($obj) . '.') );

        // record change
        $this->changeCount++;

        // need to ensure its key is correct!
        if ($this->usingIndexedMode)
        {
            $this->content[] = $this->decorateObject($obj);
        }
        else
        {
            $hash = $this->identifierHashForObject($obj);
            $this->content["$hash"] = $this->decorateObject($obj);
        }

        if ($this->selectsInsertedObjects)
        {
            $this->addSelectedObject($obj);
        }

        if ($this->avoidsEmptySelection and $this->selectionCount() == 0)
        {
            $this->addSelectedObject($obj);
        }
    }

    /**
     * Add multiple objects to the array.
     * @param array Array of objects, must be of proper class.
     * @throws If any object is not of the class managed by our controller.
     */
    function addObjects($arr)
    {
        if (!is_array($arr)) throw( new WFException("The first parameter must be a PHP array.") );
        foreach ($arr as $obj) {
            $this->addObject($obj);
        }
    }

    /**
     * Pass an array of objects to use for this array.
     *
     * Simply clears the current content and selection, then calls {@link addObjects}.
     * @param mixed Array or object - An array of objects of the proper class. Or an object of the proper class.
     *              If you pass an object the entire contents of the WFArrayController will be replaced with that single object.
     * @throws If any object is not of the class managed by our controller.
     */
    function setContent($arr)
    {
        if (is_object($arr) and (is_array($arr))) throw( new WFException("I don't understand how to deal with objects that have array interfaces yet") );
        if (is_object($arr)) $arr = array($arr);
        if (!is_array($arr)) throw( new WFException("The passed content must be a PHP array.") );

        // clear content
        $this->content = array();
        // reset selection
        $this->selectedIdentifiersHash = array();

        // add all objects via addObjects() so that ids can be generated as needed.
        $this->addObjects($arr);
    }

    /**
     * Get all of the objects managed by this controller.
     * @return array An array (numerically indexed, not by classIdentifier) of all of the objects we're managing.
     */
    function arrangedObjects()
    {
        $this->prepareContent();
        return array_values($this->content);
    }
    
    /**
     * Get an array with the selected objects.
     * @internal Since our subclass manages array directly, we don't call the parent method.
     * @return An array containing the selected objects, or an empty array if there is no selection.
     */
    function selectedObjects()
    {
        $this->prepareContent();
        $selectedObjects = array();
        foreach (array_keys($this->selectedIdentifiersHash) as $selectedObjectHash) {
            $selectedObjects[] = $this->content["$selectedObjectHash"];
        }
        return $selectedObjects;
    }

    /**
     *  Determine if an object is currently selected.
     *
     *  This is a convenient and fast way to determine if an object is selected in the array controller. Useful if you're iterating
     *  the "arrangedObjects" and want to see if an item is selected.
     *
     *  @param object mixed The object to test. Should be of the same class as managed by this arrayController instance.
     *  @return boolean TRUE if selected, FALSE otherwise.
     *  @throws If the passed item is not an object, or not an object that is managed by this array controller instance.
     */
    function objectIsSelected($obj)
    {
        $this->checkObjectClass($obj);
        //if (!is_object($obj)) throw( new WFException("First argument to objectIsSelected() must be an object.") );
        //if (!($obj instanceof $this->class)) throw( new WFException("Object must be of type managed by this array controller.") );
        $objectHash = $this->identifierHashForObject($obj);
        if (isset($this->selectedIdentifiersHash[$objectHash]))
        {
            return true;
        }
        return false;
    }

    /**
     *  Determine if an object is currently selected based on its hash.
     *
     *  @param string The hash for the object (from {$link identifierHashForObject}).
     *  @return boolean TRUE if selected, FALSE otherwise.
     */
    function hashIsSelected($hash)
    {
        if (isset($this->selectedIdentifiersHash[$hash]))
        {
            return true;
        }
        return false;
    }

    /**
     * Set the selected items of the array.
     *
     * Note: this will CLEAR the existing setting, and only the passed ids will be selected. To ADD to a selection, {@link addSelectionIdentifiers}.
     *
     * @param array An array of ids: array(1,2). The ids, if they are multi-key ids, should be passed as array(array(1,2), array(1,3)).
     */
    function setSelectionIdentifiers($idsToSelect)
    {
        if (!is_array($idsToSelect)) throw( new WFException("Must pass an array of ids.") );

        // reset selection
        $this->selectedIdentifiersHash = array();

        // add to selection
        $this->addSelectionIdentifiers($idsToSelect);
    }

    /**
     * Clear all selected items.
     */
    function clearSelection()
    {
        $this->setSelectionIdentifiers(array());
    }

    /**
     * Return a list of the IDs of all selected objects.
     *
     * @return array An array of identifier values for all selected objects.
     *               If your classIdentifiers is a single item, then it will be an array of values: array(1,2).
     *               If your classIdentifiers is multi-key, then it will be an array of arrays: array( array(1,2), array(1,3) )
     */
    function selectionIdentifiers()
    {
        $selectedValues = array();
        foreach (array_keys($this->selectedIdentifiersHash) as $selectedObjectHash) {
            if ($this->classIdentifiersMulti)
            {
                $selectedValues[] = explode(WFArrayController::ID_DELIMITER, $selectedObjectHash);
            }
            else
            {
                $selectedValues[] = $selectedObjectHash;
            }
        }
        return $selectedValues;
    }

    /**
     * Get the count of the selection.
     * @return integer The number of selected items.
     */
    function selectionCount()
    {
        return count($this->selectedIdentifiersHash);
    }

    /**
     * Get the count of the managed objects.
     * @return integer The number of items.
     */
    function arrangedObjectCount()
    {
        return count($this->content);
    }

    /**
     * Add the passed ids to the selected list.
     *
     * @param array An array of ids: array(1,2). The ids, if they are multi-key ids, should be passed as array(array(1,2),array(1,3)).
     * @throws WFException if the passed parameter is not an array.
     */
    function addSelectionIdentifiers($idsToAdd)
    {
        if (!is_array($idsToAdd)) throw( new WFException("Must pass an array of ids.") );
        if (count($idsToAdd) == 0) return;

        // record change
        $this->changeCount++;

        foreach ($idsToAdd as $id) {
            // only add to selection if they exist!
            $hash = $this->identifierHashForValues($id);
            if (isset($this->content[$hash]))
            {
                $this->selectedIdentifiersHash[$hash] = 1;
            }
        }
    }

    /**
     *  Remove the passed objects from the arrayController's selection.
     *
     *  @param array An array of hashes of the objects to be removed: array(1,2). Hashes are string for both single and multi-key arrays.
     *  @throws WFException if the passed parameter is not an array.
     *  @see identifierHashForObject, identifierHashForValues
     */
    function removeSelectionIdentifiers($idsToRemove)
    {
        if (!is_array($idsToRemove)) throw( new WFException("Must pass an array of ids.") );
        if (count($idsToRemove) == 0) return;

        // record change
        $this->changeCount++;

        foreach ($idsToRemove as $id) {
            // remove from selection, only if it exists!
            if (isset($this->selectedIdentifiersHash[$id]))
            {
                unset($this->selectedIdentifiersHash[$id]);
            }
        }
    }

    /**
     * Add the passed objects to the selection, if each exists in our array.
     *
     * Simply calls addSelectedObject for each item in the passed array.
     *
     * NOTE that this does NOT add the object to the arrayController's content, simply marks the passed object as selected.
     *
     * @param array An array of objects of the class we manage.
     * @throws If any object is not of the class managed by our controller.
     */
    function addSelectedObjects($objs)
    {
        foreach ($objs as $obj) {
            $this->addSelectedObject($obj);
        }
    }

    /**
     * Add the passed object to the selection, if it exists in our array.
     *
     * NOTE that this does NOT add the object to the arrayController's content, simply marks the passed object as selected.
     *
     * @param object An object of the class we manage.
     * @throws If any object is not of the class managed by our controller.
     */
    function addSelectedObject($obj)
    {
        // check class
        $this->checkObjectClass($obj);
        //if (!class_exists($this->class)) throw( new WFException("Managed class {$this->class} does not exist.") );
        //if (!is_object($obj) or !($obj instanceof $this->class)) throw( new WFException("Passed object not of class {$this->class} as expected.") );

        if ($this->classIdentifiersMulti)
        {
            $this->addSelectionIdentifiers(array($this->identifierValuesForObject($obj)));  // this looks like a bug; probably shouldn't be wrapped in an array; need to test!
        }
        else
        {
            $this->addSelectionIdentifiers(array($this->identifierValuesForObject($obj)));
        }
    }

    /**
     * Get the selected object, or NULL if no objects are selected.
     * @return object The seleted object, if there is one object selected; NULL if there is no selection, and throws an exception if there are multiple items selected.
     * @throws WFException If multiple objects are selected.
     */
    function selection()
    {
        $this->prepareContent();
        $selCount = $this->selectionCount();
        if ($selCount > 1) throw( new WFException("Multiple items are selected.") );
        if ($selCount == 0) return NULL;
        $selectedHashes = array_keys($this->selectedIdentifiersHash);
        return $this->content[$selectedHashes[0]];
    }

    /**
     * Iterator interface for WFArrayController.
     */
    function rewind()
    {
        $this->iteratorIndex = 0;
        $this->iteratorObjects = $this->arrangedObjects();
    }

    /**
     * Iterator interface for WFArrayController.
     */
    function valid()
    {
        return $this->iteratorIndex < count($this->iteratorObjects);
    }

    /**
     * Iterator interface for WFArrayController.
     */
    function next()
    {
        $this->iteratorIndex++;
    }

    /**
     * Iterator interface for WFArrayController.
     */
    function current()
    {
        return $this->iteratorObjects[$this->iteratorIndex];
    }

    /**
     * Iterator interface for WFArrayController.
     */
    function key()
    {
        return $this->iteratorIndex;
    }
}
?>
