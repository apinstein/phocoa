<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

class WFDieselSearch41 implements WFFacetedSearchService
{
    // dp instances
    protected $dpSearchRequest;

    // local setup
    protected $facets;
    protected $searchResults;
    protected $loadIndexDataForColumns;
    protected $indexes;

    /**
     * Instantiate a Dieselpoint Search Service (WFFacetedSearchService).
     *
     * @param string The path to the install location of dieselpoint
     * @param mixed The index name, or an array of index names.
     * @param array Options. NOTHING TO SEE HERE YET.
     * @throws
     */
    public function __construct($dpLocation, $index, $options = array())
    {
        // normalize arguments
        if (!is_array($index))
        {
            $index = array($index);
        }
        $this->indexes = $index;

        // bootstrap dieselpoint
        java_require(join(';', glob("{$dpLocation}/lib/*.jar")));
        $javaSystem = new JavaClass('java.lang.System');
        if (!$javaSystem->getProperty("app.home"))
        {
            // setProperty doesn't seem to be idempotent, hard-crashes if we call it twice.
            $javaSystem->setProperty("app.home", $dpLocation);
        }

        // initialize instance vars
        $this->dpSearchRequest = new Java('com.dieselpoint.search.server.SearchRequest');
        $this->dpSearchRequest->setIndexNames($this->indexes);
        $this->loadIndexDataForColumns = array();
        $this->facets = array();
    }

    function setQuery($query)
    {
        $this->dpSearchRequest->setQueryString($query);
        return $this;
    }

    function setLimit($limit)
    {
        $this->dpSearchRequest->setNumberOfItemsRequested($limit);
        return $this;
    }

    function setOffset($offset)
    {
        $this->dpSearchRequest->setStartingItem($offset);
        return $this;
    }

    function setSortBy($sortBy, $ascending = true)
    {
        $this->dpSearchRequest->setSort($sortBy);
        return $this;
    }

    function addFacetToGenerate(WFFacetedSearchFacet $facet)
    {
        $this->facets[] = $facet;
        return $this;
    }

    function setSelectDataFromSearchIndexForAttributes($attributes)
    {
        $this->loadIndexDataForColumns = $attributes;
        return $this;
    }

    protected $comparatorMap = array(
        WFFacetedSearchNavigationQuery::COMP_EQ => '=',
        WFFacetedSearchNavigationQuery::COMP_LT => '<',
        WFFacetedSearchNavigationQuery::COMP_LE => '<=',
        WFFacetedSearchNavigationQuery::COMP_GT => '>',
        WFFacetedSearchNavigationQuery::COMP_GE => '>=',
        WFFacetedSearchNavigationQuery::COMP_NE => '!='
    );
    function convertNavigationQueryToNativeQuery($navQ)
    {
        if (!isset($this->comparatorMap[$navQ->comparator()])) throw new Exception("unsupported comparator {$navQ->comparator()}.");
        $comparator = $this->comparatorMap[$navQ->comparator()];
        return "[{$navQ->attribute()}] {$comparator} {$navQ->value()}";
    }

    protected $joinOperatorMap = array(
            WFFacetedSearch::QUERY_OP_AND => 'AND',
            WFFacetedSearch::QUERY_OP_OR  => 'OR',
            WFFacetedSearch::QUERY_OP_NOT => 'NOT',
    );
    function joinNativeQueries($queries, $joinOperator)
    {
        if (count($queries) === 0) return NULL;

        if (!isset($this->joinOperatorMap[$joinOperator])) throw new Exception("unsupported comparator {$joinOperator}.");
        $operator = $this->joinOperatorMap[$joinOperator];
        return "(" . join(" {$operator} ", $queries) . ")";
    }
    function wrapUserQuery($userQuery)
    {
        return "<USERQUERY>{$userQuery}</USERQUERY>";
    }
    function query() { return (string) $this->dpSearchRequest->getQueryString(); }
    function queryDescription() { return $this->query(); }

    function find()
    {
        if ($this->searchResults) return $this->searchResults;

        try {
            // convert WFFacetedSearchFacet into DP objects
            $facetMap = array();
            foreach ($this->facets as $facet) {
                $dpFacet = new Java('com.dieselpoint.facet.StandardFacet', $facet->attributeId());

                $dpFacet->setShowHitCount($facet->generateHitCounts());
                $dpFacet->setIncludeZeroes($facet->includeZeroes());
                $dpFacet->setIsTaxonomyAttr($facet->isTaxonomy());
                $dpFacet->setMaxRows($facet->maxRows());

                // deal with ranges
                $ranges = $facet->ranges();
                if (is_int($ranges))
                {
                    $nRangeFilter = new Java('com.dieselpoint.facet.FacetFilterDefinedCount');
                    $nRangeFilter->setRangeCount($ranges);
                    $dpFacet->setFacetFilter($nRangeFilter);
                }
                else if (is_array($ranges))
                {
                    $customRangeFilter = new Java('com.dieselpoint.facet.FacetFilterDefinedRanges');
                    print_r($ranges);
                    // WFFacetedSearchFacetValueRangeDefinition needs refactor...
                    switch ($ranges[0]->type()) {
                        case WFFacetedSearchFacetValueRangeDefinition::TYPE_DATE:
                            $customRangeFilter->setDate(true);
                            break;
                        case WFFacetedSearchFacetValueRangeDefinition::TYPE_NUMERIC:
                            $customRangeFilter->setNumeric(true);
                            break;
                    }
                    if ($ranges[0]->locale())
                    {
                        @list($lang, $country, $variant) = explode('-', $ranges[0]->locale());
                        if ($lang && $country && $variant)
                        {
                            $jLocale = new Java('java.util.Locale', $lang, $country, $variant);
                        }
                        else if ($lang && $country)
                        {
                            $jLocale = new Java('java.util.Locale', $lang, $country);
                        }
                        else
                        {
                            $jLocale = new Java('java.util.Locale', $lang);
                        }
                        $customRangeFilter->setLocale($jLocale);
                    }
                    foreach ($ranges as $rangeDef) {
                        $customRangeFilter->addDefinedRange("{$rangeDef->startValue()} - {$rangeDef->endValue()}", $rangeDef->startValue(), $rangeDef->endValue());
                    }
                    $dpFacet->setFacetFilter($customRangeFilter);
                }

                $this->dpSearchRequest->addFacet($dpFacet);

                $facetMap[] = array('dpFacet' => $dpFacet, 'facet' => $facet);
            }

            // bootstrap server
            $ssClass = new JavaClass('com.dieselpoint.search.server.SearchServer');
            $ssServer = $ssClass->getServer();

            // execute search
            $dpSearchResults = $ssServer->getResults($this->dpSearchRequest);
            $dpResultSet = $dpSearchResults->getResultSet();

            // collect results
            $resultRows = array();
            while ($dpResultSet->next()) {
                $row = $dpResultSet->getItemResult();

                // load data from index
                $rowIndexData = array();
                foreach ($this->loadIndexDataForColumns as $c) {
                    $rowIndexData[$c] = (string) $dpResultSet->getString($c);
                }

                // create WFFacetedSearchResultHit instance
                $resultRows[] = new WFFacetedSearchResultHit((string) $row->getItemId(), (string) $row->getScore(), $rowIndexData);
            }

            $this->searchResults = new WFFacetedSearchResultSet($resultRows, $dpSearchResults->getTotalItems(), $dpSearchResults->getSearchTime());

            // populate facet results
            foreach ($facetMap as $f) {
                $dpFacet = $f['dpFacet'];
                $facetDef = $f['facet'];

                $data = array();
                //print $dpFacet->getOutput();
                foreach ($dpFacet->getFacetValues()->getArray() as $fv) {
                    if ($fv === NULL) continue;
                    $data[] = new WFFacetedSearchFacetValue((string) $fv->getValue(), (int) $fv->getHits(), (int) $fv->getItemCount(), (boolean) $fv->isRange(), (string) $fv->getSecondValue()); // no children support yet...
                }
                    //print_r($data);
                $facetResultSet = new WFFacetedSearchFacetResultSet($data, $dpFacet->hasMore());

                $facetDef->setResultSet($facetResultSet);
            }
        } catch (JavaException $e) {
            $this->handleJavaException($e);
        }

        return $this->searchResults;
    }

    function resultSet()
    {
        return $this->searchResults;
    }

    function totalItems()
    {
        $itemCount = 0;
        $dpIndexClass = new JavaClass('com.dieselpoint.search.Index');
        foreach ($this->indexes as $index) {
            $itemCount += $dpIndexClass->getInstance($index)->getItemCount();
        }
        return $itemCount;
    }

    /**
     *  Convert a JavaException (from the bridge) into a human-readable PHP Exception.
     *
     *  Basically this just copies the stack trace of the java exception into the Exception's message and throws a normal Exception.
     *
     *  @param object JavaException The exception from the bridge.
     *  @throws object Exception The PHP Exception with the java stack trace.
     */
    private function handleJavaException(JavaException $e)
    {
        $phpTrace = $e->getTraceAsString();
        $javaTrace = new java("java.io.ByteArrayOutputStream");
        $e->printStackTrace(new java("java.io.PrintStream", $javaTrace));
        throw( new PHPJavaBridgeException("<pre>Java Message: {$e->toString()}\n\nPHP Stack Trace:\n{$phpTrace}\n\nJava Stack Trace:\n{$javaTrace}</pre>\n") );
    }
}

class PHPJavaBridgeException extends Exception {}

/************************ EVERYTHING BELOW IS DEPRECEATED ************************/

/**
 * Dieselpoint helper object.
 *
 * @copyright Copyright (c) 2002 Alan Pinstein. All Rights Reserved.
 * @version $Id: smarty_showcase.php,v 1.3 2005/02/01 01:24:37 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 * @package UI
 * @subpackage Search
 **/

/**
 * This class provides a PHP-front end to the basic Dieselpoint search capabilities.
 *
 * The unit of work of WFDieselSearch is a single search. Once a search has been performed, the search cannot be re-used.
 * 
 * Submit a query in DQL and get back paginated, sorted results. Also can generate "FacetGenerator" objects for the current search.
 *
 * DP Questions:
 *
 * - Ranges; can you suggest ranges for facets? The default algo of "make equal chunks" is not that useful. Fixed ranges? Open-ended? (IE Bedrooms as 1+,2+,3+ instead of "auto") CURRENTLY not possible directly, but can pre-process items to group them like this and then display differently. Coming in 4.0
 *    Ranges: ZOOMABLE ranges; ie click on 700-800 and that unveils 700-750 and 750-800. How would this work with the planned new capabilities?
 * - Geospatial stuff? Items withing N miles? Not really.
 * - Case-sensitivity. How does this work? To turn off case-sensitivity for a field, can you still use parametric? NO. Parametric is case-sensitive; normalize data on the way in.
 * - SimpleQuery: golf and tennis => "golf or and or tennis", golf AND tennis => "golf and tennis"
 *
 * USING WFDieselSearch
 *
 * Setup:
 * 1. In your webapp.conf, you should configure DIESELPOINT to point to the install dir of the dieselpoint app.
 * 2. Instantiate a WFDieselSearch object in your module. The only required properties are {@link setIndex() index} and {@link resultObjectLoaderCallback()}.
 *
 * WFDieselSearch provides a WFPagedData interface as well, for pagination. WFDieselSearch is meant to be used only as the ID lookup for objects; the loading of the 
 * actualy objects is done via a callback function:
 *
 * array loadObjectsCallback($objectIDArray)
 *
 * You set the callback with {@link setResultObjectLoaderCallback} or {@link setResultObjectLoaderCallbackWithPropelPeer}.
 *
 * The ids passed to the callback are retrieved from the Dieselpoint index. The ID is configured in Dieselpoint by setting up one of the attributes as the item id:
 *
 * prop_id, type=Item_id, datatype=I    # configure "prop_id" as the item_id for the index items.
 *
 * IMPORTANT! This implementation of WFDieselSearch requires mfg.jar ("MouserFacetGenerator" code). This also requires a special diesel-x-x-x.jar from DP that has the "postings" class.
 */
class WFDieselSearch extends WFObject implements WFPagedData
{
    const SORT_BY_RELEVANCE = '-relevance';

    protected $index;
    protected $searcher;
    protected $paginator;
    protected $logPerformanceInfo;
    protected $loadTheseColumnsFromIndex;
    protected $hasRunQuery;
    protected $dpQueryStateParameterID;

    // populated with the *actual* sort used by the search. we have to persist this since you cannot interrogate the "direction" of the search from DP (no api call).
    protected $sortBy;

    // callback object loader
    protected $resultObjectLoaderCallback;

    // propel callback stuff
    protected $resultObjectLoaderCallbackPropelMode;
    protected $resultObjectLoaderCallbackPropelPeerName;
    protected $resultObjectLoaderCallbackPropelPeerMethod;

    function __construct()
    {
        parent::__construct();
        $this->resultObjectLoaderCallback = NULL;
        $this->searcher = NULL;
        $this->index = NULL;
        $this->paginator = NULL;
        $this->resultObjectLoaderCallbackPropelMode = false;
        $this->resultObjectLoaderCallbackPropelPeerName = NULL;
        $this->resultObjectLoaderCallbackPropelPeerMethod = NULL;
        $this->loadTheseColumnsFromIndex = array("item_id");
        $this->logPerformanceInfo = false;
        $this->hasRunQuery = false;
        $this->dpQueryStateParameterID = 'dpQueryState';
    }

    /**
     *  Get the "parameter ID" used for persisting the queryState of the dieselsearch for pagination and UI state management.
     *
     *  @return string The parameterID.
     */
    function queryStateParameterId()
    {
        return $this->dpQueryStateParameterID;
    }

    /**
     *  Should WFDieselSearch log the performance info to the PHOCOA log dir file: "diesel.log"?
     *
     *  @param boolean TRUE to log performance info.
     */
    function setLogPerformanceInfo($bool)
    {
        $this->logPerformanceInfo = $bool;
    }

    /**
     *  Has logPerformanceInfo been turned on?
     *
     *  @return boolean
     */
    function logPerformanceInfo()
    {
        return $this->logPerformanceInfo;
    }

    /**
     *  Utility function for debugging to start time tracking.
     */
    function startTrackingTime()
    {
        $this->logPerformanceInfo_t0 = microtime(true);
    }

    /**
     *  Stop tracking time; dump time to 'dieselsearch.log' file with given message.
     *
     *  @param string The message to log along with the elapsed time since {@link startTrackingTime()} was called.
     */
    function stopTrackingTime($msg)
    {
        $elapsed = microtime(true) - $this->logPerformanceInfo_t0;
        WFLog::logToFile('dieselsearch.log', "[{$elapsed}s] $msg");
    }

    /**
     *  Set the columns of data to load from the index in the results.
     *
     *  Note that "item_id" will be added automatically to the columns of the result set as it is used internally.
     *
     *  @param array An array of column names. The column names must exist in the index.
     *  @throws object Exception If the parameter is invalid.
     */
    function setLoadTheseColumnsFromIndex($colsArray)
    {
        if (!is_array($colsArray)) throw( new Exception("Column list must be array.") );
        $allCols = array_merge(array("item_id"), $colsArray);
        $this->loadTheseColumnsFromIndex = $allCols;
    }

    /**
     *  Set the paginator associated with this WFDieselSearch.
     *
     *  NOTE: This function automatically sets the WFDieselSearch instance as the data delegate for the paginator, so you don't need to do that yourself.
     *
     *  @param object WFPaginator The paginator being used.
     *  @throws Exception if the passed paginator is not valid.
     */
    function setPaginator($paginator)
    {
        if (!($paginator instanceof WFPaginator)) throw( new Exception("Paginator must be a WFPaginator instance.") );
        $this->paginator = $paginator;
        $this->paginator->setDataDelegate($this);
    }

    /**
     *  Get the paginator used by the DieselSearch.
     *
     *  @return object WFPaginator
     */
    function paginator()
    {
        return $this->paginator;
    }

    /**
     *  Callback function for loading the items found by DP.
     *
     *  void myFunction( array(WFDieselHit), $sortKeys)
     *
     *  The callback should load the objects via setObject() on each WFDieselHit.
     *
     *  @param mixed The PHP-style callback function: either function name or array(className, methodName).
     */
    function setResultObjectLoaderCallback($callbackF)
    {
        if (!is_callable($callbackF)) throw( new Exception("Callback function is not valid.") );
        $this->resultObjectLoaderCallback = $callbackF;
        $this->resultObjectLoaderCallbackPropelMode = false;
    }

    /**
     *  Use a PropelPeer to load the items.
     *
     *  This method will use the "doSelect" method of the peer to load the items with the same sort criteria passed into the DP object.
     *  Therefore, it's important that your "attribute" names in DP match the column names.
     *
     *  @param string Peer name.
     *  @param string {@link setResultObjectLoaderCallbackWithPropelPeerMethod() Peer Method} name to use.
     */
    function setResultObjectLoaderCallbackWithPropelPeer($peerName, $peerMethod = NULL)
    {
        if ($peerName == NULL)
        {
            $this->resultObjectLoaderCallback = NULL;
            $this->resultObjectLoaderCallbackPropelMode = false;
            return;
        }
        
        // turn on propel mode
        $this->resultObjectLoaderCallback = NULL;
        $this->resultObjectLoaderCallbackPropelMode = true;

        // set up peer data loaders
        $this->resultObjectLoaderCallbackPropelPeerName = $peerName;
        $this->setResultObjectLoaderCallbackWithPropelPeerMethod($peerMethod);
    }

    /**
     *  Set the PropelPeer method name to use to load the objects.
     *
     *  Default is 'doSelect', but you can set your own method for instance if you want a custom function that loads joined data.
     *
     *  @param string A method name on the propel peer to call to load the propel objects.
     */
    function setResultObjectLoaderCallbackWithPropelPeerMethod($peerMethod = NULL)
    {
        if ($peerMethod === NULL)
        {
            $peerMethod = 'doSelect';
        }
        $this->resultObjectLoaderCallbackPropelPeerMethod = $peerMethod;
    }

    /**
     *  Get the primary key column name suitable for criteria for the given peer.
     *
     *  NOTE: only works for tables with a SINGLE primary key.
     * 
     *  @param string The peer name.
     *  @return string The Criteria-compatible PK specifier.
     *  @throws object Exception On error.
     */
    private function getPrimaryKeyColumnFromPropelPeer($peerName)
    {
        $dbName = eval( "return {$peerName}::DATABASE_NAME;" );
        $tableName = eval( "return {$peerName}::TABLE_NAME;" );
        $dbMap = Propel::getDatabaseMap($dbName);
        if ($dbMap === null) {
            throw new PropelException("\$dbMap is null");
        }
        
        if ($dbMap->getTable($tableName) === null) {
            throw new PropelException("\$dbMap->getTable() is null");
        }
        
        $columns = $dbMap->getTable($tableName)->getColumns();
        foreach(array_keys($columns) as $key) { 
            if ($columns[$key]->isPrimaryKey()) {
                $pkCol = $columns[$key];
                break;
            }
        }

        $criteriaSpecifier = $tableName . '.' . $pkCol->getColumnName();
        return $criteriaSpecifier;
    }

    /**
     *  Choose the Dieselpoint index to use for this query.
     *
     *  @param mixed (string) Filesystem path to the dieselpoint index to open.
     *               (object) Java Object from ({@link WFDieselSearch::index()})
     */
    function setIndex($index)
    {
        try {
            if (!defined('DIESELPOINT')) throw( new Exception("DIESELPOINT must be defined, usually in your webapp.conf file.") );
            java_require(join(';', glob(DIESELPOINT . '/lib/*.jar')));
            $javaSystem = new JavaClass('java.lang.System');
            if (!$javaSystem->getProperty("app.home"))
            {
                // setProperty doesn't seem to be idempotent, hard-crashes if we call it twice.
                $javaSystem->setProperty("app.home", DIESELPOINT);
            }
            if (is_string($index))
            {
                $Index = new JavaClass("com.dieselpoint.search.Index");
                $this->index = $Index->getInstance($index);
            }
            else
            {
                $this->index = $index;
            }
            $this->searcher = new Java("com.dieselpoint.search.Searcher", $this->index);
        } catch (JavaException $e) {
            $this->handleJavaException($e);
        }
    }

    /**
     *  Convert a JavaException (from the bridge) into a human-readable PHP Exception.
     *
     *  Basically this just copies the stack trace of the java exception into the Exception's message and throws a normal Exception.
     *
     *  @param object JavaException The exception from the bridge.
     *  @throws object Exception The PHP Exception with the java stack trace.
     */
    private function handleJavaException(JavaException $e)
    {
        $trace = new java("java.io.ByteArrayOutputStream");
        $e->printStackTrace(new java("java.io.PrintStream", $trace));
        throw( new Exception("java exception {$e->getMessage()}, stack trace:<pre> $trace </pre>\n") );
    }

    /**
     *  Get a human-readable description of the current query.
     *
     *  @return string
     */
    function queryDescription()
    {
        if (!$this->hasRunQuery()) throw( new WFException("Search has not been executed yet.") );
        try {
            return "needs dp 4.1 update";
            $bc = new Java('com.dieselpoint.uiobjects.BreadCrumbs', $this->searcher->getSearchRequest());
            return java_values($bc->getOutput());
        } catch (JavaException $e) {
            $this->handleJavaException($e);
        }
    }

    /**
     *  Get the replacement query suggested by DP. Spelling corrections, etc.
     *
     *  This seems only to work with full-text searches, not with attribute searches.
     *
     *  @return string The replacement query string to use.
     *  @throws WFException if the search has not yet been exectued.
     */
    function replacementQuery()
    {
        if (!$this->hasRunQuery()) throw( new WFException("Search has not been executed yet.") );
        return $this->searcher->getReplacementQuery();
    }

    /**
     *  Get the index used for the search.
     *
     *  @return object Index The Dieselpoint Index java object.
     *  @throws WFException if no index has been set up.
     */
    function index()
    {
        if (is_null($this->index)) throw( new Exception("No dieselpoint index set up. Use setIndex().") );
        return $this->index;
    }

    /**
     *  Get the searcher used for the search.
     *
     *  @return object Index The Dieselpoint Searcher java object. NULL if no searcher is available yet.
     */
    function searcher()
    {
        return $this->searcher;
    }

    /**
     *  Has the search been executed yet?
     *
     *  @return boolean
     */
    function hasRunQuery()
    {
        return $this->hasRunQuery;
    }

    /**
     *  Execute the search.
     *
     *  @throws object WFException If the search has already been executed.
     *          object WFDieselSearch_ParseException If the failure is a parse error of the query.
     */
    function execute()
    {
        if ($this->hasRunQuery()) throw( new WFException("Search has already been executed. You cannot run a search more than once.") );

        if ($this->loadTheseColumnsFromIndex)
        {
            $this->searcher->addColumns(join(',', $this->loadTheseColumnsFromIndex));
        }
        if ($this->logPerformanceInfo()) $this->startTrackingTime();
        try {
            $this->searcher->execute();
        } catch (JavaException $e) {
            $trace = new java("java.io.ByteArrayOutputStream");
            $e->printStackTrace(new java("java.io.PrintStream", $trace));
            if (preg_match('/com.dieselpoint.query.(ParseException|SyntaxException)/', $trace))
            {   
                throw( new WFDieselSearch_ParseException("Dieselpoint could not parse the query: " . $this->getQueryString() ."\n\nDieselpoint said: " . $trace) );
            }
            else
            {
                throw($e);
            }
        }
        if ($this->logPerformanceInfo()) $this->stopTrackingTime("DP Search: " . $this->getQueryString());
        // if we have a paginator, we need to update the dpQueryState parameter so that pagination will work on the results.
        if ($this->paginator && $this->paginator->alternativeParameterValue($this->dpQueryStateParameterID) === NULL)
        {
            $this->paginator->setAlternativeParameterValue($this->dpQueryStateParameterID, $this->getQueryState());
        }
        $this->hasRunQuery = true;
    }

    /**
     *  Don't know if this works yet... will need to test by using a non-WFDieselNav search interface.
     *
     *  @return string The current DQL query.
     */
    function getQueryState()
    {
        return $this->getQueryString();
    }

    /**
     *  Set the queryState of the dieselsearch to the passed state string.
     *
     *  @param string The serialized query state from {@link getQueryState()}.
     */
    function setQueryState($state)
    {
        $this->setQueryString($state);
    }

    /**
     *  Set the DQL query string.
     *
     *  @param string A DQL query string.
     *  @see setSimpleQuery
     */
    function setQueryString($string)
    {
        $this->searcher->setQueryString($string);
    }

    /**
     *  Get the query string set by setQueryString.
     *
     *  @return string The DQL query string.
     */
    function getQueryString()
    {
        return $this->searcher->getQueryString();
    }

    /**
     *  A higher-level function for calling {@link setQueryString}.
     *
     *  Useful when you want to use a text-field "query" input to search. This function strips all "DQL control characters" and calls {@link setQueryString}
     *  with the result.
     *
     *  Calling this function automatically switches the result sorting to "Relevance" mode.
     *
     *  @param string The query string input by an end-user.
     *  @param string "any", "all", or "exact".
     */
    function setSimpleQuery($string, $mode = "any")
    {
        // add relevance sorting if there's a query
        if ($this->simpleQueryString)
        {
            $this->enableRelevanceSorting();
        }
        $this->searcher->setSimpleQuery($string, $mode);
    }

    /**
     *  Add the Relevance sort option to the paginator.
     *
     *  If there is no paginator, this function does nothing.
     */
    function enableRelevanceSorting()
    {
        if (!$this->paginator()) return;

        $this->paginator->addSortOption(WFDieselSearch::SORT_BY_RELEVANCE, 'Relevance');
    }

    /**
     *  Get a FacetGenerator object.
     *
     *  If no search has been run, then the FacetGenerator works off of the entire index. If a search has run, it works off the results.
     *
     *  @return object A FacetGenerator object [Java].
     */
    function getGeneratorObject($treeGenerator = false)
    {
        if ($this->hasRunQuery())
        {
            $gObj = $this->searcher;
        }
        else
        {
            $gObj = $this->index();
        }
        if ($treeGenerator)
        {
            return new Java("com.dieselpoint.projects.mouser.MouserFacetGenerator", $gObj);
        }
        else
        {
            return new Java("com.dieselpoint.search.FacetGenerator", $gObj);
        }
    }

    /**
     *  Get the total number of items in the index.
     *
     *  @return integer The number of items in the index.
     */
    function getIndexItemCount()
    {
        return $this->index()->getItemCount();
    }

    /**
     *  Get the number of items in the search result.
     *
     *  @return integer The number of "hits" in the result.
     *  @throws WFException if the search has not yet been exectued.
     */
    function getTotalItems()
    {
        if (!$this->hasRunQuery()) throw( new WFException("Search has not been executed. You cannot get the total hits from a search until it is executed.") );
        return $this->searcher->getTotalItems();
    }

    /**
     *  Get an array of the item_id's on the current page of results.
     *
     *  @return
     *  @throws WFException if the search has not yet been exectued.
     */
    function itemsIDsOnPage()
    {
        if (!$this->hasRunQuery()) throw( new WFException("Search has already been executed. You cannot run a search more than once.") );
        return $this->searcher->getItemIds();
    }

    /**
     *  Are the results sorted by relevance?
     *
     *  @return boolean
     */
    function isRelevanceSort()
    {
        $sortAttr = java_values($this->searcher->getSort());
        if (!$sortAttr) return true;
        if ($sortAttr == substr(WFDieselSearch::SORT_BY_RELEVANCE, 0, 1)) return true;
        return false;
    }

    /**
     * @return string The actual sort key sent to Dieselpoint, in form of "+/-sortkey".
     */
    function sortBy()
    {
        return $this->sortBy;
    }

    // WFPagedData interface implementation
    // NOTE: Diesel sets are special in that if there is no query in the passed dieselSearch, then there are NO ITEMS. Can only show items once searching has started.
    function itemCount()
    {
        return $this->getTotalItems();
    }

    function setSort($attrID, $sortDir = NULL)
    {
        if ($sortDir)
        {
            $this->searcher->setSort($attrID, $sortDir);
        }
        else
        {
            $this->searcher->setSort($attrID);
        }
    }

    /**
     *  @todo Factor out propel loading into self-contained custom callback function.
     */
    function itemsAtIndex($startIndex, $numItems, $sortKeys)
    {
        try {
            // need to convert PageNum to DP-style; in DP the first page is page 0.
            $pageNum = 1 + floor($startIndex / $numItems);
            //print "Fetching items starting at $startIndex, max $numItems items. This means we're on page: " . $pageNum;
            if ($numItems == WFPaginator::PAGINATOR_PAGESIZE_ALL) throw( new Exception("Paginator page size is set to PAGINATOR_PAGESIZE_ALL when using Dieselpoint. Are you crazy?") );
            $this->searcher->setNumberOfItemsOnAPage($numItems);
            $this->searcher->setPageNumber($pageNum - 1);
            
            // sorting
            // remove the SORT_BY_RELEVANCE sortKey -- relevance sorting is triggered by the ABSENCE of a "setSort" call
            $sortKeysToUse = array();
            foreach ($sortKeys as $key) {
                if ($key != WFDieselSearch::SORT_BY_RELEVANCE)
                {
                    $sortKeysToUse[] = $key;
                }
            }
            $sortKeys = $sortKeysToUse;
            $this->sortBy = self::SORT_BY_RELEVANCE;
            if (count($sortKeys) > 1) throw( new Exception("Only 1-key sorting supported at this time.") );
            else if (count($sortKeys) == 1)
            {
                $sortKey = $sortKeys[0];
                $sortAttr = substr($sortKey, 1);
                $this->sortBy = $sortKey;
                if (substr($sortKey, 0, 1) == '-')
                {
                    $this->searcher->setSort($sortAttr, -1);
                }
                else
                {
                    $this->searcher->setSort($sortAttr);
                }
            }
            // run search
            $this->execute();
            $allHits = array();
            $allIDs = array();
            $rs = $this->searcher->getResultSet();
            if (!$rs) throw( new Exception("Invalid ResultSet returned.") );
            while ($rs->next()) {
                $itemID = java_values($rs->getItemId());   // use java_values() to force conversion from java bridge string object to native PHP type. ALSO, getItemId returns non-highlighted itemID.
                $allIDs[] = $itemID;
                $hit = new WFDieselHit($itemID, 0);//java_values($rs->getRelevanceScore()));
                // load custom data
                for ($i = 1; $i < count($this->loadTheseColumnsFromIndex); $i++) {
                    $hit->addData($this->loadTheseColumnsFromIndex[$i], java_values($rs->getString($i + 1)));
                }
                $allHits[] = $hit;
            }

            // we support three main ways to load the matching objects; Propel-backed, custom callbacks, and none (just returns the WFDieselHit arrays -- but still can load data from index)
            if ($this->resultObjectLoaderCallbackPropelMode)
            {
                // For propel-backed object loading, we need to use a peer method that supports Criteria so we can preserve sorting.
                
                // check that the callback exists -- with PHOCOA's autoload, we need to check the class explicitly - I think this bug was fixed in php 5.x.x something
                // this little block may be deprecated; i think it's just to get around a goofy PHP bug
                if (!class_exists($this->resultObjectLoaderCallbackPropelPeerName))
                {
                    //__autoload($peerName); class exists should do this.
                    if (!class_exists($this->resultObjectLoaderCallbackPropelPeerName)) throw( new Exception("Callback class '{$this->resultObjectLoaderCallbackPropelPeerName}' does not exist.") );
                }
                $propelCallback = array($this->resultObjectLoaderCallbackPropelPeerName, $this->resultObjectLoaderCallbackPropelPeerMethod);
                if (!is_callable($propelCallback)) throw( new Exception("Propel Callback function is not valid: {$this->resultObjectLoaderCallbackPropelPeerName}::{$this->resultObjectLoaderCallbackPropelPeerMethod}") );
                
                // determine primary key
                $c = new Criteria;
                $c->add($this->getPrimaryKeyColumnFromPropelPeer($this->resultObjectLoaderCallbackPropelPeerName), $allIDs, Criteria::IN);
                $tableName = eval( "return {$this->resultObjectLoaderCallbackPropelPeerName}::TABLE_NAME;" );
                $propelObjects = call_user_func($propelCallback, $c);    // more efficient to grab all items in a single query
                // map the propel objects back into the WFDieselHit's.
                // we have to gracefully deal with the situation that an item in the index isn't in the database
                // when this happens we auto-prune the item from our dp index and remove that item from our list of hits.
                $propelObjectsById = array();
                $itemIDsToPrune = array();
                foreach ($propelObjects as $obj) {
                    $propelObjectsById[$obj->getPrimaryKey()] = $obj;
                }
                $existingHits = array();
                foreach ($allHits as $hit) {
                    if (!isset($propelObjectsById[$hit->itemID()]))
                    {
                        $itemIDsToPrune[] = $hit->itemID();
                        continue;
                    }
                    $hit->setObject($propelObjectsById[$hit->itemID()]);
                    $existingHits[] = $hit;
                }
                // prune missing items
                foreach ($itemIDsToPrune as $id) {
                    //print "Pruning item id $id<BR>";
                    $this->index->deleteItem($id);  // no need to save() index; happens automatically on its closing
                }
                $allHits = $existingHits;
            }
            else if ($this->resultObjectLoaderCallback)
            {
                // for custom mode, pass info on to callback and let it load up objects.
                call_user_func($this->resultObjectLoaderCallback, $allHits, $sortKeys);
            }
            return $allHits;
        } catch (JavaException $e) {
            $this->handleJavaException($e);
        }
    }
}

/**
 *  The WFDieselHit object represents a result row from a WFDieselSearch.
 * 
 *  The itemsAtIndex() call will return an array of WFDieselHit objects.
 *
 *  The WFDieselHit object encapsulates the relavance score, custom loaded callback objects, and data loaded from the index columns directly.
 */
class WFDieselHit extends WFObject
{
    /**
     * @var integer The relevance of the hit, on a scale of 0-100.
     */
    protected $relevanceScore;
    /**
     * @var mixed The unique itemId of the hit.
     */
    protected $itemID;
    /**
     * @var mixed The object var is a placeholder for callbacks to put "objects" that map to the itemID into the WFDieselHit.
     */
    protected $object;
    /**
     * @var object WFDieselHitDataObject - A KVC-compliant proxy object so we can bind to DP result data. Bindings like: object.description, object.pkId
     */
    protected $data;

    function __construct($itemID, $relevanceScore = NULL, $object = NULL)
    {
        $this->itemID = $itemID;
        $this->relevanceScore = ($relevanceScore ? $relevanceScore : NULL);
        $this->object = $object;
        $this->data = new WFDieselHitDataObject;
    }

    /**
     *  Add data to the hit object for the given key/value pair.
     *
     *  This is used to add data loaded from the dieselpoint index directly into a KVC-accesible format.
     *
     *  @param string Data column name
     *  @param mixed Data value
     */
    function addData($key, $value)
    {
        $this->data->addData($key, $value);
    }

    /**
     *  Get the WFDieselHitDataObject for this row.
     *
     *  @return object WFDieselHitDataObject
     */
    function getData()
    {
        return $this->data;
    }
    
    /**
     *  Get the value for a particular column.
     *
     *  @param string Column name
     *  @return mixed Column value
     */
    function getDataForCol($col)
    {
        return $this->data->getDataForCol($col);
    }

    /**
     *  Set the custom object for this hit. 
     *
     *  This will be set by the Propel dataloader or the custom dataloader.
     *
     *  @param mixed The object represented by this hit.
     */
    function setObject($o)
    {
        $this->object = $o;
    }

    /**
     *  Get the custom object for this hit.
     *
     *  @return mixed
     */
    function object()
    {
        return $this->object;
    }

    /**
     *  Get the relevance score for this hit.
     *
     *  @return integer
     */
    function relevanceScore()
    {
        return $this->relevanceScore;
    }

    /**
     *  Get the relevance score for this hit, in % format.
     *
     *  @return string Score as percentage match (100% is highest possible)
     */
    function relevancePercent()
    {
        if ($this->relevanceScore == 0) return NULL;
        return number_format($this->relevanceScore * 100, 0) . "%";
    }

    /**
     *  Get the unique itemID for this hit.
     *
     *  @return mixed THe unique id.
     */
    function itemID()
    {
        return $this->itemID;
    }
}

/**
 * A generic wrapper class to provide KVC-compliance to rows returned from WFDieselSearch.
 */
class WFDieselHitDataObject extends WFObject
{
    /**
     * @var array An assoc-array of all data loaded from the index: "colName" => "value"
     */
    protected $data;

    function addData($key, $value)
    {
        $this->data[$key] = $value;
    }

    function getData()
    {
        return $this->data;
    }
    
    function getDataForCol($col)
    {
        if (!isset($this->data[$col])) throw( new Exception("Col: '$col' doesn't exist.") );
        return $this->data[$col];
    }
    function valueForKey($key)
    {
        return $this->getDataForCol($key);
    }
}

/**
 * All UI Widgets that coordinate with WFDieselSearchHelper should implement this interface for QueryState management.
 *
 * We can't use the normal restoreState() callback for this since we layer a "dpQueryState" as the initial state with the data from the individual UI widgets overriding this state.
 * Since dpQueryState isn't available until AFTER restoreState is called (where setQueryState is called from the PageDidLoad callback) we had to build our own infrastructure for
 * this to work properly.
 *
 * It's very simple.
 *
 * All UI Widgets should call registerWidget() from the allConfigFinishedLoading() method, then implement this interface.
 */
interface WFDieselSearchHelperStateTracking
{
    /**
     *  IFF the UI widget knows that its state was set in the interface, it should use addAttributeQuery/setSimpleQuery to effect this.
     */
    function dieselSearchRestoreState();
}

/**
 * High-level class to help manage complex faceted navigation searches.
 *
 * Typically a UI has more complicated "query management" needs than a plain-old search, particularly for faceted navigation. This class helps facilitate these needs.
 *
 * The WFDieselSearchHelper object acts as a broker between the UI/Application Logic and the raw DP search backend.
 *
 * Needs:
 * - Ability to combine a general "free-form query" with faceted navigation. [simpleQuery]
 * - Ability to automatically produce a faceted navigation UI and maintain state while offering rich UI for browsing. [attributeQueries]
 * - Ability to restrict the search further, but not show these restrictions in the UI. [restrictDQL]
 *
 * This class is the "model" that tracks all of the complex query capabilities and works together with the faceted nav UI widgets to display them.
 *
 * @todo finish attributeQueryLogicalOperators: add to querystate, buildquery, etc
 * @see WFDieselNav
 * @see WFDieselKeyword
 * @see WFDieselFacet
 */
class WFDieselSearchHelper extends WFObject
{
    const QUERY_STATE_SIMPLE_QUERY_ATTR_NAME            = 'simpleQuery';
    const QUERY_STATE_RESTRICT_DQL_QUERY_ATTR_NAME      = 'dpqlQuery';

    const ATTRIBUTE_QUERY_ANY       = "any";
    const ATTRIBUTE_QUERY_ALL       = "all";
    const ATTRIBUTE_QUERY_RANGES    = "any";

    const QUERY_STATE_REGEX                             = '/^([A-Z]{2})_([^=]+)=(.+)$/';

    /**
     * @var object WFDieselSearch The search instance to operate on
     */
    protected $dieselSearch;
    /**
     * @var array An array of WFWidget subclasses that implement the dieselSearchRestoreState method
     */
    protected $widgets;
    /**
     * @var string A DQL query to add to the query. The WFDieselNav will not reveal to the user any info in the restrictDQL query, so it's perfect for constraining searches in a way that
     *             is transparent to the user.
     */
    protected $restrictDQL;
    /**
     * @var string The user-entered "free-form query".
     */
    protected $simpleQueryString;
    /**
     * @var string The "mode" of the simpleQuery. One of "all", "any", "exact".
     */
    protected $simpleQueryMode;
    /**
     * @var array An array of all facet queries. Each entry contains the attribute, comparator, and value. ie: EQ_property_type=Single Family
     */
    protected $attributeQueries;
    /**
     * @var array An array of the logical operator to use for multiple attributeQueries on the same attribute. Format: 'attributeID' => 'any'|'all'. Default for each attribute is "any".
     */
    protected $attributeQueryLogicalOperators;

    /**
     * @var boolean Should the search "Show All" if there is no query?
     */
    protected $showAllOnBlankQuery;
    /**
     * @var string If {@link WFDieselSearchHelper::$showAllOnBlankQuery showAllOnBlankQuery} is true, this is the DQL that will be used to "find all items".
     */
    protected $showAllDPQL;
    

    /**
     * @var string The effective DQL query string after consolidating the complex search.
     */
    private $effectiveDQL;
    /**
     * @var array An assoc-arary of legal "attribute query" comparators and their logical operators.
     */
    private $legalComparatorList = array(
                                            'EQ' => '=', 
                                            'NE' => '<>',
                                            'GT' => '>',
                                            'GE' => '>=',
                                            'LT' => '<',
                                            'LE' => '<=',
                                            'CN' => ':'
                                        );

    function __construct()
    {
        parent::__construct();
        $this->dieselSearch = NULL;
        $this->widgets = array();
        $this->effectiveDQL = NULL;
        $this->restrictDQL = NULL;
        $this->simpleQueryString = NULL;
        $this->simpleQueryMode = "any";
        $this->attributeQueries = array();
        $this->attributeQueryLogicalOperators = array();
        $this->showAllOnBlankQuery = true;
        $this->showAllDPQL = 'doctype=xml';
        $this->loadTheseColumnsFromIndex = array("item_id");
    }

    /**
     *  WFWidget subclasses that can interact with WFDieselSearch should register.
     *
     *  Objects must implement the WFDieselSearchHelperStateTracking interface.
     *
     *  @param object WFWidget
     */
    function registerWidget($widget)
    {
        $this->widgets[] = $widget;
    }

    /**
     *  Set the WFDieselSearch instance to use with this WFDieselSearchHelper instance.
     *
     *  @param object WFDieselSearch
     *  @throws object WFException If the parameter is not a WFDieselSearch.
     */
    function setDieselSearch($dieselSearch)
    {
        if (!($dieselSearch instanceof WFDieselSearch)) throw( new WFException("Passed object is not a WFDieselSearch.") );
        $this->dieselSearch = $dieselSearch;
    }

    /**
     *  Get the WFDieselSearch used by this instance.
     *
     *  @return object WFDieselSearch
     */
    function dieselSearch()
    {
        return $this->dieselSearch;
    }

    /**
     *  Convert the query represented by this instance into DQL and set it up with the associated WFDieselSearch.
     */
    function buildQuery()
    {
        $dpqlItems = array();

        if ($this->simpleQueryString)
        {
            // convert to dpql by setting it on the searcher
            $searcher = $this->dieselSearch->searcher();
            $searcher->setSimpleQuery($this->simpleQueryString, $this->simpleQueryMode);
            $dpqlQueryForSimpleQuery = java_values($searcher->getQueryString());
            $dpqlItems[] = $dpqlQueryForSimpleQuery;
            //print "Converted: '{$this->simpleQueryString}' to '$dpqlQueryForSimpleQuery'<BR>";
            $searcher->setQueryString(NULL);
        }

        if ($this->restrictDQL)
        {
            //print "found restrictDQL: {$this->restrictDQL}<BR>";
            $dpqlItems[] = $this->restrictDQL;
        }

        if (count($this->attributeQueries))
        {
            //print_r($this->attributeQueries);
            $attrQueryInfo = array();
            // an array of strings in form of <OP>_<attribute_id>=<value>
            // convert this into a multi-d array of <attrID> with <ops> with <opQueries>
            foreach ($this->attributeQueries as $qInfo) {
                $matches = array();
                if (preg_match(WFDieselSearchHelper::QUERY_STATE_REGEX, $qInfo, $matches) and count($matches) == 4)
                {
                    $op = $matches[1];
                    $attr = $matches[2];
                    $attrQuery = $matches[3];
                    $attrQueryInfo[$attr][$op][] = $attrQuery;
                }
                else
                {
                    //print "Warning: couldn't parse attribute query: $qInfo.";
                }
            }
            //print_r($attrQueryInfo);
            foreach ($attrQueryInfo as $attrID => $attrQueryInfo) {
                // for now, we allow only EQ's, or GE/LE, or GT/LT
                $comparators = array_unique(array_keys($attrQueryInfo));
                sort($comparators);

                // EQ
                if (isset($attrQueryInfo['EQ']))
                {
                    // EQ only allowed
                    if (count($comparators) > 1) throw new Exception("attribute {$attrID} contains EQ and other comparators: " . print_r($comparators, true));
                    // sensible default
                    if (!isset($this->attributeQueryLogicalOperators[$attrID]))
                    {
                        $this->attributeQueryLogicalOperators[$attrID] = self::ATTRIBUTE_QUERY_ANY;
                    }
                    switch ($this->attributeQueryLogicalOperators[$attrID]) {
                        case self::ATTRIBUTE_QUERY_ANY:
                            $combiner = ' OR ';
                            break;
                        case self::ATTRIBUTE_QUERY_ALL:
                            $combiner = ' AND ';
                            break;
                        default:
                            throw new Exception("EQ logical must be ANY or ALL");
                    }
                    $compChr = $this->legalComparatorList['EQ'];
                    $attrQueryItems = array();  // array of dpql queries on THIS attribute
                    foreach ($attrQueryInfo as $op => $opQueries) {
                        $opSubQueries = array();
                        foreach ($opQueries as $subQ) {
                            $opSubQueries[] = "{$attrID}{$compChr}\"{$subQ}\"";
                        }
                        $attrQueryItems[] = join($combiner, $opSubQueries);
                    }
                    $dpqlItems[] = join(' AND ', $attrQueryItems);
                }
                // GE/LE
                else if ( (isset($attrQueryInfo['GE']) or isset($attrQueryInfo['LE'])) )
                {
                    // only GE/LE allowed
                    if ( $comparators != array('GE', 'LE') ) throw new Exception("attribute {$attrID} contains something besides GE/LE: " . print_r($comparators, true));
                    // sensible default
                    if (!isset($this->attributeQueryLogicalOperators[$attrID]))
                    {
                        $this->attributeQueryLogicalOperators[$attrID] = self::ATTRIBUTE_QUERY_RANGES;
                    }
                    // sanity check
                    if ($this->attributeQueryLogicalOperators[$attrID] !== self::ATTRIBUTE_QUERY_RANGES) throw new Exception("expected RANGE mode");

                    // build DQL
                    $min = min($attrQueryInfo['GE']);
                    $minCompChr = $this->legalComparatorList['GE'];
                    $max = max($attrQueryInfo['LE']);
                    $maxCompChr = $this->legalComparatorList['LE'];
                    $dpqlItems[] = " {$attrID} {$minCompChr} {$min} AND {$attrID} {$maxCompChr} {$max} ";
                }
                // GT/LT
                else if ( (isset($attrQueryInfo['GT']) or isset($attrQueryInfo['LT'])) )
                {
                    // only GT/LT allowed
                    if ( $comparators != array('GT', 'LT') ) throw new Exception("attribute {$attrID} contains something besides GT/LT: " . print_r($comparators, true));
                    // sensible default
                    if (!isset($this->attributeQueryLogicalOperators[$attrID]))
                    {
                        $this->attributeQueryLogicalOperators[$attrID] = self::ATTRIBUTE_QUERY_RANGES;
                    }
                    // sanity check
                    if ($this->attributeQueryLogicalOperators[$attrID] !== self::ATTRIBUTE_QUERY_RANGES) throw new Exception("expected RANGE mode");

                    $min = min($attrQueryInfo['GT']);
                    $minCompChr = $this->legalComparatorList['GT'];
                    $max = max($attrQueryInfo['LT']);
                    $maxCompChr = $this->legalComparatorList['LT'];
                    $dpqlItems[] = " {$attrID} {$minCompChr} {$min} AND {$attrID} {$maxCompChr} {$max} ";
                }
                else
                {
                    throw new Exception("WTF!");
                }
            }
        }

        if (count($dpqlItems))
        {
            //print "Final list of dpqlItems:";
            //print_r($dpqlItems);
            $parenthesizedItems = array();
            foreach ($dpqlItems as $i) {
                $parenthesizedItems[] = "($i)";
            }
            $this->effectiveDQL = join(' AND ', $parenthesizedItems);
            //print "<BR>Final query: '{$this->effectiveDQL}'";
        }
        else
        {
            if ($this->showAllOnBlankQuery)
            {
                $this->effectiveDQL = $this->showAllDPQL;
            }
            else
            {
                $this->effectiveDQL = NULL;
            }
        }
        
        // Set up search on dieselSearch object
        //print "<br>About to query using: '{$this->effectiveDQL}'";
        $this->dieselSearch->setQueryString($this->effectiveDQL);
        // if we have a paginator, we need to update the dpQueryState parameter so that pagination will work on the results.
        if ($this->dieselSearch->paginator())
        {
            $this->dieselSearch->paginator()->setAlternativeParameterValue($this->dieselSearch->queryStateParameterId(), $this->getQueryState());
        }
    }

    /**
     *  Based on the executed query, are we doing a "show all"?
     *
     *  @return boolean
     */
    function isShowAll()
    {
        if (!$this->dieselsearch->hasRunQuery()) throw( new Exception("Only call isShowAll() after the query has been run.") );
        return ( $this->effectiveDQL == $this->showAllDPQL );
    }

    /**
     *  Ge the DQL that is used to "show all".
     *
     *  @return string
     */
    function showAllDPQL()
    {
        return $this->showAllDPQL;
    }

    /**
     *  Set the "any/all" mode for EQ queries where there is more than one value for the attribute.
     *
     *  @param string Attribute ID.
     *  @param string The "mode". One of {@link WFDieselSearchHelper::ATTRIBUTE_QUERY_ANY} or {@link WFDieselSearchHelper::ATTRIBUTE_QUERY_ALL}.
     *  @throws object WFException If the passed mode is invalid.
     */
    function setAttributeQueryMode($attribute, $mode)
    {
        if (!in_array($mode, array(WFDieselSearchHelper::ATTRIBUTE_QUERY_ANY, WFDieselSearchHelper::ATTRIBUTE_QUERY_ALL, WFDieselSearchHelper::ATTRIBUTE_QUERY_RANGES))) throw( new WFException("Invalid mode '{$mode}' for attribute '{$attribute}'.") );
        $this->attributeQueryLogicalOperators[$attribute] = $mode;
    }

    /**
     *  Add a query for a given attribute.
     *
     *  @param string The attribute ID.
     *  @param string The comparator.
     *  @param mixed The value to match.
     *  @throws object WFException If the comparator is invalid.
     *  @see WFDieselSearchHelper::$legalComparatorList
     */
    function addAttributeQuery($attribute, $comparator = NULL, $query = NULL)
    {
        // look for EQ_attribute=query
        if ($comparator === NULL and $query === NULL)
        {
            extract($this->parseAttributeQuery($attribute));
        }

        $aq = $this->buildAttributeQuery($attribute, $comparator, $query);
        switch ($comparator) {
            case 'GT':
            case 'GE':
            case 'LT':
            case 'LE':
                $this->attributeQueries[] = $aq;
                break;
            case 'EQ':
                // don't add duplicates
                if (!in_array($aq, $this->attributeQueries))
                {
                    $this->attributeQueries[] = $aq;
                }
                break;
            default:
                throw( new WFException("Illegal comparator: " . $comparator) );
        }
    }

    private function buildAttributeQuery($attribute, $comparator, $query)
    {
        return "{$comparator}_{$attribute}={$query}";
    }

    private function parseAttributeQuery($aq)
    {
        $matches = array();
        if (preg_match(WFDieselSearchHelper::QUERY_STATE_REGEX, $aq, $matches) and count($matches) == 4)
        {
            return array(
                'comparator'    => $matches[1],
                'attribute'     => $matches[2],
                'query'         => $matches[3]
            );
        }
        throw new Exception("unrecognized attribute query '{$aq}'");
    }

    /**
     *  Clear all queries for the passed attribute ID.
     *
     *  @param string The attribute ID.
     */
    function clearAttributeQueries($attribute)
    {
        //print "clearing $attribute";
        //print_r($this->attributeQueries);
        $newAttributeQueries = array();
        foreach ($this->attributeQueries as $q) {
            $matches = array();
            if (preg_match(WFDieselSearchHelper::QUERY_STATE_REGEX, $q, $matches) and count($matches) == 4)
            {
                $attr = $matches[2];
                if ($attr != $attribute)
                {
                    $newAttributeQueries[] = $q;
                }
            }
        }
        //print_r($newAttributeQueries);
        $this->attributeQueries = $newAttributeQueries;
    }

    /**
     * Get an array of all "selected" attributeQueries for the given attribute.
     */
    function getSelectedAttributeQueries($attribute)
    {
        $selections = array();
        foreach ($this->attributeQueries as $q) {
            if (!$q) continue;  // not sure why q === '' sometimes

            $aq = $this->parseAttributeQuery($q);
            if ($attribute == $aq['attribute'])
            {
                $selections[] = $q;
            }
        }
        return $selections;
    }

    function getAttributeSelectedValues($attribute)
    {
        $state = array();
        foreach ($this->attributeQueries as $q) {
            $matches = array();
            if (preg_match(WFDieselSearchHelper::QUERY_STATE_REGEX, $q, $matches) and count($matches) == 4)
            {
                $attr = $matches[2];
                if ($attr == $attribute)
                {
                    $op = $matches[1];
                    $val = $matches[3];
                    $state[$op][] = $val;
                }
            }
            else
            {
                throw new Exception("Couldn't parse attribute value for {$attribute}");
            }
        }
        return $state['EQ'];
    }

    /**
     *  Get a human-readable description of the current attribute selections for the passed attribute.
     *
     *  @param string The attribute ID.
     *  @param object WFFormatter A formatter object that will be used to "format" the value(s).
     *  @return string
     */
    function getAttributeSelection($attribute, $formatter = NULL)
    {
        $state = array();
        foreach ($this->attributeQueries as $q) {
            $matches = array();
            if (preg_match(WFDieselSearchHelper::QUERY_STATE_REGEX, $q, $matches) and count($matches) == 4)
            {
                $attr = $matches[2];
                if ($attr == $attribute)
                {
                    $op = $matches[1];
                    $val = $matches[3];
                    if ($formatter)
                    {
                        $val = $formatter->stringForValue($val);
                    }
                    $state[$op][] = $val;
                }
            }
            else
            {
                //print "Warning: couldn't parse attribute query: $q.";
            }
        }
        if (count($state))
        {
            $desc = "";
            $ops = array_keys($state);
            if (in_array('EQ', $ops))
            {
                $desc = join(', ', $state['EQ']);
            }
            //todo: need to sync this with the way the queries work -- min/max on things
            else if (count(array_intersect($ops, array('LE', 'GE'))) > 0) //in_array('GE', $ops))
            {
                $min = min($state['GE']);
                $max = max($state['LE']);
                $desc .= "{$min} - {$max}";
            }
            else if (count(array_intersect($ops, array('LT', 'GT'))) > 0) //in_array('GE', $ops))
            {
                $min = min($state['GT']);
                $max = max($state['LT']);
                $desc .= "{$min} - {$max}";
            }

            return $desc;
        }
        return NULL;
    }

    /**
     *  Is there any filter on the passed attribute?
     *
     *  @param string The attribute ID.
     *  @return boolean
     */
    function isFilteringOnAttribute($attribute)
    {
        foreach ($this->attributeQueries as $q) {
            $matches = array();
            if (preg_match(WFDieselSearchHelper::QUERY_STATE_REGEX, $q, $matches) and count($matches) == 4)
            {
                $attr = $matches[2];
                if ($attr == $attribute)
                {
                    return true;
                }
            }
            else
            {
                //print "Warning: couldn't parse attribute query: $qInfo.";
            }
        }
        return false;
    }

    /**
     *  Get the queryState for the current instance, WITHOUT the simpleQuery filter.
     *
     *  The result is plain-text; it will need to be urlencode() as necessary.
     *
     *  @return string
     */
    function getQueryStateWithoutSimpleQuery()
    {
        return $this->getQueryState(WFDieselSearchHelper::QUERY_STATE_SIMPLE_QUERY_ATTR_NAME);
    }

    /**
     *  Get the queryState for the current instace with ONLY the restrictDQL filter.
     *
     *  This is useful for a "clear all filters" link.
     *
     *  @return string
     */
    function getQueryStateWithRestrictDQLOnly()
    {
        if ($this->restrictDQL)
        {
            return WFDieselSearchHelper::QUERY_STATE_RESTRICT_DQL_QUERY_ATTR_NAME . '=' . $this->restrictDQL;
        }
        return NULL;
    }
    
    /**
     *  Get the queryState for the current instance.
     *
     *  The result is plain-text; it will need to be urlencode() as necessary.
     *
     *  @param string The attribute ID of an attribute to REMOVE from the current state (or NULL to preserve all). Useful for creating "remove" filter links.
     *  @param array An array of new "attribute queries" to add to the current state. Useful for creating links to possible sub-queries.
     *               The "attribute query" format is <op>_<attribute_id>=<query>
     *  @return string
     */
    function getQueryState($excludeAttribute = NULL, $newAttributeQueries = array())
    {
        $state = array();
        if ($this->simpleQueryString and $excludeAttribute != WFDieselSearchHelper::QUERY_STATE_SIMPLE_QUERY_ATTR_NAME)
        {
            $state[] = WFDieselSearchHelper::QUERY_STATE_SIMPLE_QUERY_ATTR_NAME . '=' . $this->simpleQueryString;
        }
        if ($this->restrictDQL)
        {
            $state[] = WFDieselSearchHelper::QUERY_STATE_RESTRICT_DQL_QUERY_ATTR_NAME . '=' . $this->restrictDQL;
        }
        foreach ($this->attributeQueries as $q) {
            $matches = array();
            if (preg_match(WFDieselSearchHelper::QUERY_STATE_REGEX, $q, $matches) and count($matches) == 4)
            {
                $attr = $matches[2];
                if ($attr != $excludeAttribute)
                {
                    $state[] = $q;
                }
            }
            else
            {
                //print "Warning: couldn't parse attribute query: $qInfo.";
            }
        }
        foreach ($newAttributeQueries as $q) {
            $state[] = $q;
        }
        $state = array_map(array($this, 'encodeAttributeQuery'), $state);
        //WFLog::log("done building state: " . print_r($state, true));
        $rv =  join('|',$state);
        //WFLog::log("called u/j");
        return $rv;
    }

    /**
     * Encode an attributeQuery chunk so that it can be concatenated into queryState without screwing things up (I'm looking at you |).
     *
     * @param string AttributeQuery Value
     * @return string Encoded AttributeQuery Value
     */
    // @todo Need to use ATTRIBUTE_QUERY_DELIMITER all over the place in WFDiesel* and client code to make this cleaner for the future.
    const ATTRIBUTE_QUERY_DELIMITER             = '|';
    const ATTRIBUTE_QUERY_DELIMITER_ENCODED     = '-::-';
    private function encodeAttributeQuery($q)
    {
        return str_replace(self::ATTRIBUTE_QUERY_DELIMITER, self::ATTRIBUTE_QUERY_DELIMITER_ENCODED, $q);
    }
    /**
     * @see encodeAttributeQuery()
     */
    private function decodeAttributeQuery($q)
    {
        return str_replace(self::ATTRIBUTE_QUERY_DELIMITER_ENCODED, self::ATTRIBUTE_QUERY_DELIMITER, $q);
    }

    /**
     *  Reset the query state to "empty". 
     *
     *  Note that this does not clear the restrictDQL.
     */
    function resetQueryState()
    {
        //print "Resetting QS<BR>";
        $this->simpleQueryString = NULL;
        $this->attributeQueries = array();
    }

    /**
     *  Set the query state to the passed state.
     *
     *  NOTE: if you are using WFDieselSearch with a paginator, and you get errors about "relevance" not being a
     *  sort option when restoring state with setQueryState, make sure that you call setQueryState BEFORE
     *  you call readPaginatorStateFromParams, as setQueryState will add the "relevance" sort option if the
     *  query state includes a simpleQuery.
     *
     *  @param string The serialized state. Should be in plain-text form (ie all decoding already done)
     */
    function setQueryState($state)
    {
        // Restore the query state indicated by the serialized state (this is the "initial" state, before any "changes" from form posts are processed)
        //print "Restoring querystate: $state<BR>";
        $attrQueries = array_map(array($this, 'decodeAttributeQuery'), explode('|', $state));
        foreach ($attrQueries as $q) {
            //print "Extracting state from: $q<BR>";
            if (strncmp($q, WFDieselSearchHelper::QUERY_STATE_SIMPLE_QUERY_ATTR_NAME, strlen(WFDieselSearchHelper::QUERY_STATE_SIMPLE_QUERY_ATTR_NAME)) == 0)
            {
                //print "Extracting simpleQuery<BR>";
                $this->setSimpleQuery(substr($q, strlen(WFDieselSearchHelper::QUERY_STATE_SIMPLE_QUERY_ATTR_NAME) + 1));
            }
            else if (strncmp($q, WFDieselSearchHelper::QUERY_STATE_RESTRICT_DQL_QUERY_ATTR_NAME, strlen(WFDieselSearchHelper::QUERY_STATE_RESTRICT_DQL_QUERY_ATTR_NAME)) == 0)
            {
                //print "Extracting dpqlQuery<BR>";
                $this->restrictDQL = substr($q, strlen(WFDieselSearchHelper::QUERY_STATE_RESTRICT_DQL_QUERY_ATTR_NAME) + 1);
            }
            else
            {
                //print "Extracting attrQuery<BR>";
                $this->attributeQueries[] = $q;
            }
        }

        // allow registered widgets to affect the "initial" state
        foreach ($this->widgets as $widget) {
            $widget->dieselSearchRestoreState();
        }
    }

    /**
     *  Set the DQL query to be used to restrict the search results in ways IN ADDITION TO what the user selects in the UI.
     *
     *  @param string A DQL query.
     */
    function setRestrictDQL($string)
    {
        $this->restrictDQL = $string;
    }

    /**
     *  Get the "restrictDQL" query.
     *
     *  @return string A DQL query.
     */
    function restrictDQL()
    {
        return $this->restrictDQL;
    }

    /**
     *  Clear the simpleQuery part of the search.
     */
    function clearSimpleQuery()
    {
        $this->simpleQueryString = NULL;
    }

    /**
     *  Set the simpleQuery part of the search.
     *
     *  Note that if using a paginator, a "Relevance" sort option (-relevance) will be automatically added to the possible sort options.
     *
     *  @param string The query. This is typically the direct user input into a "basic search" field.
     *  @param string The mode for the simplequery: one of "any", "all", or "exact".
     *  @throws object WFException if the mode is incorrect.
     */
    function setSimpleQuery($string, $mode = "any")
    {
        $this->simpleQueryString = trim($string);
        $this->simpleQueryMode = $mode;
        // add relevance sorting if there's a query
        if ($this->simpleQueryString)
        {
            $this->dieselSearch->enableRelevanceSorting();
        }
    }

    /**
     *  Get the simpleQuery string.
     *
     *  @return string
     */
    function simpleQuery()
    {
        return $this->simpleQueryString;
    }

    /**
     *  Get the simpleQuery mode.
     *
     *  @return string One of "any", "all", "exact".
     */
    function simpleQueryMode()
    {
        return $this->simpleQueryMode;
    }

    /**
     *  Get a pretty "description" of the current query.
     *
     *  @return string The description.
     *  @todo Right now this just wraps {@link WFDieselSearch::queryDescription() queryDescription}. Eventually re-write here to use a "pretty" description built a la buildQuery.
     */
    function queryDescription()
    {
        return java_values($this->dieselSearch->queryDescription());
    }

}

class WFDieselSearch_ParseException extends WFException {}

class WFDieselSearch_FacetedAttribute extends WFObject
{
    protected $attributedId;
    protected $dieselSearch;
    protected $dieselSearchHelper;

    protected $options = array();

    /**
     * @const boolean True to implement fake open-ended ranges corrections. With DP < 4.0, there is no range supprort, so we fake it with multi-value attr categories. IE Bedrooms 1+, 2+, 3+. 
     *                The downside of this is that the facets don't adjust as choices are selected (b/c 3+ includes 1+ and 2+).
     *                The fakeOpenEndedRange support will correct the facet display by eliminating choices less than the current value.
     */
    const OPT_FAKE_OPEN_ENDED_RANGE     = 'fake_open_ended_range';
    /**
     * @const int The number of ranges to show for the facet. Presently this will create N facets each containing approximately equal numbers of items. Default is 0, which disables range mode.
     */
    const OPT_RANGE_COUNT               = 'range_count';
    /**
     * @const int The maximum number of hits to count for each facet. Defaults to SHOW EXACT COUNT (Integer.MAX_VALUE). Set to a lower number if you don't care about more than a certain number, like 1000. Set to 1 for maxium performance. NOTE: setting showItemCounts to FALSE will automatically set maxHits to 1.
     */
    const OPT_MAX_HITS                  = 'max_hits';
    /**
     * @const int The maximum number of facets to show. Defaults to -1, which means SHOW ALL ROWS.
     */
    const OPT_MAX_ROWS                  = 'max_rows';
    /**
     * @const boolean If true, facets will be sorted by frequency of each facet. In this case, the facet with the most "hits" will be first, etc. If false, facets are sorted by the value they represent. Default is TRUE.
     */
    const OPT_SORT_BY_FREQUENCY         = 'sortByFrequency';
    /**
     * @const boolean If true, the number of items in each facet will be shown as well. Default is TRUE. Performance will be faster if this is set to FALSE.
     */
    const OPT_SHOW_ITEM_COUNTS          = 'showItemCounts';
    /**
     * @const boolean TRUE to enable multiple selection, false to only allow single selections. DEFAULT is TRUE.
     */
    const OPT_ALLOW_MULTIPLE_SELECTION  = 'multipleSelection';

    /**
     * @const object WFFormatter to format the facet labels.
     */
    const OPT_FORMATTER                 = 'formatter';

    // ??
    const OPT_TAXONOMY_PATH             = 'taxonomy_path';
    const OPT_TAXONOMY_DEFAULT_PATH     = 'taxonomy_default_path';

    const MAX_ROWS_UNLIMITED            = -1;


    public function __construct($attributeId, $dieselSearchHelper, $options = array())
    {
        $defaultOptions = array(
            self::OPT_FAKE_OPEN_ENDED_RANGE     => false,
            self::OPT_RANGE_COUNT               => 0,
            self::OPT_MAX_HITS                  => 10000000,    /* Integer.MAX_VALUE */
            self::OPT_MAX_ROWS                  => self::MAX_ROWS_UNLIMITED,
            self::OPT_SORT_BY_FREQUENCY         => true,
            self::OPT_FORMATTER                 => NULL,
            self::OPT_SHOW_ITEM_COUNTS          => true,
            self::OPT_ALLOW_MULTIPLE_SELECTION  => true,
        );
        $this->options = array_merge($defaultOptions, $options);
        $this->attributeId = $attributeId;
        $this->dieselSearchHelper = $dieselSearchHelper;
        $this->dieselSearch = $dieselSearchHelper->dieselSearch();

        // optimization
        if ($this->options[self::OPT_SHOW_ITEM_COUNTS] === false)
        {
            $this->options[self::OPT_MAX_HITS] = 1;
        }
    }

    public function facetSearchOptions($includeAlternateFacets = false)
    {
        $this->prepareFacets();

        $facetSearchOptions = array(
            'currentSelection'          => $this->dieselSearchHelper->getSelectedAttributeQueries($this->attributeId),
            'allowMultipleSelection'   => $this->options[self::OPT_ALLOW_MULTIPLE_SELECTION],
            'queryBase'                 => $this->dieselSearchHelper->getQueryState($this->attributeId),
            'hasMoreFacets'             => false,
            'facets'                    => array(),
        );

        if (gettype($this->generatedFacetData) === 'object')
        {
            $Array = new JavaClass("java.lang.reflect.Array");
            if ($this->options[self::OPT_MAX_ROWS] != self::MAX_ROWS_UNLIMITED and $Array->getLength($this->generatedFacetData) == $this->options[self::OPT_MAX_ROWS])
            {
                $facetSearchOptions['hasMoreFacets'] = true;
            }
            else if ($this->options[self::OPT_MAX_ROWS] == self::MAX_ROWS_UNLIMITED and $Array->getLength($this->generatedFacetData) == $this->options[self::OPT_MAX_ROWS])
            {
                $facetSearchOptions['hasMoreFacets'] = true;
            }
        }

        // actual facet data
        if (count($facetSearchOptions['currentSelection']) == 0)
        {
            $facetSearchOptions['facets'] = $this->facets();
        }
        else
        {
            if ($includeAlternateFacets)
            {
                $facetSearchOptions['facets'] = 'coming soon';
                // set up a new search w/o this attribute and just get facets for this one.
                $subDpSearch = new WFDieselSearch;
                $subDpSearch->setIndex($this->dieselSearch->index());
                $subDpSearchHelper = new WFDieselSearchHelper;
                $subDpSearchHelper->setDieselSearch($subDpSearch);
                $subDpSearchHelper->setQueryState($facetSearchOptions['queryBase']);
                $subFacet = new WFDieselSearch_FacetedAttribute($this->attributeId, $subDpSearchHelper, $this->options);
                $subFacetInfo = $subFacet->facetSearchOptions();
                // copy data over from sub-query
                $facetSearchOptions['facets'] = $subFacetInfo['facets'];
                $facetSearchOptions['hasMoreFacets'] = $subFacetInfo['hasMoreFacets'];
            }
            else
            {
                $facetSearchOptions['facets'] = 'query for more';
            }
        }

        return $facetSearchOptions;
    }

    private $facets = NULL;
    public function facets()
    {
        if ($this->facets) return $this->facets;

        $this->facets = array();
        $this->prepareFacets();
        foreach ($this->generatedFacetData as $facet) {
            $localFacetData = array();

            $attributeValue = java_values($facet->getAttributeValue());

            // calculate label
            $label = '';
            if ($this->options[self::OPT_FORMATTER])
            {
                $label .= $this->options[self::OPT_FORMATTER]->stringForValue($attributeValue);
            }
            else
            {
                $label .= $attributeValue;
            }
            if ($this->options[self::OPT_RANGE_COUNT] and java_values($facet->getEndValue()))
            {
                $label .= ' - ';
                if ($this->options[self::OPT_FORMATTER])
                {
                    $label .= $this->options[self::OPT_FORMATTER]->stringForValue(java_values($facet->getEndValue()));
                }
                else
                {
                    $label .= $facet->getEndValue();
                }
            }
            $localFacetData['label'] = $label;

            // calculate item counts
            $itemCount = NULL;
            if ($this->options[self::OPT_SHOW_ITEM_COUNTS])
            {
                $itemCount = $facet->getHits();
            }
            $localFacetData['itemCount'] = $itemCount;

            // support for fake open-ended ranges with mutli-value hack
            if ($this->options[self::OPT_FAKE_OPEN_ENDED_RANGE])
            {
                $currentVal = $this->dieselSearchHelper->getAttributeSelection($this->attributeId);
                if ($attributeValue < $currentVal) continue;
            }

            $facetAttrQuery = array();
            if ($this->options[self::OPT_RANGE_COUNT])
            {
                if ($facet->getEndValue())
                {
                    $facetAttrQuery[] = "GE_{$this->attributeId}=" . $attributeValue;
                    $facetAttrQuery[] = "LE_{$this->attributeId}=" . $facet->getEndValue();
                }
                else
                {
                    $facetAttrQuery[] = "EQ_{$this->attributeId}=" . $attributeValue;
                }
            }
            else
            {
                if ($this->isTaxonomyAttribute())
                {
                    $facetAttrQuery = array("EQ_{$this->attributeId}=" . java_values($facet->getPath()));
                }
                else
                {
                    $facetAttrQuery = array("EQ_{$this->attributeId}=" . $attributeValue);
                }
            }
            $localFacetData['attributeQuery'] = join('|', $facetAttrQuery);

            $this->facets[] = $localFacetData;
        }
        return $this->facets;
    }

    // cached for your pleasure
    private $isTaxonomyAttribute = NULL;
    private function isTaxonomyAttribute()
    {
        if (!is_null($this->isTaxonomyAttribute)) return $this->isTaxonomyAttribute;

        $row = $this->dieselSearch->index()->getAttribute()->getRowByAttributeId($this->attributeId);
        if (!$row) throw( new Exception("Couldn't find attribute: {$this->attributeId}") );
        $Attribute = new JavaClass('com.dieselpoint.search.Attribute');
        $this->isTaxonomyAttribute = ($row->getDataType()->equals($Attribute->DATATYPE_TAXONOMY));

        return $this->isTaxonomyAttribute;
    }

    private $generatedFacetData = NULL;
    private function prepareFacets()
    {
        if ($this->generatedFacetData) return $this->generatedFacetData;

        // load facet data
        if ($this->isTaxonomyAttribute())
        {
            $facetGenerator = $this->dieselSearch->getGeneratorObject(true);
            // determine "open branch"
            $cVal = $this->options[self::OPT_TAXONOMY_PATH];
            if ($cVal)
            {
                $openToBuf = new Java('com.dieselpoint.util.FastStringBuffer', $cVal);
            }
            else if ($this->options[self::OPT_TAXONOMY_DEFAULT_PATH])
            {
                $openToBuf = new Java('com.dieselpoint.util.FastStringBuffer', $this->options[self::OPT_TAXONOMY_DEFAULT_PATH]);
            }
            else
            {
                $openToBuf = new Java('com.dieselpoint.util.FastStringBuffer', '');
            }
            $treeRootBuf = new Java('com.dieselpoint.util.FastStringBuffer', $cVal ? $cVal : "");
            if ($this->dieselSearch->logPerformanceInfo()) $this->dieselSearch->startTrackingTime();
            $facets = $facetGenerator->getTaxonomyTree($this->attributeId, $treeRootBuf, 3, $this->options[self::OPT_MAX_HITS]); // mouser facetgenerator (handles multiple depths)
            if ($this->dieselSearch->logPerformanceInfo()) $this->dieselSearch->stopTrackingTime("Generating facet with getTaxonomyTree(\"{$this->attributeId}\", \"{$openToBuf}\", \"{$treeRootBuf}\", {$this->maxHits}) for {$this->id}");
            if (count($facets) == 1 and $facets[0]->getAttributeValue()->equals('')) // needed to extract facets from trees
            {
                $facets = $facets[0]->getChildren();
            }
            if (!$facets)
            {
                $facets = array();
            }
        }
        else
        {
            $facetGenerator = $this->dieselSearch->getGeneratorObject();
            if ($this->options[self::OPT_RANGE_COUNT])
            {
                $facetGenerator->setRangeCount($this->options[self::OPT_RANGE_COUNT]);
            }
            if ($this->dieselSearch->logPerformanceInfo()) $this->dieselSearch->startTrackingTime();
            $facets = $facetGenerator->getList($this->attributeId, $this->options[self::OPT_MAX_ROWS], $this->options[self::OPT_SORT_BY_FREQUENCY], $this->options[self::OPT_MAX_HITS]);
            if ($this->dieselSearch->logPerformanceInfo()) $this->dieselSearch->stopTrackingTime("Generating facet with getList(\"{$this->attributeId}\", {$this->options[self::OPT_MAX_ROWS]}, " . ($this->options[self::OPT_SORT_BY_FREQUENCY] ? 'true' : 'false') . ", {$this->options[self::OPT_MAX_HITS]})");
        }

        $this->generatedFacetData = $facets;
        return $this->generatedFacetData;
    }
}
