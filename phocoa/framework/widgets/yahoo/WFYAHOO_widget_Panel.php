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
class WFYAHOO_widget_Panel extends WFYAHOO_widget_Overlay
{
    /**
     * @var boolean TRUE if the panel can be closed (an "X" in the corner). DEFAULT: true.
     */
    protected $close;
    /**
     * @var boolean TRUE if the panel is draggable. DEFAULT: true.
     */
    protected $draggable;
    /**
     * @var string The panel's underlay. DEFAULT: shadow.
     */
    protected $underlay;
    /**
     * @var boolean TRUE if the panel is modal. If true, the panel will "mask" out the page behind the panel and make it "inactive". DEFAULT: false.
     */
    protected $modal;
    // add
    // keylisteners

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->containerClass = 'Panel';
        $this->underlay = "shadow";
        $this->draggable = true;
        $this->modal = false;
        $this->close = true;

        $this->importYahooJS("dragdrop/dragdrop-min.js,animation/animation-min.js");
        $this->importCSS("{$this->yuiPath}/container/assets/container.css");
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            'underlay' => array('shadow', 'none', 'matte'),
            'draggable' => array('true', 'false'),
            'modal' => array('true', 'false'),
            'close' => array('true', 'false'),
            ));
    }

    function setDraggable($b)
    {
        $this->draggable = $b;
    }

    function setModal($b)
    {
        $this->modal = $b;
    }

    function setClose($b)
    {
        $this->close = $b;
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

YAHOO.namespace('phocoa.widgets.panel');
YAHOO.phocoa.widgets.panel.init_{$this->id} = function() {
    YAHOO.phocoa.widgets.overlay.init_{$this->id}();
    var panel = PHOCOA.runtime.getObject('{$this->id}');
    panel.cfg.setProperty('underlay', '{$this->underlay}');
    panel.cfg.setProperty('close', " . ($this->close ? 'true' : 'false') . ");
    panel.cfg.setProperty('draggable', " . ($this->draggable ? 'true' : 'false') . ");
    panel.cfg.setProperty('modal', " . ($this->modal ? 'true' : 'false') . ");
}
" .
( (get_class($this) == 'WFYAHOO_widget_Panel') ? "YAHOO.util.Event.addListener(window, 'load', YAHOO.phocoa.widgets.panel.init_{$this->id});" : NULL ) . "
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
