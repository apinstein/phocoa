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
 * Includes
 */
require_once('framework/widgets/WFWidget.php');

/**
 * A Dieselpoint Facet widget for our framework.
 * 
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * - {@link WFDieselFacet::$attributeID attributeID}
 * - {@link WFDieselFacet::$dieselSearch dieselSearch}
 * 
 * <b>Optional:</b><br>
 * - {@link WFWidget::$formatter formatter} Any formatter assigned to the WFDieselFacet will be used to format the facet data and current selection data.
 * - {@link WFDieselFacet::$rangeCount rangeCount}
 * - {@link WFDieselFacet::$showItemCounts showItemCounts}
 * - {@link WFDieselFacet::$maxHits maxHits}
 * - {@link WFDieselFacet::$maxRows maxRows}
 * - {@link WFDieselFacet::$label label}
 * - {@link WFDieselFacet::$sortByFrequency sortByFrequency}
 * - {@link WFDieselFacet::$enableShowAll enableShowAll}
 * - {@link WFDieselFacet::$enableShowSelection enableShowSelection}
 * - {@link WFDieselFacet::$class class}
 */
class WFDieselFacet extends WFWidget
{
    /**
     * @var string The Dieselpoint attribute id that this facet shows.
     */
    protected $attributeID;
    /**
     * @var object WFDieselSearch The WFDieselSearch object that this facet is linked to.
     */
    protected $dieselSearch;
    /**
     * @var int The number of ranges to show for the facet. Presently this will create N facets each containing approximately equal numbers of items. Default is 0, which disables range mode.
     */
    protected $rangeCount;
    /**
     * @var boolean If true, the number of items in each facet will be shown as well. Default is TRUE. Performance will be faster if this is set to FALSE.
     */
    protected $showItemCounts;
    /**
     * @var int The maximum number of hits to count for each facet. Defaults to SHOW EXACT COUNT (Integer.MAX_VALUE). Set to a lower number if you don't care about more than a certain number, like 1000. Set to 1 for maxium performance. NOTE: setting showItemCounts to FALSE will automatically set maxHits to 1.
     */
    protected $maxHits;
    /**
     * @var int The maximum number of facets to show. Defaults to -1, which means SHOW ALL ROWS.
     */
    protected $maxRows;
    /**
     * @var string The label to show for the facet.
     */
    protected $label;
    /**
     * @var boolean If true, facets will be sorted by frequency of each facet. In this case, the facet with the most "hits" will be first, etc. If false, facets are sorted by the value they represent. Default is TRUE.
     */
    protected $sortByFrequency;
    /**
     * @var boolean If true, will show a "show all" link if there is currently a selection for this facet.
     */
    protected $enableShowAll;
    /**
     * @var boolean If true, will show the currently selected facet next to the label.
     */
    protected $enableShowSelection;
    /**
     * @var string The CSS class of the span that encloses each facet.
     */
    protected $class;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->attributeID = NULL;
        $this->rangeCount = 0;
        $Integer = new JavaClass('java.lang.Integer');
        $this->maxHits = $Integer->MAX_VALUE;
        $this->maxRows = -1;            // unlimited by default
        $this->showItemCounts = true;
        $this->label = NULL;
        $this->sortByFrequency = true;
        $this->enableShowAll = true;
        $this->enableShowSelection = true;
    }

    function setShowItemCounts($show)
    {
        $this->showItemCounts = $show;
        if ($this->showItemCounts === false)
        {
            $this->maxHits = 1; // makes facet generation faster
        }
    }

    function setDieselSearch($ds)
    {
        $this->dieselSearch = $ds;
    }

    function setRangeCount($rc)
    {
        $this->rangeCount = $rc;
    }

    function render($blockContent = NULL)
    {
        if ($this->hidden)
        {
            return NULL;
        }
        else
        {
            if ($this->label)
            {
                $html = "<b>{$this->label}</b>";
                if ($this->enableShowSelection and $this->dieselSearch->isFilteringOnAttribute($this->attributeID))
                {
                    $html .= ": " .  $this->dieselSearch->getAttributeSelection($this->attributeID, $this->formatter);
                }
                $html .= "<br />\n";
            }
            else
            {
                $html = '';
            }

            // calculate base URL for links
            if ($this->page->module()->invocation()->targetRootModule() and !$this->page->module()->invocation()->isRootInvocation())
            {
                $baseURL = WWW_ROOT . '/' . $this->page->module()->invocation()->rootInvocation()->invocationPath();
            }
            else
            {
                $baseURL = WWW_ROOT . '/' . $this->page->module()->invocation()->modulePath() . '/' . $this->page->pageName();
            }

            // load facets
            try {
                if ($this->class)
                {
                    $classHTML = " class=\"{$this->class}\" ";
                }
                if ($this->enableShowAll and $this->dieselSearch->isFilteringOnAttribute($this->attributeID))
                {
                    $link = $baseURL . '/' . $this->dieselSearch->getQueryState($this->attributeID);
                    $html .= "<span {$classHTML}><a href=\"{$link}\">(show all)</a></span><br />";
                }
                $facetGenerator = new Java("com.dieselpoint.search.FacetGenerator", $this->dieselSearch->getGeneratorObject());
                if ($this->rangeCount)
                {
                    $facetGenerator->setRangeCount($this->rangeCount);
                }
                $facets = $facetGenerator->getList($this->attributeID, $this->maxRows, $this->sortByFrequency, $this->maxHits);
                $Array = new java_class("java.lang.reflect.Array"); // php-java-bridge zend iterators are broken; they crash on 0-length arrays. use for() loop
                for ($i = 0; $i < $Array->getLength($facets); $i++) {
                    $facet = $facets[$i];
                    $label = '';
                    if ($this->formatter)
                    {
                        $label .= $this->formatter->stringForValue($facet->getAttributeValue());
                    }
                    else
                    {
                        $label .= $facet->getAttributeValue();
                    }
                    if ($this->rangeCount and $facet->getEndValue())
                    {
                        $label .= ' - ';
                        if ($this->formatter)
                        {
                            $label .= $this->formatter->stringForValue($facet->getEndValue());
                        }
                        else
                        {
                            $label .= $facet->getEndValue();
                        }
                    }

                    $newAttrQueries = array();
                    if ($this->rangeCount)
                    {
                        if ($facet->getEndValue())
                        {
                            $newAttrQueries[] = "GE_{$this->attributeID}=" . $facet->getAttributeValue();
                            $newAttrQueries[] = "LE_{$this->attributeID}=" . $facet->getEndValue();
                        }
                        else
                        {
                            $newAttrQueries[] = "EQ_{$this->attributeID}=" . $facet->getAttributeValue();
                        }
                    }
                    else
                    {
                        $newAttrQueries = array("EQ_{$this->attributeID}=" . $facet->getAttributeValue());
                    }
                    $link = $baseURL . '/' . $this->dieselSearch->getQueryState($this->attributeID, $newAttrQueries);
                    $html .= "<span {$classHTML}><a href=\"{$link}\">{$label}</a>";
                    if ($this->showItemCounts)
                    {
                        $html .= ' (' . $facet->getHits() . ')';
                    }
                    $html .= "</span><br />\n";
                }
                return $html;
            } catch (JavaException $e) {
                $trace = new java("java.io.ByteArrayOutputStream");
                $e->printStackTrace(new java("java.io.PrintStream", $trace));
                throw( new Exception("java stack trace:<pre> $trace </pre>\n") );
            }
        }
    }


    function canPushValueBinding() { return false; }
}

?>
