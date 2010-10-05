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
 * The Dieselpoint Faceted Navigation UI placeholder.
 * 
 * This widget coordinates all WFDieselFacet widgets (which are its children). It provides the "cookie trail" of the search and the overall search form.
 *
 * IMPORTANT: Any module/page using a DieselNav needs to have as its last 2 parameters:
 *
 * 'dpQueryState', 'paginatorState'
 *
 * The code presently assumes this "trailing" parameter order for constructing URLs to power ajax, links, etc.
 * It's possible a future version could be smart enough to handle these params at arbitrary positions and zero them out as needed when constructing URLs.
 *
 * You can have additional parameters, at the beginning, used with setBaseParams().
 * 
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * - {@link WFWidget::$value value} or {@link WFSelect::$values values}, depending on {@link WFSelect::$multiple multiple}.
 * 
 * <b>Optional:</b><br>
 * - {@link WFLabel::$ellipsisAfterChars ellipsisAfterChars}
 */
class WFDieselNav extends WFWidget
{
    /**
     * @var object WFDieselSearchHelper The WFDieselSearchHelper object that this facet is linked to.
     */
    protected $dieselSearchHelper;
    protected $facetNavOrder;
    protected $maxFacetsToShow;
    protected $baseURL;
    protected $dpQueryStateParamName;
    protected $facetNavHeight;  // css
    protected $searchAction;
    protected $showLoadingMessage;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->value = NULL;
        $this->dieselSearchHelper = NULL;
        $this->facetNavOrder = array();
        $this->maxFacetsToShow = 5;
        $this->baseURL = NULL;
        $this->dpQueryStateParamName = 'dpQueryState';
        $this->facetNavHeight = '100px';
        $this->searchAction = 'search';
        $this->showLoadingMessage = true;
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            'dieselSearchHelper',
            'facetNavOrder',
            'maxFacetsToShow',
            'facetNavHeight',
            'showLoadingMessage' => array('true', 'false'),
            'searchAction',
            ));
    }

    function facetNavHeight()
    {
        return $this->facetNavHeight;
    }

    function setFacetNavOrder($orderedList)
    {
        $this->facetNavOrder = array();
        $orderedIDs = explode(',', $orderedList);
        foreach ($orderedIDs as $id) {
            $this->facetNavOrder[] = trim($id);
        }
    }

    function setDieselSearch($ds)
    {
        $this->dieselSearchHelper = $ds;
    }

    function showLoadingMessage()
    {
        return $this->showLoadingMessage;
    }

    function baseURL()
    {
        if ($this->baseURL) return $this->baseURL;

        // calculate parameters up until our dpQueryState starts (after this point, we know what the last 4 params must be)
        $baseURLParams = NULL;
        foreach ($this->page()->parameters() as $pName => $value) {
            if ($pName == $this->dpQueryStateParamName) break;
            $baseURLParams .= "/{$value}";
        }
        
        // calculate base URL for links
        if ($this->page->module()->invocation()->targetRootModule() and !$this->page->module()->invocation()->isRootInvocation())
        {
            $this->baseURL = WWW_ROOT . '/' . $this->page->module()->invocation()->rootInvocation()->invocationPath() . $baseURLParams;
        }
        else
        {
            $this->baseURL = WWW_ROOT . '/' . $this->page->module()->invocation()->modulePath() . '/' . $this->page->pageName() . $baseURLParams;
        }
        return $this->baseURL;
    }

    function render($blockContent = NULL)
    {
        if ($this->hidden)
        {
            return NULL;
        }
        else
        {
            $html = NULL;

            if ($this->showLoadingMessage)
            {
                // set up loading container
                $loading = new WFYAHOO_widget_Panel("phocoaWFDieselNav_Loading_{$this->id}", $this->page);
                $loading->setBody('<div style="padding: 10px; font-size: 20px; line-height: 25px;">Searching... please wait...</div><div class="phocoaWFDieselNav_Loading"></div>');
                $loading->setWidth('400px');
                $loading->setHeight('125px');
                $loading->setFixedCenter(true);
                $loading->setClose(false);
                $loading->setModal(true);
                $loading->setDraggable(false);
                //$loading->setZIndex(100);
                //$loading->setIFrame(true);
                $html .= $loading->render();
            }

            // set up popup container
            $popup = new WFYAHOO_widget_Panel("phocoaWFDieselNav_Popup_{$this->id}", $this->page);
            $popup->setRenderTo("'{$this->getForm()->id()}'");
            $popup->setFixedCenter(true);
            $popup->setHeader('<div style="height: 10px"></div>');
            $popup->setBody("<div id=\"phocoaWFDieselNav_PopupContent_{$this->id}\" style=\"padding: 5px;\"></div><input " . ($this->showLoadingMessage ? 'onClick="cancelPopup(); showLoading();"' : NULL) . " type=\"submit\" name=\"action|" . $this->searchAction . "\" value=\"Go\"/>");
            $popup->setValueForKey('400px', 'width');
            $popup->setContext($this->id, 'tl', 'tl');
            //$popup->setIFrame(true);
            $popup->setModal(true);
            $html .= $popup->render();
            // js
            $html .= "
    <script type=\"text/javascript\">
    function doPopup(facetID, dpQueryState, facetSelections)
    {
        PHOCOA.runtime.getObject('phocoaWFDieselNav_Popup_{$this->id}').cfg.setProperty('context', ['{$this->id}', 'tl', 'tl']);
        PHOCOA.runtime.getObject('phocoaWFDieselNav_Popup_{$this->id}').show();
        Element.update('phocoaWFDieselNav_PopupContent_{$this->id}', '<div style=\"padding: 10px; font-size: 20px; line-height: 25px;\">Loading... please wait...</div><div class=\"phocoaWFDieselNav_Loading\"></div>');

        // use the baseURL to *remove* the existing query state and use the passed-in one instead, which excludes the selection for facetID
        var rpc = new PHOCOA.WFRPC('{$this->baseURL()}/' + dpQueryState, '#page#' + facetID, 'generatePopupHTML');
        rpc.method = 'post';
        rpc.callback.success = function() {};
        rpc.callback.failure = function() { alert('Failed to load popup data.'); };
        rpc.execute(encodeURIComponent(facetSelections), null);
    }
    function cancelPopup()
    {
        Element.hide('phocoaWFDieselNav_Popup_{$this->id}');
    }
    function facetHandleClick(newURL)
    {
        cancelPopup();
        showLoading();
        window.location.href = newURL;
    }
    function showLoading()
    {
    ";
    
            if ($this->showLoadingMessage)
            {
                $html .= "
        var loadingDlog = PHOCOA.runtime.getObject('phocoaWFDieselNav_Loading_{$this->id}');
        if (loadingDlog)
        {
            loadingDlog.show();
        }
    ";
            }
            $html .= "
    }
    </script>
    <div id=\"{$this->id}\">
            ";

            // show existing "filters" in proper order
            // prepare a list of children, keyed by ID
            $facetNavsByID = array();
            foreach ($this->children() as $facetNav) {
                $facetNavsByID[$facetNav->id()] = $facetNav;
            }
            // keep track of facets that "appear" in the interface, either as a selection or as a clickable facet so that they are not repeated
            $renderedList = array();
            // keep track of each item as rendered so we don't do it 2x
            $selectionRenderedList = array();
            
            // 1. render current selections / filters
            // first do items in desired order
            $filtersShownCount = 0;
            foreach ($this->facetNavOrder as $id) {
                if (!isset($facetNavsByID[$id])) throw( new Exception("Specified WFDieselFacet of id {$id} does not exist.") );

                $facetNav = $facetNavsByID[$id];
                if (!($facetNav instanceof WFDieselFacet)) continue;    // display only facets; skip keyword query
                $selectedHTML = $this->facetFilterNav($facetNav);
                if ($selectedHTML)
                {
                    $renderedList[$id] = true;
                    $filtersShownCount++;
                }
                $html .= $selectedHTML;
                $selectionRenderedList[$id] = true;
            }
            foreach ($facetNavsByID as $id => $facetNav) {
                if (!($facetNav instanceof WFDieselFacet)) continue;    // display only facets; skip keyword query
                if (!isset($selectionRenderedList[$id]))
                {
                    $selectedHTML = $this->facetFilterNav($facetNav);
                    if ($selectedHTML)
                    {
                        $renderedList[$id] = true;
                        $filtersShownCount++;
                    }
                    $html .= $selectedHTML;
                    $selectionRenderedList[$id] = true;
                }
            }
            // finally, do the "keyword" if there is one
            foreach ($this->children() as $widget) {
                if ($widget instanceof WFDieselKeyword)
                {
                    $keywordFilterHTML = $this->facetFilterNav($widget);
                    if ($keywordFilterHTML)
                    {
                        $filtersShownCount++;
                    }
                    $html .= $keywordFilterHTML;
                    break;
                }
            }
            // if there are any filter, offer a "clear all" link
            if ($filtersShownCount >= 2)
            {
                $html .= "\n<div class=\"phocoaWFDieselNav_FilterInfo\" style=\"border: 0\"><a " .
                        ($this->showLoadingMessage ? ' onClick="showLoading(); "' : '') . 
                        " href=\"" . $this->baseURL() . '/' . urlencode($this->dieselSearchHelper->getQueryStateWithRestrictDQLOnly()) . "\">Clear all filters</a></div>\n";
            }
            $html .= "<br clear=\"all\" />\n";

            // 2. Render "expanded" facets
            // prepare a list of children, keyed by ID
            $facetNavsByID = array();
            foreach ($this->children() as $facetNav) {
                $facetNavsByID[$facetNav->id()] = $facetNav;
            }
            $renderedCount = 0;
            $moreChoicesListIDs = array();
            // render widgets
            $html .= '<ul class="phocoaWFDieselNav_FacetList">';
            // first do items in desired order
            foreach ($this->facetNavOrder as $id) {
                if (isset($facetNavsByID[$id]))
                {
                    $facetNav = $facetNavsByID[$id];
                    if (!($facetNav instanceof WFDieselFacet)) continue;    // display only facets; skip keyword query

                    // only show up to max facets; the rest go in the "more" list
                    if ($renderedCount >= $this->maxFacetsToShow)
                    {
                        $moreChoicesListIDs[$id] = true;
                        continue;
                    }

                    $facetHTML = $facetNav->render();
                    if ($facetHTML)
                    {
                        $html .= "\n<li class='phocoaWFDieselNav_Facet'>{$facetHTML}</li>";
                        $renderedCount++;
                    }
                    $renderedList[$id] = true;
                }
                else
                {
                    throw( new Exception("Specified WFDieselFacet of id {$id} does not exist.") );
                }
            }
            // then do all remaining widgets
            foreach ($facetNavsByID as $id => $facetNav) {
                if (!($facetNav instanceof WFDieselFacet)) continue;    // display only facets; skip keyword query
                if (!isset($renderedList[$id]))
                {
                    // only show up to max facets; the rest go in the "more" list
                    if ($renderedCount >= $this->maxFacetsToShow)
                    {
                        $moreChoicesListIDs[$id] = true;
                        continue;
                    }

                    $facetHTML = $facetNav->render();
                    if ($facetHTML)
                    {
                        $html .= "\n<li class='phocoaWFDieselNav_Facet'>{$facetHTML}</li>";
                        $renderedCount++;
                    }
                    $renderedList[$id] = true;
                }
            }

            // 3. display "more choices" as needed
            if (count($moreChoicesListIDs))
            {
                $html .= "<li class='phocoaWFDieselNav_MoreChoices'><b>More Choices:</b>\n";
                $first = true;
                foreach ($moreChoicesListIDs as $id => $nothing) {
                    // skip already rendered items
                    if (isset($renderedList[$id])) continue;
                    if (isset($facetNavsByID[$id]))
                    {
                        $facetNav = $facetNavsByID[$id];
                        if (!($facetNav instanceof WFDieselFacet)) continue;    // display only facets; skip keyword query
                        if (!$first)
                        {
                            $html .= ", ";
                        }
                        $html .= $facetNav->editFacetLink($facetNav->label());
                    }
                    else
                    {
                        throw( new Exception("Specified WFDieselFacet of id {$id} does not exist.") );
                    }
                    $first = false;
                }
                $html .= "\n</li>\n";
            }
            
            $html .= "</ul></div>";
            return $html;
        }
    }

    function facetFilterNav($facet)
    {
        $html = NULL;
        $selectedFilterHTML = $facet->facetSelectionHTML();
        if ($selectedFilterHTML)
        {
            $html .= "
                <div class=\"phocoaWFDieselNav_FilterInfo\">
                " . $facet->label() . ":<br />
                <b>" . $selectedFilterHTML . "</b><br />";
                $editLink = $facet->editFacetLink();
                if ($editLink)
                {
                    $html .= "{$editLink}&nbsp;&nbsp;|&nbsp;&nbsp;";
                }
                $html .= $facet->removeFacetLink() . "
                </div>
            ";
        }
        return $html;
    }

    function canPushValueBinding() { return false; }
}

?>
