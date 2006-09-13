<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package UI
 * @subpackage Widgets
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/**
 * A YAHOO base class for our framework.
 * 
 * This abstract class provides some base features for all Yahoo! YUI classes such as js/css include management, debug, calculating the "path" to the yui assets, etc.S
 *
 * @todo Should the js/css include management be promoted to WFView so that all widgets have access to these utility features? Probably so...
 *       Also need to decide about phocoa.js and prototype.js; should these be included in the skin, or by the widgets that need them (I think this is tricky b/c then those widgets can't be added via AJAX since these base js files won't exist), or by modules that know they are using them?
 */
abstract class WFYAHOO extends WFWidget
{
    protected $jsImports;
    protected $cssImports;
    protected $yuiPath;
    protected $debug;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->debug = false;
        $this->yuiPath = WFWebApplication::webDirPath(WFWebApplication::WWW_DIR_BASE) . '/framework/yui';

        $this->jsImports = array();
        $this->cssImports = array();

        $this->importJS("{$this->yuiPath}/yahoo/yahoo.js");
    }

    /**
     *  Import a JS source file.
     *
     *  In *debug* mode, this will be imported by adding a <script> tag to the head element, for improved debug-ability.
     *  In normal mode, this will be imported by using an AJAX request to synchronously download the source file, then eval() it.
     *
     *  The eval() method has improved flexibility, because it can be used even on widget code loaded from AJAX calls with
     *  prototype's Element.update + evalScripts: true, thus allowing the loading of JS files only exactly when needed.
     *
     *  However, it has the downside of requiring that the js code is eval-clean (that is, unless you assign your functions to variables they will not "exist").
     *
     *  @param string The JS file path to include.
     */
    protected function importJS($path)
    {
        if ($this->debug)
        {
            $this->page->module()->invocation()->rootSkin()->addHeadString("<script type=\"text/javascript\" src=\"{$path}\" ></script>");
        }
        else
        {
            $this->jsImports[$path] = $path;
        }
    }

    /**
     *  Import a CSS source file.
     *
     *  In *debug* mode, this will be imported by adding a <link> tag to the head element, for improved debug-ability.
     *  In normal mode, this will be imported by using an AJAX request to synchronously download the source file, then eval() it.
     *
     *  The advantage of the latter is that CSS files can be programmatically added via javascript code, even from code that is returned
     *  from AJAX calls.
     *
     *  @param string The css file path to include.
     */
    protected function importCSS($path)
    {
        if ($this->debug)
        {
            $this->page->module()->invocation()->rootSkin()->addHeadString("<link rel=\"stylesheet\" type=\"text/css\" href=\"{$path}\" />");
        }
        else
        {
            $this->cssImports[$path] = $path;
        }
    }

    private function getImportJS()
    {
        $script = "
<script type=\"text/javascript\">
//<![CDATA[
";
        foreach ($this->jsImports as $path => $nothing) {
            $script .= "PHOCOA.importJS('{$path}');\n";
        }
        $script .= "//]]>
</script>";
        return $script;
    }

    private function getImportCSS()
    {
        $script = "
<script type=\"text/javascript\">
//<![CDATA[
";
        foreach ($this->cssImports as $path => $nothing) {
            $script .= "PHOCOA.importCSS('{$path}');\n";
        }
        $script .= "//]]>
</script>";
        return $script;
    }

    /**
     *  Render the widget.
     *
     *  Subclasses should call this function, and then append their output to that returned here.
     */
    function render($blockContent = NULL)
    {
        if ($this->hidden)
        {
            return NULL;
        }
        else
        {
            if ($this->debug)
            {
                return NULL;
            }
            else
            {
                return $this->getImportJS() . $this->getImportCSS();
            }
        }
    }

    function canPushValueBinding() { return false; }
}

?>
