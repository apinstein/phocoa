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
 * @todo Add support for relevance ranks. Add a protected var that stores a map of relevance score by item id. Add relevanceScoreForItemId(). Need to use DieselResultSet to get at relevance score.
 * @todo Add support for sorting
 */
class WFDieselSearch extends WFObject implements WFPagedData
{
    const QUERY_STATE_SIMPLE_QUERY_ATTR_NAME = 'simpleQuery';
    const QUERY_STATE_DPQL_QUERY_ATTR_NAME = 'dpqlQuery';
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

    function setResultObjectLoaderCallback($callbackF)
    {
        $this->resultObjectLoaderCallback = $callbackF;
    }
    function setResultObjectLoaderCallbackWithPropelPeer($peerName, $method = 'retrieveByPKs')
    {
        // check that the callback exists -- with PHOCOA's autoload, we need to check the class explicitly
        if (!class_exists($peerName))
        {
            __autoload($peerName);
            if (!$class_exists($peerName)) throw( new Exception("Callback class '{$peerName}' does not exist.") );
        }
        if (!is_callable($peerName, $method)) throw( new Exception("Callback method '{$method}' of class '{$peerName}' does not exist.") );

        $this->resultObjectLoaderCallback = array($peerName, $method);
    }

    function setIndex($indexPath)
    {
        try {
            if (!defined('DIESELPOINT_JAR_FILES')) throw( new Exception("DIESELPOINT_JAR_FILES must be defined, usually in your webapp.conf file.") );
            java_require(DIESELPOINT_JAR_FILES);
            $Index = new JavaClass("com.dieselpoint.search.Index");
            $this->index = $Index->getInstance($indexPath);
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

        $this->prepareSearcher();
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
            $this->finalQueryString = NULL;
        }
        $this->hasBuiltQuery = true;
        return $this->finalQueryString;
    }

    function addAttributeQuery($attribute, $comparator, $query)
    {
        if (!in_array($comparator, array('EQ'))) throw( new Exception("Illegal comparator: " . $comparator) );
        $this->attributeQueries[] = "{$comparator}_{$attribute}={$query}";
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
        return urlencode(join('|',$state));
    }

    function resetQueryState()
    {
        $this->simpleQueryString = NULL;
        $this->dpqlQueryString = NULL;
        $this->attributeQueries = array();
    }

    function setQueryState($state)
    {
        $this->resetQueryState();

        $query = urldecode($state);
        $attrQueries = explode('|', $query);
        foreach ($attrQueries as $q) {
            //print "Extracting state from: $q<BR>";
            if (strncmp($q, WFDieselSearch::QUERY_STATE_SIMPLE_QUERY_ATTR_NAME, strlen(WFDieselSearch::QUERY_STATE_SIMPLE_QUERY_ATTR_NAME)) == 0)
            {
                //print "Extracting simpleQuery<BR>";
                $this->simpleQueryString = substr($q, strlen(WFDieselSearch::QUERY_STATE_SIMPLE_QUERY_ATTR_NAME) + 1);
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

        $this->prepareSearcher();
        //print "<br>About to query using: '{$this->finalQueryString}'";
        $this->searcher->setQueryString($this->finalQueryString);
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
        $this->simpleQueryString = $string;
        $this->simpleQueryMode = $mode;
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
                $this->execute();
                $allIDs = array();
                foreach ($this->searcher->getItem_ids() as $itemId) {
                    $allIDs[] = $itemId;
                }
                if (is_null($this->resultObjectLoaderCallback)) throw( new Exception("No resultObjectLoaderCallback exists. Install one with setResultObjectLoaderCallback or setResultObjectLoaderCallbackWithPropelPeer.") );
                $objectsOnPage = call_user_func($this->resultObjectLoaderCallback, $allIDs);    // more efficient to grab all items in a single query
                return $objectsOnPage;
            } catch (JavaException $e) {
                $this->handleJavaException($e);
            }
        }
        else
        {
            return array();
        }
    }
}

?>
