<?php

class simple2_SkinManifestDelegate extends WFSkinManifestDelegate
{
    function templateTypes() { return array('min'); }
    function defaultTheme() { return 'default'; }
    function themes() { return array('default', 'alternative'); }
    function loadTheme($theme)
    {
        if ($theme == 'default')
            return array('bgcolor' => 'gray', 'textcolor' => 'black');
        else
            return array('bgcolor' => 'orange', 'textcolor' => 'blue');
    }
}

?>
