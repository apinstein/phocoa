<?php
class topnav_SkinManifestDelegate extends WFSkinManifestDelegate
{
    function themes() { return array('default'); }
    function defaultTheme() { return 'default'; }
    function loadTheme($theme) { return array(); }
}
