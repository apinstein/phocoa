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
 * A TextField widget for our framework.
 *
 * <b>Required:</b><br>
 * - (none)
 * 
 * <b>Optional:</b><br>
 * - {@link WFTextField::$maxLength maxLength}
 * - {@link WFTextField::$size size}
 * - {@link WFTextField::$nullPlaceholder nullPlaceholder}
 */
class WFTextField extends WFWidget
{
    protected $maxLength;
    protected $size;
    /**
     * @var string The "placeholder" value to show in the search box if it is empty. Default NULL.
     */
    protected $nullPlaceholder;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->maxLength = NULL;
        $this->size = NULL;
        $this->nullPlaceholder = NULL;
    }

    function restoreState()
    {
        //  must call super
        parent::restoreState();

        // look for the things in the form I need to restore state...
        if (isset($_REQUEST[$this->name]) and $this->nullPlaceholder !== $_REQUEST[$this->name])
        {
            $this->value = $_REQUEST[$this->name];
        }
    }

    function render($blockContent = NULL)
    {
        if ($this->hidden) return NULL;

        if ($this->nullPlaceholder)
        {
            $this->setOnEvent('focus do j:PHOCOA.widgets.' . $this->id . '.handleFocus()');
            $this->setOnEvent('blur do j:PHOCOA.widgets.' . $this->id . '.handleBlur()');
        }
        $html = '<input type="text" id="' . $this->id() . '" name="' . $this->valueForKey('name') . '" value="' . htmlspecialchars($this->value) . '"' .
            ($this->valueForKey('size') ? ' size="' . $this->valueForKey('size') . '" ' : '') .
            ($this->valueForKey('maxLength') ? ' maxLength="' . $this->valueForKey('maxLength') . '" ' : '') .
            ($this->class ? ' class="' . $this->class . '"' : '') .
            ($this->valueForKey('enabled') ? '' : ' disabled readonly ') .
            ($this->nullPlaceholder ? ' placeholder="' . $this->nullPlaceholder . '" ' : NULL) .
            $this->getJSActions() . 
            '/>
            <script>'
            . $this->getListenerJS();
        if ($this->nullPlaceholder)
        {
            $html .= '
            PHOCOA.namespace("widgets.' . $this->id . '");
            PHOCOA.widgets.' . $this->id . '.hasFocus = false;
            PHOCOA.widgets.' . $this->id . '.handleFocus = function(e) {
                PHOCOA.widgets.' . $this->id . '.hasFocus = true;
                if ($F(\'' . $this->id . '\') === \'' . $this->nullPlaceholder . '\')
                {
                    $(\'' . $this->id . '\').value = null;
                }
                $(\'' . $this->id . '\').removeClassName("phocoaWFSearchField_PlaceholderText");
            };
            PHOCOA.widgets.' . $this->id . '.handleBlur = function(e) {
                PHOCOA.widgets.' . $this->id . '.hasFocus = false;
                PHOCOA.widgets.' . $this->id . '.handlePlaceholder();
            };
            PHOCOA.widgets.' . $this->id . '.handlePlaceholder = function() {
                if (!PHOCOA.widgets.' . $this->id . '.hasFocus)
                {
                    if ($F(\'' . $this->id . '\') === \'\')
                    {
                        $(\'' . $this->id . '\').value = \'' . $this->nullPlaceholder . '\';
                        $(\'' . $this->id . '\').addClassName("phocoaWFSearchField_PlaceholderText");
                    }
                }
            };
            PHOCOA.widgets.' . $this->id . '.getValue = function() {
                var qField = $(\'' . $this->id . '\');
                var qVal = null;
                if (qField.getAttribute("placeholder") !== $F(qField))
                {
                    qVal = $F(qField);
                }
                return qVal;
            };
            // perform initial check on search field value
            PHOCOA.widgets.' . $this->id . '.handlePlaceholder();
            ';
        }
        $html .= '</script>';
        return $html;
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            'maxLength',
            'size',
            ));
    }
    function setupExposedBindings()
    {
        $myBindings = parent::setupExposedBindings();
        $myBindings[] = new WFBindingSetup('maxLength', 'The maxLength of the text field (in HTML).');
        $myBindings[] = new WFBindingSetup('size', 'The size of the text field (in HTML).');
        return $myBindings;
    }

    function canPushValueBinding() { return true; }
}

?>
