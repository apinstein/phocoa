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
 *  id - The id of the WFWidget to show errors for. OPTIONAL -- if the ID is for a WFForm, will show ALL errors from form submission. 
 8       NOTE: DEPRECATED: You can also leave the ID BLANK to show all errors. But this has been deprecated b/c we need the ID to make AJAX updating work.
 *       NOTE: to show errors on WFDynamic-generated widgets, use WFShowErrors with the ID of the WFDynamic. The WFShowErrors tag must occur AFTER the WFDynamic tag.
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

    // if the ID is a WFForm, that is the same as "all errors"
    $getErrorsForId = NULL;
    if (!empty($params['id']))
    {
        if ( !($smarty->getPage()->outlet($params['id']) instanceof WFForm) )
        {
            $getErrorsForId = $params['id'];
        }
    }

    // get error list
    if ($getErrorsForId === NULL)
    {
        // get all errors
        $page = $smarty->getPage();
        $errors = $page->errors();
    }
    else
    {
        // get errors for a specific widget
        $widget = $smarty->getCurrentWidget($params);
        if ($widget instanceof WFDynamic)
        {
            $widget = $widget->getLastRenderedWidget();
        }
        $errors = $widget->errors();
    }

    $errorHTML = '';
    $errorSmarty->assign('errorList', $errors);
    $errorSmarty->assign('id', (empty($params['id']) ? NULL : $params['id'] ) );
    $errorHTML = $errorSmarty->render(false);
    return $errorHTML;
}
?>
