<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package KeyValueCoding
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/**
 * A KVC-compliant array wrapper.
 *
 * To get the values of the array you can iterate on it or just call {@link WFArray::values()}.
 *
 * @todo Make completely KVC: setValueForKey, setValueForKeyPath, valuesForKeys, valuesForKeyPaths
 *       What should stuff like valueForUndefinedKey, validateValueForKey, etc do? nothing?
 */
class WFArray extends ArrayObject
{
    /**
     * A convenience function to create a hash of the contained values in interesting ways.
     *
     * @param string The key to use on each array entry to generate the hash key for the entry. NULL used sequential numerical indices rather than a hash key.
     * @param mixed
     *          NULL    => the entry for each key will be the entire entry in the array
     *          string  => the entry for each key will be the valueForKeyPath() of the passed key path
     *          array   => the entry for each key will be an associative array, the result of passing the argument to valuesForKeyPaths()
     * @return array An associative array of the contained values hashed according to the parameters provided.
     */
    public function hash($hashKey, $keyPath = NULL)
    {
        $hash = array();
        foreach ($this as $entry) {
            if ($keyPath === NULL)
            {
                $hashInfo = $entry;
            }
            else if ($entry instanceof WFObject && is_string($keyPath))
            {
                $hashInfo = $entry->valueForKeyPath($keyPath);
            }
            else if ($entry instanceof WFObject && is_array($keyPath))
            {
                $hashInfo = $entry->valuesForKeyPaths($keyPath);
            }
            else if (is_array($entry) && is_string($keyPath))
            {
                $hashInfo = $entry[$keyPath];
            }
            else if (is_array($entry) && is_array($keyPath))
            {
                $hashInfo = array();
                foreach ($keyPath as $p)
                {
                    $hashInfo[$p] = $entry[$p];
                }
            }
            if ($entry instanceof WFObject && $hashKey)
            {
                $hash[$entry->valueForKey($hashKey)] = $hashInfo;
            }
            else if (is_array($entry) && $hashKey)
            {
                $hash[$entry[$hashKey]] = $hashInfo;
            }
            else
            {
                $hash[] = $hashInfo;
            }
        }
        return $hash;
    }

    /**
     * A convenience function to create a WFArray of WFArrays keyed by the valueForKeyPath of each object.
     *
     * Whereas hash assumes that the hash key produces UNIQUE results, chunk assumes that the hash key produces mutliple results.
     *
     * Example:
     * [
     *  {
     *    id: 1,
     *    category: foo
     *  },
     *  {
     *    id: 2,
     *    category: foo
     *  },
     *  {
     *    id: 3,
     *    category: bar
     *  },
     *  {
     *    id: 4,
     *    category: bar
     *  }
     * ]
     *
     * WFArray::arrayWithArray($example)->chunk('category')
     *
     * {
     *   foo: [obj1, obj2],
     *   bar: [obj3, obj4]
     * }
     *
     * @param string The key to use on each array entry to generate the hash key for the entry.
     * @return array An associative array of the contained values hashed according to the parameters provided. All items whose valueForKeyPath match will be included in the chunk.
     */
    public function chunk($hashKey)
    {
        $chunked = new WFArray();
        foreach ($this as $entry)
        {
            $key = $entry->valueForKeyPath($hashKey);
            if (!isset($chunked[$key]))
            {
                $chunked[$key] = new WFArray();
            }
            $chunked[$key][] = $entry;
        }

        return $chunked;
    }

    /**
     * Canonical map function.
     *
     * The map function is a wrapper around {@link hash()}; the map argument can thus do some magical things since it is invoked via {@link WFObject::valueForKeyPath()}.
     *
     * @param mixed
     *          NULL    => the entry for each key will be the entire entry in the array
     *          string  => the entry for each key will be the valueForKey() of the passed key
     *          array   => the entry for each key will be an associative array, the result of passing the argument to valuesForKeyPaths()
     * @return array An associative array of the contained values hashed according to the parameters provided.
     */
    public function map($keyPath)
    {
        if (is_null($keyPath)) throw new WFException("Cannot call map with NULL argument. I think you're looking for WFArray::values().");
        return $this->hash(NULL, $keyPath);
    }

    /**
     * Get a subset of the array for the passed set of keys.
     *
     * @param array An array of keys to look for in the array.
     * @return object WFArray A new associative array with all key/value pairs matching the passed-in set of keys.
     */
    public function subArrayWithKeys($keys)
    {
        $matchedArray = array();
        foreach ($keys as $key) {
            if (array_key_exists($key, $this))
            {
                $matchedArray[$key] = $this[$key];
            }
        }
        return new WFArray($matchedArray);
    }

    /**
     * Get all of the values contained in the array
     *
     * NOTE: calls getArrayCopy.
     *
     * @return array
     */
    public function values()
    {
        return $this->getArrayCopy();
    }

    public function valueForKey($key)
    {
        if ($key === 'values')
        {
            return $this->values();
        }
        else if ($this->offsetExists($key))
        {
            $v = $this[$key];
            if (is_array($v) and !($v instanceof WFArray))
            {
                return new WFArray($v);
            }
            return $v;
        }
        else if (method_exists($this, $key))
        {
            return $this->$key();
        }
        else
        {
            throw new WFUndefinedKeyException("No value exists for key {$key}. \$this is a WFArray; did you mean 'values.{$key}'?");
        }
    }

    public function setValueForKey($value, $key)
    {
        $this[$key] = $value;
    }

    public function setValuesForKeys($valuesForKeys)
    {
        foreach ($valuesForKeys as $k => $v) {
            $this->setValueForKey($v, $k);
        }
    }

    public function valueForKeyPath($keyPath)
    {
        return WFObject::valueForTargetAndKeyPath($keyPath, $this);
    }

    // @todo Factor out into WFKVC::valuesForKeyPaths($keysAndKeyPaths, $object)
    public function valuesForKeyPaths($keysAndKeyPaths)
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
     * Helper static initializer to create a new array for fluent interfaces.
     *
     * @param array
     * @return object WFArray
     */
    public static function arrayWithArray($array)
    {
        return new WFArray($array);
    }

    public function __toString()
    {
        $str = "Array:\n";
        foreach ($this as $k => $v) {
            $str .= "  {$k} => {$v}\n";
        }
        $str .= "END Array\n";
        return $str;
    }
}
