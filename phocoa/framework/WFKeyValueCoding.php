<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package KeyValueCoding
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/**
 * Exceptions for KVC.
 */
class WFUndefinedKeyException extends WFException {}

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
     *
     * 1. Magic Arrays<br>
     *    Magic arrays are a way of returning an array of values calculated from an array of objects.
     *    For instance, let's say you have an array of Person objects, for instance in an addressBook application, and that each person has a unique ID.
     *    Now, you want an array containing the ID of every person, but you don't want to have to write a foreach loop to do it.
     *    You could use valueForKeypath's magic instead:
     *    <code>
     *    $arrayOfPersonIDs = $addressBook->valueForKeyPath("people.id")
     *    </code>
     *    And afterwards, arrayOfPersonIDs will have an array containing the ID for each person in the address book, in the same order that the Person objects appear in the array.
     *
     * 2. Array Operators, based on: http://developer.apple.com/documentation/Cocoa/Conceptual/KeyValueCoding/Concepts/ArrayOperators.html
     *    In a given keyPath, you can include an operator to perform a calculation on the keyPath to that point, provided that the result is an array: "transactions.@sum.amount"<br>
     *    Operators are preceeded by @:<br>
     *    count - Count of items specified by remainder of keypath.<br>
     *    first - Return the first item in the array, or NULL<BR>
     *    firstNotNull - Return the first non-null item in the array, or NULL<BR>
     *    sum - Sum of items specified by remainder of keypath.<br>
     *    max - Maximum value of items specified by remainder of keypath.<br>
     *    min - Minimum value of items specified by remainder of keypath.<br>
     *    avg - Average of items specified by remainder of keypath.<br>
     *    unionOfArrays - Union of all objects in the arrays specified by remainder of keypath.<br>
     *    unionOfObjects - Union of all items specified by remainder of keypath. Identical to normal magic, ie: books.author == books.@unionOfObjects.author<br>
     *    distinctUnionOfArrays - same as @unionOfArrays but with duplicate objects removed. Duplicates determined by PHP === operator.<br>
     *    distinctUnionOfObjects - same as @unionOfObjects but with duplicate objects removed. Duplicates determined by PHP === operator.<br>
     *
     * 3. Static Method/Property access<br>
     *    If the first part of the keypath contains '::', then "static" mode will be enabled, which allows you to use KVC on an instance of an object to access static methods.
     *    Note that at present all static access must be done with {@link valueForStaticKeyPath()}. This may become more flexible in the future.
     *
     * 4. Inline Decorators<br>
     *    If you want to use a decorator to wrap an object returned in a keyPath, you can use this syntax:
     *    <code>
     *      "parent[MyParentDecorator].fullName"
     *    </code>
     *    This will wrap the object returned by parent in a MyParentDecorator class before calling fullName.
     *
     * 5. Coalescing keyPaths<br>
     *    With valueForKeyPath you can provide multiple keys separated by ; and the first key returning a non-null value will be used. If no key returns a non-null value, the string
     *    after the final ; will be returned.
     *    <code>
     *      "parent.firstName;parent.lastName;No Name"
     *    </code>
     *    If you want to use a literal ; in your default value, escape it with "\;".
     * 6. Escape Hatch<br>
     *    With valueForKeyPath you sometimes end up where a value in the middle of the keyPath can be NULL. By default this will throw a WFUndefinedKeyException.
     *    However, if you prefer to just get back NULL in this case, you can use the escapeHatch.
     *    <code>
     *      "getRelatedObject^.name" - Will return NULL if getRelatedObject is NULL rather than throwing a WFUndefinedKeyException.
     *    </code>
     *
     * @see valueForKey()
     * @param string The keyPath to retrive the value for.
     * @return mixed The value for the given keyPath.
     */
    function valueForKeyPath($keyPath);

    /**
     * Creates an associative array with the set of passed keys and the corresponding values
     *
     * @param array An array of keys.
     * @return array An associative array of the passed in keys, now with values from valueForKey($theKey).
     * @throws object WFUndefinedKeyException
     */
    function valuesForKeys($keys);

    /**
     * Set values for multiple keys.
     *
     * @param array An array of key => value
     * @throws object WFUndefinedKeyException
     */
    function setValuesForKeys($valuesForKeys);

    /**
     * Creates an associative array with the set of passed keys and keyPaths and the corresponding values.
     *
     * @param array An array of key => keyPath. If a "key" is encountered without a value, uses the "key" as the "keyPath".
     * @return array An associative array of the passed in keys, now with values from valueForKeyPath($theKeyPath).
     * @throws object WFUndefinedKeyException
     */
    function valuesForKeyPaths($keys);

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
     *      <code>
     *      // parameters are the same as for the validateValueForKey function, minus the key name.
     *      // function should return TRUE if valid, FALSE if not valid.
     *      validate<KeyName>(&$value, &$edited, &$errors);
     *
     *      // clients wishing to add errors do so with:
     *      $errors[] = new WFError('My error message'); // could also add an error code (string) parameter.
     *      </code>
     *
     * @param mixed A reference to value to check. Passed by reference so that the implementation can normalize the data.
     * @param string The key for the value to validate.
     * @param boolean A reference to a boolean. This value will always be FALSE when the method is called. If the implementation edits the $value, set to TRUE.
     * @param array An array of WFError objects describing the error. The array is empty by default; you can add new error entries.
     * @return boolean TRUE indicates a valid value, FALSE indicates an error.
     */
    function validateValueForKey(&$value, $key, &$edited, &$errors);

    /**
     * Validate the value, calling the setter if the value is valid.
     *
     * @see validateValueForKey()
     */
    function validatedSetValueForKey(&$value, $key, &$edited, &$errors);

    /**
     * Validate the value, calling the setter if the value is valid.
     *
     * @see validateValueForKeyPath()
     */
    function validatedSetValueForKeyPath(&$value, $keyPath, &$edited, &$errors);

    /**
     * Run the object-level validation code.
     *
     * An object-level validator is used for interproperty validation, for instance validating 'postalCode' depends on 'country'.
     *
     * The default implementation will call all defined Key-Value Validators (any method matching "^validate*").
     * 
     * Validations are done via {@link validatedSetValueForKey()}, meaning that changes made to values by the validators will be updated via setValueForKey.
     *
     * Subclasses needing to do interproperty validation should override the validateObject() method. If subclasses wish to block the default behavior of re-validating 
     * all properties with validators, then the subclass should not call the super method. Subclasses wishing to preserve this behavior should call parent::validateObject($errors).
     *
     * NOTE: It's called validateObject right now instead of validate primarily because Propel already has a validate() method.
     *
     * NOTE: you can pass in an array or an {@link WFErrorArray} object. The latter provides convenience methods that make accessing individual errors easier.
     * See {@link WFErrorArray} for structure of errors array.
     *
     * @experimental
     * @param array An array, passed by reference, which will be populated with any errors encountered. Errors are grouped by key, ie $errors['key'] = array()
     * @return boolean TRUE if valid; FALSE if not.
     * @throws object WFExecption
     */
    function validateObject(&$errors);

    /**
     * Called by valueForKey() if the key cannot be located through normal methods.
     *
     * The default implementation raises as WFUndefinedKeyException. Subclasses can override this function to return an alternate value for the undefined key.
     * @param string The key.
     * @return mixed The value of the key.
     * @throws object WFUndefinedKeyException
     */
    function valueForUndefinedKey($key);

    /**
     * Called by valueForStaticKey() if the key cannot be located through normal methods.
     *
     * The default implementation raises as WFUndefinedKeyException. Subclasses can override this function to return an alternate value for the undefined key.
     *
     * NOTE: subclass overrides probably won't work so well until PHP 5.3.
     *
     * @param string The key.
     * @return mixed The value of the key.
     * @throws object WFUndefinedKeyException
     */
    static function valueForUndefinedStaticKey($key);

    /**
     * Get the value of a static keypath. A static keypath is a key called against a CLASS rather than an instance.
     *
     * @param string The key (method/property name). Key must be in form "ClassName::MethodName".
     * @param string The class name to which the key belongs
     * @return mixed The value of the key.
     * @throws object WFUndefinedKeyException
     */
    static function valueForStaticKey($key);

    /**
     * This function is a wrapper of valueForKeyPath that allows you to use KVC to access static methods/properties without having an instance to a class.
     *
     * MyObject::valueForStaticKeyPath('MyObject::myStaticMethod')
     *
     * @see valueForKey()
     * @param string The keyPath to retrive the value for.
     * @return mixed The value for the given keyPath.
     * @throws object WFUndefinedKeyException
     */
    static function valueForStaticKeyPath($keyPath);
}
