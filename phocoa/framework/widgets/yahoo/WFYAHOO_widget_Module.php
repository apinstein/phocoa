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
 * NOTE: Effects don't work on Modules, but do on all subclasses. Bugfix coming from YUI.
 * @todo buildModuleProgrammatically needs a way to specify a parent element to add the module to.
 */
class WFYAHOO_widget_Module extends WFYAHOO
{
    protected $header;
    protected $body;
    protected $footer;

    /**
     * @var Raw javascript string of argument passed to container.render(). Defaults to NULL.
     */
    protected $renderTo;

    /**
     * @var boolean Whether or not the module is visible. DEFAULT: false
     */
    protected $visible;
    protected $monitorresize;
    /**
     * @var array An array of ContainerEffects to use for show/hide.
     * @todo Move these to Overlay (which is the first class that can have effects)
     */
    protected $effects;

    /**
     * @var string The name of the YAHOO Container class to instantiate. Subclasses should set this method to the proper name.
     */
    protected $containerClass;
    /**
     * @var boolean Whether or not to build the module programmatically (ie via javascript) or to inline the module HTML when rendering this widget.
     *              By default, WFYAHOO_widget_Module is set to FALSE, and all other subclasses are set to TRUE.
     *              This setup works best for the respective types; since Modules are inline, they would get re-ordered if they were re-attached to document.body.
     *              All subclasses are positioned absolutely so they benefit from being attached to document.body inline, to prevent rendering artifacts during load.
     */
    protected $buildModuleProgrammatically;

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
        $this->buildModuleProgrammatically = (get_class($this) !== 'WFYAHOO_widget_Module');
        $this->renderTo = NULL;

        $this->yuiloader()->yuiRequire('container');
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            ));
    }

    public function setBuildModuleProgrammatically($b)
    {
        $this->buildModuleProgrammatically = $b;
    }

    function addEffect($effectName, $duration = 0.5)
    {
        $this->effects[$effectName] = $duration;
        $this->yuiloader()->yuiRequire('animation');
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
            // determine body html
            $bodyHTML = ($blockContent === NULL ? $this->body : $blockContent);

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
            if ($this->buildModuleProgrammatically === false)
            {
                $html .= "
<div id=\"{$this->id}\"{$visibility}>
    <div class=\"hd\">" . $this->header . "</div>
    <div class=\"bd\">" . $bodyHTML . "</div>
    <div class=\"ft\">" . $this->footer . "</div>
</div>
";
            }
            else
            {
                $html .= "<div id=\"{$this->id}\"{$visibility}></div>";
            }
            return $html;
        }
    }

    function initJS($blockContent)
    {
        // determine body html
        $bodyHTML = ($blockContent === NULL ? $this->body : $blockContent);
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

        $script = "
PHOCOA.namespace('widgets.{$this->id}.Module');
PHOCOA.widgets.{$this->id}.Module.queueProps = function(o) {
    // alert('id={$this->id}: queue Module props');
    // queue Module props here
};
PHOCOA.widgets.{$this->id}.Module.init = function() {
    var module = new YAHOO.widget.{$this->containerClass}(\"{$this->id}\");
    module.subscribe('changeBody', function(el) { PHOCOA.widgets.{$this->id}.scriptsEvald = false; } );
    module.showEvent.subscribe(function(el) {
        if (!PHOCOA.widgets.{$this->id}.scriptsEvald)
        {
            PHOCOA.widgets.{$this->id}.scriptsEvald = true;
            this.body.innerHTML.evalScripts();
        }
    }, module);
    module.cfg.queueProperty('visible', " . ($this->visible ? 'true' : 'false') . ");
    module.cfg.queueProperty('monitorresize', " . ($this->monitorresize ? 'true' : 'false') . ");
    PHOCOA.widgets.{$this->id}.{$this->containerClass}.queueProps(module);";

        if ($this->buildModuleProgrammatically)
        {
            $script .= "
    module.setHeader(" . ($this->header === NULL ? '""' : WFJSON::json_encode($this->header)) . ");
    module.setBody(" . ($bodyHTML === NULL ? '""' : WFJSON::json_encode($bodyHTML)) . ");
    module.setFooter(" . ($this->footer  === NULL ? '""' : WFJSON::json_encode($this->footer)) . ");
    module.render({$this->renderTo});
";
        }
        else
        {
            $script .= "
    module.render();
";
        }
        $script .= 
( $addEffectsJS ? "\n    module.cfg.setProperty('effect', {$addEffectsJS});" : NULL ) . 
// Module visibility controlled by display attr; subclass visibility controlled by visibilty. Non-modules must be display: block so that they'll appear when asked
( (get_class($this) != 'WFYAHOO_widget_Module') ? "\n    YAHOO.util.Dom.setStyle('{$this->id}', 'display', 'block')" : NULL) . "
    PHOCOA.runtime.addObject(module, '{$this->id}');
};
";
        if ( get_class($this) == 'WFYAHOO_widget_Module')
        {
           $script .= "PHOCOA.widgets.{$this->id}.init = function() { PHOCOA.widgets.{$this->id}.Module.init(); };";
        }
        return $script;
    }

    function canPushValueBinding() { return false; }
}

?>
