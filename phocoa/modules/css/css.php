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
	
    function dspSkinCSS_PageDidLoad()
    {
        // determine skin type/skin/theme
        $cssTemplate = $skinName = $skinThemeName = NULL;
        @list(,,,$cssTemplate, $skinTypeName, $skinName, $skinThemeName) = split('/', $_SERVER['PATH_INFO']);
        $cssFilePath = WFWebApplication::appDirPath(WFWebApplication::DIR_SKINS) . '/' . $skinTypeName . '/'. $skinName . '/' . $cssTemplate;
        if (!file_exists($cssFilePath)) {
            header("HTTP/1.0 404 Not Found");
            exit;
        }

        // set the skin's wrapper information
        $skin = $this->invocation->rootSkin();
        $skin->setDelegateName($skinTypeName);
        $skin->setSkin($skinName);
        $skin->setTemplateType(WFSkin::SKIN_WRAPPER_TYPE_RAW);
        $skin->setValueForKey($skinThemeName, 'skinThemeName');
        // load the theme vars into our smarty for this module
        $this->requestPage->assign('skinThemeVars', $skin->valueForKey('skinManifestDelegate')->loadTheme($skinThemeName));
        $this->requestPage->assign('cssFilePath', $cssFilePath);
        $this->requestPage->assign('skinDir', $skin->getSkinDir());
        $this->requestPage->assign('skinDirShared', $skin->getSkinDirShared());
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
