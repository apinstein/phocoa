<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/** 
* @package UI
* @subpackage Pagination
* @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
* @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
* @author Alan Pinstein <apinstein@mac.com>                        
*/

/**
 * Base paginator class.
 *
 * PHOCOA has its own pagination infrastructure. The UI layer of pagination is nicely separated from the data layer so that the PHOCOA pagination infrastructure
 * can be easily used with any underlying data source (SQL, PHP arrays, etc).
 * 
 * The WFPaginator class is the core pagination functionality that coordiantes the PHOCOA Pagination UI widgets with the underlying data.
 * The PHOCOA pagination infrastructure includes support for multi-key sorting as well.
 *
 * It is extremely simple to paginate your data with PHOCOA.
 * 
 * To hook up the PHOCOA widgets with a data source, you must provide a Data Delegate to WFPaginator to provide the paged data to the pagination infrastructure.
 * A Data delegate is any class that implements the {@link WFPagedData WFPagedData interface}.
 *
 * PHOCOA ships with data delegates for PHP Arrays, Propel criteria-based queries, and Creole-based queries.
 * There is also a data delegate for {@link http://www.dieselpoint.com DieselPoint} result sets.
 *
 * Because of this architecture, the pagination widgets of PHOCOA can be easily "bound" to a pagination instance and make the display of paginated data easy.
 *
 * USAGE
 * First, figure out is which {@link WFPaginator::$mode mode} you need to use.
 * - MODE_URL (default mode) effects all pagination via plain-old-urls, which is more compatible (with browsers), but of course cannot interact with form data (for instance a search form on the same page).
 * - MODE_FORM effects all pagination via javascript and your form. 
 *
 * The default is MODE_URL.
 *
 * Adding Pagination to an exist list view.
 * 1. Set up the paginator by declaring a WFPaginator instance in your shared instances.
 * 2. Manifest the paginatorState parameter id in the page's ParameterList method. By default, WFPaginator expects the id to be "paginatorState".
 * 3. If you're using MODE_FORM, enable MODE_FORM by configuring the paginator's "modeForm" element {@link WFPaginator::setModeForm()} with the value of the submit button on your form that should be clicked to "update" the display. Then, declare a {@link WFPaginatorState} in your page (the ID should be paginatorState parameter ID, "paginatorState" by default), and configure the {@link WFPaginatorState::$paginator paginator} element. This is a special form element that WFPaginator uses to pass the new settings on via the form submission.
 * 4. Declare a {@link WFPaginatorNavigation} in your page, and configure the {@link WFPaginatorNavigation::$paginator paginator} element.
 * 5. Declare a {@link WFPaginatorPageInfo} in your page, and configure the {@link WFPaginatorPageInfo::$paginator paginator} element.
 * 6. Declare a {@link WFPaginatorSortLink} in your page for each sorting option, and configure the {@link WFPaginatorSortLink::$paginator paginator} and {@link WFWidget::$value value} elements. The value of a WFPaginatorSortLink is the sortKey that the link is for, without the +/-.
 * 7. Configure the paginator in the sharedInstancesDidLoad method:
 * <code>
 *     $this->myPaginator->setSortOptions(array('+price' => 'Price', '-price' => 'Price'));
 *     $this->myPaginator->setDefaultSortKeys(array('+price'));
 * </code>
 * 8. In your page's PageDidLoad method, configure the paginator's data delegate and have the paginator read the state from the request.
 * <code>
 *     $this->myPaginator->setDataDelegate(new WFPagedPropelQuery($criteria, 'MyPeer'));
 *     $this->myPaginator->readPaginatorStateFromParams($params);    // this will read the paginator's desired state from the params.
 * </code>
 * 9. Finally, access the paged data set. Typically this is done in the PageDidLoad method if there is no form submission, otherwise it is done from the action method.
 *     $this->myArrayController->setContent($this->myPaginator->currentItems());
 * </code>
 * 
 * NOTE: The first page is page 1 (as opposed to page 0).
 *
 * @see WFPaginatorNavigation, WFPaginatorPageInfo, WFPaginatorSortLink, WFPaginatorState
 * @see WFPagedArray, WFPagedPropelQuery, WFPagedCreoleQuery
 * @todo Is it possible to make readPaginatorStateFromParams() happen automatically?
 */
class WFPaginator extends WFObject
{
    /**
     * @var int Which mode is the paginator working in, {@link WFPaginator::MODE_URL} (default) or {@link WFPaginator::MODE_FORM}.
     */
    protected $mode;
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
     * @var string The ID of the form button to click to re-submit the form. Only used if MODE_FORM.
     */
    protected $submitID;
    /**
     * @var string The name of the page's parameter ID containing the WFPaginatorState. Of course your parameterID declared in <pageName>_ParameterList and the ID of the 
     *             WFPaginatorState widget should be the same.
     */
    protected $paginatorStateParameterID;
    /**
     * @var assoc_array An associative array of alternative replacement params for the current page's parameter list. This is used by the baseURL calculation 
     *                  to allow clients to change paramters before creating the pagination URLs.
     */
    protected $alternativeParams;

    /**
     * @var boolean Internal var used to make sure that the paginator state has been read before calling currentItems() to prevent errors.
     */
    private $hasReadParameterStateFromParams;

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
        $this->paginatorStateParameterID = 'paginatorState';
        $this->hasReadParameterStateFromParams = false;
        $this->alternativeParams = array();
        $this->submitID = NULL;
    }

    /**
     *  Get the id of the submit button used for pagination in MODE_FORM.
     *
     *  @return string The ID of the submit button used for MODE_FORM.
     */
    function submitID()
    {
        return $this->submitID;
    }

    /**
     *  Get the id of the paginatorState widget.
     *
     *  @return string The ID of the paginatorState widget.
     */
    function paginatorStateParameterID()
    {
        return $this->paginatorStateParameterID;
    }

    /**
     *  Provide alternate values for the page's params that will be used with {@link urlForPaginatorState() urlForPaginatorState}.
     *
     *  The passed value will be urlencoded when used.
     *
     *  @param string The ID of the parameter.
     *  @param mixed The value of the parameter. RAW (ie NOT urlencoded).
     */
    function setAlternativeParameterValue($id, $value)
    {
        $this->alternativeParams[$id] = $value;
    }

    /**
     *  Get the alternativeParameterValue for a particular ID.
     *
     *  @param string The parameterID to get.
     *  @return string The alternative value that's been set, or NULL if none is set.
     */
    function alternativeParameterValue($id)
    {
        if (isset($this->alternativeParams[$id])) return $this->alternativeParams[$id];
        return NULL;
    }

    /**
     *  Get an absolute URL that links to a different paginator state for the current setup.
     *
     *  This is a helper function used by the pagination widgets.
     *
     *  This function is used for MODE_URL, and degrade-no-javascript for MODE_FORM. Functionality won't be quite perfect for the latter, but it is only intended
     *  to let search engines be able to scan through MODE_FORM paginated content.
     *
     *  If any of the other parameters should be different than they were in the original params created for the page, set the new value(s)
     *  with {@link setAlternativeParameterValue() setAlternativeParameterValue} before displaying any of the pagination widgets.
     *
     *  @param object WFPage The page that the widget is on.
     *  @param string The desired paginatorState for the link. See {@link paginatorState() paginatorState}.
     *  @return string The absolute URL to the desired paginator state.
     *  @throws Exception on invalid arguments.
     *  @see paginatorState(), setAlternativeParameterValue()
     */
    function urlForPaginatorState($page, $paginatorState)
    {
        // assert params
        if (!($page instanceof WFPage)) throw( new Exception("Invalid page parameter.") );
        if (empty($paginatorState)) throw( new Exception("PaginatorState is empty.") );

        // make paginatorState URL-safe; the + for "ascending" in the sort keys was being interpreted as a SPACE
        $paginatorState = str_replace('+', '%2B', $paginatorState);
        
        $params = $page->parameters();
        $module = $page->module();

        if ($module->invocation()->targetRootModule() and !$module->invocation()->isRootInvocation())
        {
            $baseURL = WWW_ROOT . '/' . $module->invocation()->rootInvocation()->invocationPath();
        }
        else
        {
            $baseURL = WWW_ROOT . '/' . $module->invocation()->modulePath() . '/' . $page->pageName();
        }

        // re-build params
        $newParams = array();
        foreach ($params as $paramID => $paramValue) {
            // use the passed paginator state
            if ($paramID == $this->paginatorStateParameterID)
            {
                $newParams[$paramID] = $paginatorState;
            }
            else
            {
                // preserve all other params, unless overloaded
                // Replace all EMPTY params with WFNull (PARAMETER_NULL_VALUE) so that we don't hit the // problem and lose our parameters.
                if (isset($this->alternativeParams[$paramID]))
                {
                    $newParams[$paramID] = ( (strlen($this->alternativeParams[$paramID]) == 0) ? WFModuleInvocation::PARAMETER_NULL_VALUE : urlencode($this->alternativeParams[$paramID]));
                }
                else
                {
                    $newParams[$paramID] = ( (strlen($paramValue) == 0) ? WFModuleInvocation::PARAMETER_NULL_VALUE : $paramValue);
                }
            }
        }

        $fullURL = $baseURL . '/' . join('/', $newParams);
        return $fullURL;
    }

    /**
     *  Read the paginator's state from the page's parameters.
     *
     *  @param assoc_array The params array from the page.
     *  @throws Exception if the paginatorStateParameterID param cannot be found.
     */
    function readPaginatorStateFromParams($params)
    {
        if ($this->hasReadParameterStateFromParams) return;
        if (!in_array($this->paginatorStateParameterID, array_keys($params))) throw( new Exception("Paginator State Parameter ({$this->paginatorStateParameterID}) is not set. Make sure that you have it declared as a parameter for your page. If your paginatorStateParameterID is different from the one reported, then update it with setPaginatorStateParameterID().") );
        $this->setPaginatorState($params[$this->paginatorStateParameterID]);
        $this->hasReadParameterStateFromParams = true;
    }

    /**
     *  Tell the paginator the name of the "parameterId" which is used for maintaining the parameter state.
     *
     *  For MODE_URL, your page should have a declared paramater with this id.
     *  For MODE_FORM, your page should have a WFPaginatorState widget with this id.
     *
     *  NOTE: if you are using a paginator outside of a WFModule context, you should set the paginatorStateParameterID to NULL.
     *
     *  @param string The parameter ID.
     */
    function setPaginatorStateParameterID($id)
    {
        $this->paginatorStateParameterID = $id;
        if ($this->paginatorStateParameterID === NULL)
        {
            $this->hasReadParameterStateFromParams = true;
        }
    }

    /**
     *  Turn on MODE_FORM.
     *
     *  @param string The ID of the submit button that should be "clicked" to udpate the page.
     */
    function setModeForm($submitID)
    {
        $this->mode = WFPaginator::MODE_FORM;
        $this->submitID = $submitID;
    }

    /**
     *  Turn on MODE_URL.
     */
    function enableModeURL()
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
     *  Add a sort option to the existing sort options.
     *
     *  @param string The sortKey of the option.
     *                IMPORTANT: WFPaginator expects sort keys to be named +sortKey and -sortKey to indicate ascending or descending sort.
     *  @param string The display name for the sort option.
     */
    function addSortOption($sortKey, $displayName)
    {
        $this->sortOptions[$sortKey] = $displayName;
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
     *  @throws WFException if the sortKey does not exist in sortOptions.
     */
    function addSortKey($key)
    {
        if (!isset($this->sortOptions[$key])) throw( new WFException("Sort key '$key' not available in sortOptions.") );
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
     *  @param mixed array - The sortKeys to use.
     *               string - The single sortKey to use.
     *  @throws object WFException If any of the passed keys fails (ie is not a valid sortOption).
     */
    function setDefaultSortKeys($keys)
    {
        if (!is_array($keys)) $keys = array($keys);
        // make sure the passed sortKeys are valid.
        foreach ($keys as $key) {
            if (!isset($this->sortOptions[$key])) throw( new WFException("Sort key '$key' not available in sortOptions.") );
        }

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
        if ($this->pageSize == WFPaginator::PAGINATOR_PAGESIZE_ALL)
        {
            return 1;
        }
        else
        {
            return (1 + ($this->pageSize * ($this->currentPage - 1)));
        }
    }

    /**
     *  Get the number of the last item in the current page.
     *
     *  @return integer The last item of the current page. Item 1 is the first item.
     */
    function endItem()
    {
        if ($this->pageSize == WFPaginator::PAGINATOR_PAGESIZE_ALL)
        {
            return $this->itemCount();
        }
        else
        {
            return min($this->itemCount(), $this->startItem() + $this->pageSize - 1);
        }
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
            $this->itemCount = (int) $this->dataDelegate()->itemCount();
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
        if (!$this->hasReadParameterStateFromParams) throw( new WFException("No call to readPaginatorStateFromParams() has been made yet. You must call readPaginatorStateFromParams() from your PageDidLoad method, or, if the paginator is used outside of a WFModule, call setPaginatorStateParameterID(NULL).") );
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
     * Are all items visible on the current page?
     *
     * This is useful for "Show All" calculations.
     *
     * @return boolean
     */
    function canSeeAllItems()
    {
        return $this->pageCount() <= 1; // still true if 0 items
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
     *  @param mixed $sortKeys  array - The sortKeys to use.
     *                          string - The single sortKey to use
     *  @return string A serialized state that when passed to {@link setPaginatorState() setPaginatorState} will load those settings.
     */
    function paginatorState($page = NULL, $pageSize = NULL, $sortKeys = NULL)
    {
        if (is_null($page)) $page = $this->currentPage();
        if (is_null($pageSize)) $pageSize = $this->pageSize;
        if (is_null($sortKeys)) $sortKeys = $this->sortKeys;
        else if (!is_array($sortKeys)) $sortKeys = array($sortKeys);

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

        // Why do we reload here? Aren't we re-loading before we really need to? Do we need a "dirty" bit to track if we've tried to load data yet?
        //$this->loadData();
    }

    /**
     *  Get the name of the js function which the various paginator widgets use when MODE_FORM is active to "go" to the correct paginator state.
     *
     *  @return string The name of the js function.
     */
    function jsPaginatorStateModeFormGoToStateFunctionName()
    {
        return "__WFPaginatorState_PaginatorModeFormGoToState_{$this->paginatorStateParameterID}";
    }

    /**
     *  Get the name of the js var which the various paginator widgets use when MODE_FORM is active to determine whether the "submit" button
     *  has been pressed by a user, or by the {@link jsPaginatorStateModeFormGoToStateFunctionName()} function.
     *
     *  @return string The name of the js var.
     */
    function jsPaginatorStateModeFormSubmissionVarName()
    {
        return "__WFPaginatorState_PaginatorModeFormSubmission_{$this->paginatorStateParameterID}";
    }

    /**
     *  Get the javascript code for the onClick of a link needed to effect the given pasinatorState. For MODE_FORM only.
     *
     *  @param $state string The result of {@link WFPaginator::paginatorState()}.
     *  @return string The JavaScript code that goes in onClick="".
     *  @throws Exception if paginatorStateParameterID or submitID are not populated.
     */
    function jsForState($state)
    {
        if (!$this->paginatorStateParameterID) throw( new Exception("No paginatorStateParameterID entered.") );
        if (!$this->submitID) throw( new Exception("No submitID entered.") );
        return $this->jsPaginatorStateModeFormGoToStateFunctionName() . "('{$state}');";
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

        // If you pass -1 as the last argument of array_slice,
        // return you all EXCEPT the last item (not what we want)
        if ($numItems == -1) $numItems = NULL;

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
    protected $peerSelectMethod;

    /**
     *  Constructor.
     *
     *  @param object Criteria The Propel criteria for the query.
     *  @param string The name of the Peer class to run the query against.
     *  @param string The name of the Peer method to use for the select. Defaults to doSelect.
     */
    function __construct($criteria, $peerName, $peerSelectMethod = 'doSelect')
    {
        $this->criteria = $criteria;
        $this->peerName = $peerName;
        $this->peerSelectMethod = $peerSelectMethod;
        // There is a bug in call_user_func that causes PHP to crash if the peer being called isn't already loaded.
        if (!class_exists($this->peerName))
        {
            WFWebApplication::autoload($this->peerName);
        }
    }
    function itemCount()
    {
        return call_user_func(array($this->peerName, 'doCount'), $this->criteria); // this is crashing on php5.1.6 IFF apd is enabled.
        //return eval("{$this->peerName}::doCount(\$this->criteria)");    // if we have to use this on a machine with apd, might need to use the (slower) eval method
    }
    function itemsAtIndex($startIndex, $numItems, $sortKeys)
    {
        $criteria = clone $this->criteria;
        $criteria->setOffset($startIndex - 1);
        if ($numItems !== WFPaginator::PAGINATOR_PAGESIZE_ALL)
        {
            $criteria->setLimit($numItems);
        }
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
        return call_user_func(array($this->peerName, $this->peerSelectMethod), $criteria);
    }
}

/**
 * A WFPagedData implementation for Propel 1.5's ModelQuery (ModelCriteria) architecture.
 *
 * Sorting support: The sortKeys should be the "XXXPeer::COLUMN" with +/- prepended.
 */
class WFPagedPropelModelCriteria implements WFPagedData
{
    protected $criteria;
    protected $countCriteria;
    protected $con;

    /**
     *  Constructor.
     *
     *  @param object ModelCriteria The Propel ModelCriteria for the query.
     *  @param object ModelCriteria The Propel ModelCriteria for the COUNT query. OPTIONAL. Useful for complex primary criteria which could have more efficient count queries.
     *  @param object PropelPDO An optional connection object to us.
     */
    function __construct($criteria, $countCriteria = NULL, $con = NULL)
    {
        if (!($criteria instanceof ModelCriteria)) throw new WFException("WFPagedPropelModelCriteria requires a ModelCriteria.");
        $this->criteria = $criteria;
        $this->countCriteria = ($countCriteria === NULL ? $criteria : $countCriteria);
        $this->con = $con;
    }
    function itemCount()
    {
        return $this->countCriteria->count();
    }
    function itemsAtIndex($startIndex, $numItems, $sortKeys)
    {
        $criteria = clone $this->criteria;
        $criteria->setOffset($startIndex - 1);
        if ($numItems !== WFPaginator::PAGINATOR_PAGESIZE_ALL)
        {
            $criteria->setLimit($numItems);
        }
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
        return $criteria->find($this->con);
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
    protected $populateObjectsCallback;

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
        $this->populateObjectsCallback = NULL;
    }

    /**
     * By default, WFPagedCreoleQuery returns an associative array of selected columns.
     *
     * If you are using a creole query load Propel objects with a custom query (ie something too complex for criteria) but you still want propel objects returned,
     * call this function with your peer name. Note that your query should contain the proper select columns as Criteria would set them up, ie the result of
     * TourPeer::getFieldNames(BasePeer::TYPE_COLNAME)
     *
     * @param string The propel peer name used to call populateObjects.
     */
    function setPopulateObjects($peerName)
    {
        $this->populateObjectsCallback = array($peerName, 'populateObjects');
    }

    function itemCount()
    {
        $matches = array();
        if (stripos($this->baseSQL, 'order by'))
        {
            $matchCount = preg_match('/^.*(\bfrom\b.*)(\border by\b.*)$/si', $this->baseSQL, $matches);
        }
        else
        {
            $matchCount = preg_match('/^.*(\bfrom\b.*)$/si', $this->baseSQL, $matches);
        }
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
        if ($rs->getRecordCount() != 1) throw(new Exception("Record count for itemCount query was not 1 as expected. You may need to set countQueryRowsMode to true.") );
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
        if ($numItems !== WFPaginator::PAGINATOR_PAGESIZE_ALL)
        {
            $pageSQL .= " limit " . $numItems;
        }
        $pageSQL .= " offset " . ($startIndex - 1);

        // run query
        $stmt = $this->connection->createStatement();
        
        // prepare results into an array of row data
        if ($this->populateObjectsCallback)
        {
            $rs = $stmt->executeQuery($pageSQL, ResultSet::FETCHMODE_NUM);
            $results = call_user_func($this->populateObjectsCallback, $rs);
        }
        else
        {
            $rs = $stmt->executeQuery($pageSQL, ResultSet::FETCHMODE_ASSOC);
            $results = array();
            while ($rs->next()) {
                $results[] = $rs->getRow();
            }
        }

        return $results;
    }
}

/**
 * A WFPagedData implementation for a PDO-based SQL query.
 *
 * Sorting support: The sortKeys should be the acutal SQL token to use in the order by clause (ie "table.column") with +/- prepended.
 */
class WFPagedPDOQuery implements WFPagedData
{
    protected $baseSQL;
    protected $connection;
    protected $options;

    const OPT_COUNT_QUERY_ROWS_MODE         = 'countQueryRowsMode';
    const OPT_PROCESS_STATEMENT_CALLBACK    = 'processRowsCallback';
    const OPT_FETCH_MODE                    = 'fetchMode';

    /**
      * Create a WFPagedPDOQuery paged query.
      *
      * @param string The SQL query desired, WITHOUT "order by" or "limit/offset" clauses.
      * @param object PDO A PDO connection.
      * @param array Various options for processing the data. See WFPagedPDOQuery::OPT_*.
      */
    function __construct($sql, $connection, $options = array())
    {
        $this->baseSQL = $sql;
        $this->connection = $connection;

        $this->options = array_merge(array(
                                            self::OPT_COUNT_QUERY_ROWS_MODE         => false,
                                            self::OPT_PROCESS_STATEMENT_CALLBACK    => array('WFPagedPDOQuery', 'defaultProcessStatementCallback'),
                                            self::OPT_FETCH_MODE                    => PDO::FETCH_BOTH,
                                          ), $options);
    }

    /**
     * By default, WFPagedPDOQuery returns an associative array of the results using PDO::FETCH_BOTH mode.
     *
     * If you are using a sql query load Propel objects with a custom query (ie something too complex for criteria) but you still want propel objects returned,
     * call this function with array('PeerName', 'populateObjects'). Note that your query should contain the proper select columns as Criteria would set them up, ie the result of
     * MyPeer::getFieldNames(BasePeer::TYPE_COLNAME)
     *
     * The protoype for the callback is: <code> array callbackF(PDOStatement $stmt) </code>
     *
     * @param callback A valid PHP callback.
     */
    function setProcessStatementCallback($callback)
    {
        $this->options[self::OPT_PROCESS_STATEMENT_CALLBACK] = $callback;
    }

    function itemCount()
    {
        $matches = array();
        if (stripos($this->baseSQL, 'order by'))
        {
            $matchCount = preg_match('/^.*?(\bfrom\b.*)(\border by\b.*)$/si', $this->baseSQL, $matches);
        }
        else
        {
            $matchCount = preg_match('/^.*?(\bfrom\b.*)$/si', $this->baseSQL, $matches);
        }
        if ($matchCount != 1) throw(new Exception("Could not parse sql statement."));

        if ($this->options[self::OPT_COUNT_QUERY_ROWS_MODE] === true)
        {
            $countSQL = "select count(*) from (select count(*) " . $matches[1] . ") as queryRows";
        }
        else
        {
            $countSQL = "select count(*) " . $matches[1];
        }

        $stmt = $this->connection->prepare($countSQL);
        $stmt->execute();
        $rowCount = $stmt->rowCount();
        if ($rowCount != 1) throw(new Exception("Record count for itemCount query was not 1 as expected. You may need to set countQueryRowsMode to true.") );
        $row = $stmt->fetch(PDO::FETCH_NUM);
        return $row[0];
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
        if ($numItems !== WFPaginator::PAGINATOR_PAGESIZE_ALL)
        {
            $pageSQL .= " limit " . $numItems;
        }
        $pageSQL .= " offset " . ($startIndex - 1);

        // run query
        $stmt = $this->connection->prepare($pageSQL);
        $stmt->execute();
        $stmt->setFetchMode($this->options[self::OPT_FETCH_MODE]);

        $results = call_user_func($this->options[self::OPT_PROCESS_STATEMENT_CALLBACK], $stmt);
        
        return $results;
    }

    public static function defaultProcessStatementCallback($stmt)
    {
        $results = array();
        foreach ($stmt as $row) {
            $results[] = $row;
        }
        return $results;
    }
}

?>
