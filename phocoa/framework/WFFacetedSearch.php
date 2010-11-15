<?php

/**
 * @todo HitObjectLoader?
 * @todo queryDescription? breadcrumb?
 */

interface WFFacetedSearchService
{
    /**
     * Set the query do be used for the search in the Search Service's native query language.
     *
     * @param string A query in the Search Service's native query language.
     * @return object WFFacetedSearchService Fluent interface.
     * @throws
     */
    function setQuery($query);

    /**
     * Set the page size.
     *
     * @param integer Page size for the results.
     * @return object WFFacetedSearchService Fluent interface.
     * @throws
     */
    function setLimit($limit);

    /**
     * Set the offset.
     *
     * @param integer Starting item, 0-based.
     * @return object WFFacetedSearchService Fluent interface.
     * @throws
     */
    function setOffset($offset);

    /**
     * Set the sortBy field.
     * @param string An attribute to sort by.
     * @param boolean Sort ascending, default TRUE.
     */
    function setSortBy($sortBy, $ascending = true);

    /**
     * Add a facet to the list of facet data to generate for this search.
     *
     * @param object WFFacetedSearchFacet A facet to add to the search request.
     * @return object WFFacetedSearchService Fluent interface.
     * @throws
     */
    function addFacetToGenerate(WFFacetedSearchFacet $facet);

    /**
     * Set the list of attributes of data to include in the result data.
     *
     * @param array An aray of attributes names.
     * @return object WFFacetedSearchService Fluent interface.
     * @throws
     */
    function setSelectDataFromSearchIndexForAttributes($attributes);

    /**
     * Convert a navigation query to the native language.
     *
     * @param object WFFacetedSearchNavigationQuery
     * @return string The native query language version of the specified navigation query
     */
    function convertNavigationQueryToNativeQuery($navQ);

    /**
     * Delegate function to join native queries.
     *
     * Used by WFFacetedSearch to aggregate hidden and navigation queries.
     *
     * Turns array('foo=1', 'bar=2') into (foo=1 AND bar=2)
     *
     * @param array An array of queries in the native query language.
     * @param string The query join operator, one of WFFacetedSearch::QUERY_OP_AND, WFFacetedSearch::QUERY_OP_OR.
     * @return string
     */
    function joinNativeQueries($queries, $joinOperator);

    /**
     * Given a user-entered query string, wrap it as a native query so that it can be joined with the rest of the query string.
     *
     * @param string The user-entered query.
     * @return string A native query that can be joined with other native queries.
     */
    function wrapUserQuery($userQuery);

    /**
     * Runs the query.
     *
     * @return object WFFacetedSearchResultSet Fluent interface.
     * @throws
     */
    function find();

    /**
     * Get the executed query string.
     *
     * @return string The query string that was executed in the native language.
     * @throws
     */
    function query();

    /**
     * A human-readable version of the executed query.
     *
     * @return string
     * @throws
     */
    function queryDescription();

    /**
     * Returns the result set of the query, subject to offset/limit/sort, etc.
     *
     * @return object WFFacetedSearchResultSet
     * @throws
     */
    function resultSet();

    /**
     * Get the total number of items in the search database.
     * @return int Number of items.
     */
    function totalItems();
}

class WFFacetedSearchComposableQueryCollection extends WFObject implements Iterator
{
    const ANY               = 'any';
    const ALL               = 'all';

    protected $queries;
    protected $queriesById;
    protected $queryMode;

    public function __construct()
    {
        parent::__construct();
        $this->clearCollection();
    }

    public function clearCollection()
    {
        $this->queries     = array();
        $this->queriesById = array();
        $this->queryMode   = array();
    }

    public function isEmpty()
    {
        return (count($this->queries) === 0);
    }

    public function addQuery(WFFacetedSearchComposableQuery $q)
    {
        $id = $q->id();
        if (!isset($this->queriesById[$id]))
        {
            $this->queriesById[$id] = array();
            $this->queryMode[$id] = self::ANY;
        }

        $this->queries[] = $q;
        $this->queriesById[$id][] = $q;

        return $this;
    }

    /**
     * Get an array of all {@link WFFacetedSearchComposableQuery} for the given id.
     *
     * @param string id.
     * @return array An array of {@link WFFacetedSearchComposableQuery}
     */
    public function queriesForId($id)
    {
        return isset($this->queriesById[$id]) ? $this->queriesById[$id] : array();
    }

    /**
     * Set the "combination" mode for the queries that are for the given ID.
     *
     * @param string One of WFFacetedSearchComposableQueryCollection::ANY, WFFacetedSearchComposableQueryCollection::ALL
     * @param string id.
     * @return object WFFacetedSearchComposableQueryCollection For fluent interface.
     */
    public function setQueryCombinerModeForId($mode, $id)
    {
        if (!in_array($mode, array(self::ANY, self::ALL))) throw new Exception("Mode must be ANY or ALL.");
        $this->queryMode[$id] = $mode;

        return $this;
    }

    public function queryMode($id)
    {
        if (!isset($this->queryMode[$id])) throw new WFException("id {$id} does not exist.");
        return $this->queryMode[$id];
    }

    /**
     * Convert the collection of queries to the native query language.
     *
     * @param object WFFacetedSearchService
     * @return string A native query language string representing the queries specified in the WFFacetedSearchComposableQueryCollection
     * @throws
     */
    public function asNativeQueryString(WFFacetedSearchService $facetedSearchService)
    {
        $nativeQueryParts = array();
        foreach ($this->queriesById as $id => $queriesForId) {
            $nativeQueriesForId = array();
            foreach ($queriesForId as $navQ) {
                $nativeQueriesForId[] = $navQ->asNativeQueryString($facetedSearchService);
            }

            switch ($this->queryMode[$id]) {
                case self::ANY:
                    $op = WFFacetedSearch::QUERY_OP_OR;
                    break;
                case self::ALL:
                    $op = WFFacetedSearch::QUERY_OP_AND;
                    break;
                default:
                    throw new Exception("Unknown query mode {$this->queryMode[$id]}.");
            }

            $nativeQueryParts[] = (count($nativeQueriesForId) === 1 ? $nativeQueriesForId[0] : $facetedSearchService->joinNativeQueries($nativeQueriesForId, $op));
        }
        return (count($nativeQueryParts) === 1 ? $nativeQueryParts[0] : $facetedSearchService->joinNativeQueries($nativeQueryParts, WFFacetedSearch::QUERY_OP_AND));
    }

    public function __toString()
    {
        $str = NULL;
        $lastId = NULL;
        foreach ($this->queriesById as $id => $queries) {
            if ($lastId != $id)
            {
                $str .= "[id={$id} ({$this->queryMode[$id]})]\n";
            }
            $str .= "  " . join("\n  ", $queries);
            $str .= "\n";
        }
        return $str;
    }

    // Iterator
    public function rewind()
    {
        reset($this->queries);
    }
    public function current()
    {
        return current($this->queries);
    }
    public function key()
    {
        return key($this->queries);
    }
    public function next()
    {
        next($this->queries);
    }
    public function valid()
    {
        return isset($this->queries[$this->key()]);
    }
}

interface WFFacetedSearchComposableQuery
{
    public function asNativeQueryString(WFFacetedSearchService $facetedSearchService);
    public function hidden();
    public function visible();
    public function id();
}

abstract class WFFacetedSearchBaseComposableQuery extends WFObject implements WFFacetedSearchComposableQuery
{
    protected $id;
    protected $hidden;

    public function __construct($id, $hidden)
    {
        parent::__construct();
        $this->id = $id;
        $this->hidden = $hidden;
    }
    public function id()
    {
        return $this->id;
    }
    public function hidden()
    {
        return $this->hidden;
    }
    public function visible()
    {
        return !$this->hidden;
    }
}

// Used for hidden queries (with hidden=true) or "Advanced Search" (with hidden=false).
class WFFacetedSearchNativeQuery extends WFFacetedSearchBaseComposableQuery
{
    protected $query;

    public function __construct($query, $id = WFFacetedSearch::DEFAULT_NATIVE_QUERY_ID, $hidden = true)
    {
        parent::__construct($id, $hidden);
        $this->query = $query;
    }
    public function asNativeQueryString(WFFacetedSearchService $facetedSearchService)
    {
        return $this->query;
    }
    public function query()
    {
        return $this->query;
    }

    public function __toString()
    {
        return "[{$this->id}: (" . ($this->hidden ? 'hidden' : 'visible') . ")] {$this->query}";
    }
}

// Used for user-entered queries (ie a google-style keyword field)
class WFFacetedSearchUserQuery extends WFFacetedSearchBaseComposableQuery
{
    protected $query;

    public function __construct($query, $id = WFFacetedSearch::DEFAULT_USER_QUERY_ID, $hidden = false)
    {
        parent::__construct($id, $hidden);
        $this->query = $query;
    }
    public function asNativeQueryString(WFFacetedSearchService $facetedSearchService)
    {
        return $facetedSearchService->wrapUserQuery($this->query);
    }

    public function query()
    {
        return $this->query;
    }

    public function __toString()
    {
        return "[{$this->id}: (" . ($this->hidden ? 'hidden' : 'visible') . ")] {$this->query}";
    }
}

// used for drilldown navigation queries
// by default all queries for the same attribute are managed together; specify an ID if you want to group attribute queries in 2+ sets
class WFFacetedSearchNavigationQuery extends WFFacetedSearchBaseComposableQuery
{
    protected $attribute;
    protected $comparator;
    protected $value;

    const COMP_EQ = '=';
    const COMP_GT = '>';
    const COMP_GE = '>=';
    const COMP_LT = '<';
    const COMP_LE = '<=';
    const COMP_NE = '<>';

    public function __construct($attribute, $comparator, $value, $id = NULL)
    {
        if (!in_array($comparator, array(self::COMP_EQ, self::COMP_GT, self::COMP_GE, self::COMP_LT, self::COMP_LE, self::COMP_NE))) throw new WFException("Unknown comparator {$comparator}.");

        $id = ($id ? $id : $attribute);

        parent::__construct($id, false);
        $this->attribute = $attribute;
        $this->comparator = $comparator;
        $this->value = $value;
    }

    public function attribute() { return $this->attribute; }
    public function comparator() { return $this->comparator; }
    public function value() { return $this->value; }

    public function asNativeQueryString(WFFacetedSearchService $facetedSearchService)
    {
       return $facetedSearchService->convertNavigationQueryToNativeQuery($this);
    }

    public function __toString()
    {
        return "[{$this->id}] {$this->attribute} {$this->comparator} {$this->value}";
    }
}

class WFFacetedSearch extends WFObject implements WFPagedData, WFFacetedSearchService
{
    const SORT_BY_RELEVANCE                        = '-relevance';

    const QUERY_OP_AND                             = 'AND';
    const QUERY_OP_OR                              = 'OR';
    const QUERY_OP_NOT                             = 'NOT';

    const NAVIGATION_QUERY_STATE_DELIMITER_ENCODED = '-::-';
    const NAVIGATION_QUERY_STATE_DELIMITER         = '|';
    const NAVIGATION_QUERY_STATE_REGEX             = '/^([A-Za-z_-]+)(=|!=|>|>=|<|<=)(.+)$/';
    const NAVIGATION_QUERY_STATE_REGEX_LEGACY      = '/^([A-Z]{2})_([^=]+)=(.+)$/';

    const DEFAULT_NATIVE_QUERY_ID                  = '<nativeQuery>';   // by default all WFFacetedSearchNativeQuery are aggregated under this id (hidden queries)
    const DEFAULT_USER_QUERY_ID                    = '<userQuery>';     // by default all WFFacetedSearchUserQuery are aggregated under this id (user queries)

    protected $searchService                       = NULL;

    protected $queries;

    protected $objectLoaderF                       = NULL;

    public function __construct(WFFacetedSearchService $searchService)
    {
        $this->searchService = $searchService;
        $this->queries = new WFFacetedSearchComposableQueryCollection;
    }

    /**
     * A callback function that takes in a list of item ID's and returns a hash of objects to match up with the hits.
     *
     * array(objects) objectLoaderCallback(array(ids))
     *
     * @param callback
     * @return object WFFacetedSearch
     */
    public function setObjectLoaderCallback($callback)
    {
        $this->objectLoaderF = $callback;

        return $this;
    }

    /**
     * Encode an attributeQuery chunk so that it can be concatenated into queryState without screwing things up (I'm looking at you |).
     *
     * @param string AttributeQuery Value
     * @return string Encoded AttributeQuery Value
     */
    private function encodeAttributeQuery($q)
    {
        return str_replace(self::NAVIGATION_QUERY_STATE_DELIMITER, self::NAVIGATION_QUERY_STATE_DELIMITER_ENCODED, $q);
    }
    private function decodeNavigationQuery($q)
    {
        return str_replace(self::NAVIGATION_QUERY_STATE_DELIMITER_ENCODED, self::NAVIGATION_QUERY_STATE_DELIMITER, $q);
    }

    /**
     * Read the passed queryState (our internal DSL for specifying queries) and set the current search's WFFacetedSearchComposableQueryCollection to represent the passed state.
     *
     * NOTE: this appends the passed query state onto the current query state. Any previous queries (hidden or otherwise) are left intact.
     * 
     * @param string A querystate string
     * @return object WFFacetedSearch
     */
    function setNavigationQueryState($qs)
    {
        $navigationQueries = array_map(array($this, 'decodeNavigationQuery'), explode('|', $qs));
        foreach ($navigationQueries as $q) {
            $composableQuery = $this->composableQueryForQueryState($q);
            if ($composableQuery instanceof WFFacetedSearchComposableQuery)
            {
                $this->addQuery($composableQuery);
            }
        }

        // allow registered widgets to affect the "initial" state
        // WHY DOESN'T BINDINGS TAKE CARE OF THIS?
//        foreach ($this->widgets as $widget) {
//            $widget->dieselSearchRestoreState();
//        }

        return $this;
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
    function getNavigationQueryState($excludeAttribute = NULL, $newNavigationQueries = array())
    {
        $state = array();
        foreach ($this->navigationQueries() as $q) {
            $state[] = $this->queryStateForComposableQuery($q);
        }
        foreach ($newNavigationQueries as $q) {
            $state[] = $this->queryStateForComposableQuery($q);
        }
        $state = array_map(array($this, 'encodeAttributeQuery'), $state);
        //WFLog::log("done building state: " . print_r($state, true));
        $rv =  join(self::NAVIGATION_QUERY_STATE_DELIMITER, $state);
        //WFLog::log("called u/j");
        return $rv;
    }

    function selectedQueryStatesForId($id)
    {
        $selections = array();
        foreach ($this->queries->queriesForId($id) as $q) {
            if ($q->visible())
            {
                $selections[] = $this->queryStateForComposableQuery($q);
            }
        }
        return $selections;
    }
        

    public function queryStateForComposableQuery($q)
    {
        $qClass = get_class($q);
        switch ($qClass) {
            case 'WFFacetedSearchUserQuery':
                return WFFacetedSearch::DEFAULT_USER_QUERY_ID . "=" . $q->query();
            case 'WFFacetedSearchNavigationQuery':
                return "{$q->attribute()}{$q->comparator()}{$q->value()}";
            default:
                throw new Exception("{$qClass} not yet supported for queryStateForComposableQuery().");
        }
    }

    /**
     * @param string A single QueryState chunk.
     * @return object WFFacetedSearchComposableQuery A WFFacetedSearchComposableQuery or NULL if empty.
     * @throws object WFException If there was an error parsing the query state.
     */
    public function composableQueryForQueryState($q)
    {
        // normalize out empties
        $q = trim($q);
        if ($q == '') return NULL;

        if (strncmp($q, self::DEFAULT_USER_QUERY_ID, strlen(self::DEFAULT_USER_QUERY_ID)) == 0)
        {
            $userQuery = substr($q, strlen(self::DEFAULT_USER_QUERY_ID) + 1);
            return new WFFacetedSearchUserQuery($userQuery, self::DEFAULT_USER_QUERY_ID, false);
        }
        else
        {
            $matches = array();
            if (preg_match(self::NAVIGATION_QUERY_STATE_REGEX_LEGACY, $q, $matches) and count($matches) == 4)
            {
                $cmp = $matches[1];
                $attr = $matches[2];
                $value = $matches[3];

                // legacy
                switch ($cmp) {
                    case 'EQ':
                        $cmp = WFFacetedSearchNavigationQuery::COMP_EQ;
                        break;
                    case 'LE':
                        $cmp = WFFacetedSearchNavigationQuery::COMP_LE;
                        break;
                    case 'LT':
                        $cmp = WFFacetedSearchNavigationQuery::COMP_LT;
                        break;
                    case 'GT':
                        $cmp = WFFacetedSearchNavigationQuery::COMP_GT;
                        break;
                    case 'GE':
                        $cmp = WFFacetedSearchNavigationQuery::COMP_GE;
                        break;
                    case 'NE':
                        $cmp = WFFacetedSearchNavigationQuery::COMP_NE;
                        break;
                }
                return new WFFacetedSearchNavigationQuery($attr, $cmp, $value);
            }
            else if (preg_match(self::NAVIGATION_QUERY_STATE_REGEX, $q, $matches) and count($matches) == 4)
            {
                $cmp = $matches[2];
                $attr = $matches[1];
                $value = $matches[3];

                return new WFFacetedSearchNavigationQuery($attr, $cmp, $value);
            }
        }
        throw new WFException("Couldn't parse queryState: {$q}");
    }

    function navigationQueries($id = NULL)
    {
        $nqs = array();
        foreach ($this->queries as $q) {
            if ($q->hidden()) continue;
            if ($id && $id !== $q->id()) continue;

            $nqs[] = $q;
        }
        return $nqs;
    }

    function navigationQueriesForId($id)
    {
        return $this->navigationQueries($id);
    }

    function queries()
    {
        return $this->queries;
    }

    /**
     * Add a query constraint.
     * 
     * @param object WFFacetedSearchComposableQuery
     * @return object WFFacetedSearch
     */
    function addQuery($navQuery)
    {
        $this->queries->addQuery($navQuery);

        return $this;
    }

    function setQueryCombinerModeForId($mode, $id)
    {
        $this->queries->setQueryCombinerModeForId($mode, $id);

        return $this;
    }

    /**
     * Add a hidden query constraint.
     *
     * @param string Query string in native format.
     * @return object WFFacetedSearch
     * @throws
     */
    function addHiddenQuery($queryString)
    {
        $this->queries->addQuery(new WFFacetedSearchNativeQuery($queryString));

        return $this;
    }

    // WFPagedData
    function itemCount()
    {
        return $this->resultSet()->totalHitCount();
    }

    function itemsAtIndex($startIndex, $numItems, $sortKeys)
    {
        $this->searchService->setOffset($startIndex);
        $this->searchService->setLimit($numItems);

        // calculate sort key
        // remove the SORT_BY_RELEVANCE sortKey -- relevance sorting is triggered by the ABSENCE of a "setSort" call
        $sortKeysToUse = array();
        foreach ($sortKeys as $key) {
            if ($key != self::SORT_BY_RELEVANCE)
            {
                $sortKeysToUse[] = $key;
            }
        }
        $sortKeys = $sortKeysToUse;
        $this->sortBy = self::SORT_BY_RELEVANCE;
        if (count($sortKeys) > 1) throw( new WFException("Only 1-key sorting supported at this time.") );
        else if (count($sortKeys) == 1)
        {
            $sortKey = $sortKeys[0];
            $sortByAttr = substr($sortKey, 1);
            $sortDirAscending = true;
            if (substr($sortKey, 0, 1) == '-')
            {
                $sortDirAscending = false;
            }
        }
        $this->searchService->setSortBy($sortByAttr, $sortDirAscending);

        return $this->find();
    }

    // WFFacetedSearchService
    function setQuery($queryString)
    {
        // clear all other queries then add the passed query as a native query
        $this->queries->clearCollection();
        $this->queries->addQuery(new WFFacetedSearchNativeQuery($queryString));

        return $this;
    }
    function setLimit($limit)
    {
        $this->searchService->setLimit($limit); return $this;
    }
    function setOffset($offset)
    {
        $this->searchService->setOffset($offset); return $this;
    }
    function setSortBy($sortBy, $ascending = true)
    {
        $this->searchService->setSortBy($sortBy, $ascending); return $this;
    }
    function addFacetToGenerate(WFFacetedSearchFacet $facet)
    {
        $facet->setFacetedSearch($this);
        $this->searchService->addFacetToGenerate($facet); return $this;
    }
    function setSelectDataFromSearchIndexForAttributes($attributes)
    {
        $this->searchService->setSelectDataFromSearchIndexForAttributes($attributes); return $this;
    }
    function convertNavigationQueryToNativeQuery($navQ)
    {
        return $this->searchService->convertNavigationQueryToNativeQuery($navQ);
    }
    function joinNativeQueries($queries, $joinOperator)
    {
        return $this->searchService->joinNativeQueries($queries, $joinOperator);
    }
    function wrapUserQuery($userQuery)
    {
        return $this->searchService->wrapUserQuery($userQuery);
    }
    function find()
    {
        $queryString = $this->queries->asNativeQueryString($this);
        $this->searchService->setQuery($queryString);
        $results = $this->searchService->find();

        // @todo munge result set to load objects...
        if ($this->objectLoaderF)
        {
            $allIds = $results->map('itemId');
            $objectsForIds = call_user_func($this->objectLoaderF, $allIds);
            $objectsById = WFArray::arrayWithArray($objectsForIds)->hash('mlsPropertyId');
            $pruneIds = array();
            foreach ($results as $index => $hit) {
                $itemId = $hit->itemId();
                if (isset($objectsById[$itemId]))
                {
                    $hit->setObject($objectsById[$itemId]);
                }
                else
                {
                    $pruneIds[] = $index;
                }
            }
            foreach ($pruneIds as $pruneId) {
                $results->offsetUnset($pruneId);
            }
        }

        return $results;
    }
    function query()
    {
        return $this->searchService->query();
    }
    function queryDescription()
    {
        return $this->searchService->queryDescription();
    }
    function resultSet()
    {
        return $this->searchService->resultSet();
    }
    function totalItems()
    {
        return $this->searchService->totalItems();
    }
}

/**
 * A light wrapper containing all "hits" for the query.
 *
 * This is an array of {@link WFFacetedSearchResultHit} with some additional metadata accessors.
 */
class WFFacetedSearchResultSet extends WFArray
{
    protected $searchTime;
    protected $totalHitCount;

    public function __construct($data, $totalHitCount, $searchTime)
    {
        parent::__construct($data);
        $this->totalHitCount = $totalHitCount;
        $this->searchTime = $searchTime;
    }

    public function totalHitCount()
    {
        return $this->totalHitCount;
    }

    public function searchTime()
    {
        return $this->searchTime;
    }
}

/**
 *  The WFFacetedSearchResultHit object represents a result row from a WFFacetedSearch.
 * 
 *  The WFFacetedSearchResultHit object encapsulates the result metadata (score, hit highlighting, etc), and custom loaded callback objects, and data loaded from the index columns directly.
 */
class WFFacetedSearchResultHit extends WFObject
{
    /**
     * @var integer The relevance of the hit, on a scale of 0-100.
     */
    protected $score;
    /**
     * @var mixed The unique itemId of the hit.
     */
    protected $itemId;
    /**
     * @var mixed The object var is a placeholder for callbacks to put "objects" that map to the itemId into the WFFacetedSearchResultHit.
     */
    protected $object;
    /**
     * @var object WFArray A KVC wrapper so we can bind to the WFFacetedSearchResultHit.
     */
    protected $data;

    function __construct($itemId, $score = NULL, $data = NULL, $object = NULL)
    {
        $this->itemId = $itemId;
        $this->score = ($score ? $score : NULL);
        $this->data = new WFArray($data ? $data : array());
        $this->object = $object;
    }

    function __toString()
    {
        return "Item ID: {$this->itemId}, Score: {$this->score}, Data: " . json_encode($this->data) . ", Object: " . get_class($this->object);
    }

    /**
     *  Set the custom object for this hit. 
     *
     *  This will be set by the {@link WFFacetedSearch::setObjectLoaderCallback() custom object loader}, if one is supplied.
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
    function score()
    {
        return $this->score;
    }

    /**
     *  Get the relevance score for this hit, in % format.
     *
     *  @return string Score as percentage match (100% is highest possible)
     */
    function scorePercent()
    {
        if ($this->score == 0) return NULL;
        return number_format($this->score * 100, 0) . "%";
    }

    /**
     *  Get the unique itemId for this hit.
     *
     *  @return mixed THe unique id.
     */
    function itemId()
    {
        return $this->itemId;
    }
}

class WFFacetedSearchFacetBehavior extends WFObject {}

class WFFacetedSearchFacetBehavior_NRanges extends WFFacetedSearchFacetBehavior
{
    protected $rangeCount;

    public function __construct($rangeCount)
    {
        $this->rangeCount = $rangeCount;
    }

    public function rangeCount()
    {
        return $this->rangeCount;
    }
}

class WFFacetedSearchFacetBehavior_DefinedRanges extends WFFacetedSearchFacetBehavior
{
    protected $ranges;

    public function __construct($ranges = array())
    {
        $this->ranges = array();
        if (!empty($ranges))
        {
            foreach ($ranges as $r) {
                call_user_func_array(array($this, 'addRange'), $r);
            }
        }
    }

    public function ranges()
    {
        return $this->ranges;
    }

    /**
     * @param mixed The start value (inclusive).
     * @param mixed The end value (exclusive).
     */
    public function addRange($startVal, $endVal, $label = NULL)
    {
        if ($label === NULL)
        {
            $label = "{$startVal} - {$endVal}";
        }
        $this->ranges[] = array($startVal, $endVal, $label);
        
        return $this;
    }
}

/**
 * WFFacetedSearchFacet represents a defition of a facet that should be generated from the search results.
 *
 * WFFacetedSearchFacet allows specification on facet generation parameters, and after search will contain a populated WFFacetedSearchFacetResultSet.
 */
class WFFacetedSearchFacet extends WFObject
{
    const MAX_ROWS_UNLIMITED = -1;

    const TYPE_AUTO          = 'auto';
    const TYPE_STRING        = 'string';
    const TYPE_DATE          = 'date';
    const TYPE_NUMERIC       = 'numeric';

    protected $id;
    protected $attribute;
    protected $isTaxonomy              = false;
    protected $taxonomyPath            = '';
    protected $maxRows                 = self::MAX_ROWS_UNLIMITED;
    protected $sortByFrequency         = true;
    protected $generateHitCounts       = true;
    protected $enableApproximateCounts = false;
    protected $includeZeroes           = false;
    protected $type                    = self::TYPE_AUTO;
    protected $locale                  = NULL;
    protected $formatter               = NULL;

    protected $behavior;

    protected $resultSet;
    protected $facetedSearch;

    public function __construct($attribute, $options = array())
    {
        parent::__construct();
        $this->attribute = $this->id = $attribute;

        foreach (array_intersect(array('id', 'isTaxonomy', 'taxonmomyPath', 'maxRows', 'sortByFrequency', 'generateHitCounts', 'enableApproximateCounts', 'includeZeroes', 'type', 'locale', 'behavior', 'formatter'), array_keys($options)) as $optKey) {
            $setter = "set{$optKey}";
            $this->$setter($options[$optKey]);
        }
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function setFormatter(WFFormatter $f)
    {
        $this->formatter = $f;
        return $this;
    }

    public function setGenerateHitCounts($generateHitCounts)
    {
        $this->generateHitCounts = $generateHitCounts;
        return $this;
    }

    public function setFacetedSearch($fs)
    {
        $this->facetedSearch = $fs;
        return $this;
    }

    public function setBehavior($b)
    {
        $this->behavior = $b;
        return $this;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    public function setEnableApproximateCounts($enable)
    {
        $this->enableApproximateCounts = $enable;
        return $this;
    }

    /**
     * Set whether this attribute should be considered a taxonomy attribute and made hierarchical.
     * @return
     */
    public function setIsTaxonomy($isTaxonomy)
    {
        $this->isTaxonomy = $isTaxonomy;
        return $this;
    }

    /**
     * Set the maximum number of options that should appear in the facet.
     * @return
     */
    public function setMaxRows($maxRows)
    {
        $this->maxRows = $maxRows;
        return $this;
    }

    /**
     * Set this value true to order the options with the most common ones first.
     * @return
     */
    public function setSortByFrequency($sortByFrequency)
    {
        $this->sortByFrequency = $sortByFrequency;
        return $this;
    }

    public function setIncludeZeroes($include)
    {
        $this->includeZeroes = $include;
        return $this;
    }

    public function setResultSet(WFFacetedSearchFacetResultSet $rs)
    {
        $this->resultSet = $rs;
        return $this;
    }

    public function formatter()
    {
        return $this->formatter;
    }

    public function generateHitCounts()
    {
        return $this->generateHitCounts;
    }

    public function includeZeroes()
    {
        return $this->includeZeroes;
    }

    /**
     * Return the id of the attribute that this facet is built on.
     * @return
     */
    public function attribute()
    {
        return $this->attribute;
    }

    /**
     * Returns true if this facet was built on an attribute with a datatype of Taxonomy.
     * @return
     */
    public function isTaxonomy()
    {
        return $this->isTaxonomy;
    }

    /**
     * Return the value set with setMaxRows(int)
     * @return
     */
    public function maxRows()
    {
        return $this->maxRows;
    }

    /**
     * Returns true if we're sorting the facet values by their hit count.
     * @return
     */
    public function sortByFrequency()
    {
        return $this->sortByFrequency;
    }

    public function behavior()
    {
        return $this->behavior;
    }

    public function type()
    {
        return $this->type;
    }

    public function locale()
    {
        return $this->locale;
    }

    public function facetSearchOptions($includeAlternateFacets = false)
    {
        $facetSearchOptions = array(
                'currentSelection'       => $this->facetedSearch->selectedQueryStatesForId($this->id),
                //'allowMultipleSelection' => $this->options[self::OPT_ALLOW_MULTIPLE_SELECTION],
                'queryBase'              => $this->facetedSearch->getNavigationQueryState($this->id),
                'hasMoreFacets'          => $this->resultSet->hasMore(),
                'facets'                 => array(),
        );

        // actual facet data
        if (count($facetSearchOptions['currentSelection']) == 0)
        {
            foreach ($this->resultSet as $fv) {
                $facetOptions = array(
                        'label'          => $fv->label(array(
                                                                WFFacetedSearchFacetValue::OPT_LABEL_FORMATTER => $this->formatter,
                                                                WFFacetedSearchFacetValue::OPT_SHOW_HIT_COUNT => $this->generateHitCounts,
                                                            )
                                                      ),
                        'itemCount'      => $fv->hitCount(), // rename
                        'attributeQuery' => array(),
                );
                // build attributeQueries
                // @todo in the future this needs to account for range queries, both open-ended and normal, and different comparators as well
                if ($fv->value())
                {
                    $facetOptions['attributeQuery'][] = $this->facetedSearch->queryStateForComposableQuery(new WFFacetedSearchNavigationQuery($this->attribute, WFFacetedSearchNavigationQuery::COMP_EQ, $fv->value()));
                }
                // merge attributeQueries
                $facetOptions['attributeQuery'] = join(WFFacetedSearch::NAVIGATION_QUERY_STATE_DELIMITER, $facetOptions['attributeQuery']);
                $facetSearchOptions['facets'][] = $facetOptions;
            }
        }
        else
        {
            if ($includeAlternateFacets)
            {
                // @todo DOES NOT WORK ON DP 4.1
                $facetSearchOptions['facets'] = 'coming soon';
                // set up a new search w/o this attribute and just get facets for this one.
                $subDpSearch = new WFDieselSearch;
                $subDpSearch->setIndex($this->dieselSearch->index());
                $subDpSearchHelper = new WFDieselSearchHelper;
                $subDpSearchHelper->setDieselSearch($subDpSearch);
                $subDpSearchHelper->setQueryState($facetSearchOptions['queryBase']);
                $subFacet = new WFDieselSearch_FacetedAttribute($this->attribute, $subDpSearchHelper, $this->options);
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
}

/**
 * A light wrapper containing all "values" for a particular facet.
 *
 * This is an array of {@link WFFacetedSearchFacetValue} with some additional metadata accessors.
 */
class WFFacetedSearchFacetResultSet extends WFArray
{
    protected $hasMore;

    public function __construct($data, $hasMore)
    {
        parent::__construct($data);
        $this->hasMore = $hasMore;
    }

    public function hasMore()
    {
        return $this->hasMore;
    }
}

/**
 * Holds information about a single "row" of a facet, including metadata and the values.
 *
 * For instance, an "Author" facet's WFFacetedSearchFacetValue would be "Mark Twain".
 */
class WFFacetedSearchFacetValue extends WFObject
{
    protected $value;
    protected $secondValue;
    protected $children;
    protected $isRange;
    protected $hitCount;
    protected $totalHitCount;

    public function __construct($value, $hitCount, $totalHitCount, $isRange = false, $secondValue = NULL, $children = NULL)
    {
        parent::__construct();
        $this->value = $value;
        $this->isRange = $isRange;
        $this->hitCount = $hitCount;
        $this->totalHitCount = $totalHitCount;
        $this->secondValue = $secondValue;
        $this->children = $children;
    }

    /**
     * Get the value in the index for the attribute.
     * @return string
     */
    public function value() 
    {
        return $this->value;
    }

    /**
     * Does this facet value represent a range of values?
     * @return boolean
     */
    public function isRange() 
    {
        return $this->isRange;
    }

    /**
     * Get the second value when this object represents a range.
     * @return string
     */
    public function secondValue() 
    {
        return $this->secondValue;
    }

    /**
     * Return true if this value has child values.
     * @return boolean
     */
    public function hasChildren() 
    {
        return ($this->children !== NULL);
    }

    /**
     * A FacetValue can have children to represent hierarchies.
     * @return array An array of WFFacetedSearchFacetValue
     */
    public function children() 
    {
        return $this->children();
    }

    /**
     * Get the number of items in the index or search result this value or range of values corresponds to.
     * @return int
     */
    public function hitCount() 
    {
        return $this->hitCount;
    }

    /**
     * Returns the total number of items in the entire index that have this value.
     * @return int
     */
    public function totalHitCount() 
    {
        return $this->totalHitCount;
    }

    const OPT_LABEL_FORMATTER   = 'formatter';
    const OPT_RANGE_SEPARATOR   = 'rangeSeparator';
    const OPT_SHOW_HIT_COUNT    = 'showHitCount';
    public function label($options = array())
    {
        $defaultOptions = array(
            self::OPT_LABEL_FORMATTER   => NULL,
            self::OPT_RANGE_SEPARATOR   => ' - ',
            self::OPT_SHOW_HIT_COUNT    => false,
        );
        $options = array_merge($defaultOptions, $options);


        $label = "";
        if ($options[self::OPT_LABEL_FORMATTER])
        {

            $label .= $options[self::OPT_LABEL_FORMATTER]->stringForValue($this->value);
        }
        else
        {
            $label .= $this->value;
        }
        if ($this->isRange())
        {
            $label .= $options[self::OPT_RANGE_SEPARATOR];
            if ($options[self::OPT_LABEL_FORMATTER])
            {
                $label .= $options[self::OPT_LABEL_FORMATTER]->stringForValue($this->secondValue);
            }
            else
            {
                $label .= $this->secondValue;
            }
        }
        if ($options[self::OPT_SHOW_HIT_COUNT] && $this->hitCount() > 0)
        {
            $label .= " ({$this->hitCount()})";
        }
        return $label;
    }
}
