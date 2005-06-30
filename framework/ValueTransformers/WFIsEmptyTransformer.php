<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package KeyValueCoding
 * @subpackage ValueTransformers
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/** 
 * WFIsEmptyTransformer
 *
 * Transforms an "empty" value into FALSE, and a non-empty value into TRUE.
 *
 * Example usage: binding the hidden property of a WFImage to an "image path" keyPath.
 */
class WFIsEmptyTransformer extends WFValueTransformer
{
    function __construct()
    {
        parent::__construct();
        $this->setReversible(false);
    }

    function transformedValue($value)
    {
        if (empty($value))
        {
            return true;
        }
        return false;
    }

    function reverseTransformedValue($value)
    {
        throw( new Exception("WFIsEmptyTransformer is not reversible.") );
    }
}
?>
