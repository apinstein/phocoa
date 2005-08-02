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
 * Includes
 */
require_once('framework/widgets/WFWidget.php');

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
 * NOTE: If you want to assign a formatter to the widgets, simply assign a formatter to the WFDynamic and it will be re-used for all dynamically created widgets too.
 *
 * <b>PHOCOA Builder Setup:</b>
 * 
 * Required:<br>
 * - {@link WFDynamic::$widgetClass widgetClass}
 * - {@link WFDynamic::$arrayController arrayController}
 *
 * Optional:<br>
 * - {@link WFDynamic::$simpleBindKeyPath simpleBindKeyPath} To set up the {@link WFWidget::$value value} for each widget.
 * - {@link WFDynamic::$parentFormID parentFormID}
 * - {@link WFDynamic::$oneShotMode oneShotMode}
 * - {@link WFDynamic::$oneShotSeparatorHTML oneShotSeparatorHTML}
 *
 * @todo Ability to use a "prototype" widget to build all widgets from, that would specify widget values such as width, value, etc. Probably this will be done by providing
 * a widget ID in as the "prototypeID" and that will impply widgetClass and all of the other default attributes.
 */
class WFDynamic extends WFWidget
{
    /**
     * @var WFArrayController The array controller that will be used to create widgets for. One widget will be created for each item in the controller. Example: #module#myArrayController
     */
    protected $arrayController;
    /**
     * @var string The ID of the parent form that will contain these widgets.
     * @todo We should probably figure out a way to refactor WFWidgets so that they automatically track their parents. Then we could remove the need to specify this directly.
     */
    protected $parentFormID;
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
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);

        $this->arrayController = NULL;
        $this->parentFormID = '';
        $this->widgetClass = '';
        $this->useUniqueNames = true;
        $this->widgetConfig = array();
        $this->renderIteration = 0;
        $this->createdWidgets = array();
        $this->simpleBindKeyPath = NULL;

        $this->oneShotMode = false;
        $this->oneShotSeparatorHTML = '<br />';
    }

    function canPushValueBinding()
    {
         return false;
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
     *  Create the dynamic widgets.
     *
     *  This will be called AFTER the _PageDidLoad method... which is what we need to wait for before creating our widgets. WFPage makes this call.
     */
    function createWidgets()
    {
        // check inputs
        if (!$this->arrayController instanceof WFArrayController) throw( new Exception("arrayController must be a WFArrayController instance."));
        try {
            $parentForm = $this->page->outlet($this->parentFormID);
        } catch (Exception $e) {
            $parentForm = NULL;
        }
        $this->createDynamicWidgets($this->arrayController, $parentForm, $this->widgetClass, $this->id, $this->useUniqueNames, $this->widgetConfig);
    }

    /**
     *  Set the config that will be used for the dynamic creation of widgets. See {@link createDynamicWidgets} for documentation on the config array.
     *
     *  @param array The config.
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
            return $this->createdWidgets[$this->renderIteration++]->render();
        }
    }

    /**
     * Function to help in the creation of dynamic widgets.
     *
     * Allows for creation of n widgets based on an WFArrayController. Various options allow you to set up nearly any type
     * of widget that is repeated for all objects in an array. Great for managing selection, editing data from joins, etc.
     *
     * Once the objects are instantiated, configured, and bound as appropriate, restoreState() will be called. You can be sure that
     * when this function exits that your widgets are in the same state as statically instantiated widgets from the page.
     *
     * @static
     * @param object A WFArrayController instance. The createDynamicWidgets will iterate over the arrangedObjects, making one widget for each item.
     * @param object The WFForm that this widget belongs to.
     * @param string The class of widget to create.
     * @param string The base name of the widget.
     *               If $useUniqueNames is true, all widgets will have this name.
     *               If $useUniqueNames is false, each widget will be named "<basename>_<itemid>".
     * @param boolean TRUE to make each widget have a unique name.
     *                FALSE to make each widget have the same name. Of course in either case, the ID's will all be uniq, "<basename>_<itemid>".
     * @param array A list of all "magic" variables for each widget. Format:
     *              array(
     *                  // enter as many widget property names as you like.
     *                  'widgetPropName' => array(
     *                                          'custom' => array(
     *                                                          'iterate' => true / false,
     *                                                          'keyPath' => 'modelKeyPath' KeyPath of the object to use for this iteration.
     *                                                                                      Only used if 'iterate' == true, and then it's optional.
     *                                                                                      If 'iterate' == true, then uses the nth value from the 'value' array.
     *                                                                                      If 'iterate' == '#identifier#', then uses the identifierHashForObject() for the current object.
     *                                                                                      NOTE: if the array controller is in indexed mode, will use the index of the object.
     *                                                                                      If 'iterate' == false, then uses the 'value' for EVERY iteration.
     *                                                          'value' => mixed            Either an array or a primitive, depending on the setting of 'iterate'.
     *                                                          )
     *                                          // use this binding config (similar format as widget .config file)
     *                                          'bind' => array(
     *                                                          'instanceID' => 'outletName' or 
     *                                                                          '#module#' (to use the module) or
     *                                                                          '#custom# (to use object specified in 'custom' above) or
     *                                                                          '#current#' (to use the object of the current iteration)
     *                                                          'controllerKey' => 'controllerKey',
     *                                                          'modelKeyPath' => 'modelKeyPath',
     *                                                          'options' => array() // bindings options
     *                                                          )
     *                                      )
     *                  )
     *              This format lets you arbitrarily configure any property of any type of widget.
     *              For each property, you can choose to use the same 'value' for EVERY widget, or you can use a different value for each widget, the nth item in 'value' for the nth instance.
     * @return assoc_array An array of 'widgetID' => widget for all newly created widgets.
     */
    function createDynamicWidgets($arrayController, $parentForm, $widgetClass, $widgetBaseName, $useUniqueNames, $widgetValueOptions)
    {
        $this->createdWidgets = array();

        if (!class_exists($widgetClass)) throw( new Exception("There is no widget class '$widgetClass'.") );

        // is there a configured simpleBindKeyPath?
        if (!is_null($this->simpleBindKeyPath))
        {
            $widgetValueOptions['value'] = 
                array(
                        'bind' => array(
                            'instanceID' => '#current#',
                            'controllerKey' => '',
                            'modelKeyPath' => $this->simpleBindKeyPath
                            )
                     );
        }

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
            $widget = new $widgetClass($id, $this->page());
            if ($parentForm)
            {
                $parentForm->addChild($widget);
            }

            // add to our list
            $this->createdWidgets[] = $widget;
            
            // set up the name
            if (!$useUniqueNames)
            {
                $widget->setName($widgetBaseName);
            }

            WFLog::log("WFMatrix:: created $widgetClass id=$id name=" . $widget->name());

            // set up properties
            if ($this->formatter)
            {
                $widget->setFormatter($this->formatter);
            }
            foreach ($widgetValueOptions as $propName => $propInfo) {
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

                    WFLog::log("WFMatrix:: setting $propName to $customValue for $id", WFLog::TRACE_LOG);
                    $widget->setValueForKey($customValue, $propName);
                }
                // or are we using bindings
                if (isset($propInfo['bind']))
                {
                    $bindingInfo = $propInfo['bind'];

                    WFLog::log("WFMatrix:: Binding property '$propName' to {$bindingInfo['instanceID']} => {$bindingInfo['controllerKey']}::{$bindingInfo['modelKeyPath']}", WFLog::TRACE_LOG);

                    // determine object to bind to:
                    if (!isset($bindingInfo['instanceID'])) throw( new Exception("No instance id specified for binding property '{$propName}'.") );
                    $bindToObject = NULL;
                    if ($bindingInfo['instanceID'] == '#module#')
                    {
                        $bindToObject = $parentForm->page()->module();
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
                        $bindToObject = $parentForm->page()->outlet($bindingInfo['instanceID']);
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
                        print_r($bindingInfo);
                        throw($e);
                    }
                }
            }

            // have widget restore state, only if we've posted! otherwise it will grab improper state
            if ($parentForm and $parentForm->page()->submittedFormName())
            {
                $widget->restoreState();
            }

            $currentIndex++;
        }

        return $this->createdWidgets;
    }
}

?>
