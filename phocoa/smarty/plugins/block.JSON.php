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
 *  Smarty plugin that allows you to code a block of HTML formatted nicely for development, but convert it to JSON for use with Javascript.
 *  
 *  @param array The params from smarty tag.
 *  @param string The HTML code.
 *  @param object WFSmarty object of the current tpl.
 *  @return string The rendered HTML.
 */

function smarty_block_JSON($params, $content, &$smarty, &$repeat)
{
    // beginning or end block?
    if (isset($content))
    {
        print WFJSON::encode($content);
    }
}

?>
