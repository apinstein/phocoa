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
 * The WFModuleView allows for the easy inclusion of module/page based components in your template.
 *
 * This is a very powerful capability. Developers can develop re-usable components for their web application
 * using the standard {@link WFModule Module/Page mechanism}. Then, any page in the entire application can easily include the
 * component with a {WFModuleView id="subModuleID"} tag in the template. The instance can be configured with
 * any {@link WFModuleView::invocationPath}, and can be used multiple times. Even the targeting of forms on the component can be
 * configured with the {@link WFModuleView::targetRootModule} property.
 *
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * - {@link WFModuleView::$invocationPath invocationPath}
 *
 * @todo Once bindings are moved to WFView, this should probably be made into a WFView subclass since it does not use "value" or represent an interactive control.
 */
class WFModuleView extends WFWidget
{
    /**
      * @var string The invocationPath for the module to include. Ex: /path/to/my/module/PageName/param1/param2
      */
    protected $invocationPath;
    /**
      * @var boolean TRUE to target the module to the root module, false to target to the current module.
      */
    protected $targetRootModule;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->invocationPath = NULL;
        $this->targetRootModule = true;
    }

    /**
     *  Set the invocationPath that will be used for the module view.
     *
     *  @param string The full invocationPath
     */
    function setInvocationPath($path)
    {
        $this->invocationPath = $path;
    }

    function render($blockContent = NULL)
    {
        // create an invocation, execute it, and return the HTML.
        $wfi = new WFModuleInvocation($this->invocationPath, $this->page()->module()->invocation());
        $wfi->setTargetRootModule($this->targetRootModule);
        return $wfi->execute();
    }

    function canPushValueBinding() { return false; }
}

?>
