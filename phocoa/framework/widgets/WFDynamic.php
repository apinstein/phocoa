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
 * A Dynamic widget for our framework. The dynamic widget is a meta-widget that automatically creates multple widgets for you, based on an array controller.
 *
 * It's perfect for repeating data, tabular data, etc. You can also use multiple ones on the same page.
 *
 * There are two modes of operation for WFDynamic. These two modes allow for great flexibility in layout of the managed widgets.
 *
 * 1. By default, the WFDynamic will output the NEXT widget each time the {WFDynamic id="XXX"} is output in the template. In this case, you will need to ensure that if your array has N entries then you output the WFDynamic tag N times in the template. The upside of this mode is that there is great flexibility in layout as you can place the widgets wherever you like them. The downside is that you need to make sure that your template has a loop in it that iterates N times so that you can be sure all widgets will be displayed.
 * 2. The other option, called {@link WFDynamic::$oneShotMode oneShotMode}, will output one entry for each item in the array when the {WFDynamic id="XXX"} tag is used in your template. In this case, each widget will be separated by the HTML snippet in {@link WFDynamic::$oneShotSeparatorHTML oneShotSeparatorHTML}. The upside to this mode is its simplicity; it's great if you just need a list of checkboxes or labels for each item in the array. The downside is that you cannot position each widget inside of a larger layout in all cases as they are all clumped together.
 * 
 * NOTE: (DEPRECEATED -- USE PROTOTYPES NOW) If you want to assign a formatter to the widgets, simply assign a formatter to the WFDynamic and it will be re-used for all dynamically created widgets too. 
 *
 * PROTOTYPES
 * 
 * Oftentimes you want to have customized attributes on the widget you are creating via WFDynamic. For instance, using formatters, setting default options, or other properties.
 * WFDynamic now supports "prototypes". Simply add a child to the WFDynamic named "<id of WFDynamic>Prototype" and configure it as you would normally. WFDynamic will use this
 * prototype object as the basis for all objects created. If you do use a prototype, there is no need to supply the widgetClass.
 *
 * BINDINGS VIA PROTOTYPES
 * 
 * You can specify "simpleBindKeyPath" style bindings via prototypes as well. To bind a property of the prototype to a keyPath of each object in the WFDynamic's ArrayController, simply
 * add a binding to the prototype object, bind it to the same array controller you configured for WFDynamic, set the controller key to "#current#", and set the desired Model Key Path.
 *
 * WFDYNAMIC AND THE VIEW HIERARCHY OF CREATED WIDGETS
 * WFDynamic creates widgets. However, WFDynamic is a "magic" widget and should be transparent to its parent and the created children. WFDynamic will add its dynamic widgets
 * to the parent WFView object of the WFDynamic, thus creating the widgets as children of that object as if they had been created normally.
 * HOWEVER: At this time, the WFDynamic is still in the view hierarchy as another child of the parent. So, at this point, WFViews that expect children such as WFRadioGroup should skip
 * any WFDynamics they find when processing their children.
 *
 * <b>PHOCOA Builder Setup:</b>
 * 
 * Required:<br>
 * - {@link WFDynamic::$arrayController arrayController}
 *
 * Optional:<br>
 * - {@link WFDynamic::$widgetClass widgetClass}
 * - {@link WFDynamic::$simpleBindKeyPath simpleBindKeyPath}
 * - {@link WFDynamic::$oneShotMode oneShotMode}
 * - {@link WFDynamic::$oneShotSeparatorHTML oneShotSeparatorHTML}
 *
 * @todo I think maybe that WFDynamic should be completely transparent to the view heirarchy. However, currently, subclasses of WFDynamic may not agree. Please check them out and see if this can all be refactored in a better, more consistent way. Maybe WFDynamic should be final? Definitely look into this and WFCheckboxGroup as well. There are related todo's in there.
 */
class WFDynamic extends WFWidget
{
    /**
     * @var WFArrayController The array controller that will be used to create widgets for. One widget will be created for each item in the controller. Example: #module#myArrayController
     */
    protected $arrayController;
    /**
     * @var string The class name of the WFWidget subclass to create. Examples: WFLabel, WFTextField, etc.
     */
    protected $widgetClass;
    /**
     * @var boolean Use unique names (HTML input name) for each widget? Or use the same for every widget (for instance, when using radios or checkboxes you may want to set this to true)
     */
    protected $useUniqueNames;
    /**
     * @var array The configuration array for the dynamic widget.
     */
    protected $widgetConfig;
    /**
     * @var boolean Tracks whether or not the widgetConfig has been updated based on other settings yet.
     */
    private $processedWidgetConfig;
    /**
     * @var integer The current iteration, used during rendering.
     */
    protected $renderIteration;
    /**
     * @var array An array of the widget ID's created by the dynamic control. Used during rendering to iterate through the widgets.
     */
    protected $createdWidgets;
    /**
     * @var string The keyPath on the class managed by arrayController to use as the {@link WFWidget::$value value} for each created widget.
     *
     * Very often, we simply want to link a WFDynamic to a particular modelKey of the current object, and this is a way to do this without having to write code.
     * A good example of this is using a WFLabel to show the value of every item in an array.
     *
     * If this property is non-null, it has the same effect as declaring the following config option:
     *
     * <code>
     *      $options = array(
     *              'value' => array(
     *                  'bind' => array(
     *                      'instanceID' => '#current#',
     *                      'controllerKey' => '',
     *                      'modelKeyPath' => $simpleBindKeyPath
     *                      )
     *                  )
     *              );
     * </code>
     */
    protected $simpleBindKeyPath;
    /**
     * @var boolean TRUE if all managed widgets should be output at the first occurence of the {WFDynamic id="XXX"} tag. False if the next occurence of the managed widgets should be output at each occurence of the tag.
     */
    protected $oneShotMode;
    /**
     * @var string If {@link WFDynamic::$oneShotMode oneShotMode} is enabled, this is the HTML that will be used to separate each widget. Default is <samp><br /></samp>.
     */
    protected $oneShotSeparatorHTML;
    /**
     * @var object WFView A prototype object to use for each widget created. Can be NULL.
     */
    protected $prototype;
    
    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);

        $this->arrayController = NULL;
        $this->widgetClass = '';
        $this->useUniqueNames = true;
        $this->widgetConfig = array();
        $this->processedWidgetConfig = false;
        $this->renderIteration = 0;
        $this->createdWidgets = array();
        $this->simpleBindKeyPath = NULL;
        $this->prototype = NULL;

        $this->oneShotMode = false;
        $this->oneShotSeparatorHTML = '<br />';
    }

     /*
     * Set whether or not each created widget will have the same name.
     *
     * If {WFDynamic::$useUniqueNames} is true, each widget will be named "<basename>_<itemid>".
     * If {WFDynamic::$useUniqueNames} is false, all widgets will be named "<basename>".
     * Of course in either case, the ID's will all be uniq, "<basename>_<itemid>".
     *
     * @param boolean TRUE to make each widget have a unique name.
     *                FALSE to make each widget have the same name. 
     */
    function setUseUniqueNames($useUniqueNames)
    {
        $this->useUniqueNames = $useUniqueNames;
    }

    function setParentFormID()
    {
        WFLog::log("Use of parentFormID is now deprecated as it is automatic. Please remove from your config of " . get_class($this) . ", id: '" . $this->id() . "'", WFLog::WARN_LOG);
    }

    function canPushValueBinding()
    {
         return false;
    }

    /**
     *  Set the WFView object to be used as the prototype for all instances created.
     *
     *  @param object WFView The WFView object to be used as the prototype.
     */
    function setPrototype(WFView $view)
    {
        $this->prototype = $view;
        $this->setWidgetClass(get_class($view));
    }

    /**
     *  To implement our prototype functionality, we need to detect when a child object named "<id>Prototype" has been added.
     *
     *  If a prototype object is detected, we set up the prototype for the WFDynamic.
     *
     *  @param object WFView The object being added.
     */
    function addChild(WFView $view)
    {
        if ($view->id() == "{$this->id}Prototype")
        {
            $this->setPrototype($view);
        }
        else
        {
            // add new view to the "parentView" object
            $parentView = $this->calculateParent();
            if ($parentView)
            {
                $parentView->addChild($view);
            }
            else
            {
                parent::addChild($view);
            }
        }
    }

    function setSimpleBindKeyPath($kp)
    {
        $this->simpleBindKeyPath = $kp;
    }

    function setWidgetClass($widgetClass)
    {
        $this->widgetClass = $widgetClass;
    }
    
    function setArrayController($ac)
    {
        if (!($ac instanceof WFArrayController)) throw( new Exception("arrayController must be a WFArrayController.") );
        $this->arrayController = $ac;
    }

    /**
     *  Get an array of all of the widgets managed by this WFDynamic instance.
     *
     *  @return array An array of WFWidget subclasses that were created by this WFDynamic.
     */
    function managedWidgets()
    {
        return $this->createdWidgets;
    }

    /**
     *  Get the parent WFView for the WFDynamic. 
     *
     *  The WFDynamic is basically used to dynamically add children to the parent of whatever it's under. So for things like WFRadioGroup and WFCheckboxGroup
     *  it's important that the parent "Group" widget gets directly assigned all objects created by WFDynamic.
     *
     *  @return object WFView The parent WFView, or NULL if the WFDynamic does not have a parent.
     */
    function calculateParent()
    {
        $parentView = NULL;
        if ($this->parent())
        {
            $parentView = $this->parent();
        }
        return $parentView;
    }

    /**
     *  Set the config that will be used for the dynamic creation of widgets. See {@link createWidgets} for documentation on the config array.
     *
     * @param array The widgetConfig to use for creation of widgets. Format:
     *  <code>
     *  array(
     *      // enter as many widget property names as you like.
     *      'widgetPropName' => array(
     *                              'custom' => array(
     *                                              'iterate' => true / false,
     *                                              'keyPath' => 'modelKeyPath' KeyPath of the object to use for this iteration.
     *                                                                          Only used if 'iterate' == true, and then it's optional.
     *                                                                          If 'iterate' == true, then uses the nth value from the 'value' array.
     *                                                                          If 'iterate' == '#identifier#', then uses the identifierHashForObject() for the current object.
     *                                                                          NOTE: if the array controller is in indexed mode, will use the index of the object.
     *                                                                          If 'iterate' == false, then uses the 'value' for EVERY iteration.
     *                                              'value' => mixed            Either an array or a primitive, depending on the setting of 'iterate'.
     *                                              )
     *                              // use this binding config (similar format as widget .config file)
     *                              'bind' => array(
     *                                              'instanceID' => 'outletName' or 
     *                                                              '#module#' (to use the module) or
     *                                                              '#custom# (to use object specified in 'custom' above) or
     *                                                              '#current#' (to use the object of the current iteration)
     *                                              'controllerKey' => 'controllerKey',
     *                                              'modelKeyPath' => 'modelKeyPath',
     *                                              'options' => array() // bindings options
     *                                              )
     *                          )
     *      )
     *  </code>
     *
     *  This format lets you arbitrarily configure any property of any type of widget.
     *  
     *  For each property, you can choose to use the same 'value' for EVERY widget, or you can use a different value for each widget, the nth item in 'value' for the nth instance.
     *  
     *  @throws If the passed parameter is not a PHP array.
     */
    function setWidgetConfig($config)
    {
        if (!is_array($config)) throw( new Exception("Config must be an array."));
        $this->widgetConfig = $config;
    }

    function render($blockContent = NULL)
    {
        // lookup proper iteration of control...
        // then render it.
        if ($this->oneShotMode)
        {
            $output = '';
            for (; $this->renderIteration < count($this->createdWidgets); $this->renderIteration++) {
                $output .= $this->createdWidgets[$this->renderIteration]->render() . $this->oneShotSeparatorHTML;
            }
            return $output;
        }
        else
        {
            $currentIteration = $this->renderIteration++;
            if (!isset($this->createdWidgets[$currentIteration]) or !($this->createdWidgets[$currentIteration] instanceof WFView)) throw( new Exception("Widget does not exist for dynamic id: '{$this->id}' iteration: {$currentIteration}. Maybe you need to call createWidgets() again after loading data into the arrayController?") );
            return $this->createdWidgets[$currentIteration]->render();
        }
    }

    /**
     *  Process the widget config.
     *
     *  The widgetConfig is the options to use for creating the dynamic widgets. Based on various other settings, the widgetConfig may be altered.
     *  The alteration process need happen only once per WFDynamic instance. The processing should be delayed until just before creating the widgets.
     */
    function processWidgetConfig()
    {
        if ($this->processedWidgetConfig) return;
        $this->processedWidgetConfig = true;

        // emulate simpleBindKeyPath from bindings on prototype that bind to the same arrayController / arrangedObjects
        if (!is_null($this->prototype))
        {
            foreach ($this->prototype->bindings() as $bindLocalProp => $binding) {
                if ($binding->bindToObject() === $this->arrayController
                        and strncmp($binding->bindToKeyPath(), '#current#', 9) === 0)
                {
                    $this->widgetConfig[$bindLocalProp] = array(
                                                                'bind' => array(
                                                                    'instanceID' => '#current#',
                                                                    'controllerKey' => '',
                                                                    'modelKeyPath' => substr($binding->bindToKeyPath(), 10),
                                                                    'options' => $binding->options()
                                                                    )
                                                             );
                    $this->prototype->unbind($bindLocalProp);
                }
            }
        }

        // is there a configured simpleBindKeyPath?
        if (!is_null($this->simpleBindKeyPath))
        {
            if (isset($this->widgetConfig['value'])) throw (new Exception("simpleBindKeyPath set but 'value' binding already set up.") );
            $this->widgetConfig['value'] = 
                array(
                        'bind' => array(
                            'instanceID' => '#current#',
                            'controllerKey' => '',
                            'modelKeyPath' => $this->simpleBindKeyPath
                            )
                     );
        }
    }

    /**
     * Create the dynamic widgets.
     *
     * Allows for creation of n widgets based on an WFArrayController. Various options allow you to set up nearly any type
     * of widget that is repeated for all objects in an array. Great for managing selection, editing data from joins, etc.
     *
     * Once the objects are instantiated, configured, and bound as appropriate, restoreState() will be called. You can be sure that
     * when this function exits that your widgets are in the same state as statically instantiated widgets from the page.
     * 
     * This will be called AFTER the _PageDidLoad method... which is what we need to wait for before creating our widgets. WFPage makes this call.
     *
     * Module code may need to call this function again, particularly if the content of they arrayController is changed by the current action.
     *
     * @return assoc_array An array of 'widgetID' => widget for all newly created widgets.
     * @todo Anything else wrong with calling more than once? This should be idempotent as well as re-callable with different data.
     */
    function createWidgets()
    {
        // remove existing widgets from page
        foreach ($this->createdWidgets as $existingWidget) {
            WFLog::log("Removing dynamically created widget: " . $existingWidget->id(), WFLog::WARN_LOG);
            $this->page->removeInstance($existingWidget->id());
            if ($this->parent)
            {
                $this->parent->removeChild($existingWidget);
            }
        }

        $arrayController = $this->arrayController;
        $widgetClass = $this->widgetClass;
        $widgetBaseName = $this->id;
        $useUniqueNames = $this->useUniqueNames;

        $parentView = $this->calculateParent();
        $this->createdWidgets = array();

        // check params
        if (!class_exists($widgetClass)) throw( new Exception("There is no widget class '$widgetClass'.") );
        if (!is_object($this->arrayController)) throw( new Exception("No WFArrayController assigned to WFDynamic. Set the arrayController object for WFDynamic '{$this->id}'."));
        if (!($this->arrayController instanceof WFArrayController)) throw( new Exception("arrayController must be a WFArrayController instance."));

        $this->processWidgetConfig();

        $currentIndex = 0;
        foreach ($arrayController->arrangedObjects() as $object) {
            // instantiate the widget with a unique ID
            if ($arrayController->usingIndexedMode())
            {
                $id = $widgetBaseName . '_' . $currentIndex;
            }
            else
            {
                $id = $widgetBaseName . '_' . $arrayController->identifierHashForObject($object);
            }

            // instantiate widget
            if (!is_null($this->prototype))
            {
                $widget = $this->prototype->cloneWithID($id);
            }
            else
            {
                $widget = new $widgetClass($id, $this->page());
            }

            // add to form if needed
            if ($parentView)
            {
                $parentView->addChild($widget);
            }

            // add to our list
            $this->createdWidgets[] = $widget;
            
            // set up the name
            if (!$useUniqueNames)
            {
                $widget->setName($widgetBaseName);
            }

            WFLog::log("WFDynamic:: created $widgetClass id=$id name=" . $widget->name());

            // set up properties
            if ($this->formatter)
            {
                $widget->setFormatter($this->formatter);
            }
            foreach ($this->widgetConfig as $propName => $propInfo) {
                // set up custom value and/or binding
                if (!isset($propInfo['custom']) and !isset($propInfo['bind']))
                {
                    throw( new Exception("You must supply either 'custom' or 'bind' information. (propName: {$propName})") );
                }

                $customValue = NULL;
                if (isset($propInfo['custom']))
                {
                    $customSettings = $propInfo['custom'];
                    // make sure it has all expected pieces
                    if (!isset($customSettings['iterate'])) throw( new Exception("An 'iterate' setting must be provided. (propName: {$propName})") );
                    
                    // use a different value for each iteration
                    if ($customSettings['iterate'])
                    {
                        // use the keyPath of the current object
                        if (isset($customSettings['keyPath']) and $customSettings['keyPath'] !== false)
                        {
                            if ($customSettings['keyPath'] == '#identifier#')
                            {
                                if ($arrayController->usingIndexedMode())
                                {
                                    $customValue = $currentIndex;
                                }
                                else
                                {
                                    $customValue = $arrayController->identifierHashForObject($object);
                                }
                            }
                            else
                            {
                                $customValue = $object->valueForKeyPath($customSettings['keyPath']);
                            }
                        }
                        // use the nth item from the value array
                        else
                        {
                            if (!is_array($customSettings['value'])) throw( new Exception('If iterate and you supply a value, value must be an array. (propName: {$propName})') );
                            $customValue = $customSettings['value'][$currentIndex];
                        }
                    }
                    // use the same value for each iteration.
                    else
                    {
                        if (!isset($customSettings['value'])) throw( new Exception("If not iterate, a 'value' must be provided. (propName: {$propName})") );
                        $customValue = $customSettings['value'];
                    }

                    WFLog::log("WFDynamic:: setting $propName to $customValue for $id", WFLog::TRACE_LOG);
                    $widget->setValueForKey($customValue, $propName);
                }
                // or are we using bindings
                if (isset($propInfo['bind']))
                {
                    $bindingInfo = $propInfo['bind'];

                    WFLog::log("WFDynamic:: Binding property '$propName' to {$bindingInfo['instanceID']} => {$bindingInfo['controllerKey']}::{$bindingInfo['modelKeyPath']}", WFLog::TRACE_LOG);

                    // determine object to bind to:
                    if (!isset($bindingInfo['instanceID'])) throw( new Exception("No instance id specified for binding property '{$propName}'.") );
                    $bindToObject = NULL;
                    if ($bindingInfo['instanceID'] == '#module#')
                    {
                        $bindToObject = $parentView->page()->module();
                    }
                    else if ($bindingInfo['instanceID'] == '#custom#')
                    {
                        if (!is_object($customValue)) throw( new Exception("Could not determine custom bindToObject ({$bindingInfo['instanceID']}) for binding property '{$propName}'.") );
                        $bindToObject = $customValue;
                    }
                    else if ($bindingInfo['instanceID'] == '#current#')
                    {
                        $bindToObject = $object;
                    }
                    else
                    {
                        $bindToObject = $parentView->page()->outlet($bindingInfo['instanceID']);
                    }
                    if (is_null($bindToObject)) throw( new Exception("Could not determine bindToObject ({$bindingInfo['instanceID']}) for binding property '{$propName}'.") );

                    $fullKeyPath = '';
                    if (isset($bindingInfo['controllerKey']))
                    {
                        $fullKeyPath .= $bindingInfo['controllerKey'];
                    }
                    if (isset($bindingInfo['modelKeyPath']))
                    {
                        if (!empty($fullKeyPath)) $fullKeyPath .= '.';
                        $fullKeyPath .= $bindingInfo['modelKeyPath'];
                    }
                    if (empty($fullKeyPath)) throw( new Exception("No keyPath specified for binding property '{$propName}'") );

                    // process options
                    $options = NULL;
                    if (isset($bindingInfo['options']))
                    {
                        $options = $bindingInfo['options'];
                    }

                    try {
                        $widget->bind($propName, $bindToObject, $fullKeyPath, $options);
                    } catch (Exception $e) {
                        throw($e);
                    }
                }
            }

            // now that we've loaded all widget options (config), call the callback so that the widget can set itself up.
            $widget->allConfigFinishedLoading();

            // have widget restore state, only if we've posted! otherwise it will grab improper state
            if ($parentView and $parentView->page()->submittedFormName())
            {
                $widget->restoreState();
            }

            $currentIndex++;
        }

        return $this->createdWidgets;
    }
}

?>
