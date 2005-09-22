<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/** 
* @package UI
* @subpackage Pagination
* @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
* @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
* @author Alan Pinstein <apinstein@mac.com>                        
*/

require_once('framework/WFObject.php');
/**
 * Base paginator class.
 *
 * PHOCOA has its own pagination widgets. So that our pagination widgets can work with any pagination infrastructure, we have a WFPaginator.
 * 
 * The pagination widgets interface with the paged data via WFPaginator. To hook up the PHOCOA widgets with your pagination infrastructure, you simply
 * need to write an adapter class that implements {@link WFPagedData}. This will allow the WFPaginator to page your data. PHOCOA ships with drivers for Array and Propel.
 *
 * Because of this architecture, the pagination widgets of PHOCOA can be easily "bound" to a pagination instance and make the display of paginated data easy.
 *
 * The PHOCOA pagination infrastructure includes support for multi-key sorting as well.
 *
 * To use the pagination infrastructure, the first thing to figure out is which {@link WFPaginator::$mode} you need to use. MODE_URL effects all pagination via plain-old-urls, which
 * is more compatible, but of course cannot interact with form data (for instance a search form on the same page). MODE_FORM effects all pagination via javascript and your form. 
 *
 * Once you have decided on the mode that's right for you, you must set up the paginator. Here's an example:
 * - Declare a WFPaginator instance in your shared instances.
 * - Declare a {@link WFPaginatorState} in your page, and configure the {@link WFPaginatorState::$paginator} element. NOTE: Only needed for MODE_FORM.
 * - Declare a {@link WFPaginatorNavigation} in your page, and configure the {@link WFPaginatorNavigation::$paginator} element.
 * - Declare a {@link WFPaginatorPageInfo} in your page, and configure the {@link WFPaginatorPageInfo::$paginator} element.
 * - Declare a {@link WFPaginatorSortLink} in your page, for each link that will sort, and configure the {@link WFPaginatorSortLink::$paginator} and {@link WFWidget::$value} elements. The value of a WFPaginatorSortLink is the sortKey that the link is for, without the +/-.
 * - Configure the paginator in the sharedInstancesDidLoad method:
 * <code>
 *     $this->myPaginator->setSortOptions(array('+price' => 'Price', '-price' => 'Price'));
 *     $this->myPaginator->setDefaultSortKeys(array('+price'));
 *     // if you want to use MODE_FORM, add this line:
 *     $this->myPaginator->setModeForm('paginatorStateID', 'formSubmitID');
 * </code>
 * - Set up the data:
 * <code>
 *     $this->myPaginator->setDataDelegate(new WFPagedPropelQuery($criteria, 'MyPeer'));
 *     // if you are using MODE_URL, use this line
 *     $this->myPaginator->setPaginatorState($params['paginatorStateID']);  // don't forget to set up your params for the page to grab the first param as the paginator info
 *     // if you are using MODE_FORM, use this line
 *     $this->myPaginator->setPaginatorState($page->outlet('paginatorStateID')->value());
 *     $this->myArrayController->setContent($this->myPaginator->currentItems());
 * </code>
 * 
 * NOTE: The first page is page 1 (as opposed to page 0).
 *
 * @see WFPaginatorNavigation, WFPaginatorPageInfo, WFPaginatorSortLink, WFPaginatorState
 * @see WFPagedArray, WFPagedPropelQuery
 * @todo What needs to be done with bindings, anything?
 */
class WFPaginator extends WFObject
{
    /**
     * @var integer The total item count for the paginated data. This is actually a cached value, see {@link WFPaginator::itemCount() itemCount}.
     */
    protected $itemCount;
    /**
     * @var array An array of items representing the current page of items. This is actually a cached value, see {@link WFPaginator::currentItems() currentItems}.
     */
    protected $currentItems;
    /**
     * @var integer The current page number. The first page is page 1.
     */
    protected $currentPage;
    /**
     * @var integer The number of items that can fit on a single page. Default is {@link WFPaginator::PAGINATOR_PAGESIZE_ALL all items}.
     */
    protected $pageSize;
    /**
     * @var object WFPagedData An object conforming to the {@link WFPagedData WFPagedData} interface.
     */
    protected $dataDelegate;
    /**
     * @var string The name of a single item being paged. Example: "person".
     */
    protected $itemPhraseSingular;
    /**
     * @var string The name of mutliple items being paged. Example: "people".
     */
    protected $itemPhrasePlural;
    /**
     * @var assoc_array An associative array of sort options for the current paginator. In the format of array('sortKey' => 'Sort Description'). The sortKeys will be managed by WFPaginator and passed to the WFPagedData interface for actual implementation.
     * IMPORTANT: if you name your sortKeys properly, the paginator can help you with toggling asc/desc. Precede your sortKey with + or - like so: "+name", "-name".
     */
    protected $sortOptions;
    /**
     * @var array An array in order of all sort keys that are applied to the paginator.
     */
    protected $sortKeys;
    /**
     * @var array The default sort keys to use if sortKeys is empty.
     */
    protected $defaultSortKeys;
    /**
     * @var string The ID of the paginatorState WFPaginatorState in the form. Only used if MODE_FORM.
     */
    protected $paginatorStateID;
    /**
     * @var string THe ID of the form button to click to re-submit the form. Only used if MODE_FORM.
     */
    protected $submitID;

    /**
     * @const Make the paginator use URL mode, which will produce pagination links via standard HTML links without Javascript.
     */
    const MODE_URL = 1;
    /**
     * @const Make the paginator use FORM mode, which will produce pagination links that use javascript to manipulate a form's data {@link WFPaginatorState} and then submits sthe form.
     */
    const MODE_FORM = 2;
    /**
     * @const Make all results show on a single page.
     */
    const PAGINATOR_PAGESIZE_ALL = -1;
    /**
     * @const A constant for the "first" page.
     */
    const PAGINATOR_FIRST_PAGE = 1;

    function __construct()
    {
        parent::__construct();

        $this->itemCount = NULL;
        $this->currentItems = NULL;
        $this->currentPage = WFPaginator::PAGINATOR_FIRST_PAGE;
        $this->pageSize = WFPaginator::PAGINATOR_PAGESIZE_ALL;
        $this->dataDelegate = NULL;
        $this->itemPhraseSingular = "Item";
        $this->itemPhrasePlural = "Items";
        $this->sortOptions = array();
        $this->sortKeys = array();
        $this->defaultSortKeys = array();
        $this->mode = WFPaginator::MODE_URL;
        $this->paginatorStateID = NULL;
        $this->submitID = NULL;
    }

    function setModeForm($paginatorStateID = NULL, $submitID = NULL)
    {
        $this->mode = WFPaginator::MODE_FORM;
        if ($paginatorStateID)
        {
            $this->paginatorStateID = $paginatorStateID;
        }
        if ($submitID)
        {
            $this->submitID = $submitID;
        }
    }

    function setModeURL()
    {
        $this->mode = WFPaginator::MODE_URL;
    }

    function mode()
    {
        return $this->mode;
    }

    /**
     *  Inform the paginator of all sort options that can be used via the UI.
     *
     *  The {@link WFPaginatorSortLink} widget will use this info to effect sorting.
     *
     *  @param assoc_array An associative array of sortKey => DisplayName.
     *                     IMPORTANT: WFPaginator expects sort keys to be named +sortKey and -sortKey to indicate ascending or descending sort.
     *                     The display name will be shown by the sort widgets.
     *  @throws Exception if an array is not passed in.
     *  @see WFPaginator::$sortOptions
     */
    function setSortOptions($opts)
    {
        if (!is_array($opts)) throw( new Exception("Sort options must be an array.") );
        $this->sortOptions = $opts;
    }

    /**
     *  Get the assigned sort options for the paginator.
     */
    function sortOptions()
    {
        return $this->sortOptions;
    }

    /**
     *  Get the effective sortKeys for the paginator.
     *
     *  This function will use the default sortKeys if there are no sortKeys set.
     *
     *  @return array An array of sortKeys that are part of the sortOptions.
     */
    function sortKeys()
    {
        if (count($this->sortKeys) == 0)
        {
            return $this->defaultSortKeys;
        }
        return $this->sortKeys;
    }
    
    /**
     *  Add a sortKey to the current paginator. Call multiple times for a multi-key-sort.
     *
     *  @param string A sortKey to use.
     *  @throws Exception if the sortKey does not exist in sortOptions.
     */
    function addSortKey($key)
    {
        if (!isset($this->sortOptions[$key])) throw( new Exception("Sort key '$key' not available in sortOptions.") );
        $this->sortKeys[] = $key;
    }

    /**
     *  Remove all sort information for the paginator.
     *
     */
    function clearSortKeys()
    {
        $this->sortKeys = array();
    }

    /**
     *  Set the default sort keys that will be used if no other sort keys are added.
     *
     *  @param array An array of sortKeys.
     *  @throws Exception if $keys is not an array.
     */
    function setDefaultSortKeys($keys)
    {
        if (!is_array($keys)) throw( new Exception('setDefaultSortKeys() requires an array of sortKeys.') );

        $this->defaultSortKeys = $keys;
    }
    
    /**
     *  Set the names of the items being paged.
     *
     *  @param string $singular The singular name, example "person".
     *  @param string $plural The plural name, example "people". Leave blank to use singluar for plural as well, example: "fish".
     */
    function setItemPhrase($singular, $plural = NULL)
    {
        $this->itemPhraseSingular = $singular;
        if (is_null($plural))
        {
            $this->itemPhrasePlural = $singular;
        }
        else
        {
            $this->itemPhrasePlural = $plural;
        }
    }

    /**
     *  Get the item phrase for the passed count. Returns singular or plural name based on count.
     *
     *  @param integer Count of items.
     *  @return string
     */
    function itemPhrase($count)
    {
        if ($count == 1)
        {
            return $this->itemPhraseSingular;
        }
        else
        {
            return $this->itemPhrasePlural;
        }
    }

    /**
     *  Set the data delegate.
     *
     *  The WFPaginator instance will call out to the data delegate to fetch the paged data.
     *
     *  @param object WFPagedData The object implementing WFPagedData to use.
     *  @throws Exception if the object is not valid.
     *  @see WFPagedArray, WFPagedPropelQuery
     */
    function setDataDelegate($delegate)
    {
        if (!is_object($delegate)) throw( new Exception("Passed delegate is not an object.") );
        if (!in_array('WFPagedData', class_implements($delegate))) throw( new Exception("Delegate object " . get_class($delegate) . " does not implement WFPagedData.") );

        $this->dataDelegate = $delegate;
    }

    /**
     *  Get the data delegate.
     *
     *  @return object WFPagedData object; the data delegate.
     *  @throws Exception if no delegate assigned.
     */
    function dataDelegate()
    {
        if ($this->dataDelegate === NULL) throw( new Exception("No dataDelegate assigned.") );
        return $this->dataDelegate;
    }

    /**
     *  Load all data from the delegate.
     *
     *  NOTE: won't reload if alread cached. Use {@link WFPaginator::reloadData() reloadData} instead.
     */
    function loadData()
    {
        $this->itemCount();
        $this->currentItems();
    }

    /**
     *  Clear all caches and reload the data from the delegate.
     */
    function reloadData()
    {
        // reset cache
        $this->itemCount = NULL;
        $this->currentItems = NULL;

        // reload data
        $this->loadData();
    }

    /**
     *  Get the current page that the paginator is on.
     *
     *  @return integer The current page number. The first page is page 1.
     */
    function currentPage()
    {
        return $this->currentPage;
    }

    /**
     *  Set the current page for the paginator.
     *
     *  @param integer $pageNum Set the current page; the first page is page 1.
     */
    function setCurrentPage($pageNum)
    {
        $this->currentPage = $pageNum;
    }

    /**
     *  Get the number of the first item in the current page.
     *
     *  @return integer The first item of the current page. Item 1 is the first item.
     */
    function startItem()
    {
        return (1 + ($this->pageSize * ($this->currentPage - 1)));
    }

    /**
     *  Get the number of the last item in the current page.
     *
     *  @return integer The last item of the current page. Item 1 is the first item.
     */
    function endItem()
    {
        return min($this->itemCount(), $this->startItem() + $this->pageSize - 1);
    }

    /**
     *  Get the number of items in a single page.
     *
     *  @return integer
     */
    function pageSize()
    {
        return $this->pageSize;
    }

    /**
     *  Set the number of items per page.
     *
     *  @param integer $sz Number of items per page.
     */
    function setPageSize($sz)
    {
        $this->pageSize = $sz;
    }

    /**
     *  Get the current total item count for the current data delgate.
     *
     *  NOTE: uses a cached value.
     *
     *  @return integer
     *  @see reloadData()
     */
    function itemCount()
    {
        // cache
        if ($this->itemCount === NULL)
        {
            $this->itemCount = $this->dataDelegate()->itemCount();
        }
        return $this->itemCount;
    }

    /**
     *  Get the array of items in the current page.
     *
     *  NOTE: uses a cached value.
     *
     *  @return array
     *  @see reloadData()
     */
    function currentItems()
    {
        // cache
        if ($this->currentItems === NULL)
        {
            // make sure to use the sortKeys() method as it factors in default sort keys.
            $this->currentItems = $this->dataDelegate()->itemsAtIndex($this->startItem(), $this->pageSize, $this->sortKeys());
        }
        return $this->currentItems;
    }

    /**
     *  Get the number of pages of data.
     *
     *  @return integer
     */
    function pageCount()
    {
        return $this->lastPage();
    }

    /**
     *  Is the current page the first page?
     *
     *  @return boolean
     */
    function atFirstPage()
    {
        if ($this->currentPage == WFPaginator::PAGINATOR_FIRST_PAGE)
        {
            return true;
        }
        return false;
    }

    /**
     *  Is the current page the last page?
     *
     *  @return boolean
     */
    function atLastPage()
    {
        if ($this->currentPage == $this->lastPage())
        {
            return true;
        }
        return false;
    }

    /**
     *  Get the page number of the last page.
     *
     *  @return integer The page number of the last page. The first page is page 1.
     */
    function lastPage()
    {
        return ceil($this->itemCount() / $this->pageSize);
    }

    /**
     *  Is there a previous page?
     *
     *  @return boolean
     */
    function prevPage()
    {
        if ($this->atFirstPage()) return NULL;

        return $this->currentPage - 1;
    }

    /**
     *  Is there a next page?
     *
     *  @return boolean
     */
    function nextPage()
    {
        if ($this->atLastPage()) return NULL;

        return $this->currentPage + 1;
    }

    /**
     *  Get the passed paginator state in a serialized format.
     *
     *  @param integer $page The page number to use.
     *  @param integer $pageSize The page size to use.
     *  @param array $sortKeys The sortKeys to use.
     *  @return string A serialized state that when passed to {@link setPaginatorState() setPaginatorState} will load those settings.
     */
    function paginatorState($page = NULL, $pageSize = NULL, $sortKeys = NULL)
    {
        if (is_null($page)) $page = $this->page;
        if (is_null($pageSize)) $pageSize = $this->pageSize;
        if (is_null($sortKeys)) $sortKeys = $this->sortKeys;

        return join('|', array($page, $pageSize, join(',', $sortKeys)));
    }

    /**
     *  Used to restore the paginator to the state specified.
     *
     *  @param string $paginatorState The serialized state. Form is "currentPage|pageSize|sortKey1,sortKey2".
     *  @see paginatorState()
     */
    function setPaginatorState($paginatorState)
    {
        if (is_null($paginatorState)) return;

        $currentPage = $this->currentPage;
        $pageSize = $this->pageSize;
        $sortKeyString = join(',', $this->sortKeys);

        @list($currentPage, $pageSize, $sortKeyString) = explode('|', $paginatorState);

        if ($currentPage)
        {
            $this->setCurrentPage($currentPage);
        }
        if ($pageSize)
        {
            $this->setPageSize($pageSize);
        }
        if ($sortKeyString)
        {
            $this->clearSortKeys();
            foreach (explode(',', $sortKeyString) as $sortKey) {
                $this->addSortKey($sortKey);
            }
        }
        //print_r(explode('|',$paginatorState));
        //print "<br>decoding paginator state: $paginatorState :: page=$currentPage, pageSize=$pageSize, sortKeys=$sortKeyString<br>";

        $this->loadData();
    }

    /**
     *  Get the javascript code for the onClick of a link needed to effect the given pasinatorState. For MODE_FORM only.
     *
     *  @param $state string The result of {@link WFPaginator::paginatorState()}.
     *  @return string The JavaScript code that goes in onClick="".
     *  @throws Exception if paginatorStateID or submitID are not populated.
     */
    function jsForState($state)
    {
        if (!$this->paginatorStateID) throw( new Exception("No paginatorStateID entered.") );
        if (!$this->submitID) throw( new Exception("No submitID entered.") );
        return "document.getElementById('" . $this->paginatorStateID . "').value = '$state'; document.getElementById('" . $this->submitID . "').click(); return false;";
    }

}

/**
 * An interface / formal protocol for accesing paged data.
 *
 * All WFPaginator objects need a dataDelegate to fetch the paged data. Any class implementing WFPagedData will work.
 */
interface WFPagedData
{
    /**
     *  Get the total number of items in the paged data.
     *
     *  @return integer The total number of items.
     */
    function itemCount();

    /**
     *  Get a page of the managed items.
     *
     *  @param integer $startIndex The first item is index 1.
     *  @param integer $numItems The number of items to fetch in the page.
     *  @param array $sortKeys The sort info for the data. This is an array of the "sort keys" that was set via {@link WFPaginator::setSortOptions()}.
     *               It is up to the client class to correctly interpret this sort data.
     *  @return array The subest of items in the current page.
     */
    function itemsAtIndex($startIndex, $numItems, $sortKeys);
}

/**
 * A WFPagedData implementation for an array.
 *
 * WFPagedArray supports the following sortKeys: "+sort, -sort". These will sort the array with sort and rsort respectively.
 */
class WFPagedArray implements WFPagedData
{
    protected $data;

    /**
     *  Constructor.
     *
     *  @param array An array of data to paginate.
     */
    function __construct($array)
    {
        $this->data = $array;
    }

    function itemCount()
    {
        //print "Loading item count.<br>";
        return count($this->data);
    }
    function itemsAtIndex($startIndex, $numItems, $sortKeys)
    {
        //print "Loading items $startIndex - $numItems.<br>";
        if (count($sortKeys) > 0)
        {
            if ($sortKeys[0] == '+sort')
            {
                sort($this->data);
            }
            else if ($sortKeys[0] == '-sort')
            {
                rsort($this->data);
            }
        }
        return array_slice($this->data, $startIndex - 1, $numItems);
    }
}

/**
 * A WFPagedData implementation for a Propel query.
 *
 * For PHOCOA, use this instead of PropelPager.
 *
 * Sorting support: The sortKeys should be the "XXXPeer::COLUMN" with +/- prepended.
 */
class WFPagedPropelQuery implements WFPagedData
{
    protected $criteria;
    protected $peerName;

    /**
     *  Constructor.
     *
     *  @param object Criteria The Propel criteria for the query.
     *  @param string The name of the Peer class to run the doSelect() query against.
     */
    function __construct($criteria, $peerName)
    {
        $this->criteria = $criteria;
        $this->peerName = $peerName;
    }
    function itemCount()
    {
        $criteria = clone $this->criteria;
        $criteria->setOffset(0);
        $criteria->setLimit(0);
        $criteria->clearOrderByColumns();   // no need to waste time sorting to get a count
		return call_user_func(array($this->peerName, 'doCount'), $criteria);
    }
    function itemsAtIndex($startIndex, $numItems, $sortKeys)
    {
        $criteria = clone $this->criteria;
        $criteria->setOffset($startIndex - 1);
        $criteria->setLimit($numItems);
        foreach ($sortKeys as $sortKey) {
            if (substr($sortKey, 0, 1) == '-')
            {
                $criteria->addDescendingOrderByColumn(substr($sortKey, 1));
            }
            else
            {
                $criteria->addAscendingOrderByColumn(substr($sortKey, 1));
            }
        }
		return call_user_func(array($this->peerName, 'doSelect'), $criteria);
    }
}

/**
 * A WFPagedData implementation for a Creole query.
 *
 * Sorting support: The sortKeys should be the acutal SQL token to use in the order by clause (ie "table.column") with +/- prepended.
 */
class WFPagedCreoleQuery implements WFPagedData
{
    protected $baseSQL;
    protected $connection;
    protected $countQueryRowsMode;

    /**
      * Create a WFPagedCreoleQuery paged query.
      *
      * @param string The SQL query desired, WITHOUT "order by" or "limit/offset" clauses.
      * @param object A Creole connection.
      * @param boolean Should the itemCount() function count the rows in the normal query, or just replace the select rows with count(*)?
      *                Default: false
      *                Most queries can leave this as the default. Aggregate queries and/or queries with having clauses may return improper row counts unless this is set to true.
      */
    function __construct($sql, $connection, $countQueryRowsMode = false)
    {
        $this->baseSQL = $sql;
        $this->countQueryRowsMode = $countQueryRowsMode;
        $this->connection = $connection;
    }

    function itemCount()
    {
        $matches = array();
        $matchCount = preg_match('/.*(\bfrom\b.*)/si', $this->baseSQL, $matches);
        if ($matchCount != 1) throw(new Exception("Could not parse sql statement."));

        if ($this->countQueryRowsMode === true)
        {
            $countSQL = "select count(*) from (select count(*) " . $matches[1] . ") as queryRows";
        }
        else
        {
            $countSQL = "select count(*) " . $matches[1];
        }

        $stmt = $this->connection->createStatement();
        $rs = $stmt->executeQuery($countSQL, ResultSet::FETCHMODE_NUM);
        if ($rs->getRecordCount() != 1) throw(new Exception("Record count for itemCount query was not 1 as expected.") );
        $rs->next();
        return $rs->get(1);
    }

    function itemsAtIndex($startIndex, $numItems, $sortKeys)
    {
        $pageSQL = $this->baseSQL;
        if (count($sortKeys)) {
            $pageSQL .= " order by ";
            $first = true;
            foreach ($sortKeys as $sortKey) {
                if (!$first)
                {
                    $pageSQL .= ",";
                }
                if (substr($sortKey, 0, 1) == '-')
                {
                    $pageSQL .= " " . substr($sortKey, 1) . " desc ";
                }
                else
                {
                    $pageSQL .= " " . substr($sortKey, 1) . " asc ";
                }
                $first = false;
            }
        }
        $pageSQL .= " limit " . $numItems . " offset " . ($startIndex - 1);

        // run query
        $stmt = $this->connection->createStatement();
        $rs = $stmt->executeQuery($pageSQL, ResultSet::FETCHMODE_ASSOC);
        
        // prepare results into an array of row data
        $results = array();
        while ($rs->next()) {
            $results[] = $rs->getRow();
        }

        return $results;
    }
}
?>
