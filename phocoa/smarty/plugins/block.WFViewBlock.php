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
 *  Smarty plugin to include a WFView in BLOCK mode.
 *
 *  Smarty Params:
 *  id - The id of WFView subclass.
 *
 *  @param array The params from smarty tag.
 *  @param object WFSmarty object of the current tpl.
 *  @return string The rendered HTML.
 */
function smarty_block_WFViewBlock($params, $content, &$smarty, &$repeat)
{
    // beginning or end block?
    if (isset($content))
    {
        return $smarty->getCurrentWidget($params)->render($content);
    }
}

?>
