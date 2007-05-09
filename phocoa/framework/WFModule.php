<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package WebApplication
 * @subpackage Module
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/**
 * The WFModuleInvocation object is a wrapper around WFModule. This allows the modules to be nicely decoupled from the callers. Thus, the http handler
 * can create a WFModuleInvocation based on the URL, while a WFModuleView can create one based on parameters set by a caller.
 *
 * This is particularly useful for using the module infrastructure to render HTML for emails, etc.
 *
 * WFModuleInvocation is also used to keep track of "composited" pages. That is, pages can contain arbitrarily nested pages {@link WFModuleView}. This allows easy
 * creation of portal-type environments and promotes re-use of pages as components. Since most reusable components of web pages are more complicated than
 * single widgets (ie WFView or WFWidget subclasses), the ability to use modules as components allows for the creation of re-usable components that
 * harness the power of the Module/Page system. This way, components can use bindings, formatters, GUI builder, etc and thus make it much easier
 * for developers to build re-usable components for their applications.
 *
 * The ROOT module always has a skin, and its output will be skinned by default. Sub-modules by default have no skin. Callers may be interested in 
 * either the module's skin {@link skin()} on the "root" skin {@link rootSkin()} of the module.
 *
 * Developers may still want to develop new {@link WFWidget} and {@link WFView} subclasses, but these should be limited to their appropriate scope, which is
 * non-application specific UI widgets.
 * 
 * @todo Eventually we'd like the ability to have multiple BASE directories containing modules. Mainly the purpose of this is so that the framework could
 *       ship with some modules, and they would be accessible, but in a different place from the user's modules. We have to make the framework easy
 *       to update separately from the user code. We could have some "aliases" inside the path-walking stuff that would shunt over to another dir.
 *       for instance, maybe if the first path was "contrib" it would shunt over to contrib/modules/ or that kind of thing.
 * @todo Evaluate whether the WFModuleInvocation and WFModule should be coalesced into a single class... can they be used apart from each other?
 * @todo Do we need to encapsulate the "login" module in a method of WFAuthorizationManager so that applications can override the login module with their own?
 */
class WFModuleInvocation extends WFObject
{
    const PARAMETER_NULL_VALUE = 'WFNull';

    /**
     * @var object WFModuleInvocation A reference to the parent WFModuleInvocation for this object, or NULL if it's the root object.
     */
    protected $parentInvocation;
    /**
     * @var string the passed-in invocation path: /path/to/module[/Param1[/Param2]]
     */
    protected $invocationPath;
    /**
     * @var array the calculated extra parameters for the invocation: (Param1, Param2)
     */
    protected $invocationParameters;
    /**
     * @var string The path to the module. This will be normalized to always start WITHOUT '/'. IE: path/to/myModule
     */
    protected $modulePath;
    /**
     * @var string The name of the module that was found, if one was found, at modulePath.
     */
    protected $moduleName;
    /**
     * @var string The name of the page that will be displayed for this invocation.
     */
    protected $pageName;
    /**
     * @var object WFModule The WFModule that this invocation wraps.
     */
    protected $module;
    /**
      * @var boolean TRUE to have all forms for this module target the rood module, FALSE to target the current module.
      */
    protected $targetRootModule;
    /**
     * @var boolean TRUE if this invocation should respond to form submissions, FALSE otherwise. 
     *              This setting is typically used in situations where the module is used from a skin or a compositing situation.
     *              For instance, you may want to use a "search" module in your skin via {@link WFSkinModuleView}. When the user
     *              submits the form, it will automatically target the search module and display the results in the body. However,
     *              unless respondsToForms is set to FALSE, the skin itself will also respond to the search!
     */
    protected $respondsToForms;
    /**
      * @var object The {@link WFSkin} object for this invocation. By default, only ROOT invocations will be skinned.
      */
    protected $skin;
    /**
     * @var string The base modules directory that contains this module path. This is one of the paths in {@link WFWebApplication::modulePaths()}, or the APP_ROOT/modules directory.
     */
    protected $modulesDir;

    /**
     *  Constructor used to create a new WFModuleInvocation.
     *
     *  A WFModuleInvocation wraps the execution of a module. It contains all of the environment information needed to
     *  actually load and cause a module to execute and return the rendered result.
     *
     *  @param string The invocationPath for the module. The invocationPath is basically a way to specify the module to run, along with parameters.
     *                Example: path/to/my/module/pageName/param1/param2/paramN
     *  @param object WFModuleInvocation The parent WFModuleInvocation that is creating this invocation, or NULL if this is the root invocation.
     *  @param string The name of the skin delegate to use. Default is NULL (no skin).
     *  @throws Various errors if the module could not be identified.
     */
    function __construct($invocationPath, $parentInvocation, $skinDelegate = NULL)
    {
        parent::__construct();

        $this->invocationPath = ltrim($invocationPath, '/');
        if (!$this->invocationPath) throw( new Exception("invocationPath cannot be blank.") );
        $this->parentInvocation = $parentInvocation;

        $this->targetRootModule = true;
        $this->invocationParameters = array();
        $this->modulePath = NULL;
        $this->moduleName = NULL;
        $this->pageName = NULL;
        $this->module = NULL;
        $this->modulesDir = NULL;
        
        // set up default skin as needed
        if ($this->isRootInvocation() and !is_null($skinDelegate))
        {
            $this->setSkinDelegate($skinDelegate);
        }
        else
        {
            $this->skin = NULL;
        }

        $this->respondsToForms = true;  // modules respond to forms by default

        // set up module -- do this here so that we can interact with the module from the caller before execution
        $this->extractComponentsFromInvocationPath();
    }

    /**
     *  Set the "modules" directory used to access this module.
     *
     *  This is not necessarily the entire path to the module; the entire path to the module is modulesDir() + modulePath()
     *
     *  @param string The modules directory.
     *  @see WFModule::pathToModule()
     */
    function setModulesDir($d)
    {
        $this->modulesDir = $d;
    }

    /**
     *  Get the "modules" directory used to access this module.
     *
     *  This is not necessarily the entire path to the module; the entire path to the module is modulesDir() + modulePath()
     *
     *  @return string The modules directory.
     *  @see WFModule::pathToModule()
     */
    function modulesDir()
    {
        return $this->modulesDir;
    }

    /**
     *  Get the skin for this module invocation.
     *
     *  This function should be used if you want to know if this module itself has a skin. Contrast with {@link rootSkin()}.
     *
     *  @return object WFSkin. The WFSkin object for this invocation, or NULL if there is none.
     *  @see rootSkin()
     */
    function skin()
    {
        return $this->skin;
    }

    /**
     *  Get the skin used by the ROOT module of this module hierarchy.
     *
     *  This function should be used if you want access to the skin that will be wrapping the current page, regardless of where in the module hierarchy the caller is.
     *
     *  @return object WFSkin. The WFSkin object for this invocation's root module, or NULL if there is none.
     *  @see skin()
     */
    function rootSkin()
    {
        $root = $this->rootInvocation();
        return $root->skin();
    }

    /**
     *  set the skin delegate for this invocation.
     *
     *  @param string The name of the skin delegate to use.
     *  @return object WFSkin The skin object set up with the passed delegate.
     */
    function setSkinDelegate($skinDelegate)
    {
        $this->skin = new WFSkin();
        $this->skin->setDelegateName($skinDelegate);
        return $this->skin;
    }

    /**
     *  should this invocation of this module respond to forms?
     *
     *  @return boolean TRUE to respond to the form, FALSE otherwise.
     *  @see WFModuleInvocation::respondsToForms
     */
    function respondsToForms()
    {
        return $this->respondsToForms;
    }

    /**
     *  Set whether or not the module in this invocation should respond to forms.
     *
     *  @param boolean TRUE to respond to the form, FALSE otherwise.
     *  @see WFModuleInvocation::respondsToForms
     */
    function setRespondsToForms($responds)
    {
        $this->respondsToForms = $responds;
    }

    /**
     *  Get the parent invocation.
     *
     *  @return object WFModuleInvocation The WFModuleInvocation for the parent, or NULL if this is the root invocation.
     */
    function parentInvocation()
    {
        return $this->parentInvocation;
    }

    /**
     *  Get the root invocation for this invocation. This may be the current invocation.
     *
     *  Please note that it is possible to have multiple "root" invocations -- this routine just finds the root of the "current" tree.
     *
     *  @return object WFModuleInvocation that is the "root" of this invocation tree.
     */
    function rootInvocation()
    {
        $root = $this;
        while (true) {
            if ($root->parentInvocation() == NULL) break;
            $root = $root->parentInvocation();
        }
        return $root;
    }

    /**
     *  Does this invocation target the root invocation's module, or the current module?
     *
     *  @return boolean TRUE if it targets the root module, false if it targets the current module.
     */
    function targetRootModule()
    {
        return $this->targetRootModule;
    }

    /**
     *  Should this invocation target the root invocation's module, or the current module?
     *  
     *  For modules that target the root module, their forms will always post to the root module.
     *  This will result in keeping the current "compositing" of the root module.
     *  
     *  For modules that target the current module, their forms will always post to the current module.
     *  This will result in making the sub-module the root module if a form is submitted.
     * 
     *  @param boolean TRUE if it targets the root module, false if it targets the current module.
     */
    function setTargetRootModule($targetRoot)
    {
        $this->targetRootModule = $targetRoot;
    }

    /**
     *  Get the module that this invocation wraps.
     *
     *  @return object WFModule The WFModule wrapped by this invocation.
     */
    function module()
    {
        return $this->module;
    }

    /**
     *  Get the name of the module that this invocation is wrapping.
     *
     *  @return string Module name.
     */
    function moduleName()
    {
        return $this->moduleName;
    }

    /**
     *  Get the name of the page that this invocation will call in the module.
     *
     *  @return string Page name.
     */
    function pageName()
    {
        return $this->pageName;
    }

    /**
     *  Get the module path to the current module.
     *
     *  Will always be normalized to the form: path/to/myModule
     *
     *  @return string Module path.
     */
    function modulePath()
    {
        return $this->modulePath;
    }

    /**
     *  Get the invocationPath that was used to create this WFModuleInvocation.
     *
     *  @return string The complete invocationPath for this WFModuleInvocation.
     */
    function invocationPath()
    {
        return $this->invocationPath;
    }

    /**
     *  Is this module the root invocation?
     *
     *  @return boolean TRUE if this is the root invocation, FALSE otherwise.
     *  @todo Should this be named isRootModule? This would be more consistent with setTargetRootModule/targetRootModule.
     */
    function isRootInvocation()
    {
        return ($this->parentInvocation == NULL);
    }

    /**
     *  Get the parameters that were provided in the invocationPath.
     *
     *  @return array The parameters extracted from the invocationPath.
     */
    function parameters()
    {
        return $this->invocationParameters;
    }

    /**
     *  Get the parameters that were provided in the invocationPath, as a '/'-separated string.
     *
     *  @return string The invocation parameters in the form: /param1/param2. Each param will be urlencoded.
     */
    function parametersAsPathInfo()
    {
        $path = '';
        foreach ($this->invocationParameters as $p) {
            $path .= '/' . urlencode($p);
        }
        return $path;
    }

    /**
     *  Execute the module wrapped by this invocation.
     *
     *  This function is where the invocationPath is parsed, the WFModule instantiated, and the module executed.
     *
     *  @return string The rendered result of the module invocation.
     *  @throws Any uncaught exception.
     */
    function execute()
    {
        if ($this->module()->shouldProfile())
        {
            apd_set_pprof_trace(WFWebApplication::sharedWebApplication()->appDirPath(WFWebApplication::DIR_LOG));
        }

        // execute
        // initialize the request page
        $this->module->requestPage()->initPage($this->pageName);

        // if the responsePage wasn't inited already, then we assume we're going to just display the same page.
        if (!$this->module->responsePage())
        {
            $this->module->setupResponsePage();
        }

        // return the rendered HTML of the page. we do this in an output buffer so that if there's an error, we can display it cleanly in the skin rather than have a
        // half-finished template dumped on screen (which is apparently what smarty does when an error is thrown from within it). Have to do this here
        // in addition to WFPage::render() since module/page system can be nested.
        try {
            ob_start();
            $html = $this->module->responsePage()->render();
            ob_end_clean();
        } catch (Exception $e) {
            ob_end_clean();
            throw($e);
        }

        // skin if necessary
        if ($this->skin)
        {
            $this->skin->setBody($html);
            $html = $this->skin->render(false);
        }

        return $html;
    }

    /**
     *  Parses the invocationPath, looks for the module, instantiates the module, etc.
     *
     *  This is where {@link WFAuthorizationManager module security} is applied. If the page requies login to continue, the system will redirect to a login page,
     *  and will redirect back to the initial page upon successful login.
     *
     *  NOTE: Will convert WFNull params into NULL values.
     *
     *  @throws Various exceptions in setting up the module.
     */
    private function extractComponentsFromInvocationPath()
    {
        // walk path looking for the module -- keep "blank" entry when "//" encountered.
        $pathInfoParts = preg_split('/\//', trim($this->invocationPath, '/'), -1);

        $modulesDirPath = WFWebApplication::appDirPath(WFWebApplication::DIR_MODULES);
        foreach (WFWebApplication::sharedWebApplication()->modulePaths() as $prefix => $dir) {
            if (strpos($this->invocationPath, $prefix) === 0)
            {
                $modulesDirPath = realpath($dir . '/..');
            }
        }

        //print_r($pathInfoParts);
        //print "URI: $<BR>";
        $foundModule = false;
        $modulePath = '';
        $partsUsedBeforeModule = 0;
        foreach ($pathInfoParts as $part) {
            $modulePath .= '/' . $part;
            $possibleModuleFilePath = $modulesDirPath . $modulePath . '/' . $part . '.php';
            //print "Testing $possibleModuleFilePath to see if it's a module file.<BR>";
            if (file_exists($possibleModuleFilePath))
            {
                $this->modulePath = ltrim($modulePath, '/');
                $this->moduleName = $pathInfoParts[$partsUsedBeforeModule];
                if (isset($pathInfoParts[$partsUsedBeforeModule + 1]))
                {
                    $this->pageName = $pathInfoParts[$partsUsedBeforeModule + 1];
                }
                // parse out parameter data from URL
                if (count($pathInfoParts) > 2)
                {
                    $params = array_slice($pathInfoParts, $partsUsedBeforeModule + 2);
                    foreach ($params as $k => $v) {
                        if ($v === WFModuleInvocation::PARAMETER_NULL_VALUE)
                        {
                            $params[$k] = NULL;
                        }
                        else
                        {
                            $params[$k] = urldecode($v);
                        }
                    }
                    $this->invocationParameters = $params;
                }
                $foundModule = true;
                $this->setModulesDir($modulesDirPath);
                //print "Found module {$this->moduleName} in {$this->modulePath}.";
                //if ($this->pageName) print " Found page name: {$this->pageName}";
                //print "<BR>";
                //print "PATH_INFO: {$this->invocationParameters}<BR>";
                break;
            }
            else if (is_dir($modulesDirPath . '/' . $modulePath))
            {
                $partsUsedBeforeModule++;
            }
        }

        if (!$foundModule)
        {
            throw( new WFRequestController_NotFoundException("Module 404: invocation path '{$this->invocationPath}' could not be found.") );
        }

        if (empty($this->moduleName) or empty($this->pageName))
        {
            $needsRedirect = true;
        }
        else
        {
            $needsRedirect = false;
        }

        // i don't think this line ever can execture...
        if (empty($this->moduleName)) throw( new WFRequestController_NotFoundException("Module 404: No module name could be determined from {$this->invocationPath}.") );

        // if we get here, we're guaranteed that a modulePath is valid.
        // load module instance
        $this->module = WFModule::factory($this);

        // determine default page
        if (empty($this->pageName))
        {
            $this->pageName = $this->module->defaultPage();
        }
        if (empty($this->pageName)) throw( new Exception("No page could be determined. Make sure you are supplying an page in the invocation path or have your module supply a defaultPage.") );

        // redirect as needed - this doesn't make sense inside of WFModuleInvocation...
        // of course cannot have invocationParameters from invocationPath unless module and pageName are specified
        if ($needsRedirect)
        {
            if ($this->isRootInvocation())
            {
                header('Location: ' . WFRequestController::WFURL($this->modulePath, $this->pageName));
                exit;
            }
            else
            {
                throw( new Exception("You must specify a complete invocationPath.") );
            }
        }
    }

    /**
     *  Easily convert an invocation path into the resulting HTML.
     *
     *  Perfect for buliding emails, AJAX popups, etc.
     *
     *  NOTE: this mechanism doesn't allow you to communitcate with the module before execution.
     *
     *  If you want to pass data to a WFModuleInvocation before executing, you'll need to instantiate WFModuleInvocation
     *  yourself and call execute() manually.
     *
     *  @param string The invocation path to use.
     *  @return string The resulting output of module execution.
     *  @throws object Exception Any exception generated during execution.
     */
    public static function quickModule($invocationPath, $skinDelegate = NULL)
    {
        $modInv = new WFModuleInvocation($invocationPath, NULL, $skinDelegate);
        $result = $modInv->execute();
        return $result;
    }
}

/**
  * The WFModule represents a single chunk of web application functionality.
  * 
  * Each module can have multiple pages, and each page can have multiple actions.
  * 
  * Basially there are two types of requests; page load requests, and actions 
  * against loaded pages.
  *
  * A url like /myModule/myPage will cause the framework to simply load the 
  * requested page. Extra PATH_INFO data may be used to load particular model 
  * data into the page.
  *
  *  Examples:
  *     Open up a product detail page with /admin/productDetail/123
  *     Open up a list of all products with /admin/productList
  *     Show page 2 of the search results with /admin/productSearch/myQuery/2
  *
  * When the ResponsePage finishes initializing / loading the page, your module
  * will be called back on the method <pageName>_PageDidLoad($page), which is your 
  * opportunity to provide default information and/or perform changes to the UI 
  * before it is rendered.
  *
  * A url like /myModule/myPage?__action=myAction will restore the state of the 
  * myPage UI to the submitted state, then call the myAction handler for that page. 
  * Of course myAction must be part of the myPage. Also, the myAction handler may 
  * decide to CHANGE the current page.
  *
  *  Examples:
  *     Edit a product with /admin/productDetail/123?__action=edit or /admin/productDetail?__action=edit&id=123
  *     Delete a product with /admin/productList?__action=delete&id=123
  *
  * Action methods have the prototype: <pageName>_<actionName>_Action($requestPage) 
  * where $requestPage is restored UI state of the calling page (pageName).
  *
  * Each module has a request and a repsonse page. The framework will restore 
  * the request's UI state before calling your module's action handler so that
  * you can easily access widget values via the framework instead of via the $_REQUEST vars.
  *
  * Once you decide which WFPage to display as the response, the framework loads 
  * all of the widgets in the page and allows you to manipulate them from your 
  * action handler before rendering the response.
  *
  * WFModule/Page CALLBACK METHODS:
  * Each method listed is a function in the module for the given page, with a name like myPageName_callbackName().
  *
  * array ParameterList() - Called by the page to get a list of URL parameters that the page accepts. Return a list of parameters in order of how they map to the URL.
  * void PageDidLoad($page, $params) - Called when the page has finished loading from the YAML file.
  * void DidRestoreState($page) - called after the page has finished restoring state from all widgets, including ones created dynamically during PageDidLoad.
  * void myPageName_myActionName_Action($page) - called on the page after pushBindings, BUT ONLY IF THERE WERE NO ERRORS.
  * void SetupSkin($skin) - Called just before rendering the template so you can set up title and other head tags.
  * 
  */
abstract class WFModule extends WFObject
{
    /**
     * @var object The WFPage for the incoming request.
     */
    protected $requestPage;
    /**
     * @var object The WFPage for the outgoing response.
     */
    protected $responsePage;
    /**
     * @var array An associative array of all shared instances for this module.
     */
    protected $__sharedInstances;
    /**
      * @var object The WFModuleInvocation that launched this module.
      */
    protected $invocation;

    /**
      * Constructor.
      *
      * @param string The relative path to this module.
      */
    function __construct($invocation)
    {
        parent::__construct();

        if (!($invocation instanceof WFModuleInvocation)) throw( new Exception("Modules must be instantiated with a WFModuleInvocation.") );

        $this->invocation = $invocation;
        $this->requestPage = NULL;
        $this->responsePage = NULL;
    }

    function init()
    {
        // check security
        $this->runSecurityCheck();

        // load shared instances
        $this->prepareSharedInstances();

        // set up pages
        $this->requestPage = new WFPage($this);
        $this->responsePage = NULL;
    }

    /**
      * Callback to allow the module to determine security clearance of the user.
      *
      * Subclasses can override this method to alter security policy for this module. Default behavior is to allow all.
      *
      * @param object WFAuthorizationInfo The authInfo for the current user.
      * @return integer One of WFAuthorizationManager::ALLOW or WFAuthorizationManager::DENY.
      */
    function checkSecurity(WFAuthorizationInfo $authInfo)
    {
        return WFAuthorizationManager::ALLOW;
    }

    /**
     *  Execute the checking of security clearance for the user and the module.
     *
     *  NOTE: This function may issue an HTTP 302 and redirect the user to the login page, then halt script execution.
     * 
     *  @throws Exception if anything unexpected happens.
     */
    private function runSecurityCheck()
    {
        try {
            // check security, but only for the root invocation
            if ($this->invocation->isRootInvocation())
            {
                $authInfo = WFAuthorizationManager::sharedAuthorizationManager()->authorizationInfo();
                $access = $this->checkSecurity($authInfo);
                if (!in_array($access, array(WFAuthorizationManager::ALLOW, WFAuthorizationManager::DENY))) throw( new Exception("Unexpected return code from checkSecurity.") );
                // if access is denied, see if there is a logged in user. If so, then DENY. If not, then allow login.
                if ($access == WFAuthorizationManager::DENY)
                {
                    if ($authInfo->isLoggedIn())
                    {
                        // if no one is logged in, allow login, otherwise deny.
                        throw( new WFAuthorizationException("Access denied.", WFAuthorizationException::DENY) );
                    }
                    else
                    {
                        // if no one is logged in, allow login, otherwise deny.
                        throw( new WFAuthorizationException("Try logging in.", WFAuthorizationException::TRY_LOGIN) );
                    }
                }
            }
        } catch (WFAuthorizationException $e) {
            switch ($e->getCode()) {
                case WFAuthorizationException::TRY_LOGIN:
                    // NOTE: we pass the redir-url base64 encoded b/c otherwise Apache picks out the slashes!!!
                    WFAuthorizationManager::sharedAuthorizationManager()->doLoginRedirect(WWW_ROOT . '/' . $this->invocation->invocationPath());
                    break;
                case WFAuthorizationException::DENY:
                    header("Location: " . WFRequestController::WFURL('login', 'notAuthorized'));
                    exit;
                    break;
            }
        }
    }

    /**
      * Generate a "full" URL to the given module / page.
      *
      * It is recommended to use this function to generate all URL's to pages in the application.
      * Of course you may append some PATH_INFO or params afterwards.
      * Also it is recommended that when referencing another action in the SAME module you pass NULL for the module,
      * as this will ensure that your links don't break if you decide to rename your module.
      *
      * @param string The module name (or NULL to use CURRENT module).
      * @param string The page name (or NULL to use CURRENT page).
      * @return string a RELATIVE URL to the requested module/page.
      */
    function WFURL($module = NULL, $page = NULL)
    {
        $module = ltrim($module, '/');  // just in case a '/path' path is passed, we normalize it for our needs.
        if (empty($module))
        {
            $module = $this->invocation->modulePath();
        }
        if (empty($page))
        {
            $page = $this->invocation->pageName();
        }
        $url = WWW_ROOT . '/' . $module . '/' . $page;
        return $url;
    }


    /**
      * Get the module invocation.
      * 
      * @return object The WFModuleInvocation object that owns this module instance.
      */
    function invocation()
    {
        return $this->invocation;
    }

    /**
      * Get the module's name
      *
      * @return string The module's name.
      */
    function moduleName()
    {
        return get_class($this);
    }

    /**
      * Get the path to the given page.
      *
      * @param string The page name.
      * @return string The path to the page, without extension. Add '.instances', '.config', or '.tpl'.
      */
    function pathToPage($pageName)
    {
        return $this->pathToModule() . '/' . $pageName;
    }

    /**
     *  Get the path to the module.
     *
     *  @return string The absolute file system path to the module's directory.
     */
    function pathToModule()
    {
        return $this->invocation->modulesDir() . '/' . $this->invocation->modulePath();
    }

    /**
      * Get a module instance for the specified module path.
      * 
      * @param string The path to the module.
      * @return object A WFModule subclass instance.
      * @throws Exception if the module subclass or file does not exist.
      * @throws WFAuthorizationException if there is an access control violation for the module.
      */
    public static function factory($invocation)
    {
        $moduleName = $invocation->moduleName();
        $modulesDirPath = $invocation->modulesDir();
        $moduleFilePath = $modulesDirPath . '/' . $invocation->modulePath() . '/' . $moduleName . '.php';

        // load module subclass and instantiate
        $module = NULL;
        // since PHP has no namespaces, this can get messy as many times a module will have the same name as another class.
        // so, we *prefer* all modules to be named "module_<moduleName>" to avoid these collisions, but will roll-over to <moduleName> for BC.
        require_once($moduleFilePath);
        if (class_exists("module_{$moduleName}"))
        {
            $moduleClassName = "module_{$moduleName}";
            $module = new $moduleClassName($invocation);
        }
        else if (class_exists($moduleName))
        {
            $module = new $moduleName($invocation);
        }
        else throw( new Exception("WFModule subclass (module_{$moduleName} or {$moduleName}) could not be found.") );

        $module->init();
        return $module;
    }

    /**
     * Prepare any declared shared instances for the module.
     *
     * Shared Instances are objects that not WFView subclasses. Only WFView subclasses may be instantiated in the <pageName>.instances files.
     * The Shared Instances mechanism is used to instantiate any other objects that you want to use for your pages. Usually, these are things
     * like ObjectControllers or Formatters, which are typically "shared" across multiple pages. The Shared Instances mechanism makes it
     * easy to instantiate and configure the properties of objects without coding, and have these objects accessible for bindings or properties.
     * Of course, you can instantiate objects yourself and use them programmatically. This is just a best-practice for a common situation.
     *
     * The shared instances mechanism simply looks for a shared.instances and a shared.config file in your module's directory. The shared.instances
     * file should simply have a var $__instances that is an associative array of 'unique id' => 'className'. For each declared instance, the
     * module's instance var $this->$uniqueID will be set to a new instance of "className".
     *
     * <code>
     *   $__instances = array(
     *       'instanceID' => 'WFObjectController',
     *       'instanceID2' => 'WFUnixDateFormatter'
     *   );
     * </code>
     *
     * To bind to a shared instance (or for that matter any object that's an instance var of the module), set the instanceID to "#module#,
     * leave the controllerKey blank, and set the modelKeyPath to "<instanceVarName>.rest.of.key.path".
     *
     * To use a shared instance as a property, .................... NOT YET IMPLEMENTED.
     * 
     *
     * @todo Allow properties of page.config files to use shared instances.
     */
    function prepareSharedInstances()
    {
        $app = WFWebApplication::sharedWebApplication();
        $modDir = $this->invocation()->modulesDir();
        $moduleInfo = new ReflectionObject($this);

        $yamlFile = $modDir . '/' . $this->invocation->modulePath() . '/shared.yaml';
        if (file_exists($yamlFile))
        {
            $yamlConfig = WFYaml::load($yamlFile);
            foreach ($yamlConfig as $id => $instInfo) {
                try {
                    $moduleInfo->getProperty($id);
                } catch (Exception $e) {
                    WFLog::log("shared.yaml:: Module '" . get_class($this) . "' does not have property '$id' declared.", WFLog::WARN_LOG);
                }

                // instantiate, keep reference in shared instances
                WFLog::log("instantiating shared instance id '$id'", WFLog::TRACE_LOG);
                $this->__sharedInstances[$id] = $this->$id = new $instInfo['class'];

                WFLog::log("loading config for shared instance id '$id'", WFLog::TRACE_LOG);
                // get the instance to apply config to
                if (!isset($this->$id)) throw( new Exception("Couldn't find shared instance with ID '$id' to configure.") );
                $configObject = $this->$id;

                // atrributes
                if (isset($instInfo['properties']))
                {
                    foreach ($instInfo['properties'] as $keyPath => $value) {
                        switch (gettype($value)) {
                            case "boolean":
                            case "integer":
                            case "double":
                            case "string":
                            case "NULL":
                                // these are all OK, fall through
                                break;
                            default:
                                throw( new Exception("Config value for shared instance id::property '$id::$keyPath' is not a vaild type (" . gettype($value) . "). Only boolean, integer, double, string, or NULL allowed.") );
                                break;
                        }
                        WFLog::log("SharedConfig:: Setting '$id' property, $keyPath => $value", WFLog::TRACE_LOG);
                        $configObject->setValueForKeyPath($value, $keyPath);
                    }
                }
            }
        }
        else
        {
            $instancesFile = $modDir . '/' . $this->invocation->modulePath() . '/shared.instances';
            $configFile = $modDir . '/' . $this->invocation->modulePath() . '/shared.config';

            if (file_exists($instancesFile))
            {
                include($instancesFile);
                foreach ($__instances as $id => $class) {
                    // enforce that the instance variable exists
                    try {
                        $moduleInfo->getProperty($id);
                    } catch (Exception $e) {
                        WFLog::log("shared.instances:: Module '" . get_class($this) . "' does not have property '$id' declared.", WFLog::WARN_LOG);
                    }

                    // instantiate, keep reference in shared instances
                    $this->__sharedInstances[$id] = $this->$id = new $class;
                }

                // configure the new instances
                $this->loadConfigPHP($configFile);
            }
        }

        // call the sharedInstancesDidLoad() callback
        $this->sharedInstancesDidLoad();
    }

    /**
     * Optional method for WFModule subclasses if they want to know when the shared instances have finished loading.
     *
     * Will be called after the Shared Instances have been instantiated and configured, and before any pages are loaded.
     */
    function sharedInstancesDidLoad() {}

    /**
     * Load the shared.config file for the module and process it.
     *
     * The shared.config file is an OPTIONAL component.
     * If your module has no instances, or the instances don't need configuration, you don't need a shared.config file.
     *
     * The shared.config file can only configure properties of objects at this time.
     * Only primitive value types may be used. String, boolean, integer, double, NULL. NO arrays or objects allowed.
     *
     * <code>
     *   $__config = array(
     *       'instanceID' => array(
     *           'properties' => array(
     *              'propName' => 'Property Value',
     *              'propName2' => 123
     *           )
     *       ),
     *       'instanceID2' => array(
     *           'properties' => array(
     *              'propName' => 'Property Value',
     *              'propName2' => true
     *           )
     *       )
     *   );
     * </code>
     *
     * @param string The absolute path to the config file.
     * @throws Various errors if configs are encountered for for non-existant instances, etc. A properly config'd page should never throw.
     * @see loadConfigYAML
     */
    protected function loadConfigPHP($configFile)
    {
        // be graceful; if there is no config file, no biggie, just don't load config!
        if (!file_exists($configFile)) return;

        include($configFile);
        foreach ($__config as $id => $config) {
            WFLog::log("loading config for id '$id'", WFLog::TRACE_LOG);
            // get the instance to apply config to
            if (!isset($this->$id)) throw( new Exception("Couldn't find shared instance with ID '$id' to configure.") );
            $configObject = $this->$id;

            // atrributes
            if (isset($config['properties']))
            {
                foreach ($config['properties'] as $keyPath => $value) {
                    switch (gettype($value)) {
                        case "boolean":
                        case "integer":
                        case "double":
                        case "string":
                        case "NULL":
                            // these are all OK, fall through
                            break;
                        default:
                            throw( new Exception("Config value for shared instance id::property '$id::$keyPath' is not a vaild type (" . gettype($value) . "). Only boolean, integer, double, string, or NULL allowed.") );
                            break;
                    }
                    WFLog::log("SharedConfig:: Setting '$id' property, $keyPath => $value", WFLog::TRACE_LOG);
                    $configObject->setValueForKeyPath($value, $keyPath);
                }
            }
        }
    }

    /**
     * Call this method if you want the ResponsePage to be the same as the RequestPage.
     *
     * Another method for altering the display of the response is to change the templateFile of the existing page with {@link WFPage::setTemplateFile()}.
     *
     * This is a convenience method for actions that don't need to switch the page.
     *
     * @param string The name of the page in this module to use as the response page; NULL to use the request page as the response page.
     * @see WFPage::setTemplateFile()
     */
    function setupResponsePage($pageName = NULL)
    {
        if (is_null($pageName) or ($this->requestPage->pageName() == $pageName))
        {
            //print "using request page for response<br>";
            $this->responsePage = $this->requestPage;
        }
        else
        {
            //print "using $pageName for response page<br>";
            $this->responsePage = new WFPage($this);
            $this->responsePage->initPage($pageName);
        }
    }

    /**
     *  Get the responsePage for the module.
     *
     *  @return object The WFPage representing the responsePage for this module.
     */
    function responsePage()
    {
        return $this->responsePage;
    }

    /**
     *  Get the requestPage for the module.
     *
     *  @return object The WFPage representing the requestPage for this module.
     */
    function requestPage()
    {
        return $this->requestPage;
    }

    /**
     * Get the default page to use for the module if no page is specified.
     * @return string The name of the default page.
     */
    abstract function defaultPage();

    /**
     *  Should APD profiling be enabled for this request? To enable profiling of your module, just add shouldProfile() to your WFModule and return TRUE.
     *
     *  If so, will turn on profiling just before executing your module, and dump the profile data into the log dir.
     *
     *  @return boolean TRUE to enable profiling.
     */
    function shouldProfile()
    {
        return false;
    }

}

?>
