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
 *  Interface for objects to adhere to to be able to use WFMenuTree helper functions.
 */
interface WFMenuTreeBuilding
{

    function menuPath();
    function addChild($child);
}

/**
 * Class with helper methods for managing menu items.
 */
class WFMenuTree
{
    /**
     *  Generic algorithm for building a menu tree from a list of items implementing WFMenuTreeBuilding.
     *
     *  This algorithm takes an array of objects, each having a menuPath in the form of "a/b/c/d", and returns them in tree form. The tree form is suitable for WFDynarchMenu or other menu subsytems.
     *
     *  Items can be both a menu item and have children.
     *
     *  The items should be sorted lexographically by their menuPath.
     * 
     *  @param array A one-dimensional array of objects implementing WFMenuTreeBuilding.
     *  @return array An arry of these same objects, but converted to tree form.
     *  @throws
     */
    static function menuTree($allItems)
    {
        // loop through items building the tree
        $menuTree = array();
        $pathStack = array();
        $topMenuItem = NULL;
        foreach ($allItems as $item) {
            // is this menu item a sub-item of the previous one? if so, add it to the current place in the tree
            if ($topMenuItem and strstr($item->menuPath(), $topMenuItem->menuPath() . '/'))
            {
                $topMenuItem->addChild($item);
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
                    $menuTree[] = $item;
                }
                else
                {
                    $topMenuItem->addChild($item);
                }
            }
            array_push($pathStack, $item);
            $topMenuItem = $item;
        }

        return $menuTree;
    }
}
?>
