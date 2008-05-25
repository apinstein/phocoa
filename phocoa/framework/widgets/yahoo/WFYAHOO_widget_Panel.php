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

        $this->yuiloader()->yuiRequire("dragdrop,animation");
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
            // set up basic HTML
            $html = parent::render($blockContent);
            return $html;
        }
    }

    function initJS($blockContent)
    {
        $script .= parent::initJS($blockContent);
        $script .= "
PHOCOA.namespace('widgets.{$this->id}.Panel');
PHOCOA.widgets.{$this->id}.Panel.queueProps = function(o) {
    PHOCOA.widgets.{$this->id}.Overlay.queueProps(o);   // queue parent props
    // alert('id={$this->id}: queue Panel props');
    // queue Panel props here
};
PHOCOA.widgets.{$this->id}.Panel.init = function() {
    PHOCOA.widgets.{$this->id}.Overlay.init();  // init parent
    var panel = PHOCOA.runtime.getObject('{$this->id}');
    panel.cfg.setProperty('underlay', '{$this->underlay}');
    panel.cfg.setProperty('close', " . ($this->close ? 'true' : 'false') . ");
    panel.cfg.setProperty('draggable', " . ($this->draggable ? 'true' : 'false') . ");
    panel.cfg.setProperty('modal', " . ($this->modal ? 'true' : 'false') . ");
};
" .
( (get_class($this) == 'WFYAHOO_widget_Panel') ? "PHOCOA.widgets.{$this->id}.init = function() { PHOCOA.widgets.{$this->id}.Panel.init(); };" : NULL );
        return $script;
    }
    function canPushValueBinding() { return false; }
}

?>
