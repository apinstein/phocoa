<?php

/**
  * This file contains delegate implementations for the basic parts of this Web Application.
  */


// custom WFWebApplication delegate
class MyWebApplicationDelegate
{
    function initialize()
    {
        // manifest core modules that we want to use -- if you don't want people to access a module, remove it from this list!
        $webapp = WFWebApplication::sharedWebApplication();
        $webapp->addModulePath('login', FRAMEWORK_DIR . '/modules/login');
        $webapp->addModulePath('css', FRAMEWORK_DIR . '/modules/css');
        $webapp->addModulePath('examples', FRAMEWORK_DIR . '/modules/examples');
    }
    
    function defaultInvocationPath()
    {
        return 'examples/widgets/toc';
    }

    // switch between different skin catalogs; admin, public, partner reporting, etc
    function defaultSkinDelegate()
    {
        return 'simple';
    }
}

?>
