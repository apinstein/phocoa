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
 * A Search field widget for our framework.
 * 
 * Javascript events:
 * - phocoa:WFSearchField:clear Fires when either ESC or the X are used, to clear the search query field.
 * - phocoa:WFSearchField:search Fires when either RET or the GO link are used, to perform the search.
 */
class WFSearchField extends WFTextField
{
    protected $placeholder;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->placeholder = NULL;
    }

    function render($blockContent = NULL)
    {
        if ($this->hidden) return NULL;

        $this->setOnEvent('focus do j:PHOCOA.widgets.' . $this->id . '.handleFocus()');
        $this->setOnEvent('blur do j:PHOCOA.widgets.' . $this->id . '.handleBlur()');

        return '<div id="' . $this->id . '_container" style="white-space: nowrap;"><input type="text" id="' . $this->id() . '" name="' . $this->valueForKey('name') . '" value="' . htmlspecialchars($this->value) . '"' .
            ($this->valueForKey('size') ? ' size="' . $this->valueForKey('size') . '" ' : '') .
            ($this->valueForKey('maxLength') ? ' maxLength="' . $this->valueForKey('maxLength') . '" ' : '') .
            ($this->class ? ' class="' . $this->class . '"' : '') .
            ($this->valueForKey('enabled') ? '' : ' disabled readonly ') .
            ($this->placeholder ? ' placeholder="' . $this->placeholder . '" ' : NULL) .
            $this->getJSActions() . 
            ' style="padding-right: 15px;" />
            <span id="' . $this->id . '_clear" style="position: relative; left: -15px;">X</span>
            </div><script>'
            . $this->getListenerJS() . 
            '
            PHOCOA.widgets.' . $this->id . '.hasFocus = false;
            PHOCOA.widgets.' . $this->id . '.handleFocus = function(e) {
                PHOCOA.widgets.' . $this->id . '.hasFocus = true;
                if ($F(\'' . $this->id . '\') === \'' . $this->placeholder . '\')
                {
                    $(\'' . $this->id . '\').value = null;
                }
                $(\'' . $this->id . '\').removeClassName("phocoaPlaceholderText");
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
                        $(\'' . $this->id . '\').value = \'' . $this->placeholder . '\';
                        $(\'' . $this->id . '\').addClassName("phocoaPlaceholderText");
                    }
                }
            };
            PHOCOA.widgets.' . $this->id . '.handleClear = function() {
                $(\'' . $this->id . '\').value = "";
                PHOCOA.widgets.' . $this->id . '.handlePlaceholder();
                $(\'' . $this->id . '\').fire("phocoa:WFSearchField:clear");
            };
            // perform initial check on search field value
            PHOCOA.widgets.' . $this->id . '.handlePlaceholder();
            // fire events
            $(\'' . $this->id . '_clear\').observe("click", function(e) {
                PHOCOA.widgets.' . $this->id . '.handleClear();
            });
            $(\'' . $this->id . '\').observe("keyup", function(e) {
                switch (e.keyCode) {
                    case Event.KEY_RETURN:
                        $(\'' . $this->id . '\').fire("phocoa:WFSearchField:search");
                        break;
                    case Event.KEY_ESC:
                        PHOCOA.widgets.' . $this->id . '.handleClear();
                        break;
                }
            }); 
            </script>';
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            'placeholder'
            ));
    }
}
