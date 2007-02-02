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
 * A YAHOO Module widget for our framework.
 *
 * NOTE: You can include YAHOO containers with WFView or WFViewBlock. If you use WFViewBlock, the block content will be used as the container body.
 * 
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * 
 * <b>Optional:</b><br>
 *
 * @todo add "effect" capability
 */
class WFYAHOO_widget_Module extends WFYAHOO
{
    protected $header;
    protected $body;
    protected $footer;

    /**
     * @var boolean Whether or not the module is visible. DEFAULT: false
     */
    protected $visible;
    protected $monitorresize;
    /**
     * @var array An array of ContainerEffects to use for show/hide.
     */
    protected $effects;

    /**
     * @var string The name of the YAHOO Container class to instantiate. Subclasses should set this method to the proper name.
     */
    protected $containerClass;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->value = NULL;

        $this->visible = false;
        $this->monitorresize = true;
        $this->effects = array();

        $this->header = NULL;
        $this->body = NULL;
        $this->footer = NULL;
        
        $this->containerClass = 'Module';

        $this->importYahooJS("container/container-min.js");
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            ));
    }

    function addEffect($effectName, $duration = 0.5)
    {
        $this->effects[$effectName] = $duration;
    }

    function setupExposedBindings()
    {
        $myBindings = parent::setupExposedBindings();

        $newValBinding = new WFBindingSetup('header', 'The header HTML.', array(WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_NAME => WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_VALUE));
        $newValBinding->setReadOnly(true);
        $newValBinding->setBindingType(WFBindingSetup::WFBINDINGTYPE_MULTIPLE_PATTERN);
        $myBindings[] = $newValBinding;

        $newValBinding = new WFBindingSetup('body', 'The body HTML.', array(WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_NAME => WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_VALUE));
        $newValBinding->setReadOnly(true);
        $newValBinding->setBindingType(WFBindingSetup::WFBINDINGTYPE_MULTIPLE_PATTERN);
        $myBindings[] = $newValBinding;

        $newValBinding = new WFBindingSetup('footer', 'The footer HTML.', array(WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_NAME => WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_VALUE));
        $newValBinding->setReadOnly(true);
        $newValBinding->setBindingType(WFBindingSetup::WFBINDINGTYPE_MULTIPLE_PATTERN);
        $myBindings[] = $newValBinding;
        return $myBindings;
    }

    function setVisible($show)
    {
        $this->visible = $show;
    }

    function setHeader($html)
    {
        $this->header = $html;
    }

    function setBody($html)
    {
        $this->body = $html;
    }

    function setFooter($html)
    {
        $this->footer = $html;
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
            // calcualte effects
            $effects = array();
            foreach ($this->effects as $name => $duration) {
                $effects[] = "{ effect: {$name}, duration: {$duration} }";
            }
            $addEffectsJS = NULL;
            if (count($effects))
            {
                $addEffectsJS = '[ ' . join(', ', $effects) . ' ]';
            }
            // set up basic HTML -- in order to prevent a "flash of content" for non-visible content, we must make it visibility: hidden
            // however, while that prevents the content from being SEEN, you will still see BLANK space where it goes, thus we must also set display: none
            // YUI's show()/hide() functions to display the module content work differently depending on the module's class...
            // show()/hide() on Module toggles display: none|block... on subclasses toggles visibility: visible|hidden
            // thus we need to use a different mechanism to prevent the "Flash of content" and "blank space" issues completely.
            $visibility = NULL;
            if (!$this->visible)
            {
                if (get_class($this) == 'WFYAHOO_widget_Module')
                {
                    $visibility = " style=\"display: none;\"";
                }
                else
                {
                    $visibility = " style=\"display: none; visibility: hidden;\"";
                }
            }
            $html .= "
<div id=\"{$this->id}\"{$visibility}>
    <div class=\"hd\">" . $this->header . "</div>
    <div class=\"bd\">" . ($blockContent === NULL ? $this->body : $blockContent) . "</div>
    <div class=\"ft\">" . $this->footer . "</div>
</div>
";
            $script = "
<script type=\"text/javascript\">
//<![CDATA[
YAHOO.namespace('phocoa.widgets.module');
YAHOO.phocoa.widgets.module.init_{$this->id} = function() {
    var module = new YAHOO.widget.{$this->containerClass}(\"{$this->id}\");
    module.cfg.queueProperty('visible', " . ($this->visible ? 'true' : 'false') . ");
    module.cfg.queueProperty('monitorresize', " . ($this->monitorresize ? 'true' : 'false') . ");
    module.render();" . 
    ( $addEffectsJS ? "\n    module.cfg.setProperty('effect', {$addEffectsJS});" : NULL ) . 
    ( (get_class($this) != 'WFYAHOO_widget_Module') ? "\n   YAHOO.util.Dom.setStyle('{$this->id}', 'display', 'block')" : NULL) . "
    PHOCOA.runtime.addObject(module);
}
" . 
( (get_class($this) == 'WFYAHOO_widget_Module') ? "YAHOO.util.Event.addListener(window, 'load', YAHOO.phocoa.widgets.module.init_{$this->id});" : NULL ) . "
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
