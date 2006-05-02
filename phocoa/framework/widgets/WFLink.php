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
 *
 * @todo Do we need a way to specify a module/page for creating links dynamically? Either a parseable syntax in the "value" field or additional properties?
 */
class WFLink extends WFWidget
{
    /**
     * @var string The class for the link.
     */
	protected $class;
    /**
     * @var string The label for the link.
     */
	protected $label;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->class = NULL;
        $this->label = NULL;
    }

    function setupExposedBindings()
    {
        $myBindings = parent::setupExposedBindings();
        $newValBinding = new WFBindingSetup('value', 'The href value for the link.', array(WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_NAME => WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_VALUE));
        $newValBinding->setReadOnly(true);
        $newValBinding->setBindingType(WFBindingSetup::WFBINDINGTYPE_MULTIPLE_PATTERN);
        $myBindings[] = $newValBinding;

        $newValBinding = new WFBindingSetup('label', 'The label for the link.', array(WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_NAME => WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_VALUE));
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
            return "<a href=\"{$this->value}\" {$class}>{$this->label}</a>";
        }
    }


    function canPushValueBinding() { return false; }
}

?>
