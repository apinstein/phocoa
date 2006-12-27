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
 *  Smarty plugin to make it easy for templates to add code to the "head" element.
 *
 *  Adds the contained block of HTML to the HTML HEAD tag if there is a skin,
 *  or just prints it out inline if there is no skin.
 *
 *  Smarty Params:
 *  NONE
 *
 *  @param array The params from smarty tag.
 *  @param string The content to put in the head tag.
 *  @param object WFSmarty object of the current tpl.
 *  @param mixed
 *  @return string The rendered HTML.
 */
function smarty_block_WFHead($params, $content, &$smarty, &$repeat)
{
    // beginning or end block?
    if (isset($content))
    {
        $module = $smarty->get_template_vars('__module');
        $skin = $module->invocation()->rootSkin();
        if ($skin)
        {
            $skin->addHeadString($content);
        }
        else
        {
            print $content;
        }
    }
}

?>
