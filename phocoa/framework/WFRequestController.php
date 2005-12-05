<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package WebApplication
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/**
 * The RequestController object is a singleton controller for the entire request-act-respond cycle.
 *
 * Basically, the WFRequestController bootstraps the request into a WFModuleInvocation, executes it, wraps it in a skin, an displays the output.
 *
 * It also has a top-level exception catcher for all uncaught exceptions and displays a friendly error message (or an informative one for development machines).
 */
class WFRequestController extends WFObject
{
    /**
      * @var object The {@link WFSkin} object for the current request. Modules that need to set items on the skin can do so
      *             by getting the sharedRequestController, getting the skin object, and making changes.
      */
    protected $skin;
    /**
     * @var object The root WFModuleInvocation object used for the request.
     */
    protected $rootModuleInvocation;

    function __construct()
    {
        // set up skin
        $this->skin = new WFSkin();
        $this->skin->setDelegateName(WFWebApplication::sharedWebApplication()->defaultSkinDelegate());
    }

    /**
      * Exception handler for the WFRequestController.
      *
      * This is basically the uncaught exception handler for the request cycle.
      * We want to have this in the request object because we want the result to be displayed within our skin system.
      * This function will display the appropriate error page based on the deployment mode for this machine, then exit.
      *
      * @param Exception The exception object to handle.
      */
    function handleException(Exception $e)
    {
        $exceptionPage = new WFSmarty();
        $exceptionPage->assign('exception', $e);
        $exceptionPage->assign('home_url', WWW_ROOT . '/');
        if (IS_PRODUCTION)
        {
            $exceptionPage->setTemplate(WFWebApplication::appDirPath(WFWebApplication::DIR_SMARTY) . '/app_error_user.tpl');
            // optionally notify an administrator via email of an exception - how to configure?
            // optionally log exception info to a logfile - how to configure?
            WFExceptionReporting::log($e);
        }
        else
        {
            $exceptionPage->setTemplate(WFWebApplication::appDirPath(WFWebApplication::DIR_SMARTY) . '/app_error_developer.tpl');
            // optionally notify an administrator via email of an exception - how to configure?
            // optionally log exception info to a logfile - how to configure?
            WFExceptionReporting::log($e);
        }

        // display the error and exit
        $body_html = $exceptionPage->render(false);
        $this->skin->setBody($body_html);
        $this->skin->setTitle("An error has occurred.");
        $this->skin->render();
        exit;
    }

    /**
     * Run the web application for the current request.
     *
     * NOTE: Both a module and page must be specified in the URL. If they are not BOTH specified, the server will REDIRECT the request to the full URL.
     *       Therefore, you should be sure that when posting form data to a module/page, you use a full path. {@link WFRequestController::WFURL}
     *
     * Will pass control onto the current module for processing.
     *
     * Create a WFModuleInvocation based on the current HTTP Request, get the results, skin them, and output the completed web page.
     *
     * @todo Handle 404 situation better -- need to be able to detect this nicely from WFModuleInvocation.. maybe an Exception subclass?
     * @todo PATH_INFO with multiple params, where one is blank, isn't working correctly. IE, /url/a//c gets turned into /url/a/c for PATH_INFO thus we skip a "null" param.
     */
    function handleHTTPRequest()
    {
        $modInvocationPath = ( empty($_SERVER['PATH_INFO']) ? '' : $_SERVER['PATH_INFO'] );
        try {
            $this->rootModuleInvocation = new WFModuleInvocation($modInvocationPath, NULL);

            // get HTML result of the module
            $body_html = $this->rootModuleInvocation->execute();

            // display the skin
            $this->skin->setBody($body_html);
            $this->skin->render();
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     *  Get the root {@link WFModuleInvocation} used by the request controller.
     *
     *  @return object The root WFModuleInvocation for the page.
     */
    function rootModuleInvocation()
    {
        return $this->rootModuleInvocation;
    }

    /**
      * Get a reference to this WFRequestController's skin object.
      *
      * This is useful when a module wants to edit some of the skin configuration:
      * Example:
      * $skin =& WFRequestController::sharedSkin();
      * $skin->setTemplateType(SKIN_WRAPPER_TYPE_MINIMAL);
      * $skin->setTitle("Page title");
      *
      * @static
      * @return object A reference to the {@link WFSkin} object.
      */
    function sharedSkin()
    {
        $rc = WFRequestController::sharedRequestController();
        return $rc->skin;
    }

    /**
      * Generate a "full" URL to the given module / page.
      *
      * It is recommended to use this function to generate all URL's to pages in the application.
      * Of course you may append some PATH_INFO or params afterwards.
      *
      * @static
      * @param string The module name (required).
      * @param string The page name (or NULL to use the default page).
      * @return string a RELATIVE URL to the requested module/page.
      */
    function WFURL($moduleName, $pageName = NULL)
    {
        $moduleName = ltrim($moduleName, '/');  // just in case a '/path' path is passed, we normalize it for our needs.
        if (empty($moduleName)) throw( new Exception("Module is required to generate a WFURL.") );
        $url = WWW_ROOT . '/' . $moduleName . '/' . $pageName;
        return $url;
    }

    /**
     * Get a reference to the shared WFRequestController object.
     * @static
     * @return object The WFRequestController object.
     */
    function sharedRequestController()
    {
        static $singleton = NULL;
        if (!$singleton) {
            $singleton = new WFRequestController();
        }
        return $singleton;
    }
}
?>
