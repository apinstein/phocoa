<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package UI
 * @subpackage Menu
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/** 
 * WFMenuItem protocol.
 *
 * The menu is a formal protocol (interface) for objects to communicate their properties as menu items.
 *
 * Menu rending systems are built to accept an array of WFMenuItem objects which are then displayed. Decoupling the rendering from the data via the WFMenuItem interface
 * means that PHOCOA applications can easily use any meny rendering system.
 *
 * Presently, there is only one rendering system implemented: {@link WFDynarchMenu}.
 */
interface WFMenuItem
{
    function label();
    function toolTip();
    function link();
    function linkTarget();
    function icon();
    function children();
}

/**
 *  Interface for objects to adhere to to be able to use the {@link WFMenuTree:menuTreeBuildingToMenuTree()} functions.
 */
interface WFMenuTreeBuilding
{
    function menuPath();
}

/**
 * Generic menu item class for dynamically building menu trees.
 *
 * @see WFMenuTree::nestedArrayToMenuTree()
 * @todo Add support for tooltop, linkTarget, and icon
 */
class WFMenuItemBasic implements WFMenuItem, WFMenuTreeBuilding
{
    /**
     * @var string The label to display for the menu.
     */
    protected $label;
    /**
     * @var string The url for the menu item.
     */
    protected $link;
    /**
     * @var array The child menu items (ie submenus) for this menu item.
     */
    protected $children;

    protected $menuPath;

    function __construct()
    {
        $this->label = NULL;
        $this->link = NULL;
        $this->menuPath = NULL;
        $this->children = array();
    }

    public static function WFMenuItemBasic()
    {
        return new WFMenuItemBasic;
    }

    /**
     *  Get the label for this menu item.
     *
     *  @return string
     */
    function label()
    {
        return $this->label;
    }
    
    /**
     *  Set the label for this menu item.
     *
     *  @param string The label
     */
    function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    function menuPath()
    {
        return $this->menuPath;
    }
    function setMenuPath($p)
    {
        $this->menuPath = $p;
        return $this;
    }
    
    /**
     *  Get the link for this menu item.
     *
     *  @return string
     */
    function link()
    {
        return $this->link;
    }

    /**
     *  Set the link for this menu item.
     *
     *  @param string The label
     */
    function setLink($link)
    {
        $this->link = $link;
        return $this;
    }

    /**
     *  Get the children (submenus) of this menu item.
     *
     *  @return array An array of WFMenuItemBasic objects.
     */
    function children()
    {
        return $this->children;
    }

    /**
     *  Add a submenu to this menu item.
     *
     *  @param object WFMenuItemBasic
     */
    function addChild($child)
    {
        $this->children[] = $child;
        return $this;
    }

    function toolTip() { return NULL; }
    function linkTarget() { return NULL; }
    function icon() { return NULL; }
}

/**
 * This is a helper class that is used by WFMenuTree when it builds WFMenuItem-compatible models from other data sources.
 */
class WFMenuItemProxy extends WFMenuItemBasic
{
    protected $object = NULL;

    function __construct($o)
    {
        parent::__construct($o);
        $this->setObject($o);
    }

    function setObject($o)
    {
        if (!($o instanceof WFMenuItem)) throw( new WFException("Objects used in menus must implement WFMenuItem.") );
        $this->object = $o;
    }
    function children() { return $this->object->children(); }
    function label() { return $this->object->label(); }
    function toolTip() { return $this->object->toolTip(); }
    function link() { return $this->object->link(); }
    function linkTarget() { return $this->object->linkTarget(); }
    function icon() { return $this->object->icon(); }
}



/**
 * Class with helper methods for adapting data sources into a tree of {@link WFMenuItem} objects.
 *
 */
class WFMenuTree
{
    /**
     *  Builds a hierarchical array of {@link WFMenuItemBasic} objects from the passed hierarchical associative array.
     *
     *  assoc array format:
     *  <code>
     *  $menuArray = array(
     *                  'topMenu1' => array(
     *                                      'Menu Item 1' => '/link/to/target1',
     *                                      'Menu Item 2' => '/link/to/target2',
     *                                      ),
     *                  'topMenuItem2' => '/link/to/target'
     *                  );
     *  </code>
     *
     *  NOTE: This function is very useful for converting menus set up in skin delegates (ie namedContent) to nice menus.
     *
     *  @param array The array of menu items.
     *  @param object WFMenuItemBasic The menu item to add items found to as submenus.
     *  @return array An array of WFMenuItemBasic objects.
     *  @todo Amend the array structure to provide ALL properties of {@link WFMenuItemBasic}.
     */
    static function nestedArrayToMenuTree($menuArray, $addToMenuItem = null)
    {
        $topMenuItems = array();
        foreach ($menuArray as $label => $value) {
            $currentMenuItem = new WFMenuItemBasic;

            $currentMenuItem->setLabel($label);
            if ($addToMenuItem == NULL)
            {
                $topMenuItems[] = $currentMenuItem;
            }
            else
            {
                $addToMenuItem->addChild($currentMenuItem);
            }

            if (is_array($value))   // submenu!
            {
                $item = WFMenuTree::nestedArrayToMenuTree($value, $currentMenuItem);
            }
            else
            {
                $currentMenuItem->setLink($value);
            }
        }

        return $topMenuItems;
    }

    /**
     *  Generic algorithm for building a menu tree from a list of objects implementing WFMenuTreeBuilding.
     *
     *  This algorithm is intended to adapt a flat list of objects which contain information on their place within a hierarchy.
     *  For instance, this could be a list of "web page objects" that each know about their proper place in a hierarchical navigation system.
     *
     *  This algorithm takes an array of objects, each having a menuPath in the form of "a/b/c/d", and returns them in tree form. The tree form is suitable for WFDynarchMenu, WFYAHOO_widget_Menu, or other menu subsytems.
     *
     *  Items can be both a menu item and have children. Any nodes along the way that aren't explicitly specified will be created for you with a label but no link.
     *
     *  IMPORTANT: The order of the items passed in is important. They should be sorted such that the nodes appear in order of appearance, if you were walking through them recursively.
     *
     *  EXAMPLE:
     *  a/b/
     *  a/b/c
     *  a/b/d
     *  a/c/a
     *  a/c/b
     *
     *  @param array A one-dimensional array of objects implementing WFMenuTreeBuilding.
     *  @return array An arry of these same objects, but converted to tree form.
     */
    static function menuTreeBuildingToMenuTree($allItems)
    {
        // loop through items building the tree
        $menuTree = array();
        $pathStack = array();
        $topMenuItem = NULL;
        $allPaths = array();
        // preflight run to make sure that all path parts are represented
        $newAllItems = array();
        foreach ($allItems as $item) {
            $allPaths[$item->menuPath()] = true;

            // if the container(s) of the current path don't yet have an item, we need to create fake WFMenuItemBasic items to represent them.
            $allPathsPartCheck = NULL;
            foreach (explode('/', $item->menuPath()) as $part) {
                if ($allPathsPartCheck !== NULL)
                {
                    $allPathsPartCheck .= '/';
                }
                $allPathsPartCheck .= $part;
                if (!isset($allPaths[$allPathsPartCheck]))
                {
                    $fakePathItem = new WFMenuItemBasic;
                    $fakePathItem->setLabel($part);
                    $fakePathItem->setMenuPath($allPathsPartCheck);

                    // add the fake item
                    $newAllItems[] = $fakePathItem;
                    $allPaths[$allPathsPartCheck] = true;
                }
            }
            $newAllItems[] = $item;
        }
        foreach ($newAllItems as $item) {
            $proxyMenuItem = new WFMenuItemProxy($item);
            // is this menu item a sub-item of the previous one? if so, add it to the current place in the tree
            if ($topMenuItem and strstr($item->menuPath(), $topMenuItem->menuPath() . '/'))
            {
                $topMenuItem->addChild($proxyMenuItem);
            }
            // else the item is not a sub-menu; thus we need to pop our stack until we match the current item
            else
            {
                // pop the stack of menu items until the curernt item fits under that place, or we reach the root
                while ( ($topMenuItem = array_pop($pathStack)) !== NULL) {
                    if (strstr($item->menuPath(), $topMenuItem->menuPath() . '/'))
                    {
                        array_push($pathStack, $topMenuItem);
                        break;
                    }
                }
                // should the item be added to the ROOT or to the current menu item?
                if ($topMenuItem === NULL)
                {
                    $menuTree[] = $proxyMenuItem;
                }
                else
                {
                    $topMenuItem->addChild($proxyMenuItem);
                }
            }
            array_push($pathStack, $item);
            $topMenuItem = $item;
        }

        return $menuTree;
    }
}
?>
