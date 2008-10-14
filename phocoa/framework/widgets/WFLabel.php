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
 * A Label widget for our framework.
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
 * - {@link WFLabel::$ellipsisAfterChars ellipsisAfterChars}
 * - {@link WFLabel::$textAsHTML textAsHTML} 
 * - {@link WFLabel::$raw raw}
 */
class WFLabel extends WFWidget
{
    /**
     * @var integer Number of chars after which the string should be ellipsised. Default UNLIMITED.
     */
	protected $ellipsisAfterChars;
    /**
     * @var boolean TRUE to convert newlines to html BR entities. False to output string as text.
     */
    protected $textAsHTML;
    /**
     * @var boolean TRUE to output ONLY the value. False to produce HTML with a span tag and ID and support other features.
     */
    protected $raw;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->value = NULL;
        $this->ellipsisAfterChars = NULL;
        $this->textAsHTML = false;
        $this->raw = false;
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            'ellipsisAfterChars',
            'textAsHTML',
            ));
    }
    function setupExposedBindings()
    {
        $myBindings = parent::setupExposedBindings();
        // do we need this custom binding setup here? wasn't WFBINDINGTYPE_MULTIPLE_PATTERN moved to WFView?
        $newValBinding = new WFBindingSetup('value', 'The selected value for non-multiple select boxes.', array(WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_NAME => WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_VALUE));
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
            // are we in RAW mode?
            if ($this->raw)
            {
                return $this->value;
            }

            if ($this->textAsHTML)
            {
                $text = str_replace("\n", "<br />", $this->value);
            }
            else
            {
                $text = $this->value;
            }
        	if ( $this->ellipsisAfterChars !== NULL && strlen( $text ) >= $this->ellipsisAfterChars)
        	{
                $text = substr($text, 0, $this->ellipsisAfterChars) . '...';
			}
            return '<span id="' . $this->id . '">' . $text . '</span>' . $this->getListenerJSInScriptTag();
        }
    }


    function canPushValueBinding() { return false; }
}

?>
