<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package KeyValueCoding
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/**
 * A KVC-compliant dictionary wrapper.
 *
 * @todo Make completely KVC: setValueForKey, setValueForKeyPath, valuesForKeys, valuesForKeyPaths
 *       What should stuff like valueForUndefinedKey, validateValueForKey, etc do? nothing?
 */
class WFDictionary extends WFObject implements ArrayAccess
{

    private $_obj = array();

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->_obj);
    }

    public function offsetGet($offset)
    {
        return $this->getValueForKey($offset);
    }

    public function offsetSet($offset, $value)
    {
        return $this->setValueForKey($value, $offset);
    }

    public function offsetUnset($offset)
    {
        unset($this->_obj[$offset]);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->_obj);
    }

    public function __get($key)
    {
        return $this->valueForKey($key);
    }

    public function __set($key, $value)
    {
        return $this->setValueForKey($value, $key);
    }

    public function valueForKey($key)
    {
        if (isset($this->_obj[$key]))
        {
            return $this->_obj[$key];
        }
        throw new WFUndefinedKeyException("No value exists for key {$key}.");
    }

    public function setValueForKey($value, $key)
    {
        $this->_obj[$key] = $value;
    }

    public static function fromHash($array)
    {
        $d = new WFDictionary();
        foreach ($array as $key => $value)
        {
            $d->setValueForKey($value, $key);
        }
        return $d;
    }

    // public function setValuesForKeys($valuesForKeys)
    // {
    //     foreach ($valuesForKeys as $k => $v) {
    //         $this->setValueForKey($v, $k);
    //     }
    // }
    // 
    // public function valueForKeyPath($keyPath)
    // {
    //     return WFObject::valueForTargetAndKeyPath($keyPath, $this);
    // }
    // 
    // @todo Factor out into WFKVC::valuesForKeyPaths($keysAndKeyPaths, $object)
    // public function valuesForKeyPaths($keysAndKeyPaths)
    // {
    //     $hash = array();
    //     // fix integer keys into keys... this allows keysAndKeyPaths to return ('myProp', 'myProp2' => 'myKeyPath', 'myProp3')
    //     foreach ( array_keys($keysAndKeyPaths) as $k ) {
    //         if (gettype($k) == 'integer')
    //         {
    //             $keysAndKeyPaths[$keysAndKeyPaths[$k]] = $keysAndKeyPaths[$k];
    //             unset($keysAndKeyPaths[$k]);
    //         }
    //     }
    //     foreach ($keysAndKeyPaths as $k => $keyPath) {
    //         $v = $this->valueForKeyPath($keyPath);
    //         $hash[$k] = $v;
    //     }
    //     return $hash;
    // }

    public function __toString()
    {
        $str = "Dictionary:\n";
        foreach ($this->_obj as $k => $v) {
            $str .= "  {$k} => {$v}\n";
        }
        $str .= "END Dictionary\n";
        return $str;
    }
}
