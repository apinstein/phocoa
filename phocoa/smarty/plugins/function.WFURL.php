<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package UI
 * @subpackage Template
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */ 
    
/** 
 *  Smarty plugin to get a relative url to the given module.
 *  
 *  Smarty Params:
 *  module - The module path to link to.
 *  page - The page name to link to.
 *
 *  NOTE: 'action' is a deprecated alias of the 'page' parameter.
 *
 *  @param array The params from smarty tag.
 *  @param object WFSmarty object of the current tpl.
 *  @return string The url, appropriate for a src or href etc. Add '/' to the end if you need to add parameters.
 */
function smarty_function_WFURL($params, &$smarty)
{
    // determine module
    $modulePath = NULL;
    if (!empty($params['module']))
    {
        $modulePath = $params['module'];
    }
    // if no module name is specified, use the one from the current module, if available
    if (empty($modulePath) and $smarty->getPage())
    {
        $modulePath = $smarty->getPage()->module()->invocation()->modulePath();
    } 

    if (!empty($params['page']))
    {
        $page = $params['page'];
    }
    else if (!empty($params['action']))
    {
        $page = $params['action'];
    }
    $rc = WFRequestController::sharedRequestController();
    return $rc->WFURL($modulePath, $page);
}
?>
