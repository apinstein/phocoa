<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

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
 * DP Questions:
 *
 * 1. Ranges; can you suggest ranges for facets? The default algo of "make equal chunks" is not that useful. Fixed ranges? Open-ended? (IE Bedrooms as 1+,2+,3+ instead of "auto") CURRENTLY not possible directly, but can pre-process items to group them like this and then display differently.
 *    Ranges: ZOOMABLE ranges; ie click on 700-800 and that unveils 700-750 and 750-800. How would this work with the planned new capabilities?
 * 2. Numbers seems to be getting treated lexographically. PRICE and SQFT are good examples. Also, new attrs cannot be found.
 *    When does the info in the index refresh, b/c it fixes itself, but not immediately after delete/re-index. Does it have to do with leaving it open?
 * 3. Geospatial stuff? Items withing N miles?
 * 4. Case-sensitivity. How does this work? To turn off case-sensitivity for a field, can you still use parametric? NO. Parametric is case-sensitive; normalize data on the way in.
 * 5. SimpleQuery: golf and tennis => "golf or and or tennis", golf AND tennis => "golf and tennis"
 *
 * USING WFDieselSearch
 *
 * Setup:
 * 1. In your webapp.conf, you should configure DIESELPOINT_JAR_FILES to include paths to all of the DP jars that you need, typically diesel-3.5.1.jar and javax.servlet.jar.
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
 */
class WFDieselSearch extends WFObject implements WFPagedData
{
    const QUERY_STATE_SIMPLE_QUERY_ATTR_NAME = 'simpleQuery';
    const QUERY_STATE_DPQL_QUERY_ATTR_NAME = 'dpqlQuery';
    const SORT_BY_RELEVANCE = '-relevance';
    protected $index;
    protected $searcher;
    protected $resultObjectLoaderCallback;
    protected $dpQueryStateParameterID;

    protected $hasBuiltQuery;
    protected $hasRunQuery;
    protected $dpqlQueryString;
    protected $simpleQueryString;
    protected $simpleQueryMode;
    protected $attributeQueries;
    protected $paginator;
    protected $showAllOnBlankQuery;
    protected $searchResultsRelevanceByItemID;
    protected $loadTheseColumnsFromIndex;

    private $finalQueryString;
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
        $this->resultObjectLoaderCallback = NULL;
        $this->searcher = NULL;
        $this->hasBuiltQuery = false;
        $this->hasRunQuery = false;
        $this->finalQueryString = NULL;
        $this->dpqlQueryString = NULL;
        $this->simpleQueryString = NULL;
        $this->simpleQueryMode = "any";
        $this->attributeQueries = array();
        $this->index = NULL;
        $this->paginator = NULL;
        $this->dpQueryStateParameterID = 'dpQueryState';
        $this->showAllOnBlankQuery = true;
        $this->resultObjectLoaderCallbackPropelMode = false;
        $this->searchResultsRelevanceByItemID = array();
        $this->loadTheseColumnsFromIndex = array("item_id");
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
     *  @throws object Exception on bad name.
     */
    function setResultObjectLoaderCallbackWithPropelPeer($peerName)
    {
        if ($peerName == NULL)
        {
            $this->resultObjectLoaderCallback = NULL;
            $this->resultObjectLoaderCallbackPropelMode = NULL;
            return;
        }
        
        // check that the callback exists -- with PHOCOA's autoload, we need to check the class explicitly
        if (!class_exists($peerName))
        {
            //__autoload($peerName); class exists should do this.
            if (!class_exists($peerName)) throw( new Exception("Callback class '{$peerName}' does not exist.") );
        }
        $this->resultObjectLoaderCallback = $peerName;
        $this->resultObjectLoaderCallbackPropelMode = true;
    }

    function setIndex($indexPath)
    {
        try {
            if (!defined('DIESELPOINT_JAR_FILES')) throw( new Exception("DIESELPOINT_JAR_FILES must be defined, usually in your webapp.conf file.") );
            java_require(DIESELPOINT_JAR_FILES);
            $Index = new JavaClass("com.dieselpoint.search.Index");
            $this->index = $Index->getInstance($indexPath);

            // prepare searcher
            $this->prepareSearcher();
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
        throw( new Exception("java stack trace:<pre> $trace </pre>\n") );
    }

    function getQueryDescription()
    {
        if (!$this->hasRunQuery()) return NULL;
        try {
            return $this->searcher->getQueryDescription(true, '', '');
        } catch (JavaException $e) {
            $this->handleJavaException($e);
        }
    }

    function index()
    {
        if (is_null($this->index)) throw( new Exception("No dieselpoint index set up. Use setIndex().") );
        return $this->index;
    }

    function searcher()
    {
        return $this->searcher;
    }

    function hasQuery()
    {
        if ($this->buildQuery()) return true;
        return false;
    }

    function buildQuery()
    {
        if ($this->hasBuiltQuery)
        {
            return $this->finalQueryString;
        }

        $dpqlItems = array();

        if ($this->simpleQueryString)
        {
            // convert to dpql by setting it on the searcher
            $this->searcher->setSimpleQuery($this->simpleQueryString, $this->simpleQueryMode);
            $dpqlQueryForSimpleQuery = $this->searcher->getQueryString();
            $dpqlItems[] = $dpqlQueryForSimpleQuery;
            //print "Converted: '{$this->simpleQueryString}' to '$dpqlQueryForSimpleQuery'<BR>";
            $this->searcher->setQueryString(NULL);
        }

        if ($this->dpqlQueryString)
        {
            //print "found dpstring<BR>";
            $dpqlItems[] = $this->dpqlQueryString;
        }

        if (count($this->attributeQueries))
        {
            //print_r($this->attributeQueries);
            $attrQueryInfo = array();
            // an array of strings in form of <OP>_<attribute_id>=<value>
            // convert this into a multi-d array of <attrID> with <ops> with <opQueries>
            foreach ($this->attributeQueries as $qInfo) {
                $matches = array();
                if (preg_match('/^([A-Z]{2})_([^=]*)=(.*)$/', $qInfo, $matches) and count($matches) == 4)
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
                $attrQueryItems = array();  // array of dpql queries on THIS attribute
                foreach ($attrQueryInfo as $op => $opQueries) {
                    $opSubQueries = array();
                    foreach ($opQueries as $subQ) {
                        if (!isset($this->legalComparatorList[$op]))
                        {
                            throw(new Exception("Unknown operator: '$op'"));
                        }
                        $opChr = $this->legalComparatorList[$op];
                        $opSubQueries[] = "{$attrID}{$opChr}\"{$subQ}\"";
                    }
                    if ($op == 'EQ')
                    {
                        $attrQueryItems[] =  join(' OR ', $opSubQueries);
                    }
                    else
                    {
                        $attrQueryItems[] =  join(' AND ', $opSubQueries);
                    }
                }
                $dpqlItems[] = join(' AND ', $attrQueryItems);
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
            $this->finalQueryString = join(' AND ', $parenthesizedItems);
            //print "<BR>Final query: '{$this->finalQueryString}'";
        }
        else
        {
            if ($this->showAllOnBlankQuery)
            {
                $this->finalQueryString = 'doctype=xml';
            }
            else
            {
                $this->finalQueryString = NULL;
            }
        }
        $this->hasBuiltQuery = true;
        return $this->finalQueryString;
    }

    function addAttributeQuery($attribute, $comparator, $query)
    {
        if (!in_array($comparator, array('EQ', 'GT', 'GE', 'LT', 'LE'))) throw( new Exception("Illegal comparator: " . $comparator) );
        // don't add duplicates
        $aq = "{$comparator}_{$attribute}={$query}";
        if (!in_array($aq, $this->attributeQueries))
        {
            $this->attributeQueries[] = $aq;
            //print "adding {$comparator}_{$attribute}={$query}<BR>";
        }
    }

    function clearAttributeQueries($attribute)
    {
        //print "clearing $attribute";
        //print_r($this->attributeQueries);
        $newAttributeQueries = array();
        foreach ($this->attributeQueries as $q) {
            $matches = array();
            if (preg_match('/^([A-Z]{2})_([^=]*)=(.*)$/', $q, $matches) and count($matches) == 4)
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

    // get a pretty description of the passed attr's navigation description: ie, 100-200, House, etc
    function getAttributeSelection($attribute, $formatter = NULL)
    {
        $state = array();
        foreach ($this->attributeQueries as $q) {
            $matches = array();
            if (preg_match('/^([A-Z]{2})_([^=]*)=(.*)$/', $q, $matches) and count($matches) == 4)
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
                //print "Warning: couldn't parse attribute query: $qInfo.";
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
            else if (in_array('GE', $ops))
            {
                $desc = $state['GE'][0];
                if (in_array('LE', $ops) or in_array('LT', $ops))
                {
                    if (in_array('LE', $ops))
                    {
                        $desc .= ' - ' . $state['LE'][0];
                    }
                    else
                    {
                        $desc .= ' - ' . $state['LT'][0];
                    }
                }
                else
                {
                    $desc .= "+";
                }
            }

            return $desc;
        }
        return NULL;
    }

    function isFilteringOnAttribute($attribute)
    {
        foreach ($this->attributeQueries as $q) {
            $matches = array();
            if (preg_match('/^[A-Z]{2}_([^=]*)=.*$/', $q, $matches) and count($matches) == 2)
            {
                $attr = $matches[1];
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

    function getQueryStateWithoutSimpleQuery()
    {
        return $this->getQueryState(WFDieselSearch::QUERY_STATE_SIMPLE_QUERY_ATTR_NAME);
    }
    
    function getQueryState($excludeAttribute = NULL, $newAttributeQueries = array())
    {
        $state = array();
        if ($this->simpleQueryString and $excludeAttribute != WFDieselSearch::QUERY_STATE_SIMPLE_QUERY_ATTR_NAME)
        {
            $state[] = WFDieselSearch::QUERY_STATE_SIMPLE_QUERY_ATTR_NAME . '=' . $this->simpleQueryString;
        }
        if ($this->dpqlQueryString)
        {
            $state[] = WFDieselSearch::QUERY_STATE_DPQL_QUERY_ATTR_NAME . '=' . $this->dpqlQueryString;
        }
        foreach ($this->attributeQueries as $q) {
            $matches = array();
            if (preg_match('/^[A-Z]{2}_([^=]*)=.*$/', $q, $matches) and count($matches) == 2)
            {
                $attr = $matches[1];
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
        //WFLog::log("done building state: " . print_r($state, true));
        $rv =  urlencode(join('|',$state));
        //WFLog::log("called u/j");
        return $rv;
    }

    function resetQueryState()
    {
        //print "Resetting QS<BR>";
        $this->simpleQueryString = NULL;
        $this->dpqlQueryString = NULL;
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
     *  @param array The serialized state.
     *  @param boolean TRUE to reset the query to empty before applying the passed state. If false, simpleQueryString and dpqlQueryString will be replaced
     *  completely, but the attributeQueries will be additive.
     */
    function setQueryState($state, $reset = true)
    {
        if ($reset)
        {
            $this->resetQueryState();
        }

        $query = urldecode($state);
        //print "Restoring querystate: $query<BR>";
        $attrQueries = explode('|', $query);
        foreach ($attrQueries as $q) {
            //print "Extracting state from: $q<BR>";
            if (strncmp($q, WFDieselSearch::QUERY_STATE_SIMPLE_QUERY_ATTR_NAME, strlen(WFDieselSearch::QUERY_STATE_SIMPLE_QUERY_ATTR_NAME)) == 0)
            {
                //print "Extracting simpleQuery<BR>";
                $this->setSimpleQuery(substr($q, strlen(WFDieselSearch::QUERY_STATE_SIMPLE_QUERY_ATTR_NAME) + 1));
            }
            else if (strncmp($q, WFDieselSearch::QUERY_STATE_DPQL_QUERY_ATTR_NAME, strlen(WFDieselSearch::QUERY_STATE_DPQL_QUERY_ATTR_NAME)) == 0)
            {
                //print "Extracting dpqlQuery<BR>";
                $this->dpqlQueryString = substr($q, strlen(WFDieselSearch::QUERY_STATE_DPQL_QUERY_ATTR_NAME) + 1);
            }
            else
            {
                //print "Extracting attrQuery<BR>";
                $this->attributeQueries[] = $q;
            }
        }
    }


    function hasRunQuery()
    {
        return $this->hasRunQuery;
    }
    
    // any way to make sure we don't search more than once?
    function execute()
    {
        if ($this->hasRunQuery()) return;

        $this->buildQuery();
        //print "<br>About to query using: '{$this->finalQueryString}'";
        $this->searcher->setQueryString($this->finalQueryString);
        if ($this->loadTheseColumnsFromIndex)
        {
            $this->searcher->addColumns(join(',', $this->loadTheseColumnsFromIndex));
        }
        $this->searcher->execute();
        // if we have a paginator, we need to update the dpQueryState parameter so that pagination will work on the results.
        if ($this->paginator)
        {
            $this->paginator->setAlternativeParameterValue($this->dpQueryStateParameterID, $this->getQueryState());
        }
        $this->hasRunQuery = true;
    }

    private function prepareSearcher()
    {
        if (!$this->searcher)
        {
            $this->searcher = new Java("com.dieselpoint.search.Searcher", $this->index());
        }
        return $this->searcher;
    }

    function setQueryString($string)
    {
        $this->dpqlQueryString = $string;
    }

    function setSimpleQuery($string, $mode = "any")
    {
        $this->simpleQueryString = trim($string);
        $this->simpleQueryMode = $mode;
        // change the default sort to "relevance" sorting when there's a keyword query
        if ($this->simpleQueryString and $this->paginator)
        {
            $this->paginator->setDefaultSortKeys(array(WFDieselSearch::SORT_BY_RELEVANCE));
            $this->paginator->addSortOption(WFDieselSearch::SORT_BY_RELEVANCE, 'Relevance');
        }
    }
    function getSimpleQuery()
    {
        return $this->simpleQueryString;
    }

    /**
     *  Get the appropriate object for facetGenerator.
     *
     *  If there is no query, then the FacetGenerator works off of the entire index. If there is a query, it works off the results.
     *
     *  @return object mixed Returns an Index if there is no query, or a Searcher if there is one.
     */
    function getGeneratorObject()
    {
        if ($this->hasQuery()) return $this->searcher;
        return $this->index();
    }

    function getTotalItems()
    {
        if ($this->hasQuery())
        {
            return $this->searcher->getTotalItems();
        }
        else
        {
            return $this->index()->getItemCount();
        }
    }



    function itemsIDsOnPage()
    {
        if ($this->hasQuery())
        {
            return $this->searcher->getItem_ids();
        }
        else
        {
            return array();
        }
    }

    // WFPagedData interface implementation
    // NOTE: Diesel sets are special in that if there is no query in the passed dpSearch, then there are NO ITEMS. Can only show items once searching has started.
    function itemCount()
    {
        if ($this->hasQuery())
        {
            return $this->getTotalItems();
        }
        else
        {
            return 0;
        }
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

    function isRelevanceSort()
    {
        $sortAttr = (string) $this->searcher->getSort();
        if (!$sortAttr) return true;
        if ($sortAttr == substr(WFDieselSearch::SORT_BY_RELEVANCE, 0, 1)) return true;
        return false;
    }

    /**
     *  
     *  @todo Factor out propel loading into self-contained custom callback function.
     *  @param 
     *  @return
     *  @throws
     */
    function itemsAtIndex($startIndex, $numItems, $sortKeys)
    {
        if ($this->hasQuery())
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
                if (count($sortKeys) > 1) throw( new Exception("Only 1-key sorting supported at this time.") );
                else if (count($sortKeys) == 1)
                {
                    $sortKey = $sortKeys[0];
                    $sortAttr = substr($sortKey, 1);
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
                    $itemID = (string) $rs->getString(1);   // use (string) to force conversion from java bridge string object to native PHP tpe
                    $allIDs[] = $itemID;
                    $hit = new WFDieselHit($itemID, (string) $rs->getRelevanceScore());
                    // load custom data
                    for ($i = 1; $i < count($this->loadTheseColumnsFromIndex); $i++) {
                        $hit->addData($this->loadTheseColumnsFromIndex[$i], (string) $rs->getString($i + 1));
                    }
                    $allHits[] = $hit;
                }
                
                // we support three main ways to load the matching objects; Propel-backed, custom callbacks, and none (just returns the WFDieselHit arrays -- but still can load data from index)
                if ($this->resultObjectLoaderCallbackPropelMode)
                {
                    // For propel-backed object loading, we need to use doSelect so we can preserve sorting.
                    
                    // determine primary key
                    $c = new Criteria;
                    $c->add($this->getPrimaryKeyColumnFromPropelPeer($this->resultObjectLoaderCallback), $allIDs, Criteria::IN);
                    $tableName = eval( "return {$this->resultObjectLoaderCallback}::TABLE_NAME;" );
                    foreach ($sortKeys as $sortKey) {
                        $sortAttr = substr($sortKey, 1);
                        if (substr($sortKey, 0, 1) == '-')
                        {
                            $c->addDescendingOrderByColumn($tableName . '.' . $sortAttr);
                        }
                        else
                        {
                            $c->addAscendingOrderByColumn($tableName . '.' . $sortAttr);
                        }
                    }
                    $propelObjects = call_user_func(array($this->resultObjectLoaderCallback, "doSelect"), $c);    // more efficient to grab all items in a single query
                    // map the propel objects back into the WFDieselHit's.
                    for ($i = 0; $i < count($allHits); $i++) {
                        $allHits[$i]->setObject($propelObjects[$i]);
                    }
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
        else
        {
            return array();
        }
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
}


class WFDieselHit extends WFObject
{
    protected $relevanceScore;
    protected $itemID;
    /**
     * @var mixed The object var is a placeholder for callbacks to put "objects" that map to the itemID into the WFDieselHit.
     */
    protected $object;
    /**
     * @var array An assoc-array of all data loaded from the index: "colName" => "value"
     */
    protected $data;

    function __construct($itemID, $relevanceScore = NULL, $object = NULL)
    {
        $this->itemID = $itemID;
        $this->relevanceScore = ($relevanceScore ? $relevanceScore : NULL);
        $this->object = $object;
        $this->data = array();
    }

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

    function setObject($o)
    {
        $this->object = $o;
    }
    function object()
    {
        return $this->object;
    }

    function relevanceScore()
    {
        return $this->relevanceScore;
    }

    function relevancePercent()
    {
        if ($this->relevanceScore == 0) return NULL;
        return number_format($this->relevanceScore * 100, 0) . "%";
    }

    function itemID()
    {
        return $this->itemID;
    }
}
?>
