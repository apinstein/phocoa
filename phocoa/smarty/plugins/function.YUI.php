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
 *  Smarty plugin to include the YUI library in your application in the phocoa-clean way.
 *  
 *  Smarty Params:
 *  require - A comma-separated list of YUI modules needed from YUI loader.
 *  callback - A string javascript function name to call once the modules have been loaded.
 *  NOTE: The {YUI} tag should be INSIDE of a <script> tag.
 *
 *  @param array The params from smarty tag.
 *  @param object WFSmarty object of the current tpl.
 *  @return string Raw JS code that imports the YUI libraries requested.
 */
function smarty_function_YUI($params, &$smarty)
{
    if (!isset($params['require']))
    {
        $params['require'] = 'yahoo';
    }
    $yl = WFYAHOO_yuiloader::sharedYuiLoader();
    $yl->yuiRequire($params['require']);
    return $yl->jsLoaderCode( (isset($params['callback']) ? $params['callback'] : NULL) );
}
?>
