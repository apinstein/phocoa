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

    public function valueForKeyPath($keyPath)
    {
        return WFObject::valueForTargetAndKeyPath($keyPath, $this);
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
