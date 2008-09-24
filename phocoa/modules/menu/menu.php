<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

class module_menu extends WFModule
{
    function defaultPage()
    {
        return 'menu';
    }
    function allPages()
    {
        return array('menu');
    }
}

class module_menu_menu
{
    function parameterList()
    {
        return array('menuId', 'horizontal');
    }
    function parametersDidLoad($page, $params)
    {
        $skin = $page->module()->invocation()->rootSkin();
        $menu = new WFYAHOO_widget_Menu($params['menuId'], $page);
        $menu->setHorizontal( $params['horizontal'] == 1 );
        $page->assign('menuId', $params['menuId']);
        $menu->setMenuItems( WFMenuTree::nestedArrayToMenuTree( $skin->namedContent($params['menuId']) ) );
    }
}
?>
