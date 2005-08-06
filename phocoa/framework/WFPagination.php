<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/** 
* @package framework-base
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
 * need to write an adapter class that implements WFPagedData. This will allow the WFPaginator to page your data.
 *
 * Because of this architecture, the pagination widgets of PHOCOA can be easily "bound" to a pagination instance and make the display of paginated data easy.
 *
 * The PHOCOA pagination infrastructure includes support for multi-key sorting as well.
 *
 * NOTE: The first page is page 1 (as opposed to page 0).
 *
 * @see WFPaginatorNavigation, WFPaginatorPageInfo
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

    const PAGINATOR_PAGESIZE_ALL = -1;
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
            $this->currentItems = $this->dataDelegate()->itemsAtIndex($this->startItem(), $this->pageSize);
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
     *  @return string A serialized state that when passed to {@link setPaginatorState() setPaginatorState} will load those settings.
     */
    function paginatorState($page = NULL, $pageSize = NULL)
    {
        if (is_null($page)) $page = $this->page;
        if (is_null($pageSize)) $pageSize = $this->pageSize;

        return join('|', array($page,$pageSize));
    }

    /**
     *  Used to restore the paginator to the state specified.
     *
     *  @param string $paginatorState The serizlied state.
     *  @see paginatorState()
     */
    function setPaginatorState($paginatorState)
    {
        if (is_null($paginatorState)) return;

        $currentPage = $this->currentPage;
        $pageSize = $this->pageSize;

        @list(,$currentPage, $pageSize) = each(explode('|', $paginatorState));

        if ($currentPage)
        {
            $this->setCurrentPage($currentPage);
        }
        if ($pageSize)
        {
            $this->setPageSize($pageSize);
        }

        $this->loadData();
    }
}

/**
 * An interface / formal protocol for accesing paged data.
 *
 * All WFPaginator objects need a dataDelegate to fetch the paged data. Any class implementing WFPagedData will work.
 */
interface WFPagedData
{
    function itemCount();
    // first item is index 1; returns an array
    function itemsAtIndex($startIndex, $numItems);
}

/**
 * A WFPagedData implementation for an array.
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
    function itemsAtIndex($startIndex, $numItems)
    {
        //print "Loading items $startIndex - $numItems.<br>";
        return array_slice($this->data, $startIndex - 1, $numItems);
    }
}

/**
 * A WFPagedData implementation for a Propel query.
 *
 * For PHOCOA, use this instead of PropelPager.
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
        $criteria->clearOrderByColumns();
		return call_user_func(array($this->peerName, 'doCount'), $criteria);
    }
    function itemsAtIndex($startIndex, $numItems)
    {
        $criteria = clone $this->criteria;
        $criteria->setOffset($startIndex - 1);
        $criteria->setLimit($numItems);
		return call_user_func(array($this->peerName, 'doSelect'), $criteria);
    }
}
?>
