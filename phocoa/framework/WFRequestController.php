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
 * Basically, the WFRequestController bootstraps the request into a WFModuleInvocation, executes it, and displays the output.
 *
 * It also has a top-level exception catcher for all uncaught exceptions and displays a friendly error message (or an informative one for development machines).
 */
class WFRequestController extends WFObject
{
    /**
     * @var object The root WFModuleInvocation object used for the request.
     */
    protected $rootModuleInvocation;

    function __construct()
    {
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
        $webAppDelegate = WFWebApplication::sharedWebApplication()->delegate();
        if (is_object($webAppDelegate) && method_exists($webAppDelegate, 'handleUncaughtException'))
        {
            $handled = $webAppDelegate->handleUncaughtException($e);
            if ($handled) return;
        }

        WFExceptionReporting::log($e);

        // build stack of errors (php 5.3+)
        if (method_exists($e, 'getPrevious'))
        {
            $allExceptions = array();
            do {
                $allExceptions[] = $e;
            } while ($e = $e->getPrevious());
        }
        else
        {
            $allExceptions = array($e);
        }

        $exceptionPage = new WFSmarty();
        $exceptionPage->assign('exceptions', $allExceptions);
        $exceptionPage->assign('exceptionClass', get_class($allExceptions[0]));
        $exceptionPage->assign('home_url', WWW_ROOT . '/');
        if (IS_PRODUCTION)
        {
            $exceptionPage->setTemplate(WFWebApplication::appDirPath(WFWebApplication::DIR_SMARTY) . '/app_error_user.tpl');
        }
        else
        {
            $exceptionPage->setTemplate(WFWebApplication::appDirPath(WFWebApplication::DIR_SMARTY) . '/app_error_developer.tpl');
        }

        // display the error and exit
        $body_html = $exceptionPage->render(false);

        // output error info
        header("HTTP/1.0 500 Uncaught Exception");
        if ($this->isAjax())
        {
            print strip_tags($body_html);
        }
        else
        {
            $skin = new WFSkin();
            $skin->setDelegateName(WFWebApplication::sharedWebApplication()->defaultSkinDelegate());
            $skin->setBody($body_html);
            $skin->setTitle("An error has occurred.");
            $skin->render();
        }
        exit;
    }

    /**
     * Error handler callback for PHP fatal errors; helps synthesize PHP errors and exceptions into the same handling workflow.
     */
    function handleError($errNum, $errString, $file, $line, $contextArray)
    {
        $this->handleException( new Exception("FATAL ERROR: {$errNum}, {$errString}\nFile: {$file}:{$line}\n" . print_r($contextArray, true)) );
    }

    /**
     * Run the web application for the current request.
     *
     * NOTE: Both a module and page must be specified in the URL. If they are not BOTH specified, the server will REDIRECT the request to the full URL.
     *       Therefore, you should be sure that when posting form data to a module/page, you use a full path. {@link WFRequestController::WFURL}
     *
     * Will pass control onto the current module for processing.
     *
     * Create a WFModuleInvocation based on the current HTTP Request, get the results, and output the completed web page.
     *
     * @todo Handle 404 situation better -- need to be able to detect this nicely from WFModuleInvocation.. maybe an Exception subclass?
     * @todo PATH_INFO with multiple params, where one is blank, isn't working correctly. IE, /url/a//c gets turned into /url/a/c for PATH_INFO thus we skip a "null" param.
     *       NOTE: Partial solution; use /WFNull/ to indicate NULL param instead of // until we figure something out.
     *       NOTE: Recent change to REQUEST_URI instead of PATH_INFO to solve decoding problem seems to have also solved the // => / conversion problem... test more!
     *       WORRY: That the new PATH_INFO calculation will fail when using aliases other than WWW_ROOT. IE: /products/myProduct might break it...
     * @todo The set_error_handler doesn't seem to work very well. PHP issue? Or am I doing it wrong? For instance, it doesn't catch $obj->nonExistantMethod().
     */
    function handleHTTPRequest()
    {
        // OLD WAY - use server's PATH_INFO
        //$modInvocationPath = ( empty($_SERVER['PATH_INFO']) ? '' : $_SERVER['PATH_INFO'] );

        // NEW WAY - calculate our own PATH_INFO. WHY? This way we can eliminate the urldecoding of the PATH_INFO which prevents passing through / and also 
        // destroys the ability to pass NULL URL params via //.
        // This way is much better, so long as it's compatible! So make sure that it doesn't break anything.
        $relativeURI = parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH); // need to run this to convert absolute URI's to relative ones (sent by SOME http clients)
        $modInvocationPath = ltrim(substr($relativeURI, strlen(WWW_ROOT)), '/');
        $paramsPos = strpos($modInvocationPath, '?');
        if ($paramsPos !== false)
        {
            $modInvocationPath = substr($modInvocationPath, 0, $paramsPos);
        }
        
        $errset = E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR;
        if (defined("E_RECOVERABLE_ERROR"))
        {
            $errset |= E_RECOVERABLE_ERROR;
        }
        set_error_handler(array($this, 'handleError'), $errset);
        try {
            if ($modInvocationPath == '')
            {
                $modInvocationPath = WFWebApplication::sharedWebApplication()->defaultInvocationPath();
            }
            // allow routing delegate to munge modInvocationPath
            $webAppDelegate = WFWebApplication::sharedWebApplication()->delegate();
            if (is_object($webAppDelegate) && method_exists($webAppDelegate, 'rerouteInvocationPath'))
            {
                $newInvocationPath = $webAppDelegate->rerouteInvocationPath($modInvocationPath);
                if ($newInvocationPath)
                {
                    $modInvocationPath = $newInvocationPath;
                }
            }

            // create the root invocation; only skin if we're not in an XHR
            $this->rootModuleInvocation = new WFModuleInvocation($modInvocationPath, NULL, ($this->isAjax() ? NULL : WFWebApplication::sharedWebApplication()->defaultSkinDelegate()) );
            // get HTML result of the module, and output it
            $html = $this->rootModuleInvocation->execute();
            
            // respond to WFRPC::PARAM_ENABLE_AJAX_IFRAME_RESPONSE_MODE for iframe-targeted XHR. Some XHR requests (ie uploads) must be done by creating an iframe and targeting the form
            // post to the iframe rather than using XHR (since XHR doesn't support uploads methinks). This WFRPC flag makes these such "ajax" requests need to be wrapped slightly differently
            // to prevent the HTML returned in the IFRAME from executing in the IFRAME which would cause errors.
            if (isset($_REQUEST[WFRPC::PARAM_ENABLE_AJAX_IFRAME_RESPONSE_MODE]) && $_REQUEST[WFRPC::PARAM_ENABLE_AJAX_IFRAME_RESPONSE_MODE] == 1)
            {  
                header('Content-Type: text/xml');
                $html = "<?xml version=\"1.0\"?><raw><![CDATA[\n{$html}\n]]></raw>";
            }   

            print $html;
        } catch (WFRequestController_HTTPException $e) {
            header("HTTP/1.0 {$e->getCode()}");
            print $e->getMessage();
            exit;
        } catch (WFRequestController_BadRequestException $e) {
            header("HTTP/1.0 400 Bad Request");
            print "Bad Request: " . $e->getMessage();
            exit;
        } catch (WFRequestController_NotFoundException $e) {
            header("HTTP/1.0 404 Not Found");
            print $e->getMessage();
            exit;
        } catch (WFRequestController_InternalRedirectException $e) {
            // internal redirect are handled without going back to the browser... a little bit of hacking here to process a new invocationPath as a "request"
            // @todo - not sure what consequences this has on $_REQUEST; seems that they'd probably stay intact which could foul things up?
            $_SERVER['REQUEST_URI'] = $e->getRedirectURL();
            WFLog::log("Internal redirect to: {$_SERVER['REQUEST_URI']}");
            self::handleHTTPRequest();
            exit;
        } catch (WFRequestController_RedirectException $e) {
            header("Location: " . $e->getRedirectURL());
            exit;
        } catch (WFRedirectRequestException $e) {
            header("Location: " . $e->getRedirectURL());
            exit;
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     *  Is the current request an XHR (XmlHTTPRequest)?
     *
     *  @return boolean
     */
    function isAjax()
    {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) and strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') return true;
        if (isset($_REQUEST['HTTP_X_REQUESTED_WITH']) and strtolower($_REQUEST['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') return true;  // for debugging
        return false;
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
      * @static
      * @return object A reference to the {@link WFSkin} object.
      * @deprecated Use $module->rootSkin()
      */
    public static function sharedSkin()
    {
        $rc = WFRequestController::sharedRequestController();
        $rootInv = $rc->rootModuleInvocation();
        if (!$rootInv) throw( new Exception("No root invocation, thus no shared skin..") );
        return $rootInv->skin();
    }

    /**
      * Generate a "full" URL to the given module / page.
      *
      * It is recommended to use this function to generate all URL's to pages in the application.
      * Of course you may append some PATH_INFO or params afterwards.
      *
      * Guaranteed to *never* end in a trailing slash. Always add your own if you are addition additional stuff to the URL.
      *
      * @static
      * @param string The module name (required).
      * @param string The page name (or NULL to use the default page).
      * @return string a RELATIVE URL to the requested module/page.
      */
    public static function WFURL($moduleName, $pageName = NULL)
    {
        $moduleName = ltrim($moduleName, '/');  // just in case a '/path' path is passed, we normalize it for our needs.
        if (empty($moduleName)) throw( new Exception("Module is required to generate a WFURL.") );
        $url = WWW_ROOT . '/' . $moduleName;
        if ($pageName !== NULL)
        {
            $url .= '/' . $pageName;
        }
        return $url;
    }

    /**
     * Get a reference to the shared WFRequestController object.
     * @static
     * @return object The WFRequestController object.
     */
    public static function sharedRequestController()
    {
        static $singleton = NULL;
        if (!$singleton) {
            $singleton = new WFRequestController();
        }
        return $singleton;
    }
}

/**
 * Helper class to allow modules to easily redirect the client to a given URL.
 * 
 * Modules can throw a WFRedirectRequestException anytime to force the client to redirect.
 *
 * @deprecated
 * @see WFRequestController_RedirectException
 */
class WFRedirectRequestException extends WFException
{
    protected $redirectUrl;
    
    function __construct($message = NULL, $code = 0)
    {
        parent::__construct($message, $code);
        $this->redirectUrl = $message;
    }

    function getRedirectURL()
    {
        return $this->redirectUrl;
    }
}

class WFRequestController_RedirectException extends WFRedirectRequestException {}
class WFRequestController_InternalRedirectException extends WFRedirectRequestException {}
class WFRequestController_NotFoundException extends WFException {}
class WFRequestController_BadRequestException extends WFException {}
class WFRequestController_HTTPException extends WFException
{
    public function __construct($message = NULL, $code = 500) { parent::__construct($message, $code); }
}
?>
