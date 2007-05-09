<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/** 
 * The WFPage object.
 *
 * Each request creates a module/page to handle the request. This struct uses exactly 2 pages, a requestPage and a responsePage. The requestPage respesents the UI state of the submitted form, if any, and the responsePage represents the UI state of the page that will be displayed in response to the request. The client responds to the request/action by reading from the requestPage and taking appropriate actions (ie saving data, etc). The client then selects a responsePage and sets up the UI elements as desired. The rendered responsePage is what the user will see.
 *
 * Clients get access to the instances via {@link outlet} and get/set values as appropriate.
 *
 * @copyright Copyright (c) 2002 Alan Pinstein. All Rights Reserved.
 * @version $Id: smarty_showcase.php,v 1.3 2005/02/01 01:24:37 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 * @package UI
 * @subpackage PageController
 * @todo Some more refactoring... it's odd that the class has to figure out if it's the requestPage or responsePage. I think instead they should be 2 classes.
 * The base class, WFPage, should be the response page, and the WFRequestPage subclass should add methods / functionality for restoring state and triggering
 * actions, as I think that's the only two things it does more than the responsePage. ** What about errors?**
 */

/**
 * The WFPage encapsulates the UI and Controller Layer state of a page.
 *
 * The Page infrastructure initializes pages by instantiating all WFView instances (including widgets) in a page (from the .instances file),
 * loads all configs for those instances (from the .config file), and also can render the page to HTML using a WFPageRendering-compatible 
 * template instance.
 *
 * The page manages the WFView instances with a Mediator pattern.
 *
 * The page is responsible for helping the widgets restore their state from a request.
 * 
 * SEE COMPOSITE PATTERN IN GoF for ideas about the widget hierarchy.
 *
 * WFPage automatically adds a few useful variables to your template:
 * - __page The current {@link WFPage} being rendered.
 * - __module The {@link WFModule} that the page belongs to.
 * - __skin The {@link WFSkin} being used to wrap the page. MAY BE NULL! When a page is not the "root" module, it may not be wrapped in a skin, so be careful when using this.
 */
class WFPage extends WFObject
{
    protected $module;      // the module that contains this WFPage instance
    protected $pageName;    // the name of the "page" (ie UI bundle - needs to be placed in the forms so the requestPage can load the UI instances)
    protected $template;    // and object conforming to interface WFPageRendering
    protected $instances;   // assoc array of all instances, 'id' => instance object
    protected $parameters;  // finalized calculated parameters in effect for the page
    protected $errors;      // all validation errors for the current page; errors are managed in real-time. Any errors added to widgets of this page 
                            // are automatically added to our page errors list.

    function __construct(WFModule $module)
    {
        parent::__construct();

        $this->module = $module;
        $this->pageName = NULL;
        $this->template = NULL;
        $this->instances = array();
        $this->errors = array();
        $this->parameters = array();
        WFLog::log("instantiating a page", WFLog::TRACE_LOG, PEAR_LOG_DEBUG);
    }

    /**
     * Get the module that the page is a part of.
     * @return object A WFModule object.
     */
    function module()
    {
        return $this->module;
    }

    /**
     *  Get the parameters for the page. These are the parsed parameters of the URL, coalesced with form elements of the same name.
     *
     *  NOTE: This data isn't available until after initPage() has executed.
     *
     *  @return assoc_array Array of parameters in format 'parameterID' => 'parameterValue'
     *  @todo refactor initPage to move the code from there to here so that it's available earlier in the life cycle.
     */
    function parameters()
    {
        return $this->parameters;
    }

    /**
     * Get the name of the "page" for the current WFPage instance. The "page" is our equivalent of a nib file, essentially.
     * @return string The page name for this instance.
     */
    function pageName()
    {
        $this->assertPageInited();

        return $this->pageName;
    }

    /**
     * Determine the name of the submitted form, if there is a submitted form for the current module.
     *
     * This function takes into account the module invocation's respondsToForms setting...
     * 
     * @todo Should this be in WFRequestController?
     * @return string The name of the submitted form, if one was submitted. NULL if no form was submitted.
     */
    function submittedFormName()
    {
        if (!$this->module->invocation()->respondsToForms()) return NULL;

        $formName = NULL;
        if (isset($_REQUEST['__modulePath'])
            and $_REQUEST['__modulePath'] == ($this->module->invocation()->modulePath() . '/' . $this->pageName())
            and $this->module->invocation()->pageName() == $this->pageName())
        {
            $formName = $_REQUEST['__formName'];
        }
        return $formName;
    }

    /**
     *  Does this page have a form that was submitted for it?
     *
     *  @return boolean TRUE if a form was submitted; FALSE otherwise.
     */
    function hasSubmittedForm()
    {
        return $this->submittedFormName() != NULL;
    }

    /**
     *  Tell the page to use an alternate .tpl file (besides the default, '<pagename>.tpl') for 
     *  rendering the page.
     *
     *  When responding to a request, you will form a response to send back to the client. Depending on the 
     *  nature of the response, there are two options in PHOCOA for building the response page.
     *
     *  In many cases, your application will need to present the same data in different ways. Once example
     *  of this is on "thank you" pages for contact form submissions. Since you will display the same data
     *  in the page, but display it differently, it is a good application of setTemplateFile().
     *
     *  The alternative is to use $module->setupResponsePage() to have PHOCOA respond to the request with
     *  a completely different page. However, this is most useful only if you are going to be displaying
     *  different data from the request. For instance, the "continue shopping" button of a shopping cart may 
     *  go back to a product list page.
     *
     *  @param string The template file name to use.
     *  @see WFModule::setupResponsePage()
     */
    function setTemplateFile($tplFileName)
    {
        $template = $this->prepareTemplate();
        $tplPath = $this->templateFilePath($tplFileName);
        $template->setTemplate($tplPath);
    }

    /**
     *  Get the template used for this page.
     *
     *  @return object WFPageRendering An object that implemented WFPageRendering
     */
    function template()
    {
        $this->prepareTemplate();
        return $this->template;
    }

    private function templateFilePath($templateFile)
    {
        return $this->module->pathToModule() . '/' . $templateFile;
    }

    /**
     * Make sure a template object (a template engine conforming to WFPageRendering interface) is initialized.
     * @return object The template object for this page.
     * @throws Exception if there is no template file for the current page.
     */
    function prepareTemplate()
    {
        $this->assertPageInited();

        if (is_null($this->template))
        {
            WFLog::log("preparing the template", WFLog::TRACE_LOG);

            // instantiate a template object
            $this->template = new WFSmarty();     // eventually could use a factory here to use any template mechanism.
            // initialize page with default template name; can always update it later. This way we don't store the template file name in 2 places (here and WFPageRendering instance)
            $this->template->setTemplate($this->templateFilePath($this->pageName . '.tpl'));
        }

        return $this->template;
    }

    /**
     * Get a list of all instantiated widgets belonging to this page.
     * @return array An array of widgets.
     */
    function widgets()
    {
        $this->assertPageInited();

        // construct a list of all widgets that are in our instances list.
        $widgets = array();
        foreach ($this->instances as $id => $obj) {
            if (!($obj instanceof WFWidget)) continue;
            $widgets[$id] = $obj;
        }

        return $widgets;
    }

    /**
     * Has the page been loaded?
     * This becomes true once initPage() is called.
     * @return boolean TRUE if loaded, FALSE otherwise.
     */
    function isLoaded()
    {
        if (is_null($this->pageName))
        {
            return false;
        }
        return true;
    }

    /**
     * Get a reference to an instance of the page. 
     *
     * Using $page->outlet($id) is equivalent to accessing an object through a Cocoa outlet.
     *
     * @param string The id of the instance to get.
     * @return object An outlet (reference) to the specified instance id.
     * @throws An exception if no object exists with that id or the page is not inited.
     */
    function outlet($id)
    {
        $this->assertPageInited();
        if (!isset($this->instances[$id])) throw( new Exception("No object exists with id '{$id}'.") );
        return $this->instances[$id];
    }

    /**
     *  Determine if there is a page instance for the given id.
     *
     *  @param string The id of the instance to get.
     *  @return boolean TRUE if there is an instance of that id, false otherwise.
     */
    function hasOutlet($id)
    {
        return ( isset($this->instances[$id]) );
    }

    /**
     * Add an instance to our page.
     *
     * Use this function to add dynamically created instances to the page.
     * NOTE: for adding WFView instances, you don't need to do this as the WFView class automatically registers all widgets.
     *
     * @param string The ID of the instance (must be unique).
     * @param object The object.
     * @throws If there is a duplicate, or $object is not an object.
     */
    function addInstance($id, $object)
    {
        // check params
        if (!is_object($object)) throw( new Exception("All instances must be objects. Instance ID '$id' is not an object.") );

        // ensure uniqueness
        if (isset($this->instances[$id])) throw( new Exception("Instance ID '$id' already declared. Duplicates not allowed.") );

        // add to our internal list
        $this->instances[$id] = $object;
    }

    /**
     *  Remove an instance from the page.
     *
     *  Useful for dynamically created instances, if one needs to re-create them.
     *
     *  @param string $id The id of the page instance to remove.
     *  @throws object Exception if the instance doesn't exist.
     */
    function removeInstance($id)
    {
        if (!isset($this->instances[$id])) throw( new Exception("Instance ID '$id' cannot be removed because it doesn't exist.") );
        unset($this->instances[$id]);
    }

    /**
     * Handle the instantiation of the passed object from the instances file.
     * 
     * The .instances mechanism simply looks for a file named <pageName>.instances and a <pageName>.config file in your module's templates directory.
     * The .instances contains a list of all WFView instances for the page, and the hierarchy information.
     * <code>
     *   $__instances = array(
     *       'instanceID' => array(
     *              'class' => 'WFViewSubclass',
     *              'children' => array(
     *                  'subInstanceID' => array('class' => 'WFTextField')
     *              ),
     *       'instanceID2' => array(
     *              'class' => 'WFViewSubclass'
     *              )
     *   );
     * </code>
     *
     * For each instance id, an instance of the listed class will be added to the view hierarchy. If children are listed, they will be added as well
     * at the appropriate place in the hierarchy.
     *
     * @param string The ID of the instance.
     * @param assoc_array The manifest info for the instance. 'class' is the class of the instance, and 'children' contains child items (widgets only!)
     * @return object The instantiated object.
     */
    protected function initInstancePHP($id, $instanceManifest) {
        // determine the class
        if (!isset($instanceManifest['class'])) throw( new Exception("Instance ID '$id' declared without a class. FATAL!") );
        $class = $instanceManifest['class'];

        WFLog::log("Instantiating object id '$id'", WFLog::TRACE_LOG);
        // we want to see if the class is a WFView subclass before instantiating (so that we can be sure our 'new' call below calls an existing prototype).
        // bug in PHP's is_subclass_of() causes segfault sometimes if the class needs to be autoloaded, so in 5.1.0 PHP stops calling autoload.
        // Thus, the fix is to load the class ourselves if needed before checking the inheritance.
        if (!class_exists($class))
        {
            __autoload($class);
        }
        if (!is_subclass_of($class, 'WFView')) throw( new Exception("Only WFView objects can be instantiated in the .instances file. Object id '$id' is of class '$class'.") );

        // NOTE!! We don't need to call addInstance() for widgets, as the WFWidget constructor does this automatically.
        // instantiate widget
        $object = new $class($id, $this);

        // determine widgets contained by this widget
        if (isset($instanceManifest['children']))
        {
            if (!is_array($instanceManifest['children'])) throw( new Exception("Widget ID '$id' children list is not an array.") );

            // recurse into children
            foreach ($instanceManifest['children'] as $id => $childInstanceManifest) {
                $childInstance = $this->initInstancePHP($id, $childInstanceManifest);
                $object->addChild($childInstance);
            }
        }

        return $object;
    }

    /**
     * Handle the instantiation of the passed object from the .yaml file.
     * 
     * The .yaml mechanism simply looks for a file named <pageName>.yaml in your module's templates directory.
     * The .your contains a list of all WFView instances for the page, in a hierarchical tree, and the configuration / binding information for each instance.
     *
     * <code>
     * form:
     *   class: WFForm
     *   properties:
     *     method: post
     *   children:
     *     aField:
     *       class: WFTextField
     *       properties:
     *         maxLength: 50
     *       bindings:
     *         value:
     *           instanceID: customer
     *           controllerKey: selection
     *           modelKeyPath: creationDTS
     *           options:
     * </code>
     *
     * For each instance id, an instance of the listed class will be added to the view hierarchy. If children are listed, they will be added as well
     * at the appropriate place in the hierarchy.
     *
     * @param string The ID of the instance.
     * @param assoc_array The manifest info for the instance.
     * @return object The instantiated object.
     */
    protected function initInstanceYAML($id, $instanceManifest) {
        // determine the class
        if (!isset($instanceManifest['class'])) throw( new Exception("Instance ID '$id' declared without a class. FATAL!") );
        $class = $instanceManifest['class'];

        WFLog::log("Instantiating object id '$id'", WFLog::TRACE_LOG);
        // we want to see if the class is a WFView subclass before instantiating (so that we can be sure our 'new' call below calls an existing prototype).
        // bug in PHP's is_subclass_of() causes segfault sometimes if the class needs to be autoloaded, so in 5.1.0 PHP stops calling autoload.
        // Thus, the fix is to load the class ourselves if needed before checking the inheritance.
        if (!class_exists($class))
        {
            __autoload($class);
        }
        if (!is_subclass_of($class, 'WFView')) throw( new Exception("Only WFView objects can be instantiated in the .instances file. Object id '$id' is of class '$class'.") );

        // NOTE!! We don't need to call addInstance() for widgets, as the WFWidget constructor does this automatically.
        // instantiate widget
        $object = new $class($id, $this);

        // set up object properties
        if (isset($instanceManifest['properties']))
        {
            // configure widget
            WFLog::log("loading properties for id '$id'", WFLog::TRACE_LOG);

            // atrributes
            foreach ($instanceManifest['properties'] as $keyPath => $value) {
                switch (gettype($value)) {
                    case "boolean":
                    case "integer":
                    case "double":
                    case "string":
                    case "NULL":
                        // these are all OK, fall through
                        break;
                    default:
                        throw( new Exception("Config value for WFView instance id::property '$id::$keyPath' is not a vaild type (" . gettype($value) . "). Only boolean, integer, double, string, or NULL allowed.") );
                        break;
                }
                if (is_string($value) and strncmp($value, "#module#", 8) == 0)
                {
                    $module_prop_name = substr($value, 8);
                    WFLog::log("Setting '$id' property, $keyPath => shared object: $module_prop_name", WFLog::TRACE_LOG);
                    $object->setValueForKeyPath($this->module->valueForKey($module_prop_name), $keyPath);
                }
                else
                {
                    WFLog::log("Setting '$id' property, $keyPath => $value", WFLog::TRACE_LOG);
                    $object->setValueForKeyPath($value, $keyPath);
                }
            }
        }
        // set up bindings
        if (isset($instanceManifest['bindings']))
        {
            foreach ($instanceManifest['bindings'] as $bindProperty => $bindingInfo) {
                WFLog::log("Binding '$id' property '$bindProperty' to {$bindingInfo['instanceID']} => {$bindingInfo['modelKeyPath']}", WFLog::TRACE_LOG);

                // determine object to bind to:
                if (!is_string($bindingInfo['instanceID'])) throw( new Exception("'$bindProperty' binding parameter instanceID is not a string.") );
                if (!isset($bindingInfo['instanceID'])) throw( new Exception("No instance id specified for binding object id '{$id}', property '{$bindProperty}'.") );
                // let objects be bound to the module, like "File's Owner" kinda thing...
                if ($bindingInfo['instanceID'] == '#module#')
                {
                    $bindToObject = $this->module;
                }
                else
                {
                    $bindToObject = $this->module->valueForKey($bindingInfo['instanceID']);
                }
                // at this point we should have an object...
                if (!is_object($bindToObject)) throw( new Exception("Module instance var '{$bindingInfo['instanceID']}' does not exist for binding object id '{$id}', property '{$bindProperty}'.") );

                // now calculate full modelKeyPath from controllerKey and modelKeyPath (simple concatenation).
                $fullKeyPath = '';
                if (isset($bindingInfo['controllerKey']))
                {
                    if (!is_string($bindingInfo['controllerKey'])) throw( new Exception("'$bindProperty' binding parameter controllerKey is not a string.") );
                    $fullKeyPath .= $bindingInfo['controllerKey'];
                }
                if (isset($bindingInfo['modelKeyPath']))
                {
                    if (!is_string($bindingInfo['modelKeyPath'])) throw( new Exception("'$bindProperty' binding parameter modelKeyPath is not a string.") );
                    if (!empty($fullKeyPath)) $fullKeyPath .= '.';
                    $fullKeyPath .= $bindingInfo['modelKeyPath'];
                }
                if (empty($fullKeyPath)) throw( new Exception("No keyPath specified for binding object id '{$id}', property '{$bindProperty}'.") );

                // process options
                $options = NULL;
                if (isset($bindingInfo['options']))
                {
                    // check type of all options
                    foreach ($bindingInfo['options'] as $key => $value) {
                        switch (gettype($value)) {
                            case "boolean":
                            case "integer":
                            case "double":
                            case "string":
                            case "NULL":
                                // these are all OK, fall through
                                break;
                            default:
                                throw( new Exception("Binding option '$key' for WFView instance id::property '$id::$bindProperty' is not a vaild type (" . gettype($value) . "). Only boolean, integer, double, string, or NULL allowed.") );
                                break;
                        }
                    }
                    $options = $bindingInfo['options'];
                }

                try {
                    $object->bind($bindProperty, $bindToObject, $fullKeyPath, $options);
                } catch (Exception $e) {
                    print_r($bindingInfo);
                    throw($e);
                }
            }
        }

        // set up children
        if (isset($instanceManifest['children']))
        {
            if (!is_array($instanceManifest['children'])) throw( new Exception("Widget ID '$id' children list is not an array.") );

            // recurse into children
            foreach ($instanceManifest['children'] as $id => $childInstanceManifest) {
                $childInstance = $this->initInstanceYAML($id, $childInstanceManifest);
                $object->addChild($childInstance);
            }
        }

        return $object;
    }

    /**
     * Load the .config file for the page and process it.
     *
     * The .config file is an OPTIONAL component. If your page has no instances, or the instances don't need configuration, you don't need a .config file.
     * The .config file is used to set up 'properties' of the WFView instances AND to configure the 'bindings'.
     * 
     * Only primitive value types may be used. String, boolean, integer, double, NULL. NO arrays or objects allowed.
     *
     * <code>
     *   $__config = array(
     *       'instanceID' => array(
     *           'properties' => array(
     *              'propName' => 'Property Value',
     *              'propName2' => 123
     *              'propName3' => '#module#moduleInstanceVarName'      // use the '#module#' syntax to assign data from the module (or shared instances)
     *           ),
     *           'bindings' => array(
     *              'value' => array(
     *                  'instanceID' => 'moduleInstanceVarName',        //  put the instance name of a module instance var, or "#module#" to bind to the actual module (like File's Owner)
     *                  'controllerKey' => 'Name of the key of the controller, if the instance is a controller',
     *                  'modelKeyPath' => 'Key-Value Coding Compliant keyPath to use with the bound object'
     *                  'options' => array( // Binding options go here.
     *                      'InsertsNullPlaceholder' => true,
     *                      'NullPlaceholder' => 'Select something!'
     *                  )
     *              )
     *           )
     *       )
     *   );
     * </code>
     *
     * NOTE: To see what bindings and options are available for a widget, cd to /framework and run "php showBindings.php WFWidgetName".
     *
     * @param string The absolute path to the config file.
     * @throws Various errors if configs are encountered for for non-existant instances, etc. A properly config'd page should never throw.
     */
    protected function loadConfig($configFile)
    {
        // be graceful; if there is no config file, no biggie, just don't load config!
        if (!file_exists($configFile)) return;

        include($configFile);
        foreach ($__config as $id => $config) {
            WFLog::log("loading config for id '$id'", WFLog::TRACE_LOG);
            // get the instance to apply config to
            $configObject = NULL;
            try {
                $configObject = $this->outlet($id);
            } catch (Exception $e) {
                throw( new Exception("Attempt to load config for instance ID '$id' failed because it does not exist.") );
            }

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
                            throw( new Exception("Config value for WFView instance id::property '$id::$keyPath' is not a vaild type (" . gettype($value) . "). Only boolean, integer, double, string, or NULL allowed.") );
                            break;
                    }
                    if (is_string($value) and strncmp($value, "#module#", 8) == 0)
                    {
                        $module_prop_name = substr($value, 8);
                        WFLog::log("Setting '$id' property, $keyPath => shared object: $module_prop_name", WFLog::TRACE_LOG);
                        $configObject->setValueForKeyPath($this->module->valueForKey($module_prop_name), $keyPath);
                    }
                    else
                    {
                        WFLog::log("Setting '$id' property, $keyPath => $value", WFLog::TRACE_LOG);
                        $configObject->setValueForKeyPath($value, $keyPath);
                    }
                }
            }
            // bindings
            if (isset($config['bindings']))
            {
                foreach ($config['bindings'] as $bindProperty => $bindingInfo) {
                    WFLog::log("Binding '$id' property '$bindProperty' to {$bindingInfo['instanceID']} => {$bindingInfo['modelKeyPath']}", WFLog::TRACE_LOG);

                    // determine object to bind to:
                    if (!is_string($bindingInfo['instanceID'])) throw( new Exception("'$bindProperty' binding parameter instanceID is not a string.") );
                    if (!isset($bindingInfo['instanceID'])) throw( new Exception("No instance id specified for binding object id '{$id}', property '{$bindProperty}'.") );
                    // let objects be bound to the module, like "File's Owner" kinda thing...
                    if ($bindingInfo['instanceID'] == '#module#')
                    {
                        $bindToObject = $this->module;
                    }
                    else
                    {
                        $bindToObject = $this->module->valueForKey($bindingInfo['instanceID']);
                    }
                    // at this point we should have an object...
                    if (!is_object($bindToObject)) throw( new Exception("Module instance var '{$bindingInfo['instanceID']}' does not exist for binding object id '{$id}', property '{$bindProperty}'.") );

                    // now calculate full modelKeyPath from controllerKey and modelKeyPath (simple concatenation).
                    $fullKeyPath = '';
                    if (isset($bindingInfo['controllerKey']))
                    {
                        if (!is_string($bindingInfo['controllerKey'])) throw( new Exception("'$bindProperty' binding parameter controllerKey is not a string.") );
                        $fullKeyPath .= $bindingInfo['controllerKey'];
                    }
                    if (isset($bindingInfo['modelKeyPath']))
                    {
                        if (!is_string($bindingInfo['modelKeyPath'])) throw( new Exception("'$bindProperty' binding parameter modelKeyPath is not a string.") );
                        if (!empty($fullKeyPath)) $fullKeyPath .= '.';
                        $fullKeyPath .= $bindingInfo['modelKeyPath'];
                    }
                    if (empty($fullKeyPath)) throw( new Exception("No keyPath specified for binding object id '{$id}', property '{$bindProperty}'.") );

                    // process options
                    $options = NULL;
                    if (isset($bindingInfo['options']))
                    {
                        // check type of all options
                        foreach ($bindingInfo['options'] as $key => $value) {
                            switch (gettype($value)) {
                                case "boolean":
                                case "integer":
                                case "double":
                                case "string":
                                case "NULL":
                                    // these are all OK, fall through
                                    break;
                                default:
                                    throw( new Exception("Binding option '$key' for WFView instance id::property '$id::$bindProperty' is not a vaild type (" . gettype($value) . "). Only boolean, integer, double, string, or NULL allowed.") );
                                    break;
                            }
                        }
                        $options = $bindingInfo['options'];
                    }

                    try {
                        $configObject->bind($bindProperty, $bindToObject, $fullKeyPath, $options);
                    } catch (Exception $e) {
                        print_r($bindingInfo);
                        throw($e);
                    }
                }
            }
        }

        // after all config info is loaded, certain widget types need to "update" things...
        // since we don't control the order of property loading (that would get way too complex) we just handle some things at the end of the loadConfig
        foreach ( $this->widgets() as $widgetID => $widget ) {
            $widget->allConfigFinishedLoading();
        }
    }

    /**
     * Get a URL that will take you to the current requestPage of the module, with the current state.
     * Only meaningful when called on the requestPage of the module.
     * @return string A URL to load the current page with the current state, but NOT send the current action. Useful for
     *                things like a "modify search" link.
     * @throws If called on the responseView, as it is not meaningful.
     */
    function urlToState()
    {
        if ($this->module->requestPage() !== $this) throw( new Exception("urlToState called on a page other than the requestPage.") );

        $rc = WFRequestController::sharedRequestController();
        $url = $_SERVER['PHP_SELF'] . '?';
        foreach ($_REQUEST as $k => $v) {
            if (strncmp($k, 'action|', 7) != 0)
            {
                $url .= $k . '=' . $v . '&';
            }
        }
        return $url;
    }
    
    /**
     * For each widget in the current form, give the widget a chance to PULL the values from the bound objects onto the bound properties.
     * Only meaningful when called on the responsePage of the module.
     * pullBindings() will call the pullBindings() method on all widgets in the form, thus allowing them to determine which bound properties need to
     * "lookup" their values from the bound objects.
     * @throws If called on the responseView, as it is not meaningful.
     */
    function pullBindings()
    {
        if ($this->module->responsePage() !== $this) throw( new Exception("pullBindings called on a page other than the requestPage.") );
        $this->assertPageInited();

        WFLog::log("pullBindings()", WFLog::TRACE_LOG);

        // Call pullBindings for all widgets!
        foreach ( $this->widgets() as $widgetID => $widget ) {
            try {
                // lookup bound values for this widget.
                WFLog::log("pullBindings() for " . get_class($widget)  . " ($widgetID)", WFLog::TRACE_LOG);
                $widget->pullBindings();
            } catch (Exception $e) {
                WFLog::log("Error in pullBindings() for " . get_class($widget) . " ($widgetID):" . $e->__toString(), WFLog::TRACE_LOG);
            }
        }
    }
    
    /**
     * For each widget in the current form, give the widget a chance to PUSH the values from the form onto the bound objects.
     * Only meaningful when called on the requestPage of the module.
     * pushBindings() will call the pushBindings() method on all widgets in the form, thus allowing them to determine which bound properties need to
     * "publish" their values from the form onto the bound objects.
     * If the value has changed, validate the value and set it as appropriate (on the bound object).
     * WFWidget subclasses do that from their pushBindings() callback using propagateValueToBinding()
     * @throws If called on the responseView, as it is not meaningful.
     */
    function pushBindings()
    {
        if ($this->module->requestPage() !== $this) throw( new Exception("pushBindings called on a page other than the requestPage.") );
        $this->assertPageInited();

        if (!$this->hasSubmittedForm()) return; // no need to pushBindings() if no form submitted as nothing could have changed!

        WFLog::log("pushBindings()", WFLog::TRACE_LOG);

        // Call pushBindings for all widgets in the form!
        $idStack = array($this->submittedFormName());
        while ( ($widgetID = array_pop($idStack)) != NULL ) {
            try {
                $view = $this->outlet($widgetID);
                // add all children for THIS view to the stack
                foreach (array_keys($view->children()) as $id) {
                    array_push($idStack, $id);
                }
                if (!($view instanceof WFWidget)) continue; // only need to process WIDGETS below.
                $widget = $view;

                // restore the UI state for this widget
                $widget->pushBindings();
            } catch (Exception $e) {
                WFLog::log("Error in pushBindings() for widget '$widgetID': " . $e->getMessage(), WFLog::TRACE_LOG);
                throw($e);
            }
        }
    }

    /**
     * Restore the UI state of the page based on the submitted form.
     * Only meaningful when called on the requestPage of the module.
     * Restore state will call the restoreState() method on all widgets in the form, thus allowing them to re-create the state they should be in
     * after the changes that the user made to the form.
     * IMPORTANT!!! THIS FUNCTION ONLY RESTORES THE VALUE OF THE UI WIDGET AND DOES NOT PUBLISH THE VALUE BACK THROUGH BINDINGS.
     * @see pushBindings
     * @throws If called on the responseView, as it is not meaningful.
     */
    function restoreState()
    {
        if ($this->module->requestPage() !== $this) throw( new Exception("restoreState called on a page other than the requestPage.") );
        $this->assertPageInited();

        if (!$this->hasSubmittedForm()) return; // no state to restore if no form was submitted!
        
        WFLog::log("restoreState()", WFLog::TRACE_LOG);

        // Restore state of all widgets in the form!
        // for each widget in the form, let it restoreState()!
        $idStack = array($this->submittedFormName());
        while ( ($widgetID = array_pop($idStack)) != NULL ) {
            try {
                $view = $this->outlet($widgetID);
                // add all children for THIS view to the stack
                foreach (array_keys($view->children()) as $id) {
                    array_push($idStack, $id);
                }
                if (!($view instanceof WFWidget)) continue; // only need to process WIDGETS below.
                $widget = $view;

                // restore the UI state for this widget, if it hasn't tried already.
                if (!$widget->hasRestoredState())
                {
                    WFLog::log("restoring state for widget id '$widgetID'", WFLog::TRACE_LOG);
                    $widget->restoreState();
                }
            } catch (Exception $e) {
                WFLog::log("Error restoring state for widget '$widgetID'.", WFLog::TRACE_LOG);
            }
        }
    }

    /**
     * Add an error to the current page.
     *
     * This function is used by WFWidgets or from action methods to add errors to the current request page.
     * Widgets automatically register all errors generated by Key-Value Validation of bound values.
     * Clients can call this function from the action method to add other run-time errors to the error mechanism.
     *
     * If the requestPage has *any* errors, the responsePage will *not* be loaded.
     *
     * @param object A WFError object.
     * @throws If the passed error is not a WFError or if addError() is called on the responsePage.
     */
    function addError($error)
    {
        if ($this->module->requestPage() !== $this) throw( new Exception("addError called on a page other than the requestPage.") );
        if (!($error instanceof WFError)) throw( new Exception("All errors must be WFError instances (not " . get_class($error)  . ").") );
        $this->errors[] = $error;
    }

    /**
     * Get a list of all errors on the page.
     * @return array An array of WFErrors, or an empty array if there are no errors.
     */
    function errors()
    {
        return $this->errors;
    }

    /**
     * Is the form valid? A form (ie requestPage submission) is considered valid if there are NO errors after it has been processed.
     * @return boolean TRUE if the form is valid, FALSE otherwise.
     * @throws If called on a view besides the requestPage, or if no form was submitted.
     */
    function formIsValid()
    {
        if ($this->module->requestPage() !== $this) throw( new Exception("formIsValid called on a page other than the requestPage.") );
        $this->assertPageInited();
        if (!$this->hasSubmittedForm()) throw( new Exception("formIsValid called, but no form was submitted.") );

        if (count($this->errors) > 0)
        {
            return false;
        }
        return true;
    }

    /**
     * Initialize the named page. This will load the widget instances and prepare for manipulating the UI.
     *
     * Each page has two parts, the HTML template, and the page instances config file (also called the .yaml file).
     * On the filesystem, they are named <pageName>.tpl (the template file) and <pageName>.yaml (the config file).
     *
     * Once the instances are initialized and configured, the module will be given a chance to load default settings for the page via a callback.
     * This is the time to set up select lists, default values, etc.
     * The method name that will be called on the module is "<pageName>_PageDidLoad". Here is the prototype for that method:
     * void <pageName>_PageDidLoad($page, $parameters);
     *
     * The parameters argument is an associative array, with "name" => "value". The items that will be in the array are determined by the page's parameterList,
     * which is provided by the <pageName>_ParameterList() method, you can implement if your page needs parameters. This method should simply return a list of
     * strings, which will become the "names" passed into your _PageDidLoad method.
     *
     * Here's how the parameterization works... for each item in the array, first the PATH_INFO is mapped to the items. So, if your parameterList is:
     *
     * array('itemID', 'otherParameter')
     *
     * And the PATH_INFO is /12/moreData then the parameters passed in will be array('itemID' => '12', 'otherParameter' => 'moreData').
     * Any data that cannot be matched up will be passed with a NULL value.
     * Also, if there is a form submitted, then the values for the parameters will be replaced by the "value" of the outlets of the same name.
     * Thus, if your form has an "itemID" hidden field, the value from the form will supercede the value from PATH_INFO.
     *
     * After the _PageDidLoad call, the UI state from the request will be applied on top of the defaults.
     *
     * @todo Something is goofy with the detection of isRequestPage surrounding the action processor... seems to be getting called on the response page too. investiage.
     * @todo createWidgets()... works fine unless the ACTION method alters the arrayController. Then you need to call createWidgets() for each Dynamic again. Should this be automated after the "action" callback is called? If so, should we implement the "no action" handler for !hasSubmittedForm so that createWidgets() doens't get called 2x when there's an action?
     */
    function initPage($pageName)
    {
        WFLog::log("Initing page $pageName", WFLog::TRACE_LOG);
        if (!empty($this->pageName)) throw( new Exception("Page already inited with: {$this->pageName}. Cannot initPage twice.") );
        $this->pageName = $pageName;

        // calculate various file paths
        $basePagePath = $this->module->pathToPage($this->pageName);
        $yamlFile = $basePagePath . '.yaml';
        $instancesFile = $basePagePath . '.instances';
        $configFile = $basePagePath . '.config';

        if (file_exists($yamlFile))
        {
            WFLog::log("Loading YAML config: {$pageName}.yaml", WFLog::TRACE_LOG);
            $yamlConfig = WFYaml::load($yamlFile);
            //print_r($yamlConfig);
            foreach ($yamlConfig as $id => $instanceManifest) {
                $this->initInstanceYAML($id, $instanceManifest);
            }
            // after all config info is loaded, certain widget types need to "update" things...
            // since we don't control the order of property loading (that would get way too complex) we just handle some things at the end of the loadConfig
            foreach ( $this->widgets() as $widgetID => $widget ) {
                $widget->allConfigFinishedLoading();
            }
        }
        else
        {
            // parse instances file and instantiate all objects be graceful -- no need to have fatal error if there are no instances
            if (file_exists($instancesFile))
            {
                include($instancesFile);
                foreach ($__instances as $id => $instanceManifest) {
                    $this->initInstancePHP($id, $instanceManifest);
                }

                // parse config file - for each instance, see if there is a config setup for that instance ID and apply it.
                $this->loadConfig($configFile);
            }
        }

        // restore UI state, if this is the requestPage
        // must happen AFTER config is loaded b/c some config options may affect how the widgets interpret the form data.
        // must happen BEFORE _PageDidLoad callback because that callback may need to access widget data, before it's available via bindings.
        if ($this->isRequestPage())
        {
            $this->restoreState();
        }

        // give module a chance to load defaults for this page

        // the PARAMTERS are ONLY determined for the requestPage!
        // determine parameters first
        $parameters = array();
        if ($this->isRequestPage())
        {
            WFLog::log("Parameterizing $pageName", WFLog::TRACE_LOG);
            $parameterManifestMethod = "{$this->pageName}_ParameterList";
            if (method_exists($this->module, $parameterManifestMethod))
            {
                $parameterList = $this->module->$parameterManifestMethod($this);
                if (count($parameterList) > 0)
                {
                    // first map all items through from PATH_INFO
                    // @todo Right now this doesn't allow DEFAULT parameter values (uses NULL). Would be nice if this supported assoc_array so we could have defaults.
                    $invocationParameters = $this->module->invocation()->parameters();
                    for ($i = 0; $i < count($parameterList); $i++) {
                        if (isset($invocationParameters[$i]))
                        {
                            $parameters[$parameterList[$i]] = $invocationParameters[$i];
                        }
                        else
                        {
                            $parameters[$parameterList[$i]] = NULL;
                        }
                    }

                    // then over-ride with from form, if one has been submitted
                    if ($this->hasSubmittedForm())
                    {
                        foreach ($parameterList as $id) {
                            if (!$this->hasOutlet($id)) continue;
                            // see if there is an instance of the same name in the submitted form
                            $instance = $this->outlet($id);
                            if ($instance instanceof WFWidget)
                            {
                                // walk up looking for parent
                                $parent = $instance->parent();
                                do {
                                    if ($parent and $parent instanceof WFForm and $parent->name() == $this->submittedFormName())
                                    {
                                        $parameters[$id] = $this->outlet($id)->value();
                                        break;
                                    }
                                    if (!is_object($parent)) throw( new Exception("Error processing parameter overload for parameter id: '$id': found widget of same id, but cannot determine the form that it belongs to. Are you sure that widget id: '$id' is in a WFForm?") );
                                    $parent = $parent->parent();
                                } while ($parent);
                            }
                        }
                    }
                }
            }
        }
        else
        {
            WFLog::log("Skipping Parameterization for $pageName", WFLog::TRACE_LOG);
            $parameterManifestMethod = "{$this->pageName}_ParameterList";
            if (method_exists($this->module, $parameterManifestMethod))
            {
                $parameterList = $this->module->$parameterManifestMethod($this);
                if (count($parameterList) > 0)
                {
                    // NULL-ify all params
                    for ($i = 0; $i < count($parameterList); $i++) {
                        $parameters[$parameterList[$i]] = NULL;
                    }
                }
            }
        }
        // save completed parameters
        $this->parameters = $parameters;

        // this is where pages will set up their bound objects, etc.
        $didLoadMethod = "{$this->pageName}_PageDidLoad";
        if (method_exists($this->module, $didLoadMethod))
        {
            WFLog::log("Calling {$didLoadMethod}", WFLog::TRACE_LOG);
            $this->module->$didLoadMethod($this, $parameters);
        }

        // call into all WFDynamic widgets to set up their dynamic controls.
        foreach ($this->widgets() as $id => $obj) {
            if ($obj instanceof WFDynamic)
            {
                $obj->createWidgets();
            }
        }

        // restore UI state AGAIN so that any controls created dynamically can update their values based on the UI state.
        if ($this->isRequestPage())
        {
            $this->restoreState();
        }

        // callback method on WFModule after all state is restored (allows module to react to UI state before pushBindings())
        $didRestoreStateMethod = "{$pageName}_DidRestoreState";
        if (method_exists($this->module, $didRestoreStateMethod))
        {
            WFLog::log("Calling {$didRestoreStateMethod}", WFLog::TRACE_LOG);
            $this->module->$didRestoreStateMethod($this);
        }

        // now that the UI is set up (instantiated), it's time to propagate the values from widgets to bound objects if this is the requestPage!
        // then, once the values are propagated, we should call the action handler for the current event, if there is one.
        if ($this->isRequestPage())
        {
            // push values of bound properties back to their bound objects
            $this->module->requestPage()->pushBindings();
            
            // Are we performing an action? 
            // initialize to PERFORM NO ACTION
            $actionOutletID = NULL;
            // only perform an action if there is one in the form, and that form is valid
            if ($this->hasSubmittedForm()) {
                if ($this->formIsValid()) {
                    // look up the instance ID for the specified action... look for "action|<actionOutletID>" in $_REQUEST...
                    // but need to skip the _x and _y fields submitted with image submit buttons
                    foreach ($_REQUEST as $name => $value) 
                    {
                        if (strncmp("action|", $name, 7) == 0 and !in_array(substr($name, -2, 2), array('_x', '_y')))
                        {
                            list(,$actionOutletID) = explode('|', $name);
                            break;
                        }
                    }
                    // if there is no button found in the parameters, we ask the WFForm what the default submit button is
                    if (!$actionOutletID)
                    {
                        $form = $this->outlet($this->submittedFormName());
                        $actionOutletID = $form->defaultSubmitID();
                        WFLog::log("Form submitted, but no action button detected. Using default button: {$actionOutletID}", WFLog::TRACE_LOG);
                    }
                    // call the ACTION handler for the page, if there is an action.
                    if ($actionOutletID)
                    {
                        try {
                            $actionName = $this->outlet($actionOutletID)->action();
                        } catch (Exception $e) {
                            throw( new WFException("Could not find form button (outlet) for current action: {$actionOutletID}") );
                        }
                        $actionMethod = $this->pageName . '_' . $actionName . '_Action';
                        if (!method_exists($this->module, $actionMethod)) throw( new Exception("Action method {$actionMethod} does not exist for module " . $this->module->moduleName()) );
                        WFLog::log("Running action: $actionMethod", WFLog::TRACE_LOG);
                        $this->module->$actionMethod($this);
                    }
                    else
                    {
                        WFLog::log("Not running action because no action occurred (no action specified in form data)", WFLog::WARN_LOG);
                    }
                }
                else
                {
                    WFLog::log("Not running action because form data did not validate.", WFLog::TRACE_LOG);
                }
            }
            else
            {
                WFLog::log("Not running action because no action occurred (i.e. no form posted)", WFLog::TRACE_LOG);
            }

        }
    }

    /**
     * Is this instance the responsePage for the module?
     * @return boolean TRUE if this is the responsePage, false if it is not (thus it's the requestPage).
     */
    function isResponsePage()
    {
        if ($this->module->responsePage() === $this)
        {
            return true;
        }
        return false;
    }

    /**
     * Is this instance the requestPage for the module?
     * @return boolean TRUE if this is the requestPage, false if it is not (thus it's the responsePage).
     */
    function isRequestPage()
    {
        if ($this->module->requestPage() === $this)
        {
            return true;
        }
        return false;
    }

    /**
     * Ensure the page is inited.
     * @internal
     */
    protected function assertPageInited()
    {
        if (!$this->isLoaded()) throw( new Exception("Attempted to access an uninitialized page.") );
    }
    
    /**
     * Assign a value to the underlying template engine.
     * @param string Name of the value in the template engine.
     * @param mixed The value to assign.
     */
    function assign($name, $value)
    {
        $this->prepareTemplate();

        $this->template->assign($name, $value);
    }

    /**
     * Get the HTML output of the page.
     *
     * This function will call the <pageName>_SetupSkin() callback IFF this page belongs to the root module.
     * This function is useful if a page wants to munge skin settings such as Title or Meta Info. It is called
     * just prior to rendering the page, as this way the client can be certain that all data is loaded prior to this call.
     *
     * The prototype is:
     *
     * void <pageName>_SetupSkin($skin)
     *
     * @return string The HTML output of the module.
     */
    function render()
    {
        if (!$this->isResponsePage()) throw( new Exception("Render called on a page that is not the responsePage.") );
        
        // there isn't always a skin; only on the ROOT invocation.
        $skin = $this->module->invocation()->skin();

        // ensure that a template object is instantiated
        $this->prepareTemplate();

        // stuff a copy of the current module in the template...
        $this->template->assign('__module', $this->module());
        // stuff a copy of the current page in the template...
        $this->template->assign('__page', $this);
        // stuff a copy of the skin in the template...
        $this->template->assign('__skin', $skin);

        // pull bound values into all widgets based on bindings.
        $this->pullBindings();

        // let the page set up stuff on the skin, IFF there is a skin and a delegate method, and we're the root invocation (which now should be redundant with skin in invocation
        if ($skin)
        {
            $pageSkinSetupMethod = "{$this->pageName}_SetupSkin";
            if ($this->module->invocation()->isRootInvocation() and method_exists($this->module, $pageSkinSetupMethod))
            {
                $this->module->$pageSkinSetupMethod($skin);
            }
        }

        // return the rendered HTML of the page. we do this in an output buffer so that if there's an error, we can display it cleanly in the skin rather than have a
        // half-finished template dumped on screen (which is apparently what smarty does when an error is thrown from within it)
        $html = NULL;
        try {
            ob_start();
            $html = $this->template->render(false);
            ob_end_clean();
        } catch(Exception $e) {
            ob_end_clean();
            throw($e);
        }
        return $html;
    }

    /**
     *  Debug function for dumping the instance tree for the page.
     *  
     *  Recursive. Call dumpTree() to use.
     */
    function dumpTree($obj = NULL)
    {
        static $depth = 0;

        if ($depth == 0) print "\nInstance tree:\n";

        if ($obj === NULL)
        {
            foreach ($this->instances as $inst) {
                if ($inst->parent() === NULL)
                {
                    $this->dumpTree($inst);
                }
            }
        }
        else
        {
            $depth++;
            print str_repeat(' ', $depth * 2 - 2) . '\-> ' . $obj->id() . ' => ' . get_class($obj) . "\n";
            if (count($obj->children()))
            {
                foreach ($obj->children() as $inst) {
                    $this->dumpTree($inst);
                }
            }
            $depth--;
        }

    }
}
?>
