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
 * A YAHOO Menu widget for our framework. This widget allows you to easily create vertical or horizontal multi-level menus.
 * 
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * menuItems - An array of objects implementing WFMenuItem
 * 
 * <b>Optional:</b><br>
 * horizontal - TRUE to make this a menu bar. FALSE for a vertical set of menu items. Default: TRUE.
 */
class WFYAHOO_widget_Menu extends WFYAHOO
{
    protected $menuItems;
    protected $horizontal;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->menuItems = array();
        $this->horizontal = true;

        $this->yuiloader()->yuiRequire('menu');
    }

    /**
     * @param array An array of objects conforming to WFMenuItem.
     */
    function setMenuItems($items)
    {
        $this->menuItems = $items;
    }
    function setMenuItemsNestedArray($items)
    {
        $this->setMenuItems( WFMenuTree::nestedArrayToMenuTree($items) );
    }
    function setMenuItemsMenuPath($items)
    {
        $this->setMenuItems( WFMenuTree::menuTreeBuildingToMenuTree($items) );
    }

    function setHorizontal($h)
    {
        $this->horizontal = $h;
    }

    function setupExposedBindings()
    {
        $myBindings = parent::setupExposedBindings();
        $newValBinding = new WFBindingSetup('menuItems', 'The menu items for the menu.');
        $newValBinding->setReadOnly(true);
        $myBindings[] = $newValBinding;
        $newValBinding = new WFBindingSetup('menuItemsNestedArray', 'The menu items, in nested associative array format. Will be converted through WFMenuTree::nestedArrayToMenuTree().');
        $newValBinding->setReadOnly(true);
        $myBindings[] = $newValBinding;
        $newValBinding = new WFBindingSetup('menuItemsMenuPath', 'The menu items, as an array of objects imlpementing WFMenuTreeBuilding. Will be converted through WFMenuTree::menuTreeBuildingToMenuTree().');
        $newValBinding->setReadOnly(true);
        $myBindings[] = $newValBinding;
        return $myBindings;
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            'horizontal' => array('true', 'false')
            ));
    }

    function render($blockContent = NULL)
    {
        if ($this->hidden or count($this->menuItems) == 0)
        {
            return NULL;
        }
        else
        {
            $html = parent::render($blockContent);

            $html .= "\n<div id=\"{$this->id}\" class=\"" . ($this->horizontal ? 'yuimenubar yuimenubarnav' : 'yuimenu')  . "\"><div class=\"bd\"><ul>\n";
            foreach ($this->menuItems as $menuItem) {
                $html .= $this->menuItemToHTML($menuItem);
            }
            $html .= "\n</ul></div></div>\n";
            return $html;
        }
    }
    
    function initJS($blockContent)
    {   
        // quickly hide it then menu-ify it; need it shown originally for SEO
        $html = "
        PHOCOA.widgets.{$this->id}.init = function() {
            $('{$this->id}').hide();
            var oMenu = new YAHOO.widget." . ($this->horizontal ? 'MenuBar' : 'Menu') . "('{$this->id}', { position: 'static', autosubmenudisplay: true, hidedelay: 750, showdelay: 50, lazyload: true });
            oMenu.render();
            oMenu.show();
        };
        ";

        return $html;
    }

    // internal helper recursive function to output tree-form menu items.
    function menuItemToHTML($item, $root = true)
    {
        $itemclass = ($this->horizontal && $root) ? 'yuimenubaritem' : 'yuimenuitem';
        $itemlabelclass = ($this->horizontal && $root) ? 'yuimenubaritemlabel' : 'yuimenuitemlabel';
        $html = "\n<li class=\"{$itemclass}\"";
        if ($item->toolTip())
        {
            $html .= " title=\"" . $item->toolTip() . "\" ";
        }
        $html .= ">\n";
        
        if ($item->icon())
        {
            $html .= "<img src=\"" . $this->icon() . "\" />";
        }
        
        $submenu = NULL;
        if ($item->link())
        {
            $html .= "<a class=\"{$itemlabelclass}\" href=\"" . $item->link() . "\"" . ($item->linkTarget() ? " target=\"" . $item->linkTarget() . "\" " : NULL) . ">" . $item->label() . "</a>";
        }
        else
        {
            $submenu = $this->id . '_' . md5($item->label());
            $html .= "<a class=\"{$itemlabelclass}\" href=\"#{$submenu}\">" . $item->label() . "</a>";
        }

        if ($item->children())
        {
            $html .= "\n<div id=\"{$submenu}\" class=\"yuimenu\"><div class=\"bd\"><ul>\n";
            foreach ($item->children() as $childMenu) {
                $html .= $this->menuItemToHTML($childMenu, false);
            }
            $html .= "\n</ul></div></div>\n";
        }
        
        $html .= "</li>\n";
        
        return $html;
    }

    function canPushValueBinding() { return false; }

    /**
     * This is a hack...
     * @todo Do we need a WFObject::keyExists() function or similar that can "check" for a binding without having to call the accessor? or WFObject::canSetKey()? Or do binding need to have a "readyonly" or "writeonly" option?
     */
    function valueForUndefinedKey($key)
    {
        switch ($key) {
            case 'menuItemsMenuPath':
            case 'menuItemsNestedArray':
                return NULL;    // we never return a value here, but we need to make it seem that we're a legitimate property so that bindings will work.
                break;
        }
        return parent::valueForUndefinedKey($key);
    }
}

?>
