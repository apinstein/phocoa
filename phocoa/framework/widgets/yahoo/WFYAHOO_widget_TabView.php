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
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * 
 * <b>Optional:</b><br>
 * cacheData - boolean - Whether or not to cache the data (if using dataSrc)
 * dataSrc - string - The absolute URL of a page on this server to load for dynamic content. If you want to load http:// urls, you need to set up a proxy.
 * dataTimeout - integer - Number of ms to wait before "failing" a dynamic content load.
 * disabled - boolean - Disabled tabs cannot be activated.
 * label - string - The label of the tab. Can be HTML.
 * loadMethod - string - GET/POST. What dataSrc will be loaded with.
 */
class WFYAHOO_widget_Tab extends WFYAHOO
{
    protected $cacheData;
    protected $dataSrc;
    protected $dataTimeout;
    protected $disabled;
    protected $label;
    protected $loadMethod;

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

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);

        $this->yuiloader()->yuiRequire('tabview');
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

    function render($blockContent = NULL)
    {
        if ($blockContent === NULL) return NULL;
        if ($this->hidden)
        {
            return NULL;
        }
        else
        {
            $html = parent::render($blockContent);
            $html .= "\n<div id=\"{$this->id}\" class=\"yui-navset\">";
            $html .= "\n<ul class=\"yui-nav\">";
            foreach ($this->tabRenderOrder as $tabId => $tab) {
                $html .= "<li class=\"" . ($this->selectedTabId === $tabId ? ' selected' : NULL) . "\"><a href=\"#" . $tabId . "\"><em>" . $tab->label() . "</em></a></li>";
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
        PHOCOA.widgets.{$this->id}.init = function() {
            var tabView = new YAHOO.widget.TabView('{$this->id}', {";
        // set up tabview properties
        if ($this->orientation !== NULL)
        {
            $html .= "orientation: '{$this->orientation}'";
        }
        $html .= "});
            var tab;
            ";
        // set up individual tabs
        $configs = array('cacheData', 'dataSrc', 'dataTimeout', 'disabled', 'loadMethod');
        $i = 0;
        foreach ($this->tabRenderOrder as $tabId => $tab) {
            $html .= "
            tab = tabView.getTab(" . $i++ . ");\n";
            foreach ($configs as $config) {
                if ($tab->$config() !== NULL)
                {
                    $html .= "
            tab.set('{$config}', " . $this->jsValueForValue($tab->$config()) . ");";
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
