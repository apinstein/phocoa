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
 * A YAHOO AutoComplete widget for our framework. This widget acts like a ComboBox: it is a text field with a pick-list and OPTIONAL custom entry.
 * 
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * none
 * 
 * <b>Optional:</b><br>
 */
class WFYAHOO_widget_AutoComplete extends WFYAHOO
{
    const DATASOURCE_JS_ARRAY       = 0;
    const DATASOURCE_JS_FUNCTION    = 1;
    const DATASOURCE_XHR            = 2;
 
    /**
     * @var integer Maximum length in chars of text input field. Default: unlimited.
     */
    protected $maxLength = NULL;
    /**
     * @var string The css width of the input field. Remember to specify like "200px" or "15em". Default: 200px.
     */
    protected $width = '200px';
    /**
     * @var string The css width of the autocomplete box. Remeber to specify like "100px" or "80%". Default: 100%.
     */
    protected $autocompleteWidth = '100%';
    /**
     * @var boolean TRUE to animate vertically. Default: true.
     */
    protected $animVert = NULL;
    /**
     * @var boolean TRUE to animate horizontally. Default: false.
     */
    protected $animHoriz = NULL;
    /**
     * @var float The number of seconds for the animation to take to "open" the autocomplete suggestions. Default: 0.3.
     */
    protected $animSpeed = NULL;
    /**
     * @var mixed The "delim" char used to help autocomplete multiple items. Can pass a single char or an array of single chars. Default: none;
     */
    protected $delimChar = NULL;
    /**
     * @var integer Maximum number of items to display in the autocomplete list. Default: 10
     */
    protected $maxResultsDisplayed = NULL;
    /**
     * @var integer Minimum number of chars that must be in the field before the autocomplete kicks in. Default: 1. Use 0 in conjunction with {WFYAHOO_widget_AutoComplete::$alwaysShowContainer alwaysShowContainer}.
     */
    protected $minQueryLength = NULL;
    /**
     * @var float The time in seconds that autocomplete waits from the last key entry until looking up results. Default: 0.5.
     */
    protected $queryDelay = NULL;
    /**
     * @var boolean Whether or not the first item in the list is automatically highlighted. Default: true.
     */
    protected $autoHighlight = NULL;
    /**
     * @var string The class name to apply to all li elements in the autocomplete results. Default: yui-ac-highlight
     */
    protected $highlightClassName = NULL;
    /**
     * @var string The class name to apply to all li elements during pre-click mouseover of mouse selection. Default: yui-ac-prehighlight
     */
    protected $prehighlightClassName = NULL;
    /**
     * @var boolean Whether or not to use a drop-shadow on the autocomplete container. Default: false. If you set to true, be sure to set up a call 'yui-ac-shadow'.
     */
    protected $useShadow = NULL;
    /**
     * @var boolean True to use an IFRAME in IE 5.x and IE 6 to fix a visual bug. If your autocomplete container appears on top of a form select element, you'll want to set this to true. Default: false.
     */
    protected $useIFrame = NULL;
    /**
     * @var boolean True to force users to choose one of the list items and not be allowed to enter their own text. Default: false
     */
    protected $forceSelection = NULL;
    /**
     * @var boolean True to cause the user's input to be automatically completed with the first match. Default: false
     */
    protected $typeAhead = NULL;
    /**
     * @var boolean All browser built-in "autocomplete" behavior in this field. Default: true. You may want to turn this off for sensitive data fields.
     */
    protected $allowBrowserAutocomplete = NULL;
    /**
     * @var boolean True to show the container even if there is no text entered. Default: false. Useful in conjunction with {@link WFYAHOO_widget_AutoComplete::$minQueryLength minQueryLength}.
     */
    protected $alwaysShowContainer = NULL;

    /**
     * @var integer Which of the DATASOURCE_* methods will be used for this instance.
     */
    protected $datasource = NULL;
    protected $datasourceMaxCacheEntries = NULL;
    protected $datasourceQueryMatchCase = NULL;
    protected $datasourceQueryMatchContains = NULL;
    protected $datasourceQueryMatchSubset = NULL;
    /**
     * @var array When using DATASOURCE_JS_ARRAY, this is the array of items. It can be a simple array, or an array of arrays. The latter will be used as multi-column data.
     */
    protected $datasourceJSArray = NULL;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);

        $this->importYahooJS("autocomplete/autocomplete-min.js");
    }

    function setupExposedBindings()
    {
        $myBindings = parent::setupExposedBindings();
        $newValBinding = new WFBindingSetup('datasourceJSArray', 'The array of items for DATASOURCE_JS_ARRAY mode.');
        $newValBinding->setReadOnly(true);
        $myBindings[] = $newValBinding;
        return $myBindings;
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            'maxLength',
            'width',
            'autocompleteWidth',
            'animVert' => array('true', 'false'),
            'animHoriz' => array('true', 'false'),
            'animSpeed',
            'delimChar',
            'maxResultsDisplayed',
            'minQueryLength',
            'queryDelay',
            'autoHighlight' => array('true', 'false'),
            'highlightClassName',
            'prehighlightClassName',
            'useShadow' => array('true', 'false'),
            'useIFrame' => array('true', 'false'),
            'forceSelection' => array('true', 'false'),
            'typeAhead' => array('true', 'false'),
            'allowBrowserAutocomplete' => array('true', 'false'),
            'alwaysShowContainer' => array('true', 'false'),
            'datasourceMaxCacheEntries',
            'datasourceQueryMatchCase' => array('true', 'false'),
            'datasourceQueryMatchContains' => array('true', 'false'),
            'datasourceQueryMatchSubset' => array('true', 'false'),
            ));
    }

    public function setDatasourceJSArray($a)
    {
        $this->datasource = WFYAHOO_widget_AutoComplete::DATASOURCE_JS_ARRAY;
        $this->datasourceJSArray = $a;
        sort($this->datasourceJSArray);
    }

    function restoreState()
    {
        //  must call super
        parent::restoreState();

        // look for the things in the form I need to restore state...
        if (isset($_REQUEST[$this->name]))
        {
            $this->value = $_REQUEST[$this->name];
        }
    }

    function render($blockContent = NULL)
    {
        if ($this->hidden)
        {
            return NULL;
        }
        else
        {
            // sanity checks
            if ($this->datasource === NULL) throw( new WFException("No datasource defined.") );

            // setup dependencies before calling parent::render()
            if ($this->animVert or $this->animHoriz)
            {
                $this->importYahooJS("animation/animation-min.js");
            }
            $html = parent::render($blockContent);

            $myWidgetContainer = "WFYAHOO_widget_AutoComplete_{$this->id}_container";
            $myAutoCompleteContainer = "WFYAHOO_widget_AutoComplete_{$this->id}_autocomplete";

            $myJSDatasourceVarName = "WFYAHOO_widget_AutoComplete_{$this->id}_datasource";
            $myJsAutoCompleteVarName = "WFYAHOO_widget_AutoComplete_{$this->id}";

            $html .= "
<style type=\"text/css\">
#{$myWidgetContainer} {
    position: relative;
    width: {$this->width};
}
#{$this->id} {
    width: 100%;
}
#{$myAutoCompleteContainer} {
    width: {$this->autocompleteWidth};
    position: absolute;
    background: white;
    overflow: hidden;
    margin: 0;
    padding: 0;
}
#{$myAutoCompleteContainer} ul {
    border: 1px solid black;
    padding: 5px 0;
    margin: 0;
}
#{$myAutoCompleteContainer} li.yui-ac-prehighlight {
    background: yellow;
}
#{$myAutoCompleteContainer} li.yui-ac-highlight {
    background: yellow;
}
</style>
            ";
            $html .= "
            <div id=\"{$myWidgetContainer}\">
                <input id=\"{$this->id}\" name=\"{$this->id}\" type=\"text\" value=\"{$this->value}\"" .
                ($this->valueForKey('maxLength') ? ' maxLength="' . $this->valueForKey('maxLength') . '" ' : '') .
                " /><div id=\"{$myAutoCompleteContainer}\"></div>
             </div>";
            $html .= $this->jsStartHTML();
            switch ($this->datasource) {
                case WFYAHOO_widget_AutoComplete::DATASOURCE_JS_ARRAY:
                    $myJSDatasourceArrayVarName = "WFYAHOO_widget_AutoComplete_{$this->id}_datasource_array";
                    $html .= "var {$myJSDatasourceArrayVarName} = [";
                    $first = true;
                    // we allow 
                    $multiColumnData = false;
                    foreach ($this->datasourceJSArray as $item) {
                        if ($first)
                        {
                            $first = false;
                            if (is_array($item))
                            {
                                $multiColumnData = true;
                            }
                        }
                        else
                        {
                            $html .= ", ";
                        }
                        if ($multiColumnData)
                        {
                            $subItems = $item;
                            array_walk($subItems, create_function('&$v,$k', '$v = \'"\' . str_replace(\'"\', \'\\"\', $v) . \'"\';'));
                            $html .= '[' . join(',', $subItems) . ']';
                        }
                        else
                        {
                            $html .= '"' . str_replace('"', '\\"', $item) . '"';
                        }
                    }
                    $html .= "];\n";
                    $html .= "var {$myJSDatasourceVarName} = new YAHOO.widget.DS_JSArray({$myJSDatasourceArrayVarName});";
                    break;
                default:
                    throw( new WFException("Unsupported datasource type.") );
            }
            // add properties to datasource
            $html .= $this->jsForSimplePropertyConfig($myJSDatasourceVarName, 'maxCacheEntries', $this->datasourceMaxCacheEntries);
            $html .= $this->jsForSimplePropertyConfig($myJSDatasourceVarName, 'queryMatchCase', $this->datasourceQueryMatchCase);
            $html .= $this->jsForSimplePropertyConfig($myJSDatasourceVarName, 'queryMatchContains', $this->datasourceQueryMatchContains);
            $html .= $this->jsForSimplePropertyConfig($myJSDatasourceVarName, 'queryMatchSubset', $this->datasourceQueryMatchSubset);
            // set up widget
            $html .= "\nvar {$myJsAutoCompleteVarName} = new YAHOO.widget.AutoComplete('{$this->id}','{$myAutoCompleteContainer}', {$myJSDatasourceVarName});\n";
            $html .= $this->jsForSimplePropertyConfig($myJsAutoCompleteVarName, 'animVert', $this->animVert);
            $html .= $this->jsForSimplePropertyConfig($myJsAutoCompleteVarName, 'animHoriz', $this->animHoriz);
            $html .= $this->jsForSimplePropertyConfig($myJsAutoCompleteVarName, 'animSpeed', $this->animSpeed);

            // calculate delimiter
            $delimJs = NULL;
            if (is_array($this->delimChar))
            {
                $delimJs = '[';
                $first = true;
                foreach ($this->delimChar as $c) {
                    if ($first)
                    {
                        $first = false;
                    }
                    else
                    {
                        $delimJs .= ', ';
                    }
                    $delimJs .= "'{$c}'";
                }
                $delimJs .= ']';
            }
            else if ($this->delimChar)
            {
                $delimJs = "'{$this->delimChar}'";
            }
            if ($delimJs)
            {
                $html .= "\n{$myJsAutoCompleteVarName}.delimChar = {$delimJs};\n";
            }

            $html .= $this->jsForSimplePropertyConfig($myJsAutoCompleteVarName, 'maxResultsDisplayed', $this->maxResultsDisplayed);
            $html .= $this->jsForSimplePropertyConfig($myJsAutoCompleteVarName, 'minQueryLength', $this->minQueryLength);
            $html .= $this->jsForSimplePropertyConfig($myJsAutoCompleteVarName, 'queryDelay', $this->queryDelay);
            $html .= $this->jsForSimplePropertyConfig($myJsAutoCompleteVarName, 'autoHighlight', $this->autoHighlight);
            $html .= $this->jsForSimplePropertyConfig($myJsAutoCompleteVarName, 'highlightClassName', $this->highlightClassName);
            $html .= $this->jsForSimplePropertyConfig($myJsAutoCompleteVarName, 'prehighlightClassName', $this->prehighlightClassName);
            $html .= $this->jsForSimplePropertyConfig($myJsAutoCompleteVarName, 'useShadow', $this->useShadow);
            $html .= $this->jsForSimplePropertyConfig($myJsAutoCompleteVarName, 'useIFrame', $this->IFrame);
            $html .= $this->jsForSimplePropertyConfig($myJsAutoCompleteVarName, 'forceSelection', $this->forceSelection);
            $html .= $this->jsForSimplePropertyConfig($myJsAutoCompleteVarName, 'typeAhead', $this->typeAhead);
            $html .= $this->jsForSimplePropertyConfig($myJsAutoCompleteVarName, 'allowBrowserAutocomplete', $this->allowBrowserAutocomplete);
            $html .= $this->jsForSimplePropertyConfig($myJsAutoCompleteVarName, 'alwaysShowContainer', $this->alwaysShowContainer);
            $html .= "
" . $this->jsEndHTML();
            return $html;
        }
    }

    function canPushValueBinding() { return true; }
}

?>
