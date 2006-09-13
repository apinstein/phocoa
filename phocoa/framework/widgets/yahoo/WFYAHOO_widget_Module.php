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
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * - {@link WFWidget::$value value} or {@link WFSelect::$values values}, depending on {@link WFSelect::$multiple multiple}.
 *   Note that you can create complex strings by using the value binding option "ValuePattern" along with multiple value<N> bindings.
 *   For instance, a ValuePattern of "%1% of %2% selected." with a value binding that resolves to "5" and a value2 binding that resolves to "10" will say "5 of 10 selected."
 *   In case it's not obvious, "%1%" is substituted with the value from the "value" binding, and "%n%" is substituted with the value from "value<N>" binding. N starts at 2 and goes up consecutively.
 * 
 * <b>Optional:</b><br>
 */
class WFYAHOO_widget_Module extends WFYAHOO
{
    protected $header;
    protected $body;
    protected $footer;

    protected $fixedcenter;
    protected $width;
    protected $height;
    protected $zIndex;
    protected $iframe;
    protected $x;
    protected $y;
    protected $context;

    protected $containerClass;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->value = NULL;
        $this->header = NULL;
        $this->body = NULL;
        $this->footer = NULL;

        $this->fixedcenter = false;
        $this->width = NULL;
        $this->height = NULL;
        $this->zIndex = NULL;
        $this->iframe = false;
        $this->x = NULL;
        $this->y = NULL;
        $this->context = NULL;
        
        $this->containerClass = 'Module';

        $this->importJS("{$this->yuiPath}/dom/dom.js");
        $this->importJS("{$this->yuiPath}/event/event.js");
        $this->importJS("{$this->yuiPath}/container/container.js");
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

    function setContext($id, $elementCorner, $contextCorner)
    {
        $this->context['id'] = $id;
        $this->context['elementCorner'] = $elementCorner;
        $this->context['contextCorner'] = $contextCorner;
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
            // set up basic HTML
            $html .= "
<div id=\"{$this->id}\" style=\"visibility: hidden\">
  <div class=\"hd\"></div> 
  <div class=\"bd\"></div> 
  <div class=\"ft\"></div> 
</div>
<div style=\"visibility: hidden\">
    <div id=\"{$this->id}_header\">" . $this->header . "<div style=\"height: 11px;\"></div></div>
    <div id=\"{$this->id}_body\">" . $this->body . "</div>
    <div id=\"{$this->id}_footer\">" . $this->footer . "</div>
</div>
";
            $script = "
<script type=\"text/javascript\">
//<![CDATA[
var WFYAHOO_widget_Module_{$this->id} = new YAHOO.widget.{$this->containerClass}(\"{$this->id}\", { visible: false } );
WFYAHOO_widget_Module_{$this->id}.render();
WFYAHOO_widget_Module_{$this->id}.setHeader(YAHOO.util.Dom.get('{$this->id}_header'));
WFYAHOO_widget_Module_{$this->id}.setBody(YAHOO.util.Dom.get('{$this->id}_body'));
WFYAHOO_widget_Module_{$this->id}.setFooter(YAHOO.util.Dom.get('{$this->id}_footer'));
WFYAHOO_widget_Module_{$this->id}.cfg.setProperty('fixedcenter', " . ($this->fixedcenter ? 'true' : 'false') . ");
WFYAHOO_widget_Module_{$this->id}.cfg.setProperty('iframe', " . ($this->iframe ? 'true' : 'false') . ");
" . ($this->context ? "WFYAHOO_widget_Module_{$this->id}.cfg.setProperty('context', [ '{$this->context['id']}', '{$this->context['elementCorner']}', '{$this->context['contextCorner']}' ] )" : NULL ) . "
" . ($this->width ? "WFYAHOO_widget_Module_{$this->id}.cfg.setProperty('width', '{$this->width}')" : NULL ) . "
" . ($this->height ? "WFYAHOO_widget_Module_{$this->id}.cfg.setProperty('height', '{$this->height}')" : NULL ) . "
" . ($this->zIndex ? "WFYAHOO_widget_Module_{$this->id}.cfg.setProperty('zIndex', '{$this->zIndex}')" : NULL ) . "
PHOCOA.runtime.addObject(WFYAHOO_widget_Module_{$this->id});
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
