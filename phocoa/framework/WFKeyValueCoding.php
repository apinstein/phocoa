<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package KeyValueCoding
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/** 
 * Key-Value Coding protocol.
 *
 * The basic idea behind KVC is to be able to manipulate "properties" of all objects with the same interface.
 * The key is the property name. A keyPath is a dot-separated string of key chains to call.
 * Example: $book->setValueForKey('Charles Dickens', 'name');
 * Example: $bookList->setValueForKeyPath('Charles Dickens', 'selection.author.name');
 *
 * This class provides the interface for the Key-Value Coding informal protocol.
 *
 * The {@link WFObject} has a default impementation of this interface that should be good for pretty much all of your subclasses.
 *
 * Key Value Naming Conventions:
 *
 * Getters:
 *   <key>(), get<Key>()
 *
 * Setters:
 *   set<Key>($value)
 *
 * Validators:
 *   validate<Key>( params )
 * 
 * Instance Var Names:
 * <key>
 *
 * @see WFObject::valueForKey(), WFObject::valueForKeyPath() , WFObject::setValueForKey() , WFObject::setValueForKeyPath() , WFObject::validateValueForKey() , WFObject::validateValueForKeyPath()
 */
interface WFKeyValueCoding
{
    /**
     * Get the value for the given key.
     *
     * First tries to use a getter method (get<key>) then attempts access on class member.
     *
     * @param string The key to retrive the value for.
     * @return mixed The value for the given key.
     */
    function valueForKey($key);

    /**
     * Set the value for the given key.
     *
     * First tries to use a setter method (set<key>) then attempts access on class member.
     *
     * @param mixed The value to set.
     * @param string The key to set the value for.
     */
    function setValueForKey($value, $key);

    /**
     * Get the value for the given key path.
     *
     * valueForKeypath's default implementation is in {@link WFObject}.
     *
     * The default implementation does some very special things...
     * 1) Magic Arrays
     *    Magic arrays are a way of returning an array of values calculated from an array of objects.
     *    For instance, let's say you have an array of Person objects, for instance in an addressBook application, and that each person has a unique ID.
     *    Now, you want an array containing the ID of every person, but you don't want to have to write a foreach loop to do it.
     *    You could use valueForKeypath's magic instead:
     *    <code>
     *    $arrayOfPersonIDs = $addressBook->valueForKeyPath("people.id")
     *    </code>
     *    And afterwards, arrayOfPersonIDs will have an array containing the ID for each person in the address book, in the same order that the Person objects appear in the array.
     * 2) Calculated values (@count, etc.. not yet implemented)
     *
     * @see valueForKey()
     * @param string The keyPath to retrive the value for.
     * @return mixed The value for the given keyPath.
     */
    function valueForKeyPath($keyPath);

    /**
     * Set the value for the given keyPath.
     *
     * @see setValueForKey()
     * @param mixed The value to set.
     * @param string The keyPath to set the value for.
     */
    function setValueForKeyPath($value, $keyPath);

    /**
     * Validate the given value for the given keypath.
     *
     * The default implementation (in WFObject) finds the target object based on the keypath and then calls the validateValueForKey method.
     *
     * @see validateValueForKey()
     * @param mixed A reference to value to check. Passed by reference so that the implementation can normalize the data.
     * @param string keyPath the keyPath for the value.
     * @param boolean A reference to a boolean. This value will always be FALSE when the method is called. If the implementation edits the $value, set to TRUE.
     * @param array An array of WFError objects describing the error.
     * @return boolean TRUE indicates a valid value, FALSE indicates an error.
     */
    function validateValueForKeyPath(&$value, $keyPath, &$edited, &$errors);

    /**
     * Validate the given value for the given key.
     *
     * Clients can normalize the value, and also report and error if the value is not valid.
     *
     * If the value is valid without modificiation, return TRUE and do not alter $edited or $error.
     * If the value is valid after being modified, return TRUE, and $edited to true.
     * IF the value is not valid, do not alter $value or $edited, but fill out the $error object with desired information.
     *
     * The default implementation (in WFObject) looks for a method named validate<key> and calls it, otherwise it returns TRUE.
     *
     * Classes that wish to provide validators for keys should implement one validator per key, with the following prototype:
     * 
     *      // parameters are the same as for the validateValueForKey function, minus the key name.
     *      // function should return TRUE if valid, FALSE if not valid.
     *      validate<KeyName>(&$value, &$edited, &$errors);
     *
     *      // clients wishing to add errors do so with:
     *      $error[] = new WFError('My error message'); // could also add an error code (string) parameter.
     *
     * @param mixed A reference to value to check. Passed by reference so that the implementation can normalize the data.
     * @param string The key for the value to validate.
     * @param boolean A reference to a boolean. This value will always be FALSE when the method is called. If the implementation edits the $value, set to TRUE.
     * @param array An array of WFError objects describing the error. The array is empty by default; you can add new error entries.
     * @return boolean TRUE indicates a valid value, FALSE indicates an error.
     */
    function validateValueForKey(&$value, $key, &$edited, &$errors);
}
?>
