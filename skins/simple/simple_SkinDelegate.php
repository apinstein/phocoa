<?php

// custom skin delegates
class simple_SkinDelegate
{
    function loadDefaults(&$skin)
    {
        // look at URL and determine skin.
        $skin->setSkin('simple2');

        // set up other skin defaults
        $skin->setMetaDescription('Default Skin Type Description Goes Here');
        $skin->addMetaKeywords(array('default keywords'));
        $skin->setTitle('Default Skin Type Title');
    }

    /**
      * The namedContent mechanism for our skin. Here is the catalog:
      * 
      * mainMenu - An associative array of links ('link name' => 'link url') for the header area.
      * copyright - a copyright notice, as a string.
      *
      */
    function namedContent($name, $options = NULL)
    {
        switch ($name) {
            case 'mainMenu':
                return array(
                        'Hello World' => WFRequestController::WFURL('helloworld'),
                        'phpinfo' => WFRequestController::WFURL('phpinfo'),
                        'Examples' => WFRequestController::WFURL('examplemodule'),
                        'Skin Info' => WFRequestController::WFURL('skininfo'),
                        );
                break;
            case 'copyright':
                return "Copyright (c) 2005 Open Development. All Rights Reserved.";
                break;
        }
    }

    function namedContentList()
    {
        return array('mainMenu', 'copyright');
    }
}

?>
