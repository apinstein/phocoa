<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/** 
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 * @package KeyValueCoding
 * @subpackage ValueTransformers
 */

/** 
 * WFValueTransformer
 *
 * Value transformers provide one-way or reversible transformations.
 *
 * Value transformers are part of the bindings system.
 *
 * To create your own value transformer, simply subclass WFValueTransformer, implement the necessary methods, etc.
 * Then, in the ViewDidLoad method of the view that uses the transformer, register the named transformer.
 * To add a valueTransformer to the bindings, simply add an option to the bindings config with WFBinding::VALUE_TRANSFORMER_NAME => 'name of registered transformer'.
 *
 */
abstract class WFValueTransformer extends WFObject
{
    /**
     * @static
     * @var A list of all transformers currently registered. Format: transformerName => transformerInstance (a WFValueTransformer subclass)
     */
    static protected $transformerList = array();
    /**
     * @var boolean TRUE if the transformation is reversible.
     */
    protected $reversible;
    
    function __construct()
    {
        parent::__construct();
        $this->reversible = false;
    }

    function setReversible($reversible)
    {
        $this->reversible = $reversible;
    }
    function reversible()
    {
        return $this->reversible;
    }

    /**
     * Get the transfored value.
     * @param mixed The value to transform.
     * @return mixed The transformed value.
     */
    abstract function transformedValue($value);
    /**
     * Get the reverse-transfored value.
     * @param mixed The value to reverse-transform.
     * @return mixed The reverse-transformed value.
     */
    abstract function reverseTransformedValue($value);

    /**
     * Register a value transformer for the passed name.
     * @static
     * @param object A WFValueTransformer object.
     * @param string The name of the instance.
     *               Each instance is registered separately to promoted re-use. For instance you may write a MultiplierTransformer that has a multiplier
     *               as a member variable. The you could instantiate it more than once with different multipliers and register them as
     *               "MutliplyBy10Transformer", "MultplyBy2Transformer", etc.
     * @throws if you try to set a non-WFValueTransformer object.
     */
    public static function setValueTransformerForName($valueTransformer, $name)
    {
        if (!is_subclass_of($valueTransformer, 'WFValueTransformer')) throw( new Exception("Value Transformer provided for '$name' is not a subclass of WFValueTransformer.") );
        WFValueTransformer::$transformerList[$name] = $valueTransformer;
    }

    /**
     * Retrieve a named value transformer instance.
     * @static
     * @param string The name of the value transformer to retrieve.
     * @return object A WFValueTransformer instance.
     * @throws for unknown transformers.
     */
    public static function valueTransformerForName($name)
    {
        if (!isset(WFValueTransformer::$transformerList[$name])) throw( new Exception("No Value Transformer named '$name' is registered.") );
        return WFValueTransformer::$transformerList[$name];
    }

    /**
     * Get a list of all registered transformers.
     * @static
     * @return array An array of WFValueTransformer instances that are registered and available.
     */
    public static function valueTransformerNames()
    {
        return WFValueTransformer::$transformerList;
    }
}
?>
