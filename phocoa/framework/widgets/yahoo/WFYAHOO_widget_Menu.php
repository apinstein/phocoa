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
 * A YAHOO AutoComplete widget for our framework. This widget acts like a ComboBox: it is a text field with a pick-list and OPTIONAL custom entry.
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
        if ($this->hidden)
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
        $html = "
        PHOCOA.widgets.{$this->id}.init = function() {
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
            $html .= "<a class=\"{$itemlabelclass}\" href=\"" . $item->link() . "\">" . $item->label() . "</a>";
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

    function canPushValueBinding() { return true; }
}

?>
