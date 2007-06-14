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
 * WFUrlencodeTransformer
 *
 * Transforms a boolean value by reversing it.
 *
 * Useful when binding a boolean property to a boolean keyPath that is opposite (logical NOT) from your needs.
 */
class WFUrlencodeTransformer extends WFValueTransformer
{
    function __construct()
    {
        parent::__construct();
        $this->setReversible(true);
    }

    function transformedValue($value)
    {
        return urlencode($value);
    }
    function reverseTransformedValue($value)
    {
        return urldecode($value);
    }
}
?>
