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
 *  Smarty plugin to allow inclusion of WFModule invocations from the skin.
 *
 *  Smarty Params:
 *  invocationPath - The invocationPath for the module. See {@link WFModuleInvocation}. Required.
 *  targetRootModule - If you want to customize the value of {@link WFModuleInvocation::$targetRootModule}, specify it in the param.
 *                     BOOLEAN, but remember that in smarty targetRootModule="false" passing the STRING false, so do targetRootModule=false
 *
 *  @param array The params from smarty tag
 *  @param object WFSmarty object of the current tpl
 *  @return string The HTML snippet from the WFModule.
 *  @throws Exception if the module cannot be found or no invocationPath is specified.
 */
function smarty_function_WFSkinModuleView($params, &$smarty)
{
    if (empty($params['invocationPath'])) throw( new Exception("InvocationPath is required.") );
    $rc = WFRequestController::sharedRequestController();
    $modInvocation = new WFModuleInvocation($params['invocationPath'], $rc->rootModuleInvocation());
    $modInvocation->setRespondsToForms(false);
    if (isset($params['targetRootModule']))
    {
        $modInvocation->setTargetRootModule($params['targetRootModule']);
    }
    return $modInvocation->execute();
}

/* vim: set expandtab: */

?>
