<?php

require_once('framework/WFModule.php');

/**
  * The CSS module provides for a way to cleanly include CSS files from skin templates.
  *
  * The one action, dspSkinCSS, will pull the skin's type, name, theme, and css filename from the PATH_INFO, in that order.
  * The module looks up the skin file or throws a 404 if it's not there. If it is there, outputs proper Content-Type header and the CSS.
  * Displaying CSS via this method will allow smarty directives to be processed, which is very useful when you want to share a CSS file
  * among multiple themes and have a few theme variables used in the CSS for the differences (IE colors, image paths, etc).
  *
  * Also, using PATH INFO to designate different skins / themes gets around browsers that try to cache things. For instance,
  * trying to retrieve a css with showcss.php?theme=XXX typically results in browsers incorrectly caching the CSS files when the theme is switched.
  * So, we include the theme name, even though we don't actually use it, just for the security of making sure the browsers don't cache the wrong CSS.
  *
  * @see WFSkin
  *
  * @package UI
  * @subpackage Skin
  */
class css extends WFModule
{
    function defaultPage() 
    { 
    	return 'dspSkinCSS'; 
    }
}

class css_dspSkinCSS
{
    function parameterList()
    {
        return array('cssTemplate', 'skinTypeName', 'skinName', 'skinThemeName');
    }
    function noAction($page, $params)
    {
        extract($params);

        $cssFilePath = WFWebApplication::appDirPath(WFWebApplication::DIR_SKINS) . '/' . $skinTypeName . '/'. $skinName . '/' . $cssTemplate;
        if (!file_exists($cssFilePath)) {
            header("HTTP/1.0 404 Not Found");
            print "No css file at: {$cssFilePath}";
            exit;
        }

        // set the skin's wrapper information
        $skin = $page->module()->invocation()->rootSkin();
        $skin->setDelegateName($skinTypeName);
        $skin->setSkin($skinName);
        $skin->setTemplateType(WFSkin::SKIN_WRAPPER_TYPE_RAW);
        $skin->setValueForKey($skinThemeName, 'skinThemeName');
        // load the theme vars into our smarty for this module
        $page->assign('skinThemeVars', $skin->valueForKey('skinManifestDelegate')->loadTheme($skinThemeName));
        $page->assign('cssFilePath', $cssFilePath);
        $page->assign('skinDir', $skin->getSkinDir());
        $page->assign('skinDirShared', $skin->getSkinDirShared());
        header("Content-Type: text/css");
        
        // make CSS cacheable for a day at least
        $seconds = 24 * 60 * 60;
        // cache control - we can certainly safely cache the search results for 15 minutes
        header('Pragma: '); // php adds Pragma: nocache by default, so we have to disable it
        header('Cache-Control: max-age=' . $seconds);
        // Format: Fri, 30 Oct 1998 14:19:41 GMT
        header('Expires: ' . gmstrftime ("%a, %d %b %Y %T ", time() + $seconds) . ' GMT');
    }
}

?>
