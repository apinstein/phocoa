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
 * A Link widget for our framework.
 * 
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * - {@link WFWidget::$value value} The URL for the link. Supports ValuePattern binding.
 * - {@link WFLink::$label label} The label for the link. Supports ValuePattern binding.
 * 
 * <b>Optional:</b><br>
 * - {@link WFLink::$class class} The class of the <a> tag.
 * - {@link WFLink::$title title} The title for the link. Supports ValuePattern binding.
 *
 * @todo Do we need a way to specify a module/page for creating links dynamically? Either a parseable syntax in the "value" field or additional properties?
 */
class WFLink extends WFWidget
{
    /**
     * @var string The label for the link. The label is what the viewer sees as the link text.
     */
	protected $label;
    /**
     * @var string The HTML title for the link. The title is often rendered as a "tooltip" by browsers.
     */
    protected $title;
    /**
     * @var string The HTML target for the link. Example: _blank, _self, _top etc.
     */
    protected $target;
    /**
     * @var string The class of the <a> tag.
     */
    protected $class;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->label = NULL;
        $this->target = NULL;
        $this->title = NULL;
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            'label',
            'title',
            'target',
            ));
    }

    public function setLabel($label)
    {
        $this->label = $label;
    }
    
    function setupExposedBindings()
    {
        $myBindings = parent::setupExposedBindings();
        $newValBinding = new WFBindingSetup('value', 'The href value for the link.', array(WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_NAME => WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_VALUE));
        $newValBinding->setReadOnly(true);
        $newValBinding->setBindingType(WFBindingSetup::WFBINDINGTYPE_MULTIPLE_PATTERN);
        $myBindings[] = $newValBinding;

        $newValBinding = new WFBindingSetup('label', 'The label for the link. The label is what the viewer sees as the link text.', array(WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_NAME => WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_VALUE));
        $newValBinding->setReadOnly(true);
        $newValBinding->setBindingType(WFBindingSetup::WFBINDINGTYPE_MULTIPLE_PATTERN);
        $myBindings[] = $newValBinding;

        $newValBinding = new WFBindingSetup('title', 'The title for the link. The title is often rendered as a "tooltip" by browsers.', array(WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_NAME => WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_VALUE));
        $newValBinding->setReadOnly(true);
        $newValBinding->setBindingType(WFBindingSetup::WFBINDINGTYPE_MULTIPLE_PATTERN);
        $myBindings[] = $newValBinding;

        return $myBindings;
    }

    function render($blockContent = NULL)
    {
        if ($this->hidden)
        {
            return NULL;
        }
        else
        {
            $class = NULL;
            if ($this->class)
            {
                $class = " class=\"{$this->class}\" ";
            }
            $target = NULL;
            if ($this->target)
            {
                $target = " target=\"{$this->target}\" ";
            }
            $label = ($this->label !== NULL ? $this->label : $this->value);
            $title = ($this->title ? " title=\"{$this->title}\" " : NULL);
            return "<a id=\"{$this->id}\" href=\"{$this->value}\" {$class}{$target}{$title}" . $this->getJSActions() . ">{$label}</a>" . $this->getListenerJSInScriptTag();
        }
    }


    function canPushValueBinding() { return false; }
}

?>
