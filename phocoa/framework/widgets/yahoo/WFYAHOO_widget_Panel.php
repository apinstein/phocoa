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
 * A YAHOO Panel widget for our framework.
 * 
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * 
 * <b>Optional:</b><br>
 */
class WFYAHOO_widget_Panel extends WFYAHOO_widget_Module
{
    protected $underlay;
    protected $draggable;
    protected $modal;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->containerClass = 'Panel';
        $this->underlay = "shadow";
        $this->draggable = true;
        $this->modal = true;

        $this->importJS("{$this->yuiPath}/dragdrop/dragdrop.js");
        $this->importJS("{$this->yuiPath}/animation/animation.js");
        $this->importCSS("{$this->yuiPath}/container/assets/container.css");
    }

    function render($blockContent = NULL)
    {
        if ($this->hidden)
        {
            return NULL;
        }
        else
        {
            $yuiPath = WFWebApplication::webDirPath(WFWebApplication::WWW_DIR_BASE) . '/framework/yui';

            // set up basic HTML
            $html = parent::render($blockContent);
            $script = "
<script type=\"text/javascript\">
//<![CDATA[

var WFYAHOO_widget_Panel_{$this->id} = PHOCOA.runtime.getObject('{$this->id}');
WFYAHOO_widget_Panel_{$this->id}.cfg.setProperty('underlay', '{$this->underlay}');
WFYAHOO_widget_Panel_{$this->id}.cfg.setProperty('draggable', " . ($this->draggable ? 'true' : 'false') . ");
WFYAHOO_widget_Panel_{$this->id}.cfg.setProperty('modal', " . ($this->modal ? 'true' : 'false') . ");

//]]>
</script>";
            // output script
            $html .= "\n{$script}\n";
            return $html;
        }
    }

    function canPushValueBinding() { return false; }
}

?>
