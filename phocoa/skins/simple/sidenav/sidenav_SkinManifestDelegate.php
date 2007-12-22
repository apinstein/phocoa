<?php
class sidenav_SkinManifestDelegate extends WFSkinManifestDelegate
{
    function themes() { return array('brushed', 'paper'); }
    function defaultTheme() { return 'brushed'; }
    function loadTheme($theme)
    {
        switch ($theme) {
            case 'brushed':
                return array(
                            'contentBG' => '#999999',
                            'headingColor' => 'white',
                            'yuiTemplate' => 'yui-t2',
                            );
            case 'paper':
                return array(
                            'contentBG' => 'beige',
                            'headingColor' => 'black',
                            'yuiTemplate' => 'yui-t4',
                            );
            default:
                throw( new Exception("Unknown theme: {$theme}") );
        }
    }
}
