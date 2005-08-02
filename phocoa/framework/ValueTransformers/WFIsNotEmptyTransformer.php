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
 * WFIsNotEmptyTransformer
 *
 * Transforms an "empty" value into TRUE, and a non-empty value into FALSE.
 *
 * Example usage: binding the hidden property of a "delete this image" WFCheckbox to an "image path" keyPath, so that the checkbox will be hidden if the path is not empty.
 */
class WFIsNotEmptyTransformer extends WFValueTransformer
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
        throw( new Exception("WFIsNotEmptyTransformer is not reversible.") );
    }
}
?>
