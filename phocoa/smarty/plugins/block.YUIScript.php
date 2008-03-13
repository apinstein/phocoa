<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package UI
 * @subpackage Widgets
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/** 
 *  Smarty plugin to include Javascript code that depends on YUI stuff.
 *  
 *  Smarty Params:
 *  require - A comma-separated list of required YUI components for the script block.
 *
 *  @param array The params from smarty tag.
 *  @param string The javascript code.
 *  @param object WFSmarty object of the current tpl.
 *  @return string The rendered HTML.
 */

function smarty_block_YUIScript($params, $content, &$smarty, &$repeat)
{
    // beginning or end block?
    if (isset($content))
    {
        if (!isset($params['require'])) throw( new WFException("YUIScript requires a 'require' parameter.") );
        $loader = WFYAHOO_yuiloader::sharedYuiLoader();
        $loader->yuiRequire($params['require']);
        $callback = '
function() {
' . $content . '}';
        print $loader->jsLoaderCode($callback);
    }
}

?>
