<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package framework-base
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/** 
 * Base Class for all framework classes.
 *
 * Provides:
 *   - {@link KeyValueCoding}
 */
class WFObject implements WFKeyValueCoding
{
    function __construct()
    {
    }

    /**
     *  Empty placeholder for exposedProperties setup.
     *
     *  Subclasses should call parent and merge results.
     *
     *  @return array
     */
    public static function exposedProperties()
    {
        return array();
    }

    protected static function _valueForStaticKey($key, $target)
    {
        if ($key == NULL) throw( new WFUndefinedKeyException("NULL key Exception") );

        $performed = false;

        // try calling getter with naming convention "key"
        if (method_exists($target, $key))
        {
            $result = call_user_func(array($target, $key));
            $performed = true;
        }

        // try calling getter with naming convention "getKey"
        if (!$performed)
        {
            $getterMethod = 'get' . ucfirst($key);
            if (method_exists($target, $getterMethod))
            {
                $result = call_user_func(array($target, $getterMethod));
                $performed = true;
            }
        }

        // try accessing property directly
        if (!$performed)
        {
            $vars = get_class_vars($target);
            if (array_key_exists($key, $vars))
            {
                throw( new WFException("No way to support this before PHP 5.3.") );
                //$result = $target::$$key;
                $performed = true;
            }
        }

        if (!$performed)
        {
            $result = self::valueForUndefinedStaticKey($key);
        }

        return $result;
    }

    function valueForKey($key)
    {
        if ($key == NULL) throw( new WFUndefinedKeyException("NULL key Exception") );

        $performed = false;

        // try calling getter with naming convention "key"
        if (method_exists($this, $key))
        {
            $result = $this->$key();
            $performed = true;
        }

        // try calling getter with naming convention "getKey"
        if (!$performed)
        {
            $getterMethod = 'get' . ucfirst($key);
            if (method_exists($this, $getterMethod))
            {
                $result = $this->$getterMethod();
                $performed = true;
            }
        }

        // try accessing property directly
        if (!$performed)
        {
            $vars = get_object_vars($this);
            if (array_key_exists($key, $vars))
            {
                $result = $this->$key;
                $performed = true;
            }
        }

        if (!$performed)
        {
            $result = $this->valueForUndefinedKey($key);
        }

        return $result;
    }

    function valuesForKeys($keys)
    {
        if (!is_array($keys)) throw new WFException("valuesForKeys() requires an array as first argument.");
        $hash = array();
        foreach ($keys as $k) {
            $v = $this->valueForKey($k);
            $hash[$k] = $v;
        }
        return $hash;
    }

    function setValuesForKeys($valuesForKeys)
    {
        foreach ($valuesForKeys as $k => $v) {
            $this->setValueForKey($v, $k);
        }
        return $this;
    }

    function valuesForKeyPaths($keysAndKeyPaths)
    {
        $hash = array();
        // fix integer keys into keys... this allows keysAndKeyPaths to return ('myProp', 'myProp2' => 'myKeyPath', 'myProp3')
        foreach ( array_keys($keysAndKeyPaths) as $k ) {
            if (gettype($k) == 'integer')
            {
                $keysAndKeyPaths[$keysAndKeyPaths[$k]] = $keysAndKeyPaths[$k];
                unset($keysAndKeyPaths[$k]);
            }
        }
        foreach ($keysAndKeyPaths as $k => $keyPath) {
            $v = $this->valueForKeyPath($keyPath);
            $hash[$k] = $v;
        }
        return $hash;
    }

    /**
     * Called by valueForKey() if the key cannot be located through normal methods.
     *
     * The default implementation raises as WFUndefinedKeyException. Subclasses can override this function to return an alternate value for the undefined key.
     * @param string The key.
     * @return mixed The value of the key.
     * @throws object WFUndefinedKeyException
     */
    function valueForUndefinedKey($key)
    {
        throw( new WFUndefinedKeyException("Unknown key '$key' (" . gettype($key) . ") requested for object '" . get_class($this) . "'.") );
    }

    /**
     * Called by valueForStaticKey() if the key cannot be located through normal methods.
     *
     * The default implementation raises as WFUndefinedKeyException. Subclasses can override this function to return an alternate value for the undefined key.
     * @param string The key.
     * @return mixed The value of the key.
     * @throws object WFUndefinedKeyException
     */
    public static function valueForUndefinedStaticKey($key)
    {
        throw( new WFUndefinedKeyException("Unknown key '$key' requested for object '" . __CLASS__ . "'.") );
    }

    /**
     * Helper function for implementing KVC.
     * 
     * Supports "coalescing" KVC by using ; separated keyPaths. The first non-null value returned will be used.
     * The *last* keypath is actually the "default default" which is used if all keyPaths return NULL.
     *
     * This is public so that other objects that don't subclass WFObject can leverage this codebase to implement KVC.
     *
     * @param string The keyPath.
     * @param object Generic The root object to run the keyPath search against.
     * @return mixed
     * @throws Exception, WFUndefinedKeyException
     */
    public static function valueForTargetAndKeyPath($inKeyPath, $rootObject = NULL)
    {
        // detect coalescing keypath
        if (strpos($inKeyPath, ';') !== false)
        {
            $coalescingKeyPaths = preg_split('/(?<!\\\\);/', $inKeyPath);
            if (count($coalescingKeyPaths) < 2) throw new WFException("Error parsing coalescing keypath: {$inKeyPath}");
            $coalesceDefault = str_replace('\\;', ';', array_pop($coalescingKeyPaths));

            $val = NULL;
            while ($val === NULL && ($keyPath = array_shift($coalescingKeyPaths))) {
                $val = self::valueForTargetAndKeyPathSingle($keyPath, $rootObject);
            }
            if ($val === NULL)
            {
                $val = $coalesceDefault;
            }
            return $val;
        }
        else
        {
            return self::valueForTargetAndKeyPathSingle($inKeyPath, $rootObject);
        }
    }

    private static function valueForTargetAndKeyPathSingle($keyPath, $rootObject = NULL)
    {
        if ($keyPath == NULL) throw( new Exception("NULL keyPath Exception") );
        $staticMode = ($rootObject === NULL);

        // initialize
        $result = NULL;

        $keyParts = explode('.', $keyPath);
        $keyPartCount = count($keyParts);

        // walk keypath
        $keys = explode('.', $keyPath);
        $keyPartsLeft = $keyCount = count($keys);
        $arrayMode = false;
        for ($keyI = 0; $keyI < $keyCount; $keyI++) {
            $key = $keys[$keyI];
            $keyPartsLeft--;

            // look for escape hatch
            $escapeHatch = false;
            $lastChrModifier = substr($key, -1);
            if ($lastChrModifier === '^')
            {
                $escapeHatch = true;
                $key = substr($key, 0, strlen($key)-1);
            }

            // parse out decorate magic, if any
            $decoratorClass = NULL;
            $decoratorPos = strpos($key, '[');
            if ($decoratorPos !== false and substr($key, -1) === ']')
            {
                $decoratorClass = substr($key, $decoratorPos + 1, -1);
                $key = substr($key, 0, $decoratorPos);
            }

            // determine target; use this if on first key, use result otherwise
            if ($keyI == 0)
            {
                // having "::" in your first key or a rootObject of NULL triggers STATIC mode
                if ($staticMode && strpos($key, '::') !== false)
                {
                    $staticParts = explode('::', $key);
                    if (count($staticParts) !== 2)
                    {
                        throw( new WFException("First part of keypath for static KVC must be 'ClassName::StaticMethodName'; you passed: " . $key) );
                    }
                    $target = $staticParts[0];
                    $key = $staticParts[1];
                    if (!class_exists($target)) throw( new WFException("First part of a static keypath must be a valid class name, you passed: " . $target) );
                }
                else
                {
                    $target = $rootObject;
                }
            }
            else
            {
                $target = $result;
            }

            // get result of this part of path
            if ($staticMode && $keyI == 0)
            {
                if (!is_string($target)) throw( new WFException('Target is not class name at static keyPath: ' . join('.', array_slice($keys, 0, $keyI))) );
                $staticF = array($target, '_valueForStaticKey');
                if (!is_callable($staticF)) throw( new WFException('Target class (' . $target . ') does not implement WFObject protocol.') );
                $result = call_user_func($staticF, $key, $target);
            }
            else
            {
                if (!is_object($target)) throw( new WFUndefinedKeyException('Value at keyPath: "' . join('.', array_slice($keys, 0, $keyI)) . "\" is not an object when trying to get next key \"{$key}\".") );
                $result = $target->valueForKey($key);
            }

            if (is_array($result) or ($result instanceof ArrayObject))
            {
                $arrayMode = true;
            }
            else
            {
                if ($escapeHatch and $result === NULL and $keyPartsLeft) return NULL;

                if ($decoratorClass)
                {
                    $result = new $decoratorClass($result);
                }
            }

            // IF the result of the a key is an array, we do some magic.
            // We CREATE a new array with the results of calling EACH object in the array with the rest of the path.
            // We also support several operators: http://developer.apple.com/documentation/Cocoa/Conceptual/KeyValueCoding/Concepts/ArrayOperators.html
            if ($arrayMode and $keyPartsLeft)
            {
                $nextPart = $keys[$keyI + 1];
                // are we in operator mode as well?
                if (in_array($nextPart, array('@count', '@first', '@firstNotNull', '@sum', '@max', '@min', '@avg', '@unionOfArrays', '@unionOfObjects', '@distinctUnionOfArrays', '@distinctUnionOfObjects')))
                {
                    $operator = $nextPart;
                    $rightKeyPath = join('.', array_slice($keyParts, $keyI + 2));
                }
                else
                {
                    $operator = NULL;
                    $rightKeyPath = join('.', array_slice($keyParts, $keyI + 1));
                }
                //print "magic on $keyPath at " . join('.', array_slice($keyParts, 0, $keyI + 1)) . " kp: $rightKeyPath\n";

                // if there is a rightKeyPath, need to calculate magic array from remaining keypath. Otherwise, just use current result (it's arrayMode) as magicArray.
                if ($rightKeyPath)
                {
                    if (!$operator && $result instanceof WFArray && array_key_exists($nextPart, $result))
                    {
                        $magicArray = $result[$nextPart]->valueForKeyPath($rightKeyPath);
                    }
                    else
                    {
                        $magicArray = array();
                        foreach ($result as $object) {
                            xdebug_break();
                            if (!is_object($object)) throw( new Exception("All array items must be OBJECTS THAT IMPLEMENT Key-Value Coding for KVC Magic Arrays to work.") );
                            if (!method_exists($object, 'valueForKey')) throw( new Exception("target is not Key-Value Coding compliant for valueForKey.") );
                            if ($decoratorClass)
                            {
                                $object = new $decoratorClass($object);
                            }
                            $magicArray[] = $object->valueForKeyPath($rightKeyPath);
                        }
                    }
                }
                else
                {
                    $magicArray = $result;
                }

                if ($operator)
                {
                    switch ($operator) {
                        case '@count':
                            $result = count($magicArray);
                            break;
                        case '@first':
                            if (count($magicArray) > 0)
                            {
                                $result = $magicArray[0];
                            }
                            else
                            {
                                $result = null;
                            }
                            break;
                        case '@firstNotNull':
                            $result = null;
                            foreach ($magicArray as $v) {
                                if ($v !== NULL)
                                {
                                    $result = $v;
                                    break;
                                }
                            }
                            break;
                        case '@sum':
                            $result = array_sum ( $magicArray );
                            break;
                        case '@max':
                            $result = max ( $magicArray );
                            break;
                        case '@min':
                            $result = min ( $magicArray );
                            break;
                        case '@avg':
                            $result = array_sum ( $magicArray ) / count($magicArray);
                            break;
                        case '@unionOfArrays':
                            $result = array();
                            foreach ($magicArray as $item) {
                                if (!is_array($item))
                                {
                                    throw( new Exception("unionOfArrays requires that all results be arrays... non-array encountered: $item") );
                                }
                                $result = array_merge($item, $result);
                            }
                            break;
                        // I think this is equivalent to what our magic arrays do anyway
                        // for instance: transactions.payee will give a list of all payee objects of each transaction
                        // it would seem: transactions.@unionOfObjects.payee would yield the same?
                        case '@unionOfObjects':
                            $result = $magicArray;
                            break;
                        case '@distinctUnionOfArrays':
                            $result = array();
                            foreach ($magicArray as $item) {
                                if (!is_array($item))
                                {
                                    throw( new Exception("distinctUnionOfArrays requires that all results be arrays... non-array encountered: $item") );
                                }
                                $result = array_merge($item, $result);
                            }
                            $result = array_unique($result);
                            break;
                        case '@distinctUnionOfObjects':
                            $result = array_unique($magicArray);
                            break;
                    }
                }
                else
                {
                    $result = $magicArray;
                }
                break;
            }
        }

        return $result;
    }

    public function valueForKeyPath($keyPath)
    {
        return self::valueForTargetAndKeyPath($keyPath, $this);
    }

    public static function valueForStaticKeyPath($keyPath)
    {
        return self::valueForTargetAndKeyPath($keyPath);
    }
    public static function valueForStaticKey($key)
    {
        return self::valueForStaticKeyPath($key);
    }

    /**
     * Returns the current object.
     *
     * Useful for KVC "hacking" in cases where you need to use KVC magic on the "current" object.
     * Examples: this[MyDecorator].name, this.@first (for an array), etc.
     *
     * @return object WFObject
     */
    public function this()
    {
        return $this;
    }


    function setValueForKey($value, $key)
    {
        $performed = false;

        // try calling setter
        $setMethod = "set" . ucfirst($key);
        if (method_exists($this, $setMethod))
        {
            $this->$setMethod($value);
            $performed = true;
        }

        if (!$performed)
        {
            // try accesing instance var directly
            $vars = get_object_vars($this);
            if (array_key_exists($key, $vars))
            {
                $this->$key = $value;
                $performed = true;
            }
        }

        if (!$performed)
        {
            throw( new WFUndefinedKeyException("Unknown key '$key' (" . gettype($key) . ") requested for object '" . get_class($this) . "'.") );
        }
    }

    /**
     * Helper function to convert a keyPath into the targetKeyPath (the object to call xxxKey on) and the targetKey (the key to call on the target object).
     *
     * Usage: 
     *
     * <code>
     * list($targetKeyPath, $targetKey) = keyPathToParts($keyPath);
     * </code>
     *
     * @return array targetKeyPath, targetKey. 
     */
    protected function keyPathToTargetAndKey($keyPath)
    {
        if ($keyPath == NULL) throw( new Exception("NULL key Exception") );

        // walk keypath
        // If the keypath is a.b.c.d then the targetKeyPath is a.b.c and the targetKey is d
        $keyParts = explode('.', $keyPath);
        $keyPartCount = count($keyParts);
        if ($keyPartCount == 0) throw( new Exception("Illegal keyPath: '{$keyPath}'. KeyPath must have at least one part.") );

        if ($keyPartCount == 1)
        {
            $targetKey = $keyPath;
            $target = $this;
        }
        else
        {
            $targetKey = $keyParts[$keyPartCount - 1];
            $targetKeyPath = join('.', array_slice($keyParts, 0, $keyPartCount - 1));
            $target = $this->valueForKeyPath($targetKeyPath);
        }

        return array($target, $targetKey);
    }

    function setValueForKeyPath($value, $keyPath)
    {
        list($target, $targetKey) = $this->keyPathToTargetAndKey($keyPath);
        $target->setValueForKey($value, $targetKey);
    }

    /**
     * Validate the given value for the given keypath.
     *
     * This is the default implementation for this method. It looks for the target object based on the keyPath and then calls the validateValueForKey method.
     *
     * @param mixed A reference to value to check. Passed by reference so that the implementation can normalize the data.
     * @param string keyPath the keyPath for the value.
     * @param boolean A reference to a boolean. This value will always be FALSE when the method is called. If the implementation edits the $value, set to TRUE. This will resultion in setValueForKey() being called again with the new value.
     * @param object A WFError object describing the error.
     * @return boolean TRUE indicates a valid value, FALSE indicates an error.
     */
    function validateValueForKeyPath(&$value, $keyPath, &$edited, &$errors)
    {
        list($target, $targetKey) = $this->keyPathToTargetAndKey($keyPath);

        if (!($target instanceof WFObject))
        {
            throw( new WFException("Target not an object at keypath: " . $keyPath . " for object " . get_class($this)) );
        }
        return $target->validateValueForKey($value, $targetKey, $edited, $errors);
    }

    /**
     * Validate, and call setter if valid, a value for a key.
     *
     * This is the default implementation for this method. It simply calls validateValueForKey and if there are no errors, calls the setter.
     *
     * @see validateValueForKey()
     */
    function validatedSetValueForKey(&$value, $key, &$edited, &$errors)
    {
        if ($this->validateValueForKey($value, $key, $edited, $errors))
        {
            $this->setValueForKey($value, $key);
            return true;
        }
        return false;
    }

    /**
     * Default implementation for validateObject().
     *
     * The default implementation will call all defined Key-Value Validators (any method matching "^validate*") using {@link validatedSetValueForKey()}.
     * 
     * Validations are done via {@link validatedSetValueForKey()}, meaning that changes made to values by the validators will be updated via setValueForKey.
     *
     * Subclasses needing to do interproperty validation should override the validateObject() method. If subclasses wish to block the default behavior of re-validating 
     * all properties with validators, then the subclass should not call the super method. Subclasses wishing to preserve this behavior should call parent::validateObject($errors).
     *
     * @experimental
     * @param array An array, passed by reference, which will be populated with any errors encountered. Errors are grouped by key, ie $errors['key'] = array()
     * @return boolean TRUE if valid; FALSE if not.
     * @throws object WFExecption
     * @see WFKeyValueCoding::validateObject()
     */
    function validateObject(&$errors)
    {
        if ($errors === null)
        {
            $errors = new WFErrorArray();
        }

        $allMethods = get_class_methods(get_class($this));
        foreach ($allMethods as $f) {
            if (strncasecmp('validate', $f, 8) === 0)
            {
                // now, make sure it's a KVV method by reflecting the args; should be 3 args.
                $methodInfo = new ReflectionMethod(get_class($this), $f);
                if ($methodInfo->getNumberOfParameters() !== 3) continue;
                $p = $methodInfo->getParameters();
                if (!($p[0]->isPassedByReference() and $p[1]->isPassedByReference() and $p[2]->isPassedByReference())) continue;

                // we found a real validator! now, validate the value.
                $key = strtolower(substr($f, 8, 1)) . substr($f, 9);
                $keyErrors = array();
                $val = $this->valueForKey($key);
                $ok = $this->validatedSetValueForKey($val, $key, $edited, $keyErrors);
                if (!$ok)
                {
                    $errors[$key] = $keyErrors;
                }
            }
        }

        return count($errors) == 0;
    }

    /**
     * Validate, and call setter if valid, a value for a keyPath.
     *
     * This is the default implementation for this method. It simply calls validateValueForKeyPath and if there are no errors, calls the setter.
     *
     * @see validateValueForKeyPath()
     */
    function validatedSetValueForKeyPath(&$value, $keyPath, &$edited, &$errors)
    {
        if ($this->validateValueForKeyPath($value, $keyPath, $edited, $errors))
        {
            $this->setValueForKeyPath($value, $keyPath);
            return true;
        }
        return false;
    }


    /**
     * Validate the given value for the given key.
     *
     * Clients can normalize the value, and also report and error if the value is not valid.
     *
     * If the value is valid without modificiation, return TRUE and do not alter $edited or $error.
     * If the value is valid after being modified, return TRUE, and $edited to true.
     * IF the value is not valid, do not alter $value or $edited, but fill out the $error object with desired information.
     *
     * The default implementation (in WFObject) looks for a method named validate<key> and calls it, otherwise it returns TRUE. Here is the prototype:
     * <code>
     *      (boolean) function(&$value, &$edited, &$errors)
     * </code>
     *
     * @param mixed A reference to value to check. Passed by reference so that the implementation can normalize the data.
     * @param string keyPath the keyPath for the value.
     * @param boolean A reference to a boolean. This value will always be FALSE when the method is called. If the implementation edits the $value, set to TRUE.
     * @param array An array of WFError objects describing the error. The array is empty by default; you can add new error entries with:
     *      <code>
     *          $error[] = new WFError('My error message'); // could also add an error code (string) parameter.
     *      </code>
     * @return boolean TRUE indicates a valid value, FALSE indicates an error.
     */
    function validateValueForKey(&$value, $key, &$edited, &$errors)
    {
        $valid = true;

        // try calling validator
        $validatorMethod = 'validate' . ucfirst($key);
        if (method_exists($this, $validatorMethod))
        {
            // track whether or not validator lies
            $errCount = count($errors);

            // run validator
            $valid = $this->$validatorMethod($value, $edited, $errors);

            // check for mismatch b/w $valid and errors generated
            $errCount = count($errors) - $errCount;
            if ($valid && $errCount) throw( new WFException("Validator for key '{$key}' returned TRUE but also returned errors.") );
            else if (!$valid && $errCount === 0) throw( new WFException("Validator for key '{$key}' returned FALSE but didn't provide any errors.") );
        }

        if (!$valid)
        {
            WFLog::log("Errors: " . print_r($errors, true), WFLog::TRACE_LOG);
        }

        return $valid;
    }

    /* Print a description of the object.
     *
     * @return string A text description of the object.
     */
    function __toString()
    {
        return print_r($this, true);
    }

    /**
     * @todo refactor to getPhpClass() or something. this collides horribly with WFWidget...
     * @deprecated
     */
    function getClass()
    {
        return get_class($this);
    }
}
