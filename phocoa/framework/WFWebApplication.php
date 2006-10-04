<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package WebApplication
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/**
 * BOOTSTRAP function used by the main web page to start our framework.
 */
function WFWebApplicationMain()
{
    $api = php_sapi_name();
    $webapp = NULL;
    $webapp = WFWebApplication::sharedWebApplication();
    switch ($api) {
        case 'cli':
            break;
        default:
            $webapp->runWebApplication();
            break;

    }
    return $webapp;
}

/**
 * The WFWebApplication object is a singleton object that manages the running of the any Phocoa request, be it CLI or HTTP.
 *
 * Right now it doesn't do a whole lot besides manage the shared application object and provide access to a few application defaults via the delegate.
 * Eventually it can be used to manage application-wide settings and state.
 *
 * @tutorial WebApplication.pkg
 */
class WFWebApplication extends WFObject
{
    const DIR_LOG 		= 1;
    const DIR_RUNTIME 	= 2;
    const DIR_CLASSES 	= 3;
    const DIR_SMARTY 	= 4;
    const DIR_MODULES 	= 5;
    const DIR_WWW       = 6;
    const DIR_SKINS     = 7;

    const WWW_DIR_BASE          = 1;
    const WWW_DIR_FRAMEWORK     = 2;

    /**
     * @var object The delegate object for the application.
     */
    protected $delegate;

    /**
     * Constructor
     */
    function __construct() {}

    /**
     *  This is the true "setup function"; use this instead of constructor for setup because the object is a singleton with callback,
     *  and down in the call stack of "init" are calls to get the singleton. 
     *  If this happens from the constructor, then the singleton accessor is called before it has a chance to save the singleton.
     *
     *  NOTE: assuming right now that this function is called exactly once... thus we use require() instead of require_once() for optimal performance with opcode caches.
     */
    private function init()
    {
        $this->delegate = NULL;
        if (defined('WEBAPP_DELEGATE')) 
        {
            // load delegate
            $delegate_path = APP_ROOT . '/classes/' . WEBAPP_DELEGATE . '.php';
            if ( !file_exists($delegate_path) )
            {
                die("WFWebApplicationDelegate class file cannot be found: $delegate_path");
            }
            // include the application's delegate. This file should load any classes needed by the session.
            require($delegate_path);
            $delegate_class = WEBAPP_DELEGATE;
            if ( !class_exists($delegate_class) )
            {
            	die("WFWebApplicationDelegate class $delegate_class does not exist.");
            }
            $this->delegate = new $delegate_class;

            // load the session HERE - CANNOT DO THIS VIA AUTOLOAD! EXACT TIMING OF THIS IS VERY IMPORTANT
            require('framework/WFSession.php');

            if (method_exists($this->delegate, 'initialize'))
            {
                $this->delegate->initialize();
            }
        }
    }

    /**
     *  Get the delegate for the WFWebApplication.
     *
     *  @return object WFObject An object implementing the {@link WFWebApplicationDelegate} informal protocol.
     */
    function delegate()
    {
        return $this->delegate;
    }

    /**
     * Bootstrap control of the application to the RequestController.
     *
     * The web framework's normal cycle is to instantiate the WFWebApplication then pass control to the WFRequestController to handle the request.
     */
    function runWebApplication()
    {
        $rc = WFRequestController::sharedRequestController();
        $rc->handleHTTPRequest();
    }

    /**
     * Get a reference to the shared application object.
     * @static
     * @return object The WFWebApplication object.
     */
    static function sharedWebApplication()
    {
        static $webapp = NULL;
        if (!$webapp) 
        {
            $webapp = new WFWebApplication();
            $webapp->init();
        }
        return $webapp;
    }

    /**
      * Get the absolute path of one of the application directories.
      *
      * @param string One of the DIR_* constants.
      * @return string The absolute path of the passed directory type. NO TRAILING SLASH!! ADD IT YOURSELF.
      */
    static function appDirPath($appDirName)
    {
        switch ($appDirName) {
            case self::DIR_LOG:
                return LOG_DIR;
            case self::DIR_RUNTIME:
                return RUNTIME_DIR;
            case self::DIR_CLASSES:
                return APP_ROOT . '/classes';
            case self::DIR_SMARTY:
                return FRAMEWORK_DIR . '/smarty/templates';
            case self::DIR_MODULES:
                return APP_ROOT . '/modules';
            case self::DIR_WWW:
                return APP_ROOT . '/wwwroot/www';
            case self::DIR_SKINS:
                return APP_ROOT . '/skins';
            default:
                throw(new Exception("Unknown app dir: {$appDirName}."));
        }
    }

    /**
     *  Get the www-absolute path of one of the application's public www directories.
     *
     *  @param string One of the WWW_DIR_* constants.
     *  @return string The absolute path to the passed directory type.
     *  @throws
     */
    static function webDirPath($webDirPath)
    {
        switch ($webDirPath) {
            case self::WWW_DIR_BASE:
                return WWW_ROOT . '/www';
            case self::WWW_DIR_FRAMEWORK:
                return WWW_ROOT . '/www/framework';
            default:
                throw(new Exception("Unknown app dir: {$webDirPath}."));
        }
    }

    /** DELEGATE WRAPPER METHODS BELOW */

    /**
     *  Autoload callback for WFWebApplication.
     *
     *  Will allow the app delegate to autoload classes if autoload() is declared in the app delegate.
     *
     *  @param string The class name needing loading.
     *  @return boolean TRUE if the class loading request was handled, FALSE otherwise.
     */
    function autoload($className)
    {
        // let the application try to autoload the class
        if (is_object($this->delegate) && method_exists($this->delegate, 'autoload'))
        {
            $loaded = $this->delegate->autoload($className);
            if ($loaded) return true;
        }
        return false;
    }

    /**
     * Get the default module for this web application. The default module is the module that will be run if the web root is accessed.
     *
     * The default module is provided by the {@link WFWebApplicationDelegate}.
     * 
     * @return string The default module for this web application. Will be either a module name (examplemodule) or a path to a module (path/to/examplemodule).
     */
    function defaultModule()
    {
        if (is_object($this->delegate) && method_exists($this->delegate, 'defaultModule'))
        {
            return $this->delegate->defaultModule();
        }
        return NULL;
    }

    /**
     * Get the default Skin delegate for the application.
     *
     * The default skin delegate is provided by the {@link WFWebApplicationDelegate}.
     *
     * @return object Object implementing the {@link WFSkinDelegate} delegate protocol, or NULL if there is no default delegate.
     */
    function defaultSkinDelegate()
    {
        if (is_object($this->delegate) && method_exists($this->delegate, 'defaultSkinDelegate'))
        {
            return $this->delegate->defaultSkinDelegate();
        }
        return NULL;
    }

    /**
     *  Hook to provide opportunity for the web application to munge the session config before php's session_start() is called.
     */
    function sessionWillStart()
    {
        if (is_object($this->delegate) && method_exists($this->delegate, 'sessionWillStart'))
        {
            $this->delegate->sessionWillStart();
        }
    }
    /**
     *  Hook to provide opportunity for the web application to munge the session data after php's session_start() is called.
     */
    function sessionDidStart()
    {
        if (is_object($this->delegate) && method_exists($this->delegate, 'sessionDidStart'))
        {
            $this->delegate->sessionDidStart();
        }
    }
}


?>
