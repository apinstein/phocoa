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
 * WFEmptyToNullTransformer
 *
 * Transforms an "empty" value into NULL, and all other values are passed through cleanly.
 *
 * Example usage: turning integer/numeric 0 vals into Null
 */
class WFEmptyToNullTransformer extends WFValueTransformer
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
            return NULL;
        }
        return $value;
    }

    function reverseTransformedValue($value)
    {
        throw( new Exception("WFEmptyToNullTransformer is not reversible.") );
    }
}
?>
