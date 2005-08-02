<?php

class simple1_SkinManifestDelegate extends WFSkinManifestDelegate
{
    function defaultTheme() { return 'default'; }
    function loadTheme($theme)
    {
        return array('themeName' => 'Default');
    }
}

?>
