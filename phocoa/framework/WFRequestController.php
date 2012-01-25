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

    /* These are the errors that phocoa tries to roll-up and catch and report on. All other errors will be left to their default handling.
             E_ERROR
       //  | E_WARNING
           | E_PARSE
       //  | E_NOTICE
           | E_CORE_ERROR
           | E_CORE_WARNING
           | E_COMPILE_ERROR
           | E_COMPILE_WARNING
           | E_USER_ERROR
       //  | E_USER_WARNING
       //  | E_USER_NOTICE
       //  | E_STRICT
           | E_RECOVERABLE_ERROR
       //  | E_DEPRECATED
       //  | E_USER_DEPRECATED
     */
    private $handleErrors = 4597;

    private function phpErrorAsString($e)
    {
        $el = array(
            1     => 'E_ERROR',
            2     => 'E_WARNING',
            4     => 'E_PARSE',
            8     => 'E_NOTICE',
            16    => 'E_CORE_ERROR',
            32    => 'E_CORE_WARNING',
            64    => 'E_COMPILE_ERROR',
            128   => 'E_COMPILE_WARNING',
            256   => 'E_USER_ERROR',
            512   => 'E_USER_WARNING',
            1024  => 'E_USER_NOTICE',
            2048  => 'E_STRICT',
            4096  => 'E_RECOVERABLE_ERROR',
            8192  => 'E_DEPRECATED',
            16384 => 'E_USER_DEPRECATED',
        );
        return $el[$e];
    }

    /**
     * Error handler callback for PHP catchable errors; helps synthesize PHP errors and exceptions into the same handling workflow.
     */
    function handleError($errNum, $errString, $file, $line, $contextArray)
    {
        $errNum = $this->phpErrorAsString($errNum);

        $this->handleException( new ErrorException("{$errNum}: {$errString}\n\nAt {$file}:{$line}") );
    }

    /**
     * Error handler callback for PHP un-catchable errors; helps synthesize PHP errors and exceptions into the same handling workflow.
     */
    function checkShutdownForFatalErrors()
    {
        $last_error = error_get_last();
        if ($last_error['type'] & $this->handleErrors)
        {
            $last_error['type'] = $this->phpErrorAsString($last_error['type']);
            $this->handleException( new ErrorException("{$last_error['type']}: {$last_error['message']}\n\nAt {$last_error['file']}:{$last_error['line']}") );
        }
    }

    /**
     * PHOCOA's default error handling is to try to catch *all* fatal errors and run them through the framework's error handling system.
     *
     * The error handling system unifies excptions and errors into the same processing stream and additionally gives your application
     * an opportunity to handle the error as well. For instance your app may prefer to email out all errors,
     * or send them to an exception service like Hoptoad/Exceptional/Loggly
     */
    private function registerErrorHandlers()
    {
        // convert these errors into exceptions
        set_error_handler(array($this, 'handleError'), $this->handleErrors);

        // catch non-catchable errors
        register_shutdown_function(array($this, 'checkShutdownForFatalErrors'));
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
        // give ourselves a little more memory so we can process the exception
        ini_set('memory_limit', memory_get_usage() + 25000000 /* 25MB */);

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
        // point all error handling to phocoa's internal mechanisms since anything that happens after this line (try) will be routed through the framework's handler
        $this->registerErrorHandlers();
        try {
            $relativeURI = parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH); // need to run this to convert absolute URI's to relative ones (sent by SOME http clients)
            if ($relativeURI === false) throw new WFRequestController_NotFoundException("Malformed URI: {$_SERVER['REQUEST_URI']}");
            $modInvocationPath = ltrim(substr($relativeURI, strlen(WWW_ROOT)), '/');
            $paramsPos = strpos($modInvocationPath, '?');
            if ($paramsPos !== false)
            {
                $modInvocationPath = substr($modInvocationPath, 0, $paramsPos);
            }

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
                $html = "<"."?xml version=\"1.0\"?"."><raw><![CDATA[\n{$html}\n]]></raw>";
            }   

            print $html;
        } catch (WFRequestController_RedirectException $e) {
            header("HTTP/1.1 {$e->getCode()}");
            header("Location: {$e->getRedirectURL()}");
            exit;
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
     * Determine whether the current request is from a mobile browers
     *
     *
     * @return boolean
     */
    private $isMobileBrowser = NULL;
    function isMobileBrowser()
    {
        if ($this->isMobileBrowser !== NULL) return $this->isMobileBrowser;

        $op = (isset($_SERVER['HTTP_X_OPERAMINI_PHONE']) ? strtolower($_SERVER['HTTP_X_OPERAMINI_PHONE']) : '');
        $ua = (isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '');
        $ac = (isset($_SERVER['HTTP_ACCEPT']) ? strtolower($_SERVER['HTTP_ACCEPT']) : '');

        $this->isMobileBrowser = strpos($ac, 'application/vnd.wap.xhtml+xml') !== false
                    || $op != ''
                    || strpos($ua, 'sony') !== false 
                    || strpos($ua, 'symbian') !== false 
                    || strpos($ua, 'nokia') !== false 
                    || strpos($ua, 'samsung') !== false 
                    || strpos($ua, 'mobile') !== false
                    || strpos($ua, 'windows ce') !== false
                    || strpos($ua, 'epoc') !== false
                    || strpos($ua, 'opera mini') !== false
                    || strpos($ua, 'nitro') !== false
                    || strpos($ua, 'j2me') !== false
                    || strpos($ua, 'midp-') !== false
                    || strpos($ua, 'cldc-') !== false
                    || strpos($ua, 'netfront') !== false
                    || strpos($ua, 'mot') !== false
                    || strpos($ua, 'up.browser') !== false
                    || strpos($ua, 'up.link') !== false
                    || strpos($ua, 'audiovox') !== false
                    || strpos($ua, 'blackberry') !== false
                    || strpos($ua, 'ericsson,') !== false
                    || strpos($ua, 'panasonic') !== false
                    || strpos($ua, 'philips') !== false
                    || strpos($ua, 'sanyo') !== false
                    || strpos($ua, 'sharp') !== false
                    || strpos($ua, 'sie-') !== false
                    || strpos($ua, 'portalmmm') !== false
                    || strpos($ua, 'blazer') !== false
                    || strpos($ua, 'avantgo') !== false
                    || strpos($ua, 'danger') !== false
                    || strpos($ua, 'palm') !== false
                    || strpos($ua, 'series60') !== false
                    || strpos($ua, 'palmsource') !== false
                    || strpos($ua, 'pocketpc') !== false
                    || strpos($ua, 'smartphone') !== false
                    || strpos($ua, 'rover') !== false
                    || strpos($ua, 'ipaq') !== false
                    || strpos($ua, 'au-mic,') !== false
                    || strpos($ua, 'alcatel') !== false
                    || strpos($ua, 'ericy') !== false
                    || strpos($ua, 'up.link') !== false
                    || strpos($ua, 'vodafone/') !== false
                    || strpos($ua, 'wap1.') !== false
                    || strpos($ua, 'wap2.') !== false;

        return $this->isMobileBrowser;
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

class WFRequestController_InternalRedirectException extends WFRedirectRequestException {}
class WFRequestController_NotFoundException extends WFException {}
class WFRequestController_BadRequestException extends WFException {}
class WFRequestController_HTTPException extends WFException
{
    public function __construct($message = NULL, $code = 500) { parent::__construct($message, $code); }
}
class WFRequestController_RedirectException extends WFRequestController_HTTPException
{
    protected $redirectUrl;

    /**
     * By default use http code 302, but allow user to override it
     * to use e.g. a 301.
     */
    public function __construct($url, $code = 302)
    {
        $this->redirectUrl = $url;
        return parent::__construct($url, $code);
    }

    public function getRedirectURL()
    {
        return $this->redirectUrl;
    }
}

/**
 * There are certain classes that are needed to successfully handle errors. We need to make sure that they are loaded up front so that
 * they don't need to be autoloaded during error handling, which can result in errors during error handling such as:
 *
 * - Fatal error: Class declarations may not be nested in /Users/alanpinstein/dev/sandbox/showcaseng/showcaseng/externals/phocoa/phocoa/framework/WFExceptionReporting.php on line 14
 *
 * Simply running a class_exists on each class will force an autoload when WFRequestController is parsed, preventing the problem.
 * We don't do a hard require('file.php') here since that would break our automated opcode-cache-friendly require('/full/path/to/file.php') system in phocoa's autoloader.
 */
class_exists('WFExceptionReporting');
