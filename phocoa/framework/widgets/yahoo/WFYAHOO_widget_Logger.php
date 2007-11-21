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
 * A YAHOO Logger widget for our framework.
 * 
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * none
 * 
 * <b>Optional:</b><br>
 */
class WFYAHOO_widget_Logger extends WFYAHOO
{
    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);

        $this->yuiloader()->yuiRequire("logger");
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            ));
    }

    function render($blockContent = NULL)
    {
        if ($this->hidden)
        {
            return NULL;
        }
        else
        {
            $html = parent::render($blockContent);
            $html .= "<div id=\"{$this->id}\"></div>";
            return $html;
        }
    }

    function initJS($blockContent)
    {
        return "
PHOCOA.widgets.{$this->id}.init = function() {
    var WFYAHOO_widget_Logger_{$this->id} = new YAHOO.widget.LogReader(null, {top:'4em',fontSize:'92%',width:'30em',height:'20em'});
    PHOCOA.runtime.addObject(WFYAHOO_widget_Logger_{$this->id}, '{$this->id}');
};
";
    }

    function canPushValueBinding() { return false; }
}

?>
