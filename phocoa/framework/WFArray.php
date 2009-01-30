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
 * @todo Make completely KVC
 * @todo Add all interfaces used by SPL::ArrayObject: class ArrayObject implements IteratorAggregate, ArrayAccess, Countable
 * @todo Should we just subclass ArrayObject and add KVC? Or add implementations for these interfaces?
 */
class WFArray extends WFObject
{
    protected $_array;

    public function __construct($array = NULL)
    {
        parent::__construct();
        $this->_array = $array;
    }

    public function valueForKeyPath($keyPath)
    {
        return parent::valueForKeyPath('_array.' . $keyPath);
    }

    public function _array()
    {
        return $this->_array;
    }

    public static function arrayWithArray($array)
    {
        return new WFArray($array);
    }
}
