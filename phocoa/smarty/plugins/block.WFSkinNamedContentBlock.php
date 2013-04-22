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
 *  Smarty plugin to make it easy for templates to store template partials into namedContent blocks.
 *
 *  Always stuffs the content into the rootSkin.
 *
 *  Smarty Params:
 *  name            The "name" of the namedContent where the block's content should be stored (required).
 *  inlineIfNoSkin  If true, will "print" the content inline if there is no skin available. If false, will throw an exception. Default false.
 *
 *  @param array The params from smarty tag.
 *  @param string The content to put in the namedContent
 *  @param object WFSmarty object of the current tpl.
 *  @param mixed
 *  @return string The rendered HTML.
 */
function smarty_block_WFSkinNamedContentBlock($params, $content, &$smarty, &$repeat)
{
    if (!isset($params['name'])) throw new WFException('The "name" attribute is required for WFSkinNamedContentBlock.');
    if (!isset($params['inlineIfNoSkin']))
    {
        $params['inlineIfNoSkin'] = false;
    }

    // beginning or end block?
    if (isset($content))
    {
        // end block; content is populated.
        $skin = $smarty->get_template_vars('__module')->invocation()->rootSkin();
        if ($skin)
        {
            $skin->setContentForName($content, $params['name']);
        }
        else
        {
            if ($params['inlineIfNoSkin'])
            {
                print $content;
            }
            else
            {
                throw new WFException("There is no root skin found for namedContent: {$params['name']}.");
            }
        }
    }
}

?>
