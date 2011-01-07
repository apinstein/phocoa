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
 *
 * @todo AJAX lookups seem to be failing... JSON is getting returned but no results displayed.
 */
class WFYAHOO_widget_AutoComplete extends WFYAHOO
{
    const DATASOURCE_JS_ARRAY       = 0;
    const DATASOURCE_JS_FUNCTION    = 1;
    const DATASOURCE_XHR            = 2;

    const INPUT_TYPE_TEXTFIELD      = 'input';
    const INPUT_TYPE_TEXTAREA       = 'textarea';
 
    /**
     * @var string The type of input to use. One of {@link WFYAHOO_widget_AutoComplete::INPUT_TYPE_TEXTAREA INPUT_TYPE_TEXTAREA} or {@link WFYAHOO_widget_AutoComplete::INPUT_TYPE_TEXTFIELD INPUT_TYPE_TEXTFIELD}. Default is INPUT_TYPE_TEXTFIELD.
     */
    protected $inputType = self::INPUT_TYPE_TEXTFIELD;
    /**
     * @var string The css width of the input. Remember to specify like "200px" or "15em". Default: 200px.
     */
    protected $width = '200px';
    /**
     * @var string The css height of the input. Remember to specify like "200px" or "15em". Only used for INPUT_TYPE_TEXTAREA. Default: 50px.
     */
    protected $height = '1em';
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
     * @var string A NULL placeholder
     */
    protected $nullPlaceholder = NULL;

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

    // dynamic data loading
    protected $dynamicDataLoader = NULL;
    protected $dynamicDataLoaderSchema = NULL;

    /**
     * @var boolean Add a toggle button for the list dropdown. Requires {@link minQueryLength} of 0 to work as expected.
     */
    protected $enableToggleButton = false;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);

        $this->yuiloader()->yuiRequire('autocomplete');

        $this->initializeWaitsForID = "WFYAHOO_widget_AutoComplete_{$this->id}_container";
        $this->nullPlaceholder = NULL;
    }

    public function setNullPlaceholder($v)
    {
        $this->nullPlaceholder = $v;
    }

    /**
     *  Set up a dataloader callback for dynamically loading autocomplete matches.
     *
     *  The callback function prototype is:
     *
     *  (array) loadMatchesForQuery($page, $params, $query)
     *
     *  return: array An array of assoc_arrays in structure ["QueryKey","AdditionalData1",..."AdditionalDataN"]
     *
     *  @param mixed Callback.
     *         string A method on the page delegate object to call to get the child nodes.
     *         array  A php callback structure.
     *  @param array An array strings with the schema of the data returned from the callback.
     *  @throws object WFException If the callback is invalid.
     */
    function setDynamicDataLoader($callback, $schema = array('k'))
    {
        $this->datasource = WFYAHOO_widget_AutoComplete::DATASOURCE_XHR;
        $this->yuiloader()->yuiRequire('connection');

        if (is_string($callback))
        {
            $callback = array($this->page()->delegate(), $callback);
        }
        if (!is_callable($callback)) throw( new WFException('Invalid callback.') );

        $this->dynamicDataLoader = $callback;
        if (!is_array($schema)) throw( new WFException("Schema must be an array.") );
        $this->dynamicDataLoaderSchema = $schema;
    }

    function setWidth($w)
    {
        $this->width = $w;
        return $this;
    }

    function setDynamicDataLoaderSchema($schema)
    {
        if (is_string($schema))
        {
            $schema = explode(',', $schema);
            $schema = array_map('trim', $schema);
        }
        if (!is_array($schema)) throw( new WFException("Schema must be an array.") );
        $this->dynamicDataLoaderSchema = $schema;
    }

    public function ajaxLoadData($page, $params)
    {
        if (!isset($_REQUEST['query'])) throw( new WFException("No query passed to ajaxLoadData.") );

        // perform query
        $callbackResults = call_user_func($this->dynamicDataLoader, $page, $params, $_REQUEST['query']);

        // initialize results structure
        $results = array();
        $results['results'] = array();
        if (count($callbackResults))
        {
            // sanity check results format
            if (count($callbackResults[0]) != count($this->dynamicDataLoaderSchema)) throw( new WFException("dynamicDataLoader returned a different number of items than declared in the schema.") );
            foreach ($callbackResults as $res) {
                $results['results'][] = array_combine( $this->dynamicDataLoaderSchema, $res );
            }
        }
        return new WFActionResponseJSON($results);
    }

    public function setEnableToggleButton($b)
    {
        if ($b)
        {
            $this->yuiloader()->yuiRequire('button');
        }
        $this->enableToggleButton = $b;
    }

    public function setAnimVert($b)
    {
        if ($b)
        {
            $this->yuiloader()->yuiRequire('animation');
        }
        $this->animVert = $b;
    }

    public function setAnimHoriz($b)
    {
        if ($b)
        {
            $this->yuiloader()->yuiRequire('animation');
        }
        $this->animHoriz = $b;
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
            'width',
            'height',
            'inputType' => array(self::INPUT_TYPE_TEXTFIELD, self::INPUT_TYPE_TEXTAREA),
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
            'nullPlaceholder',
            ));
    }

    public function setDatasourceJSArray($a, $sort = true)
    {
        $this->datasource = WFYAHOO_widget_AutoComplete::DATASOURCE_JS_ARRAY;
        $this->datasourceJSArray = $a;
        if ($sort)
        {
            sort($this->datasourceJSArray);
        }
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
        if ($this->hidden)
        {
            return NULL;
        }
        else
        {
            // sanity checks
            if ($this->datasource === NULL) throw( new WFException("No datasource defined.") );

            if ($this->nullPlaceholder)
            {
                $this->setOnEvent('focus do j:PHOCOA.widgets.' . $this->id . '.handleFocus()');
                $this->setOnEvent('blur do j:PHOCOA.widgets.' . $this->id . '.handleBlur()');
            }

            $html = parent::render($blockContent);

            $myAutoCompleteContainer = "WFYAHOO_widget_AutoComplete_{$this->id}_autocomplete";

            $html .= "
<style type=\"text/css\">
#{$this->initializeWaitsForID} {
    position: relative;
    width: {$this->width};
    margin-right: 12px; /* to compensate for browser dressing of input  */
    height: {$this->height};
}
#{$this->id} {
    width: 100%;
    height: {$this->height};
}
#{$this->id}_acToggle {
    position: absolute;
    left: {$this->width};
    padding-left: 5px;
}
#{$myAutoCompleteContainer} {
    top: {$this->height};
}
.yui-ac .yui-button {vertical-align:middle; }
.yui-ac .yui-button button {
    background: url({$this->getWidgetWWWDir()}/ac-arrow-rt.png) center center no-repeat;
} 
.yui-ac .open .yui-button button {background: url({$this->getWidgetWWWDir()}/ac-arrow-dn.png) center center no-repeat} 
</style>
            ";
            $html .= "
            <div id=\"{$this->initializeWaitsForID}\">";
            if ($this->inputType == self::INPUT_TYPE_TEXTFIELD)
            {
                $html .= "<input id=\"{$this->id}\" name=\"{$this->id}\" type=\"text\" value=\"" . htmlspecialchars($this->value) ."\" " .
                         ($this->nullPlaceholder ? ' placeholder="' . htmlspecialchars($this->nullPlaceholder) . '" ' : NULL) .
                         ($this->tabIndex ? ' tabIndex="' . $this->tabIndex . '" ' : NULL) .
                         $this->classHTML() . " />";
            }
            else if ($this->inputType == self::INPUT_TYPE_TEXTAREA)
            {
                $html .= "<textarea id=\"{$this->id}\" name=\"{$this->id}\"" . $this->classHTML() . ">" . htmlspecialchars($this->value) ."</textarea>";
            }
            else
            {
                throw( new WFException("inputType must be either INPUT_TYPE_TEXTFIELD or INPUT_TYPE_TEXTAREA.") );
            }
            if ($this->enableToggleButton)
            {
                $html .= "<span id=\"{$this->id}_acToggle\"></span>";
            }
            $html .= "<div id=\"{$myAutoCompleteContainer}\"></div>
            </div>";
            if ($this->nullPlaceholder)
            {
                $escapedNullPlaceholder = json_encode($this->nullPlaceholder);
                $html .= '<script>
                PHOCOA.namespace("widgets.' . $this->id . '");
                PHOCOA.widgets.' . $this->id . '.hasFocus = false;
                PHOCOA.widgets.' . $this->id . '.handleFocus = function(e) {
                    PHOCOA.widgets.' . $this->id . '.hasFocus = true;
                    if ($F(\'' . $this->id . '\') === ' . $escapedNullPlaceholder . ')
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
                        if ($F(\'' . $this->id . '\') === \'\')
                        {
                            $(\'' . $this->id . '\').value = ' . $escapedNullPlaceholder . ';
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
                PHOCOA.widgets.' . $this->id . '.handlePlaceholder();'
                . $this->getListenerJS() . 
                '</script>';
            }
            return $html;
        }
    }

    function initJS($blockContent)
    {
        $myAutoCompleteContainer = "WFYAHOO_widget_AutoComplete_{$this->id}_autocomplete";

        $html = "
        PHOCOA.widgets.{$this->id}.init = function() {
        ";
        switch ($this->datasource) {
            case WFYAHOO_widget_AutoComplete::DATASOURCE_JS_ARRAY:
                $html .= "var jsDSArray = [";
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
                $html .= "var acDatasource = new YAHOO.widget.DS_JSArray(jsDSArray);";
                break;
            case WFYAHOO_widget_AutoComplete::DATASOURCE_XHR:
                $html .= "
                    // need to create a PHOCOA.RPC to get the URL for the query
                    var acXHRRPC = new PHOCOA.WFRPC('" . WWW_ROOT . '/' . $this->page()->module()->invocation()->invocationPath() . "', '#page#{$this->id}', 'ajaxLoadData');
                ";
                $schema = array();
                foreach ($this->dynamicDataLoaderSchema as $field) {
                    $schema[] = array('key' => $field);
                }
                $html .= "
                    var acDatasource = new YAHOO.util.XHRDataSource(acXHRRPC.actionURL() + '?' + acXHRRPC.actionURLParams() + '&');
                    acDatasource.responseType = YAHOO.util.XHRDataSource.TYPE_JSON;
                    acDatasource.responseSchema = {
                        resultsList: 'results',
                             fields: " . WFJSON::encode($schema) . "
                    };
                    ";
                break;
            default:
                throw( new WFException("Unsupported datasource type.") );
        }
        // add properties to datasource
        $html .= $this->jsForSimplePropertyConfig('acDatasource', 'maxCacheEntries', $this->datasourceMaxCacheEntries);
        // set up widget
        $html .= "\nvar AutoCompleteWidget = new YAHOO.widget.AutoComplete('{$this->id}','{$myAutoCompleteContainer}', acDatasource);\n";
        $html .= "\nAutoCompleteWidget.queryQuestionMark = false;\n";
        $html .= $this->jsForSimplePropertyConfig('AutoCompleteWidget', 'animVert', $this->animVert);
        $html .= $this->jsForSimplePropertyConfig('AutoCompleteWidget', 'animHoriz', $this->animHoriz);
        $html .= $this->jsForSimplePropertyConfig('AutoCompleteWidget', 'animSpeed', $this->animSpeed);
        $html .= $this->jsForSimplePropertyConfig('AutoCompleteWidget', 'queryMatchCase', $this->datasourceQueryMatchCase);
        $html .= $this->jsForSimplePropertyConfig('AutoCompleteWidget', 'queryMatchContains', $this->datasourceQueryMatchContains);
        $html .= $this->jsForSimplePropertyConfig('AutoCompleteWidget', 'queryMatchSubset', $this->datasourceQueryMatchSubset);

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
            $html .= "\nAutoCompleteWidget.delimChar = {$delimJs};\n";
        }

        $html .= $this->jsForSimplePropertyConfig('AutoCompleteWidget', 'maxResultsDisplayed', $this->maxResultsDisplayed);
        $html .= $this->jsForSimplePropertyConfig('AutoCompleteWidget', 'minQueryLength', $this->minQueryLength);
        $html .= $this->jsForSimplePropertyConfig('AutoCompleteWidget', 'queryDelay', $this->queryDelay);
        $html .= $this->jsForSimplePropertyConfig('AutoCompleteWidget', 'autoHighlight', $this->autoHighlight);
        $html .= $this->jsForSimplePropertyConfig('AutoCompleteWidget', 'highlightClassName', $this->highlightClassName);
        $html .= $this->jsForSimplePropertyConfig('AutoCompleteWidget', 'prehighlightClassName', $this->prehighlightClassName);
        $html .= $this->jsForSimplePropertyConfig('AutoCompleteWidget', 'useShadow', $this->useShadow);
        $html .= $this->jsForSimplePropertyConfig('AutoCompleteWidget', 'useIFrame', $this->IFrame);
        $html .= $this->jsForSimplePropertyConfig('AutoCompleteWidget', 'forceSelection', $this->forceSelection);
        $html .= $this->jsForSimplePropertyConfig('AutoCompleteWidget', 'typeAhead', $this->typeAhead);
        $html .= $this->jsForSimplePropertyConfig('AutoCompleteWidget', 'allowBrowserAutocomplete', $this->allowBrowserAutocomplete);
        $html .= $this->jsForSimplePropertyConfig('AutoCompleteWidget', 'alwaysShowContainer', $this->alwaysShowContainer);
        if ($this->enableToggleButton)
        {
            $html .= <<<END
var bToggler = YAHOO.util.Dom.get("{$this->id}_acToggle");
var oPushButtonB = new YAHOO.widget.Button({container:bToggler});
oPushButtonB.on("click", function(e) {
    if(!YAHOO.util.Dom.hasClass(bToggler, "open")) {
        YAHOO.util.Dom.addClass(bToggler, "open")
    }

    // Is open
    if(AutoCompleteWidget.isContainerOpen()) {
        AutoCompleteWidget.collapseContainer();
    }
    // Is closed
    else {
        AutoCompleteWidget.getInputEl().focus(); // Needed to keep widget active
        setTimeout(function() { // For IE
            AutoCompleteWidget.sendQuery("");
        },0);
    }
});
AutoCompleteWidget.containerCollapseEvent.subscribe(function(){YAHOO.util.Dom.removeClass(bToggler, "open")});
END;
        }
        $html .= "\nPHOCOA.runtime.addObject(AutoCompleteWidget, '{$this->id}');\n";
        $html .= "\n};";
        return $html;
    }

    function canPushValueBinding() { return true; }
}

?>
