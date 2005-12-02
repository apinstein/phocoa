<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package WebApplication
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/**
 * Various includes used in all cases.
 */
require_once('WFObject.php');
require_once('WFLog.php');
require_once('WFException.php');


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

    /**
     * @var object The delegate object for the application.
     */
    protected $delegate;

    /**
     * Constructor
     */
    function __construct()
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
            require_once( $delegate_path );
            $delegate_class = WEBAPP_DELEGATE;
            if ( !class_exists($delegate_class) )
            {
            	die("WFWebApplicationDelegate class $delegate_class does not exist.");
            }
            $this->delegate = new $delegate_class;

            // load the session HERE, once the app has had a chance to load any needed classes that are serialized (specifically for WFAuthorization*)
            require_once('WFSession.php');

            if (method_exists($this->delegate, 'initialize'))
            {
                $this->delegate->initialize();
            }
        }
    }

    /**
     * Bootstrap control of the application to the RequestController.
     *
     * The web framework's normal cycle is to instantiate the WFWebApplication then pass control to the WFRequestController to handle the request.
     */
    function runWebApplication()
    {
        require_once('WFRequestController.php');
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
            $webapp = new WFWebApplication();   // must make copy or the singleton part won't work.
        }
        return $webapp;
    }

    /**
      * Get the absolute path of one of the application directories.
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

    /** DELEGATE WRAPPER METHODS BELOW */

    /**
     *  Autoload callback for WFWebApplication.
     *
     *  @param string The class name needing loading.
     *  @return boolean TRUE if the class loading request was handled, FALSE otherwise.
     */
    function autoload($className)
    {
        // let the application try to autoload the class
        if (is_object($this->delegate) && method_exists($this->delegate, 'autoload')) {
            $loaded = $this->delegate->autoload($className);
            if ($loaded) return true;
        }

        // I am guessing that using a hashmap will be faster than a big switch statement... no tests yet, but... in any case I'll do it this way first.
        // other option is to add a bunch of paths to include_path, but that seems like a bad idea...
        static $autoloadClassmapCache = array(
            'WFAuthorizationDelegate' => 'framework/WFAuthorization.php',
            'WFAuthorizationInfo' => 'framework/WFAuthorization.php',
            'WFAuthorizationException' => 'framework/WFAuthorization.php',
            'WFAuthorizationManager' => 'framework/WFAuthorization.php',
            'WFUNIXDateFormatter' => 'framework/widgets/WFFormatter.php',
            'WFSQLDateFormatter' => 'framework/widgets/WFFormatter.php',
            'WFNumberFormatter' => 'framework/widgets/WFFormatter.php',
            'WFPaginator' => 'framework/WFPagination.php',
            'WFPagedArray' => 'framework/WFPagination.php',
            'WFPagedPropelQuery' => 'framework/WFPagination.php',
            'WFPagedCreoleQuery' => 'framework/WFPagination.php',
            'WFDieselSearch' => 'framework/WFDieselpoint.php',
            'WFWidget' => 'framework/widgets/WFWidget.php',
            'WFDieselFacet' => 'framework/widgets/WFDieselFacet.php',
            'WFDynarchMenu' => 'framework/widgets/WFDynarchMenu.php',
            'WFDynamic' => 'framework/widgets/WFDynamic.php',
            'WFSelectionCheckbox' => 'framework/widgets/WFSelectionCheckbox.php',
            'WFImage' => 'framework/widgets/WFImage.php',
            'WFForm' => 'framework/widgets/WFForm.php',
            'WFLabel' => 'framework/widgets/WFLabel.php',
            'WFLink' => 'framework/widgets/WFLink.php',
            'WFMessageBox' => 'framework/widgets/WFMessageBox.php',
            'WFPassword' => 'framework/widgets/WFPassword.php',
            'WFTextField' => 'framework/widgets/WFTextField.php',
            'WFTextArea' => 'framework/widgets/WFTextArea.php',
            'WFHTMLArea' => 'framework/widgets/WFHTMLArea.php',
            'WFSubmit' => 'framework/widgets/WFSubmit.php',
            'WFSelect' => 'framework/widgets/WFSelect.php',
            'WFJumpSelect' => 'framework/widgets/WFJumpSelect.php',
            'WFTimeSelect' => 'framework/widgets/WFTimeSelect.php',
            'WFHidden' => 'framework/widgets/WFHidden.php',
            'WFCheckbox' => 'framework/widgets/WFCheckbox.php',
            'WFCheckboxGroup' => 'framework/widgets/WFCheckboxGroup.php',
            'WFRadio' => 'framework/widgets/WFRadio.php',
            'WFRadioGroup' => 'framework/widgets/WFRadioGroup.php',
            'WFUpload' => 'framework/widgets/WFUpload.php',
            'WFPaginatorNavigation' => 'framework/widgets/WFPaginatorNavigation.php',
            'WFPaginatorSortLink' => 'framework/widgets/WFPaginatorSortLink.php',
            'WFPaginatorState' => 'framework/widgets/WFPaginatorState.php',
            'WFModuleView' => 'framework/widgets/WFModuleView.php',
            'WFTabView' => 'framework/widgets/WFTabView.php',
            'WFPaginatorPageInfo' => 'framework/widgets/WFPaginatorPageInfo.php',
            'WFValueTransformer' => 'framework/ValueTransformers/WFValueTransformer.php',
            'WFNegateBooleanTransformer' => 'framework/ValueTransformers/WFNegateBooleanTransformer.php',
            'WFIsEmptyTransformer' => 'framework/ValueTransformers/WFIsEmptyTransformer.php',
            'WFIsNotEmptyTransformer' => 'framework/ValueTransformers/WFIsNotEmptyTransformer.php',
        );

        if (isset($autoloadClassmapCache[$className]))
        {
            require_once($autoloadClassmapCache[$className]);
            return true;
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
        if (is_object($this->delegate) && method_exists($this->delegate, 'defaultModule')) {
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
        if (is_object($this->delegate) && method_exists($this->delegate, 'defaultSkinDelegate')) {
            return $this->delegate->defaultSkinDelegate();
        }
        return NULL;
    }
}


?>
