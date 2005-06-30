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
 *  Smarty plugin to include a css file for a skin.
 *  
 *  Smarty Params:
 *  file - The CSS file name.
 *  media - The media for this CSS link.
 *
 *  @param array The params from smarty tag.
 *  @param object WFSmarty object of the current tpl.
 *  @return string The rendered HTML <link> tag.
 */
function smarty_function_WFSkinCSS($params, &$smarty)
{
    // validate input
    // determine css file name
    if (isset($params['file']) and $params['file'])
        $file = $params['file'];
    else
        die("must specify a css file via file=");

    // determine media
    if (isset($params['media']) and $params['media'])
        $media = " media=\"{$params['media']}\"";
    else
        $media = '';

    $skin = $smarty->get_template_vars('skin');
    return '<link rel="stylesheet" type="text/css" href="' . WWW_ROOT . '/css/dspSkinCSS/' . $file . '/' . $skin->delegateName() . '/' . $skin->valueForKey('skinName') . '/' . $skin->valueForKey('skinThemeName') . '"' . $media . ' />';

}
?>
