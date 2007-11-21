<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package UI
 * @subpackage Views
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/**
 * The base "view" class. Views are the components that make up a WFPage. Each page has a root view, and then is made up of various subviews in
 * a view hierarchy. There is also a well-defined class hierarchy for WFView that contains all components that are used in creating a web page.
 *
 * <pre>WFView - Views are just generalized components for displaying on screen. NSView is abstract.
 *  |
 *  `-WFTabView - A tabbed view containing other pages. (NOT YET IMPLEMENTED)
 *  `-WFBoxView - A simple box containing content from another page. (NOT YET IMPLEMENTED)
 *  |
 *  `-WFWidget - A specialized view for dealing with displaying editale values or actionable controls.
 *      |                   Also abstract. Adds data get/set methods.
 *      |                   Add support for editable, error tracking, and formatters.
 *      `-WFForm - A Form object.
 *      `-WFTextField - A textfield item.
 *      `-WFCheckbox - A checkbox item.
 *      `-WFLabel - For displaying uneditable text, but can use bindings and formatters.
 *      (etc)</pre>
 *
 * Since we're a web framework, all views eventually know how to render themselves into HTML.
 * 
 * Views have no maintained state. Anything that needs to maintain state should be a {@link WFWidget} subclass.
 * 
 * WFView contains the basic infrastructure support the YUI library, which is PHOCOA's Javascript and AJAX layer.
 */
abstract class WFView extends WFObject
{
    /**
      * @var string The ID of the view. Used both internally {@link outlet} and externally as in the HTML id.
      */
    protected $id;
    /**
      * @var object WFView The parent of this view, or NULL if there is no parent.
      */
    protected $parent;
    /**
      * @var assoc_array Placeholder for additional HTML name/value pairs.
      */
    protected $children;
    /**
      * @var object The WFPage object that contains this view.
      */
    protected $page;
    /**
     * @var boolean Enabled. TRUE if the control is enabled (ie responds to input), FALSE otherwise.
     */
    protected $enabled;
    /**
     * @var assoc_array An array whose keys are the paths to javascript files to be included. The value has 3 keys; url, globalNamespace, localNamespace.
     */
    protected $jsImports;
    /**
     * @var assoc_array An array whose keys are the paths to css files to be included.
     */
    protected $cssImports;
    /**
     * @var boolean TRUE to include JS and CSS files in a less-efficient, but more-debuggable way. Basically, will include with link/script tags if true, otherwise will use
     * javascript to include the files with the PHOCOA.importCSS() javascript function.
     */
    protected $importInHead;
    /**
     * @var array A list of the JavaScript actions for the widget. Mananged centrally; used by subclasses to allow actions to be attached. DEPRECATE FOR YAHOO JS STUFF!
     */
    protected $jsActions;

    protected $jsEvents;

    /**
      * Constructor.
      *
      * Sets up the smarty object for this module.
      */
    function __construct($id, $page)
    {
        parent::__construct();

        if (is_null($id)) throw( new Exception("id required for new " . get_class($this) . '.') );
        if (!($page instanceof WFPage)) throw( new Exception("page must be a WFPage.") );

        $this->id = $id;
        $this->enabled = true;
        $this->children = array();
        $this->parent = NULL;
        $this->page = $page;
        $this->setId($id);
        $this->jsActions = array();
        $this->jsEvents = array();

        // js/css import infrastructure
        $this->importInHead = false;
        $this->jsImports = array();
        $this->cssImports = array();
    }

    public function setListener($event)
    {
        if ( !($event instanceof WFEvent) ) throw( new WFException("Event must be a WFEvent.") );
        $event->setWidget($this);   // point back to ourselves

        $eventName = $event->name();
        //if (isset($this->jsEvents[$eventName])) throw ( new WFException("There is already a listener for event '{$eventName}' on {$this->id}. Having multiple event handlers isn't a good idea because you can't guarantee the order in which the handlers are called.") );

        $this->jsEvents[$eventName] = $event;
    }

    public function getListenerJS()
    {
        $script = NULL;
        foreach ($this->jsEvents as $eventName => $event) {
            $script .= $event->action()->jsSetup();
        }
        return $script;
    }

    public static function yuiPath()
    {
        return WFWebApplication::webDirPath(WFWebApplication::WWW_DIR_FRAMEWORK) . '/yui';
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array('enabled'));
    }

    /**
     *  Create a clone of the WFView with a new ID.
     *
     *  This will give you a copy of WFView that has been registered with the page.
     *
     *  @param 
     *  @return
     *  @throws
     */
    function cloneWithID($id)
    {
        $newView = clone($this);
        $newView->setId($id);
        $newView->setName($id);
        return $newView;
    }

    /**
     *  Set the unique ID of this widget.
     *
     *  ID's are unique within a page; this function will tell the page of the new instance ID so it can be registered.
     *
     *  @param string The ID of the WFView instance.
     */
    function setId($id)
    {
        $this->id = $id;
        // add the widget to the page's widget list
        $this->page->addInstance($this->id, $this);
    }

    /**
      * Get the id of this view. All id's on a single page are unique.
      *
      * @return string The unique id of this view.
      */
    function id()
    {
        return $this->id;
    }

    /**
     *  Import a JS source file.
     *
     *  In *debug* mode, this will be imported by adding a <script> tag to the head element, for improved debug-ability.
     *  In normal mode, this will be imported by using an AJAX request to synchronously download the source file, then eval() it.
     *
     *  The eval() method has improved flexibility, because it can be used even on widget code loaded from AJAX calls with
     *  prototype's Element.update + evalScripts: true, thus allowing the loading of JS files only exactly when needed.
     *
     *  However, it has the downside of requiring that the js code is eval-clean (that is, unless you assign your functions to variables they will not "exist").
     *
     *  @param string The JS file path to include.
     *  @param string When importing javascript libraries, the global variable name you want the library loaded as. Default NULL (not a library).
     *  @param string When importing javascript libraries, the local variable name that contains the library in included code. Default to same as globalNamespace.
     */
    protected function importJS($path, $globalNamespace = NULL, $localNamespace = NULL)
    {
        $this->jsImports[$path]['url'] = $path;
        $this->jsImports[$path]['globalNamespace'] = $globalNamespace;
        $this->jsImports[$path]['localNamespace'] = $localNamespace;
    }

    /**
     *  Import a CSS source file.
     *
     *  In *debug* mode, this will be imported by adding a <link> tag to the head element, for improved debug-ability.
     *  In normal mode, this will be imported by using an AJAX request to synchronously download the source file, then eval() it.
     *
     *  The advantage of the latter is that CSS files can be programmatically added via javascript code, even from code that is returned
     *  from AJAX calls.
     *
     *  @param string The css file path to include.
     */
    protected function importCSS($path)
    {
        $this->cssImports[$path] = $path;
    }

    private function getImportJS()
    {
        if (empty($this->jsImports)) return;

        $script = $this->jsStartHTML();
        if ($this->importInHead)
        {
            // no namespace issues when importInHead
            foreach ($this->jsImports as $path => $nothing) {
                $this->page->module()->invocation()->rootSkin()->addHeadString("<script type=\"text/javascript\" src=\"{$path}\" ></script>");
            }
            $script .= "// importInHead = true; all js includes in head section";
        }
        else
        {
            foreach ($this->jsImports as $path => $jsInfo) {
                $script .= "PHOCOA.importJS('{$path}'";
                if ($jsInfo['globalNamespace'])
                {
                    $script .= ", '{$jsInfo['globalNamespace']}'";
                }
                if ($jsInfo['localNamespace'])
                {
                    $script .= ", '{$jsInfo['localNamespace']}'";
                }
                $script .= ");\n";
            }
        }
        $script .= $this->jsEndHTML();
        return $script;
    }

    private function getImportCSS()
    {
        if (empty($this->cssImports)) return;

        $script = $this->jsStartHTML();
        if ($this->importInHead)
        {
            foreach ($this->cssImports as $path => $nothing) {
                $this->page->module()->invocation()->rootSkin()->addHeadString("<link rel=\"stylesheet\" type=\"text/css\" href=\"{$path}\" />");
            }
            $script .= "// importInHead = true; all css includes in head section";
        }
        else
        {
            foreach ($this->cssImports as $path => $nothing) {
                $script .= "PHOCOA.importCSS('{$path}');\n";
            }
        }
        $script .= $this->jsEndHTML();
        return $script;
    }

    /**
     *  Helper function to get the proper "start" block for using Javascript in a web page.
     *
     *  NOTE: The conventions used here are from Douglas Crockford's "Theory of DOM" video.
     *  - Comments not needed since Mosaic and Netscape 1.
     *  - type attribute IGNORED. They only trust mime-type header on actual file.
     *  - language attribute IGNORED.
     *
     *  @return string HTML code for the "start" block of a JS script section.
     */
    function jsStartHTML()
    {
        return "\n<script>\n";
    }

    /**
     *  Helper function to get the proper "end" block for using Javascript in a web page.
     *
     *  @return string HTML code for the "end" block of a JS script section.
     *  @see jsStartHTML()
     */
    function jsEndHTML()
    {
        return "\n</script>\n";
    }

    /**
     *  Set the "onBlur" JavaScript code for the view.
     *
     *  @param string JavaScript code.
     */
    function setJSonBlur($js)
    {
        $this->jsActions['onBlur'] = $js;
    }

    /**
     *  Set the "onClick" JavaScript code for the view.
     *
     *  @param string JavaScript code.
     */
    function setJSonClick($js)
    {
        $this->jsActions['onClick'] = $js;
    }

    /**
     *  Get the HTML code for all JavaScript actions.
     *
     *  @return string The HTML code (space at beginning, no space at end) for use in attaching JavaScript actions to views.
     */
    function getJSActions()
    {
        $jsHTML = '';
        foreach ($this->jsActions as $jsAction => $jsCode) {
            $jsHTML .= " {$jsAction}=\"{$jsCode}\"";
        }

        return $jsHTML;
    }
    
    /**
     *  Is the view enabled? 
     *
     *  @return boolean
     */
    function enabled()
    {
        return $this->enabled;
    }

    /**
     *  Set whether or not the view is enabled. Enabled views respond to the user and accept input (widgets).
     *
     *  @param boolean
     */
    function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     *  Get a relative URL path to the public www dir for graphics for this widget.
     *
     *  @return string The URL to directory containing www items for this widget.
     */
    function getWidgetWWWDir()
    {
        return WFWebApplication::webDirPath(WFWebApplication::WWW_DIR_FRAMEWORK) . '/widgets/' . get_class($this);
    }

    /**
     *  Get an absolute filesystem path to the www dir for graphics for this widget in the current project.
     *
     *  @return string The path to directory containing www items for this widget.
     */
    function getWidgetDir()
    {
        return WFWebApplication::appDirPath(WFWebApplication::DIR_WWW) . '/framework/widgets/' . get_class($this);
    }
    
    /**
      * Get the {@link WFPage} object that this view belongs to.
      */
    function page()
    {
        return $this->page;
    }

    /**
     *  Set the parent view for this view.
     *
     *  @param object WFView The parent object.
     */
    function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     *  Get the parent view for this view.
     *
     *  @return object WFView The parent object.
     */
    function parent()
    {
        return $this->parent;
    }

    /**
     *  Remove a child view from the view hierarchy.
     *
     *  @param object WFView The view object to remove as a child of this view.
     *  @return
     *  @throws
     */
    function removeChild(WFView $view)
    {
        if (!isset($this->children[$view->id()])) throw( new Exception("The view id: '" . $view->id() . "' is not a child of this view.") );
        unset($this->children[$view->id()]);
    }
    /**
      * Add a child view to this view.
      *
      * @param object A WFView object to add.
      */
    function addChild(WFView $view)
    {
        $this->children[$view->id()] = $view;
        $view->setParent($this);
    }
    /**
      * Get all child views of this view.
      *
      * @return array An array of WFView objects.
      */
    function children()
    {
        return $this->children;
    }

    /**
      * Render the view into HTML.
      *
      * Subclasses need to start their output with the result of the super's render() method.
      *
      * @param string $blockContent For block views (ie have open and close tags)r, the ready-to-use HTML content that goes inside this view.
      *                             For non-block views (ie single tag only) will always be null.
      * @return string The final HTML output for the view.
      */
    function render($blockContent = NULL)
    {
        return $this->getImportJS() . $this->getImportCSS();
    }

    /**
     * After WFPage has completed the loading of all config for all widgets, it will call this function on each widget.
     *
     * This function is particularly useful for widgets that have children. When called, the widget can be sure that all instances and config of its children 
     * have been loaded from the .config file.
     */
    function allConfigFinishedLoading() {}
}

?>
