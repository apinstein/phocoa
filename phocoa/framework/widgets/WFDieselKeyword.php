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
 * A Dieselpoint Keyword search widget for our framework.
 *
 * NOTE: by default, whenever a keyword is entered (see {@link WFDieselpoint::setSimpleQuery() setSimpleQuery}, the 
 * default sort will be set to sort by relevance instead of whatever the existing default is. Of course
 * this can be overridden with the Paginator Sort controls.
 */
class WFDieselKeyword extends WFWidget implements WFDieselSearchHelperStateTracking
{
    protected $maxLength;
    protected $size;
    /**
     * @var object WFDieselSearch The WFDieselSearch object that this facet is linked to.
     */
    protected $dieselSearchHelper;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->maxLength = NULL;
        $this->size = NULL;
        $this->dieselSearchHelper = NULL;
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            'maxLength',
            'size',
            ));
    }

    function setDieselSearchHelper($ds)
    {
        $this->dieselSearchHelper = $ds;
    }

    function dieselSearchRestoreState()
    {
        if (isset($_REQUEST[$this->name]))
        {
            $this->dieselSearchHelper->clearSimpleQuery();
            $this->dieselSearchHelper->setSimpleQuery($_REQUEST[$this->name]);
        }
    }

    function allConfigFinishedLoading()
    {
        $this->dieselSearchHelper->registerWidget($this);
    }

    function isKeywordQuery()
    {
        if ($this->dieselSearchHelper->simpleQuery())
        {
            return true;
        }
        return false;
    }

    function label()
    {
        return 'Keywords';
    }

    function facetSelectionHTML()
    {
        $html = NULL;
        // SHOW CURRENT SELECTION
        if ($this->isKeywordQuery())
        {
            $html .= $this->dieselSearchHelper->simpleQuery();
        }
        return $html;
    }
    function editFacetLink()
    {
        return NULL;
    }
    function removeFacetLink($linkText = "Remove")
    {
        $showLoadingJS = NULL;
        if ($this->parent()->showLoadingMessage())
        {
            $showLoadingJS = " onClick=\"showLoading();\" ";
        }
        return "<a {$showLoadingJS} href=\"" . $this->parent()->baseURL() . '/' . urlencode($this->dieselSearchHelper->getQueryStateWithoutSimpleQuery()) . "\">{$linkText}</a>";
    }
    
    function render($blockContent = NULL)
    {
        if ($this->hidden /* always show for now -- or $this->dieselSearchHelper->getSimpleQuery() */) return NULL;

        return 'Keywords: <input type="text" id="' . $this->id() . '" name="' . $this->valueForKey('name') . '" ' .
            // always get value from the dieselSearchHelper, not the local value, because it can be changed outside of this widget
            'value="' . $this->dieselSearchHelper->simpleQuery() . '"' .
            ($this->valueForKey('size') ? ' size="' . $this->valueForKey('size') . '" ' : '') .
            ($this->valueForKey('maxLength') ? ' maxLength="' . $this->valueForKey('maxLength') . '" ' : '') .
            ($this->class ? ' class="' . $this->class . '"' : '') .
            ($this->valueForKey('enabled') ? '' : ' disabled readonly ') .
            $this->getJSActions() . 
            '/>';
    }

    /********************* BINDINGS SETUP ************************/
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
