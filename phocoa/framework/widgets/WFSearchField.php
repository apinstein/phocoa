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
 * NOTE: these events fire on $('searchFieldId'). So, $('mySearchField').observe('phocoa:WFSearchField:search', function(e) {});
 *
 * Javascript Public Functions:
 * PHOCOA.widgets.<id>.getValue() Gets the "real" value of the search field (ie, will still return NULL if the "placeholder" is showing).
 *
 * CSS classes:
 * phocoaWFSearchField_Container The div that contains the widget.
 * phocoaWFSearchField_Clear The span that contains the X button.
 * phocoaWFSearchField_Search The span that contains the GO button.
 * phocoaWFSearchField_PlaceholderText The class added to the input when the value is the "nullPlaceholder" value.
 *
 * <b>Required:</b><br>
 * - (none)
 * 
 * <b>Optional:</b><br>
 * - {@link WFSearchField::$nullPlaceholder nullPlaceholder}
 */
class WFSearchField extends WFTextField
{
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
        $this->nullPlaceholder = NULL;
    }

    function render($blockContent = NULL)
    {
        if ($this->hidden) return NULL;

        $this->setOnEvent('focus do j:PHOCOA.widgets.' . $this->id . '.handleFocus()');
        $this->setOnEvent('blur do j:PHOCOA.widgets.' . $this->id . '.handleBlur()');

        return '<div id="' . $this->id . '_container" class="phocoaWFSearchField_Container"><input type="text" id="' . $this->id() . '" name="' . $this->valueForKey('name') . '" value="' . htmlspecialchars($this->value) . '"' .
            ($this->valueForKey('size') ? ' size="' . $this->valueForKey('size') . '" ' : '') .
            ($this->valueForKey('maxLength') ? ' maxLength="' . $this->valueForKey('maxLength') . '" ' : '') .
            ($this->class ? ' class="' . $this->class . '"' : '') .
            ($this->valueForKey('enabled') ? '' : ' disabled readonly ') .
            ($this->nullPlaceholder ? ' placeholder="' . $this->nullPlaceholder . '" ' : NULL) .
            $this->getJSActions() . 
            ' style="padding-right: 15px;" />
            <span id="' . $this->id . '_clear" class="phocoaWFSearchField_Clear">X</span>
            <span id="' . $this->id . '_search" class="phocoaWFSearchField_Search">GO</span>
            </div><script>'
            . $this->getListenerJS() . 
            '
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
            PHOCOA.widgets.' . $this->id . '.handleClear = function() {
                $(\'' . $this->id . '\').value = "";
                PHOCOA.widgets.' . $this->id . '.handlePlaceholder();
                $(\'' . $this->id . '\').fire("phocoa:WFSearchField:clear");
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
            // fire events
            $(\'' . $this->id . '_clear\').observe("click", function(e) {
                PHOCOA.widgets.' . $this->id . '.handleClear();
            });
            $(\'' . $this->id . '_search\').observe("click", function(e) {
                $(\'' . $this->id . '\').fire("phocoa:WFSearchField:search");
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
            'nullPlaceholder'
            ));
    }
}
