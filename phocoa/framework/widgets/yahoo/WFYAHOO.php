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
 */
class WFYAHOO_yuiloader
{
    static protected $_instance = NULL;

    protected $base;
    protected $required = array();
    protected $allowRollup = true;
    protected $loadOptional = true; // needs to be TRUE for 2.3.1, otherwise YUILoader doesn't sort optional but explicitly included dependencies
    protected $debug = false;

    protected $hasRendered = false;

    private function __construct() 
    {
        $this->base = WFYAHOO::yuiPath() . '/';
    }

    public static function sharedYuiLoader()
    {
        if (!self::$_instance)
        {
            self::$_instance = new WFYAHOO_yuiloader;
        }
        return self::$_instance;
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

    public function allowRollup()
    {
        return $this->allowRollup;
    }

    public function setAllowRollup($b)
    {
        $this->allowRollup = $b;
    }

    public function setBase($path)
    {
        $this->base = $path;
    }

    public function base()
    {
        return $this->base;
    }

    public function setDebug($path)
    {
        $this->debug = $path;
    }

    public function debug()
    {
        return $this->debug;
    }

    public function setLoadOptional($b)
    {
        $this->loadOptional = $b;
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

        return "
                     (function() {
                         PHOCOA.importJS('" . WFView::yuiPath() . "/yuiloader/yuiloader-beta-" . ($this->debug ? 'debug' : 'min') . ".js', 'YAHOO');
                         var yl = new YAHOO.util.YUILoader();
                         " . ($this->debug() ? 'yl.filter = "DEBUG";' : NULL) . "
                         " . ($this->base() ? 'yl.base = "' . $this->base() . '";' : NULL) . "
                         yl.require(" . join(',', $this->quotedRequired()) . ");
                         yl.allowRollup = " . ($this->allowRollup() ? 'true' : 'false') . ";
                         yl.loadOptional = " . ($this->loadOptional() ? 'true' : 'false') . ";
                         yl.onSuccess = {$callback};
                         yl.insert();
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
     *
     * @param string The content of the YUI widget as a block, if needed. Some YUI widgets like the Container family need access to this in the bootstrap routines.
     * @return string The JS code to run to instantiate the YUI widget.
     */
    abstract public function initJS($blockContent);

    function canPushValueBinding() { return false; }

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
            $html .= $this->jsStartHTML() . $this->yuiloader()->jsLoaderCode(
                                                                            "function() {
    PHOCOA.namespace('widgets.{$this->id}.yuiDelegate');
    // let widget inject JS that depends on YUI libs, and define the init function
    " . $this->initJS($blockContent) . "

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
}
"
                                                                            ) . $this->jsEndHTML();
        }
        return $html;
    }
}

?>
