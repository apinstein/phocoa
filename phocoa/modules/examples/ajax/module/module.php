<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

class module_module extends WFModule
{
    function sharedInstancesDidLoad()
    {
    }

    function defaultPage()
    {
        return 'module';
    }

    function module_PageDidLoad($page, $params)
    {
        $m = new WFYAHOO_widget_Panel('test', $page);
        $m->setBody('<p>This is a test.</p>');
    }

    function module_SetupSkin($skin)
    {
        $skin->addHeadString('<script type="text/javascript" src="' . WFWebApplication::webDirPath(WFWebApplication::WWW_DIR_BASE) . '/framework/js/prototype.js"></script>');
        $skin->addHeadString('<script type="text/javascript" src="' . WFWebApplication::webDirPath(WFWebApplication::WWW_DIR_BASE) . '/framework/js/phocoa.js"></script>');
    }

}
?>
