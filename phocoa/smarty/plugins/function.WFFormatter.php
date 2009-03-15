<?php
/* vim: set expandtab: */
/*
 * Smarty plugin
 *
-------------------------------------------------------------
 * File:     function.WFFormatter
 * Type:     function
 * Name:     WFFormatter
 * Version:  1.0
 * Date:     March 11, 2009
 * Purpose:  Allow use of PHOCOA Formatters directly from smarty.
 * Parameters: class - The class of the formatter to instantiate.
 *             All additional items passed through directly as key-value coding pairs.
 * Author:   Alan Pinstein <apinstein@mac.com>
 *
-------------------------------------------------------------
 */
function smarty_function_WFFormatter($params, &$smarty)
{
    // check inputs
    if (!isset($params['class'])) throw new WFException('WFFormmater class required.');
    if (!isset($params['value'])) throw new WFException('value required.');

    $class = $params['class'];
    $value = $params['value'];

    // remove class & value keys from params; rest will pass thru to setValuesForKeys
    unset($params['class']);
    unset($params['value']);

    $formatter = new $class;
    $formatter->setValuesForKeys($params);
    return $formatter->stringForValue($value);
}
