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
 * The WFTabContent is a helper class for the WFTabView.
 *
 * Each tab has an id, a label, and a tpl file.
 *
 * @ignore
 */
class WFTabContent extends WFObject
{
    protected $id;
    protected $label;
    protected $template;

    function __construct($id, $label, $tpl)
    {
        $this->id = $id;
        $this->label = $label;
        $this->template = $tpl;
    }

    function id()
    {
        return $this->id;
    }
    function label()
    {
        return $this->label;
    }

    function template()
    {
        return $this->template;
    }
}

/**
 * The WFTabView widget provides a tabbed-interface wrapper around several templates.
 * 
 * All templates must be designed for the same page; the sub-templates are simply chucks of what would normally be a single template.
 */
class WFTabView extends WFWidget
{
    /**
     * @var array Array of WFTabContent objects.
     */
    protected $tabs;
    /**
     * @var object WFTabContent The currently active WFTabContent object.
     */
    protected $activeTab;
    /**
     * @var string The ID of the tab to make active by default if there is no active tab.
     */
    protected $defaultTabID;
    /**
     * @var boolean TRUE to display the tabs in "onePage" mode, which means that all content of the tabs are part of a single page.
     *              In this case, all tab content views are rendered and put in divs. Only the "active" tab will be visible, and javascript is added
     *              to the page to help toggle between tabs when clicked.
     *
     *              OnePage mode is designed to break up parts of the same form into smaller chunks, but that should still be all submitted together.
     *              For design consistency, you should put the form's submit buttons OUTSIDE the tabbed view so users understand that all tabs are part
     *              of the same form.
     *
     *              The other mode, non-onePage mode, will actually load a different URI for each page. Each tab is considered its own stand-alone view.
     *              NOT YET IMPLEMENTED.
     */
    protected $onePageMode;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);

        $this->tabs = array();
        $this->activeTab = NULL;
        $this->onePageMode = true;
        $this->defaultTabID = NULL;
    }

    function canPushValueBinding()
    {
        return false;
    }

    /**
     * Restore the default tab ID to the one that was selected by the user.
     */
    function restoreState()
    {
        if (!empty($_REQUEST[$this->id]))
        {
            $this->setDefaultTabID($_REQUEST[$this->id]);
        }
    }

    /**
     *  Set the default tab ID, but only if there is no default already set.
     *
     *  This is the call modules should use to set the default tab. Using this function will ensure that the default set by the module
     *  doesn't overwrite the currently selected tab being restored when a form is submitted.
     *
     *  @param string The ID of the default tab. Note that the tab does not have to be created yet.
     */
    function setDefaultTabIDIfNoDefault($defaultTabID)
    {
        if ($this->defaultTabID == NULL)
        {
            $this->setDefaultTabID($defaultTabID);
        }
    }

    /**
     *  Set the default tab ID.
     *
     *  @param string The ID of the default tab. Note that the tab does not have to be created yet.
     */
    function setDefaultTabID($defaultTabID)
    {
        $this->defaultTabID = $defaultTabID;
    }

    /**
     *  Add a new tab to the WFTabView.
     *
     *  Modules must use this method for each tab that should be displayed in the interface.
     *
     *  @param string A unique ID for the tab. Can only contain characters A-z0-9_.
     *  @param string The name of the tab, to be used as the tab's label.
     *  @param string The name of the .tpl file that should be used for this tab.
     *  @throws If a non-unique or invalid ID is passed.
     */
    function addTab($id, $label, $tpl)
    {
        $tpl = $this->page->module()->pathToModule() . '/' . $tpl;
        if (isset($this->tabs[$id])) throw( new Exception("Tab IDs must be unique! Duplicate ID '$id' passed to addTab().") );
        $newTabContent = new WFTabContent($id, $label, $tpl);
        $this->tabs[$newTabContent->id()] = $newTabContent;
        $this->setDefaultTabIDIfNoDefault($newTabContent->id());
    }

    /**
     *  Remove the passed tab from the TabView.
     *
     *  @param string The id of the tab to remove.
     *  @param string The id of the tab that should become the active tab if the removed tab happens to be active.
     *  @throws Exception if $newActiveTabID is not a valid tab.
     */
    function removeTab($id, $newActiveTabID)
    {
        if ($this->activeTabID() == $id)
        {
            $this->setActiveTabID($newActiveTabID);
        }
        unset($this->tabs[$id]);
    }

    /**
     *  Set the ID of the active tab.
     *
     *  @param string The ID for the active tab. This tab MUST exist.
     *  @throws Exception if the passed ID does not yet exist.
     */
    function setActiveTabID($id)
    {
        if (!isset($this->tabs[$id])) throw( new Exception("Tab ID '$id' cannot be made the active tab because it does not exist.") );
        $this->activeTab = $this->tabs[$id];
    }

    /**
     *  Get a list of all tabs.
     *
     *  @return array An array of WFTabContent objects.
     */
    function tabs()
    {
        return $this->tabs;
    }

    /**
     *  Get the ID of the active tab.
     *
     *  @return string The ID of the active tab. Could be NULL.
     */
    function activeTabID()
    {
        if (!$this->activeTab) return NULL;
        return $this->activeTab->id();
    }

    /**
     *  Get the label of the active tab.
     *
     *  @return string The label of the active tab. Could be NULL.
     */
    function activeTabLabel()
    {
        if (!$this->activeTab) return NULL;
        return $this->activeTab->label();
    }

    /**
     *  Get the active tab.
     *
     *  @return object WFTabContent The active tab object. Could be NULL if no active tab.
     */
    function activeTab()
    {
        return $this->activeTab;
    }

    /**
     *  Is the passed tab object the active tab?
     *
     *  @param object WFTabContent The tab object to test.
     *  @return boolean TRUE if the passed tab is the currently active tab, FALSE otherwise.
     */
    function isActiveTab($tab)
    {
        if ($this->activeTab === $tab)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    function render($blockContent = NULL)
    {
        if ($this->activeTab == NULL)
        {
            $this->setActiveTabID($this->defaultTabID);
        }

        $template = $this->page->template();
        $template->assign('__tabView', $this);

        if ($this->onePageMode)
        {
            $tabTplFile = WFWebApplication::appDirPath(WFWebApplication::DIR_SMARTY) . '/onepage_tabs.tpl';
        }
        else
        {
            $tabTplFile = WFWebApplication::appDirPath(WFWebApplication::DIR_SMARTY) . '/multipage_tabs.tpl';
            throw( new Exception("Non-onePage mode not yet implemented.") );
        }

        return $template->fetch($tabTplFile);

    }
}

?>
