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
 *  Smarty plugin to display errors from a form submission.
 *
 *  The error is rendered by the "form_error.tpl" template file.
 *  
 *  Smarty Params:
 *  id - The id of the WFWidget to show errors for. OPTIONAL -- if BLANK, will show ALL errors from form submission.
 *
 *  @param array The params from smarty tag.
 *  @param object WFSmarty object of the current tpl.
 *  @return string The rendered HTML of the error.
 *  @todo Need to make it not-hard-coded to get the form_error.tpl file... should be able to override this in userland.
 */

function smarty_function_WFShowErrors($params, &$smarty)
{
    static $errorSmarty = NULL;

    if (is_null($errorSmarty))
    {
        $errorSmarty = new WFSmarty;
        $errorSmarty->setTemplate(WFWebApplication::appDirPath(WFWebApplication::DIR_SMARTY) . '/form_error.tpl');
    }

    // get error list
    if (empty($params['id']))
    {
        // get all errors
        $page = $smarty->getPage();
        $errors = $page->errors();
    }
    else
    {
        // get errors for a specific widget
        $widget = $smarty->getCurrentWidget($params);
        $errors = $widget->errors();
    }

    $errorHTML = '';
    if (count($errors) > 0)
    {
        $errorSmarty->assign('errorList', $errors);
        $errorHTML = $errorSmarty->render(false);
    }
    return $errorHTML;
}
?>
