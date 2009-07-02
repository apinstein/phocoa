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
 * Wrapper for YUILoader
 *
 * @todo Deal with filters.
 * @todo Document.
 * @todo Look at re-working the way we do YUIloader. They recommend only 1x per page; and it looks like the way we do it, you load ALL items once for each time a widget exists. Probably slows things down a lot.
 */
class WFYAHOO_yuiloader
{
    const YUI_VERSION = '2.7.0';

    static protected $_instance = NULL;

    protected $base;
    protected $required = array();
    protected $allowRollup = true;
    protected $combine = false;
    protected $comboBase = null;
    protected $loadOptional = false;
    protected $customModules = array();

    protected $hasRendered = false;

    private function __construct() 
    {
        $this->setBaseToLocal();
    }

    public function addModule($name, $type, $path, $fullpath, $requires, $optional, $after, $varName)
    {
        $params = array(
                                        'name'     => $name,
                                        'type'     => $type,
                                        'path'     => $path,
                                        'fullpath' => $fullpath,
                                        'requires' => $requires,
                                        'optional' => $optional,
                                        'after'    => $after,
                                        'varName'  => $varName
                                    );
        foreach (array_keys($params) as $k) {
            if ($params[$k] === NULL)
            {
                unset($params[$k]);
            }
        }
        $this->customModules[$name] = $params;
    }

    public static function sharedYuiLoader()
    {
        if (!self::$_instance)
        {
            self::$_instance = new WFYAHOO_yuiloader;
        }
        return self::$_instance;
    }

    public function getSetupJS()
    {
        $options = array();
        foreach (array('base', 'loadOptional', 'allowRollup', 'combine', 'comboBase') as $k) {
            if ($this->$k() === NULL) continue;
            $options[$k] = $this->$k();
        }
        if ($this->debug())
        {
            $options['filter'] = "DEBUG";
        }
        $optionsJSON = WFJSON::encode($options);

        return "<script>new PHOCOA.YUI({$optionsJSON});</script>"; 
    }

    public function yuiRequire($requires)
    {
        $modules = explode(',', $requires);
        foreach ($modules as $module) {
            $this->required[$module] = true;
        }
    }

    public function quotedRequired()
    {
        $a = array();
        foreach ($this->required as $mod => $unused) {
            $a[] = '"' . $mod . '"';
        }
        return $a;
    }

    public function combine()
    {
        return $this->combine;
    }

    public function setCombine($b)
    {
        $this->combine = $b;
        return $this;
    }

    public function comboBase()
    {
        return $this->comboBase;
    }

    public function setComboBase($b)
    {
        $this->comboBase = $b;
        return $this;
    }

    public function allowRollup()
    {
        return $this->allowRollup;
    }

    public function setAllowRollup($b)
    {
        $this->allowRollup = $b;
        return $this;
    }

    /**
     * Set the "base" URL for loading YUI assets. Path must end in '/'.
     *
     * If you want to override this, the best place is in the WFWebApplicationDelegate::initialize() method.
     *
     * NOTE the contrast: most PHOCOA paths end WITHOUT trailing '/', but YAHOO uses the opposite convention so we stick with their convention.
     *
     * @param string The Base URL path for YUI assets. Defaults to the local framework version.
     * @return object WFYAHOO_yuiloader For fluent interface.
     */
    public function setBase($path)
    {
        $this->base = $path;
        return $this;
    }

    /**
     * Set the base to the version of YUI bundled with PHOCOA.
     *
     * @return object WFYAHOO_yuiloader For fluent interface.
     */
    public function setBaseToLocal()
    {
        $this->base = $this->localYUI();
        return $this;
    }

    public function localYUI()
    {
        return WFWebApplication::webDirPath(WFWebApplication::WWW_DIR_FRAMEWORK) . '/yui/';
    }

    /**
     * Set the base to the version of YUI hosted on YUI.
     *
     * @return object WFYAHOO_yuiloader For fluent interface.
     */
    public function setBaseToYUIHosted()
    {
        $this->base = 'http://yui.yahooapis.com/' . WFYAHOO_yuiloader::YUI_VERSION . '/build/';
        return $this;
    }

    /**
     * Get the "base" URL for loading YUI assets. Paths end in '/'.
     *
     * NOTE the contrast: most PHOCOA paths end WITHOUT trailing '/', but YAHOO uses the opposite convention so we stick with their convention.
     *
     * @return string The Base URL path for YUI assets. Defaults to the local framework version.
     */
    public function base()
    {
        return $this->base;
    }

    public function debug()
    {
        return WFWebApplication::sharedWebApplication()->debug();
    }

    public function setLoadOptional($b)
    {
        $this->loadOptional = $b;
        return $this;
    }

    public function loadOptional()
    {
        return $this->loadOptional;
    }

    /**
     * 
     * @param string The callback is an anonymous javascript function (including the function definition syntax)
     */
    public function jsLoaderCode($callback = NULL)
    {
        if (count($this->required) == 0) return NULL;

        //if ($this->hasRendered) return NULL;
        $this->hasRendered = true;

        $customModules = NULL;
        $indent = 
"                             ";
        foreach ($this->customModules as $mod) {
            $customModules .= "\n{$indent}PHOCOA.YUILoader.yuiLoader.addModule({";
            foreach ($mod as $k => $v) {
                if (is_array($v) and count($v))
                {
                    $customModules .= "\n{$indent}  {$k}: ['" . join("','", $v) . "'],";
                }
                else
                {
                    $customModules .= "\n{$indent}  {$k}: '{$v}',";
                }
            }
            // strip last ,
            $customModules = substr($customModules, 0, -1);
            $customModules .= "\n{$indent}});\n";
        }

        return "
                     (function() {
                         {$customModules}
                         onSuccess = " . ($callback ? $callback : "null") . ";
                         PHOCOA.YUILoader.require([" . join(',', $this->quotedRequired()) . "], { 'onSuccess': onSuccess } );
                     })();
         ";
    }
}

/**
 * A YAHOO base class for our framework.
 * 
 * This class provides some base features for all Yahoo! YUI classes such as core js/css includes, etc.
 *
 * It's also useful as a stub if you're doing custom YUI coding; you can use a single {WFYAHOO} widget to make sure needed libs are loaded.
 *
 * NOTE: Core js/css include capabilities have been moved to the WFView base class since YUI is now "bundled" with PHOCOA and other PHOCOA widgets rely on YUI.
 *
 * @todo Also need to decide about phocoa.js and prototype.js; should these be included in the skin, or by the widgets that need them (I think this is tricky b/c then those widgets can't be added via AJAX since these base js files won't exist), or by modules that know they are using them?
 */
abstract class WFYAHOO extends WFWidget
{
    protected $initializeWaitsForID;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);

        $this->initializeWaitsForID = $this->id;
    }

    public function yuiloader()
    {
        return WFYAHOO_yuiloader::sharedYuiLoader();
    }

    /**
     * The initJS function is where YUI widgets perform their bootstrap/initialization.
     *
     * The YUI integration also includes some delegate methods for performing pre- and post- initialization tasks.
     *
     * These are the delegate method for YUI widget instantiation:
     * - PHOCOA.widgets.<widgetId>.yuiDelegate.widgetWillLoad()
     * - PHOCOA.widgets.<widgetId>.yuiDelegate.widgetDidLoad(obj) // obj is the YUI widget instance, also available from PHOCOA.runtime.getObject('<widgetId>')
     *
     * @param string The content of the YUI widget as a block, if needed. Some YUI widgets like the Container family need access to this in the bootstrap routines.
     * @return string The JS code to run to instantiate the YUI widget. This could should define PHOCOA.widgets.<id>.init(), which will be executed to load the widget.
     */
    abstract public function initJS($blockContent);

    function canPushValueBinding() { return false; }

    function jsValueForValue($value)
    {
        if ($value === NULL)
        {
            return 'null';
        }
        if (is_numeric($value))
        {
            return $value;
        }
        else if (is_bool($value) or $value == 'true' or $value == 'false')
        {
            if (is_bool($value))
            {
                return ($value ? 'true' : 'false');
            }
            else
            {
                return $value;
            }
        }
        else // string
        {
            return "'" . str_replace("'", "\\'", $value) . "'";
        }
    }
    function jsForSimplePropertyConfig($widgetVarName, $propertyName, $value)
    {
        $simpleJS = NULL;

        if ($value !== NULL)
        {
            $simpleJS = "{$widgetVarName}.{$propertyName} = ";
            if (is_numeric($value))
            {
                $simpleJS .= $value;
            }
            else if (is_bool($value) or $value == 'true' or $value == 'false')
            {
                if (is_bool($value))
                {
                    $simpleJS .= ($value ? 'true' : 'false');
                }
                else
                {
                    $simpleJS .= $value;
                }
            }
            else // string
            {
                $simpleJS .= "'" . str_replace("'", "\\'", $value) . "'";
            }
            $simpleJS .= ";\n";
        }

        return $simpleJS;
    }

    /**
     * IMPORTANT: The subclasses should get the base class's html, then add the needed code.
     * All subclasses need a DOM element of the ID of the widget so that the loading bootstrapping works correctly.
     */
    function render($blockContent = NULL)
    {
        if ($this->hidden)
        {
            return NULL;
        }
        else
        {
            // set up basic HTML
            $html = parent::render($blockContent);
            $initJSCode = $this->initJS($blockContent);
            if ($initJSCode)
            {
                $html .= $this->jsStartHTML() . $this->yuiloader()->jsLoaderCode(
                                                                            "function() {
    // alert('YUI deps loaded callback for \'{$this->id}\' widget');
    PHOCOA.namespace('widgets.{$this->id}.yuiDelegate');
    // let widget inject JS that depends on YUI libs, and define the init function
    {$initJSCode}

    YAHOO.util.Event.onContentReady('{$this->initializeWaitsForID}', function() {
        if (PHOCOA.widgets.{$this->id}.yuiDelegate.widgetWillLoad)
        {
            PHOCOA.widgets.{$this->id}.yuiDelegate.widgetWillLoad();
        }
        PHOCOA.widgets.{$this->id}.init();
        if (PHOCOA.widgets.{$this->id}.yuiDelegate.widgetDidLoad)
        {
            PHOCOA.widgets.{$this->id}.yuiDelegate.widgetDidLoad(PHOCOA.runtime.getObject('{$this->id}'));
        }
    });
};
"
                                                                            ) . $this->jsEndHTML();
            }
        }
        return $html;
    }
}

?>
