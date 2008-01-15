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
 * A special widget that makes it easy to include Javascript that relies on YAHOO stuff to run.
 * 
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * none
 * 
 * <b>Optional:</b><br>
 * none
 */
class WFYAHOO_widget_YahooScript extends WFYAHOO
{
    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);

        $this->yuiloader()->yuiRequire("yahoo,dom,event");
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
            $html .= '<div id="' . $this->id . '" style="display: none;"></div>';
            return $html;
        }
    }

    function initJS($blockContent)
    {
        return "
PHOCOA.widgets.{$this->id}.init = function() {
{$blockContent}
};
";
    }

    function canPushValueBinding() { return false; }
}

?>
