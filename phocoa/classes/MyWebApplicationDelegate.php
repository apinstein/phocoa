<?php

/**
  * This file contains delegate implementations for the basic parts of this Web Application.
  */


// custom WFWebApplication delegate
class MyWebApplicationDelegate
{
    function defaultModule()
    {
        return 'helloworld';
    }

    // switch between different skin catalogs; admin, public, partner reporting, etc
    function defaultSkinDelegate()
    {
        return 'simple';
    }
}

?>
