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
class WFDieselKeyword extends WFWidget
{
    protected $maxLength;
    protected $size;
    /**
     * @var object WFDieselSearch The WFDieselSearch object that this facet is linked to.
     */
    protected $dieselSearch;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->maxLength = NULL;
        $this->size = NULL;
        $this->dieselSearch = NULL;
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            'maxLength',
            'size',
            ));
    }

    function setDieselSearch($ds)
    {
        $this->dieselSearch = $ds;
    }

    function restoreState()
    {
        //  must call super
        parent::restoreState();

        if (isset($_REQUEST[$this->name]))
        {
            $this->dieselSearch->setSimpleQuery($_REQUEST[$this->name]);
        }
    }

    function isKeywordQuery()
    {
        if ($this->dieselSearch->getSimpleQuery())
        {
            return true;
        }
        return false;
    }

    function facetSelectionHTML()
    {
        if ($this->dieselSearch->getSimpleQuery())
        {
            return $this->dieselSearch->getSimpleQuery() . '<br /><a href="' . $this->dieselSearch->getQueryState(WFDieselSearch::QUERY_STATE_SIMPLE_QUERY_ATTR_NAME) . '">remove</a>';
        }
        return NULL;
    }

    function label()
    {
        return 'Keywords';
    }

    function render($blockContent = NULL)
    {
        if ($this->hidden /* always show for now -- or $this->dieselSearch->getSimpleQuery() */) return NULL;

        return 'Keywords: <input type="text" id="' . $this->id() . '" name="' . $this->valueForKey('name') . '" value="' . $this->dieselSearch->getSimpleQuery() . '"' .
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
