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
 * - {@link WFTextArea::$nullPlaceholder nullPlaceholder}
 */
class WFTextArea extends WFWidget
{
    protected $cols;
    protected $rows;
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
        $this->cols = 40;
        $this->rows = 10;
        $this->nullPlaceholder = NULL;
    }

    private function normalizeLineEndings($text)
    {
        $text = str_replace("\r\n", "\n", $text);   // win -> un*x
        $text = str_replace("\r", "\n", $text);     // mac -> un*x
        return $text;
    }
    function restoreState()
    {
        //  must call super
        parent::restoreState();

        if (isset($_REQUEST[$this->name]))
        {
            $reqVal = $this->normalizeLineEndings($_REQUEST[$this->name]);
            $nullPlaceholder = $this->normalizeLineEndings($this->nullPlaceholder);

            if ($reqVal !== $nullPlaceholder)
            {
                $this->setValue($_REQUEST[$this->name]);
            }
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

        $html = '<textarea name="' . $this->name() . '" id="' . $this->id() . '"' . 
            ($this->class ? ' class="' . $this->class . '" ' : '') .
            ($this->cols ? ' cols="' . $this->cols . '" ' : '') .
            ($this->rows ? ' rows="' . $this->rows . '" ' : '') .
            ($this->valueForKey('enabled') ? '' : ' disabled ') .       // used to add readonly here too, but not sure why. may have been a browser compatibility thing, but seems to work now (tested on ie6/ie7/ff2/ff3/safari3)
            ($this->nullPlaceholder ? ' placeholder="' . $this->nullPlaceholder . '" ' : NULL) .
            '>' . $this->value . '</textarea>
            <script>'
            . $this->getListenerJS();
        if ($this->nullPlaceholder)
        {
            $html .= '
            PHOCOA.namespace("widgets.' . $this->id . '");
            PHOCOA.widgets.' . $this->id . '.nullPlaceholder = ' . json_encode($this->nullPlaceholder) . ';
            PHOCOA.widgets.' . $this->id . '.hasFocus = false;
            PHOCOA.widgets.' . $this->id . '.handleFocus = function(e) {
                PHOCOA.widgets.' . $this->id . '.hasFocus = true;
                if ($F(\'' . $this->id . '\') === PHOCOA.widgets.' . $this->id . '.nullPlaceholder)
                {
                    $(\'' . $this->id . '\').value = "";
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
                    if ($F(\'' . $this->id . '\') === \'\' || $F(\'' . $this->id . '\') === PHOCOA.widgets.' . $this->id . '.nullPlaceholder)
                    {
                        $(\'' . $this->id . '\').value = PHOCOA.widgets.' . $this->id . '.nullPlaceholder;
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
        $html .=  '</script>';
        return $html;
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            'cols',
            'rows',
            ));
    }

    function setupExposedBindings()
    {
        $myBindings = parent::setupExposedBindings();
        $myBindings[] = new WFBindingSetup('rows', 'The # of rows in the text area (in HTML).');
        $myBindings[] = new WFBindingSetup('columns', 'The # of columns in the text area (in HTML).');
        return $myBindings;
    }

    function canPushValueBinding() { return 'true'; }
}

?>
