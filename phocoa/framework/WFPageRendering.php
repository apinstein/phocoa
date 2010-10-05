<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package UI
 * @subpackage Template
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

interface WFPageRendering
{
    /**
     * Assign a name/value pair to the underlying template engine.
     * @param string Name of the variable.
     * @param mixed Value of the variable.
     */
    function assign($name, $value);

    /**
     * Get the value of a previously assigned variable.
     *
     * @param string Name
     * @return mixed Value
     */
    function get($name);

    /**
     * Render the view.
     * @param boolean Display the output or return it in a variable?
     * @return string The HTML output, or NULL, depending on $display.
     */
    function render($display = true);

    /**
     * Get the widget specified in the "id" field of the passed associative array. Do all sanity checking along the way.
     * @param assoc_array An associative array with the name/value pairs of the item's attributes in the template engine.
     * @return object WFWidget Sublcass specified by the current widget... with smarty we look in $params['id'].
     * @throws Exception
     */
    function getCurrentWidget($params);

    /**
     * Get the WFPage object that is using the template.
     * The only thing the framework assigns to the template is __page, which is a reference to itself so that the
     * template can access widgets, etc. By having this interface include this function, we can always get back
     * to the WFPage from any template engine.
     * @return object WFPage Page that the template is part of. 
     */
    function getPage();

    /**
     * Set the template file path.
     * @param string The HTML template file path for this view.
     */
    function setTemplate($template);
}
?>
