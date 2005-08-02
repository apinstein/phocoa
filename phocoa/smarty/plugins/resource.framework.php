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
 *  Smarty plugin to include a framework-bundled tpl file.
 *
 *  For instance, some templates, like the template shown when there is an uncaugt exception, are shipped with the framework.
 *  Using this call to access the file instead of the include will automatically use "over-ridden" tpl files from userland.
 *  
 *  Ex: {include file="framework:app_error_user.tpl"}
 *  
 *  @param string The framework resource path.
 *  @param string Where to put the tpl source.
 *  @param object WFSmarty object of the current tpl.
 *  @return boolean TRUE to use the returned tpl source, false if error.
 *  @todo Have the system look for the tpl in a userland directory first.
 */

function smarty_resource_framework_source($resource_path, &$tpl_source, &$smarty)
{
    $tpl_path = APP_ROOT . '/smarty/templates/' . $resource_path;
    if (!file_exists($tpl_path)) die("framework resource {$tpl_path} does not exist.");
    $tpl_source = file_get_contents($tpl_path);
    return true;
}

/**
 * @ignore
 */
function smarty_resource_framework_timestamp($resource_path, &$tpl_timestamp, &$smarty)
{
    $tpl_path = APP_ROOT . '/smarty/templates/' . $resource_path;
    if (!file_exists($tpl_path)) die("framework resource {$tpl_path} does not exist.");
    $tpl_timestamp = filemtime($tpl_path);
    return true;
}

/**
 * @ignore
 */
function smarty_resource_framework_secure($resource_path, &$smarty)
{
    // assume all templates are secure
    return true;
}

/**
 * @ignore
 */
function smarty_resource_framework_trusted($resource_path, &$smarty)
{
    // not used for templates
}
?>

