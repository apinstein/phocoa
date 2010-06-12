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
 * A wrapper for the individual tab instances.
 *
 * Content can be static or dynamically loaded.
 *
 * WFYAHOO_widget_Tab is a block element. Use WFViewBlock to use a tab in your template. The block content is the default content for the tab.
 *
 * HINT: If you have want to keep the current tab active when a form is submitted, add a hidden field to that form whose "name" is the ID of the WFYAHOO_widget_TabView
 * and whose value is the ID of the WFYAHOO_widget_Tab that the form is on.
 *
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * 
 * <b>Optional:</b><br>
 * {@link WFYAHOO_widget_Tab::$cacheData cacheData}
 * {@link WFYAHOO_widget_Tab::$dataSrc dataSrc}
 * {@link WFYAHOO_widget_Tab::$dataTimeout dataTimeout}
 * {@link WFYAHOO_widget_Tab::$disabled disabled}
 * {@link WFYAHOO_widget_Tab::$label label}
 * {@link WFYAHOO_widget_Tab::$loadMethod loadMethod}
 * {@link WFYAHOO_widget_Tab::$preventAbandondedForm preventAbandondedForm}
 */
class WFYAHOO_widget_Tab extends WFYAHOO
{
    // YUI properties
    /**
     * @var boolean Whether or not to cache the data (if using dataSrc)
     */
    protected $cacheData;
    /**
     * @var string The absolute URL of a page on this server to load for dynamic content. If you want to load http:// urls, you need to set up a proxy.
     */
    protected $dataSrc;
    /**
     * @var integer Number of ms to wait before "failing" a dynamic content load.
     */
    protected $dataTimeout;
    /**
     * @var boolean Disabled tabs cannot be activated.
     */
    protected $disabled;
    /**
     * @var string The label of the tab. Can be HTML.
     */
    protected $label;
    /**
     * @var string GET/POST. What dataSrc will be loaded with.
     */
    protected $loadMethod;

    // phocoa features
    /**
     * @var string The ID of a form appearing on this tab that you want to prevent people from accidentally leaving without saving. Default: NULL
     *             If you put an id in here, a modal dialog will be displayed if the form is dirty and someone tries to switch to another tab.
     *             They will be allowed to save, discard changes, or cancel.
     */
    protected $preventAbandondedForm;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);

        $this->cacheData = NULL;
        $this->dataSrc = NULL;
        $this->dataTimeout = NULL;
        $this->disabled = NULL;
        $this->label = $id;
        $this->loadMethod = NULL;

        $this->preventAbandondedForm = NULL;
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
                'cacheData' => array('true','false'),
                'dataSrc',
                'dataTimeout',
                'disabled',
                'label',
                'loadMethod',
                'preventAbandondedForm'
            ));
    }

    function cacheData()
    {
        return $this->cacheData;
    }
    function dataSrc()
    {
        return $this->dataSrc;
    }
    function dataTimeout()
    {
        return $this->dataTimeout;
    }
    function disabled()
    {
        return $this->disabled;
    }
    function label()
    {
        return $this->label;
    }
    function loadMethod()
    {
        return $this->loadMethod;
    }
    function preventAbandondedForm()
    {
        return $this->preventAbandondedForm;
    }
    function initJS($blockContent)
    {
        return NULL;
    }
    function render($blockContent = NULL)
    {
        if (!($this->parent() instanceof WFYAHOO_widget_TabView)) throw( new WFException("WFYAHOO_widget_Tab must have a WFYAHOO_widget_TabView as a parent.") );

        if ($blockContent === NULL) return NULL;
        if ($this->hidden)
        {
            return NULL;
        }
        else
        {
            // tell the tabview parent that we're drawing now so it can keep track of the order of its kids
            $this->parent()->tabDidRender($this);
            // include connection manager if needed
            if ($this->dataSrc !== NULL)
            {
                $this->yuiloader()->yuiRequire('connection');
            }
            if ($this->preventAbandondedForm())
            {
                $this->yuiloader()->yuiRequire('container');
            }
            // render the tab's content block
            $html = parent::render($blockContent);
            $html .= "\n<div id=\"{$this->id}\">{$blockContent}</div>";
            return $html;
        }
    }
}

/**
 * A YAHOO TabView widget for our framework. This widget allows you to easily create tabbed content.
 *
 * To use tabview, simply create a WFYAHOO_widget_TabView with WFYAHOO_widget_Tab for children. PHOCOA takes care of the rest.
 *
 * WFYAHOO_widget_TabView is a block element. Use WFViewBlock to use a tabview in your template. All WFYAHOO_widget_Tab instancs should be inside the block, in the order you with them to appear.
 *
 * Example:
 * <code>
 * {WFViewBlock id="myTabs"}
 *     {WFViewBlock id="tab1"}Tab 1 content goes here{/WFViewBlock}
 *     {WFViewBlock id="tab2"}Tab 2 content goes here{/WFViewBlock}
 * {/WFViewBlock}
 * </code>
 *
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * 
 * <b>Optional:</b><br>
 * selectedTabId - The ID of the tab that should be selected by default.
 * orientation - One of top, left, right, or bottom.
 */
class WFYAHOO_widget_TabView extends WFYAHOO
{
    protected $selectedTabId = NULL;
    protected $tabRenderOrder = array();

    // YUI Tab Config Properties
    protected $orientation = NULL;

    protected $hideWhileLoading = false;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);

        $this->yuiloader()->yuiRequire('tabview');
    }

    function allConfigFinishedLoading()
    {
        if (isset($_REQUEST[$this->id]))
        {
            $this->setSelectedTabId($_REQUEST[$this->id]);
        }
    }

    function tabDidRender($tab)
    {
        $tabId = $tab->id();
        $this->tabRenderOrder[$tabId] = $tab;
    }

    function setupExposedBindings()
    {
        $myBindings = parent::setupExposedBindings();
        return $myBindings;
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            ));
    }

    function setSelectedTabId($tabId)
    {
        $this->selectedTabId = $tabId;
    }

    function addChild($newChild)
    {
        parent::addChild($newChild);
        // make sure one tab is always selected
        if (!$this->selectedTabId and ($newChild instanceof WFYAHOO_widget_Tab))
        {
            $this->selectedTabId = $newChild->id();
        }
    }

    function getSelectedTabId()
    {
        if (!isset($this->tabRenderOrder[$this->selectedTabId]))
        {
            $this->selectedTabId = array_shift(array_keys($this->tabRenderOrder));
        }
        return $this->selectedTabId;
    }

    function render($blockContent = NULL)
    {
        if ($blockContent === NULL) return NULL;
        if ($this->hidden)
        {
            return NULL;
        }
        else
        {
            $initialStyle = $this->hideWhileLoading ? 'style="display:none;"' : NULL;
            $html = parent::render($blockContent);
            $html .= "\n<div id=\"{$this->id}\" class=\"yui-navset\" {$initialStyle}>";
            $html .= "\n<ul class=\"yui-nav\">";
            foreach ($this->tabRenderOrder as $tabId => $tab) {
                $html .= "<li class=\"" . ($this->getSelectedTabId() === $tabId ? ' selected' : NULL) . "\"><a href=\"#" . $tabId . "\"><em>" . $tab->label() . "</em></a></li>";
            }
            $html .= "\n</ul>";
            $html .= "\n<div class=\"yui-content\">{$blockContent}</div>";
            $html .= "\n</div>";
            return $html;
        }
    }
    
    function initJS($blockContent)
    {
        if ($blockContent === NULL) return NULL;
        $html = "
        PHOCOA.widgets.{$this->id}.preventAbandondedFormsTabs = {};
        PHOCOA.widgets.{$this->id}.init = function() {
            var tabView = new YAHOO.widget.TabView('{$this->id}', {";
        // set up tabview properties
        if ($this->orientation !== NULL)
        {
            $html .= "orientation: '{$this->orientation}'";
        }
        $html .= "});";
        if ($this->hideWhileLoading)
        {
            $html .= "
            YAHOO.util.Dom.setStyle('{$this->id}', 'display', 'block');
            ";
        }

        $html .= "
            var tab;
            ";
        // set up individual tabs
        $configs = array('cacheData', 'dataSrc', 'dataTimeout', 'disabled', 'loadMethod', 'preventAbandondedForm');
        $i = 0;
        foreach ($this->tabRenderOrder as $tabId => $tab) {
            $needTabVarInJS = true;
            foreach ($configs as $config) {
                if ($tab->$config() !== NULL)
                {
                    if ($needTabVarInJS)
                    {
                        $html .= "
            tab = tabView.getTab(" . $i++ . ");\n";
                    $needTabVarInJS = false;
                    }
                    switch ($config) {
                        case 'cacheData':
                        case 'dataSrc':
                        case 'dataTimeout':
                        case 'disabled':
                        case 'loadMethod':
                            $html .= "            tab.set('{$config}', " . $this->jsValueForValue($tab->$config()) . ");\n";
                            break;
                        case 'preventAbandondedForm':
                            if ($tab->preventAbandondedForm())
                            {
                                // beforeActiveIndexChange, beforeActiveTabChange. return false to cancel
                                $html .= "
            YAHOO.util.Event.onContentReady('" . $tab->preventAbandondedForm() ."', function(e) {
                PHOCOA.widgets.{$this->id}.preventAbandondedFormsTabs." . $tab->id() . " = \$H(\$('" . $tab->preventAbandondedForm() . "').serialize(true)).toJSON();
                tabView.addListener('beforeActiveTabChange', function(o) {
                                    var losingTabId = o.prevValue.get('contentEl').id;
                                    var theForm = \$('" . $tab->preventAbandondedForm() . "');
                                    if (theForm && typeof PHOCOA.widgets.{$this->id}.preventAbandondedFormsTabs[losingTabId] !== 'undefined' && \$H(theForm.serialize(true)).toJSON() !== PHOCOA.widgets.{$this->id}.preventAbandondedFormsTabs[losingTabId])
                                    {
                                        var handleSave = function() {
                                            this.hide();
                                            theForm.submit();
                                        };
                                        var handleDontSave = function() {
                                            this.hide();
                                            theForm.reset();
                                            // also fix hidden elements
                                            var originalData = PHOCOA.widgets.{$this->id}.preventAbandondedFormsTabs[losingTabId].evalJSON();
                                            theForm.select('input[type=\"hidden\"]').each( function(hidEl) {
                                                if (hidEl.id)
                                                {
                                                    hidEl.value = originalData[hidEl.id];
                                                }
                                            });
                                            tabView.set('activeTab', o.newValue);
                                        };
                                        var handleCancel = function() {
                                            this.hide();
                                        };
                                        var confirmDialog = new YAHOO.widget.SimpleDialog('dlg', { 
                                                                                            //effect:{effect:YAHOO.widget.ContainerEffect.FADE, duration:0.25}, 
                                                                                            fixedcenter:true,
                                                                                            width: '20em',
                                                                                            modal:true,
                                                                                            visible:false,
                                                                                            draggable:false,
                                                                                            close: false,
                                                                                            icon: YAHOO.widget.SimpleDialog.ICON_WARN,
                                                                                            buttons: [
                                                                                                        { text:'Save', handler: handleSave },
                                                                                                        { text:'Don\'t Save', handler: handleDontSave },
                                                                                                        { text:'Cancel', handler: handleCancel, isDefault: true }
                                                                                            ]
                                                                                          });
                                        confirmDialog.setHeader('Warning: Unsaved data!');
                                        confirmDialog.setBody('You are navigating away from a tab that has unsaved changes. What do you want to do with that unsaved data?');
                                        confirmDialog.render(document.body);
                                        confirmDialog.show();
                                        return false;   // don't switch tabs; we'll go to the clicked tab as needed in callbacks
                                    }
                                });
            });";
                            }
                            break;
                    }
                }
            }
        }
        $html .= "
            PHOCOA.runtime.addObject(tabView, '{$this->id}');
        };
        ";

        return $html;
    }

    function canPushValueBinding() { return false; }
}

?>
