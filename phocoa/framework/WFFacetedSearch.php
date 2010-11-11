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
     * @see buildQuery
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

class WFFacetedSearchNavigationQueryCollection extends WFObject implements Iterator
{
    const ANY               = 'any';
    const ALL               = 'all';

    protected $navigationQueries;
    protected $navigationQueryMode;

    protected $iteratorState;

    public function __construct()
    {
        parent::__construct();
        $this->clearCollection();
    }

    public function isEmpty()
    {
        return (count($this->navigationQueries) === 0);
    }

    public function clearCollection()
    {
        $this->navigationQueries = array();
        $this->navigationQueryMode = array();
    }

    public function addNavigationQuery($attribute, $comparator = NULL, $value = NULL)
    {
        if ($attribute instanceof WFFacetedSearchNavigationQuery && $comparator === NULL && $value === NULL)
        {
            $nq = $attribute;
            $attribute = $nq->attribute();
        }
        else
        {
            $nq = new WFFacetedSearchNavigationQuery($attribute, $comparator, $value);
        }

        if (!isset($this->navigationQueries[$attribute]))
        {
            $this->navigationQueries[$attribute] = array();
            $this->navigationQueryMode[$attribute] = self::ANY;
            $this->rewind();
        }

        $this->navigationQueries[$attribute][] = $nq;

        return $this;
    }

    /**
     * Get an array of all "selected" attributeQueries for the given attribute.
     * @param string Attribute.
     * @return array An array of {@link WFFacetedSearchNavigationQuery}
     */
    function navigationQueriesForAttribute($attribute)
    {
        return isset($this->navigationQueries[$attribute]) ? $this->navigationQueries[$attribute] : array();
    }

    public function setNavigationQueryMode($attribute, $mode)
    {
        if (!in_array($mode, array(self::ANY, self::ALL))) throw new Exception("Mode must be ANY or ALL.");
        $this->navigationQueryMode[$attribute] = $mode;

        return $this;
    }

    public function navigationQueryMode($attribute)
    {
        if (!isset($this->navigationQueryMode[$attribute])) throw new WFException("Attribute {$attribute} does not exist.");
        return $this->navigationQueryMode[$attribute];
    }

    /**
     * Convert the collection of attribute queries to the native query language.
     *
     * @param object WFFacetedSearchService
     * @return string A native query language string representing the queries specified in the WFFacetedSearchNavigationQueryCollection
     * @throws
     */
    public function asNativeQueryString(WFFacetedSearchService $facetedSearchService)
    {
        $nativeQueryParts = array();
        foreach ($this->navigationQueries as $attribute => $attributeQueries) {
            $nativeQueriesForAttribute = array();
            foreach ($attributeQueries as $navQ) {
                $nativeQueriesForAttribute[] = $navQ->asNativeQueryString($facetedSearchService);
            }

            switch ($this->navigationQueryMode[$attribute]) {
                case self::ANY:
                    $op = WFFacetedSearch::QUERY_OP_OR;
                    break;
                case self::ALL:
                    $op = WFFacetedSearch::QUERY_OP_AND;
                    break;
                default:
                    throw new Exception("Unknown query mode {$this->navigationQueryMode[$attribute]}.");
            }

            $nativeQueryParts[] = $facetedSearchService->joinNativeQueries($nativeQueriesForAttribute, $op);
        }
        return $facetedSearchService->joinNativeQueries($nativeQueryParts, WFFacetedSearch::QUERY_OP_AND);
    }

    // Iterator
    public function rewind()
    {
        $this->iteratorState = array_keys($this->navigationQueries);
    }
    public function current()
    {
        return $this->navigationQueries[current($this->iteratorState)];
    }
    public function key()
    {
        return current($this->iteratorState);
    }
    public function next()
    {
        next($this->iteratorState);
    }
    public function valid()
    {
        return isset($this->navigationQueries[$this->key()]);
    }
}

class WFFacetedSearchNavigationQuery extends WFObject
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

    public function __construct($attribute, $comparator, $value)
    {
        if (!in_array($comparator, array(self::COMP_EQ, self::COMP_GT, self::COMP_GE, self::COMP_LT, self::COMP_LE, self::COMP_NE))) throw new WFException("Unknown comparator {$comparator}.");

        parent::__construct();
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
}

class WFFacetedSearch extends WFObject implements WFPagedData, WFFacetedSearchService
{
    const SORT_BY_RELEVANCE                                            = '-relevance';

    const QUERY_OP_AND                                                 = 'AND';
    const QUERY_OP_OR                                                  = 'OR';
    const QUERY_OP_NOT                                                 = 'NOT';

    const NAVIGATION_QUERY_STATE_USER_QUERY                            = 'userQuery';
    const NAVIGATION_QUERY_STATE_DELIMITER_ENCODED                     = '-::-';
    const NAVIGATION_QUERY_STATE_DELIMITER                             = '|';
    const NAVIGATION_QUERY_STATE_REGEX                                 = '/^([A-Z]{2})_([^=]+)=(.+)$/';

    protected $searchService                                           = NULL;

    protected $hiddenQueries                                           = array();
    protected $userQuery                                               = NULL;
    protected $navigationQueries;

    protected $objectLoaderF                                           = NULL;

    public function __construct(WFFacetedSearchService $searchService)
    {
        $this->searchService = $searchService;
        $this->navigationQueries = new WFFacetedSearchNavigationQueryCollection;
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
     * Read the passed queryState (our internal DSL for specifying queries) and set the current search's WFFacetedSearchNavigationQueryCollection to represent the passed state.
     * 
     * @param string A querystate string
     * @return object WFFacetedSearch
     */
    function setNavigationQueryState($qs)
    {
        $this->navigationQueries->clearCollection();

        $navigationQueries = array_map(array($this, 'decodeNavigationQuery'), explode('|', $qs));
        foreach ($navigationQueries as $q) {
            // normalize out empties
            $q = trim($q);
            if ($q == '') continue;

            if (strncmp($q, self::NAVIGATION_QUERY_STATE_USER_QUERY, strlen(self::NAVIGATION_QUERY_STATE_USER_QUERY)) == 0)
            {
                $this->setUserQuery(substr($q, strlen(self::NAVIGATION_QUERY_STATE_USER_QUERY) + 1));
            }
            else
            {
                $matches = array();
                if (preg_match(self::NAVIGATION_QUERY_STATE_REGEX, $q, $matches) and count($matches) == 4)
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

                    $this->addNavigationQuery(new WFFacetedSearchNavigationQuery($attr, $cmp, $value));
                }
                else
                {
                    throw new WFException("Couldn't parse navigationQuery in queryState: {$q}");
                    //print "Warning: couldn't parse attribute query: $qInfo.";
                }
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
        if ($this->userQuery and $excludeAttribute != self::NAVIGATION_QUERY_STATE_USER_QUERY)
        {
            $state[] = self::NAVIGATION_QUERY_STATE_USER_QUERY . '=' . $this->userQuery;
        }
        foreach ($this->navigationQueries as $attr => $queries) {
            foreach ($queries as $q) {
                $state[] = "{$q->attribute()}{$q->comparator()}{$q->value()}";
            }
        }
        foreach ($newNavigationQueries as $q) {
            $state[] = "{$q->attribute()}{$q->comparator()}{$q->value()}";
        }
        $state = array_map(array($this, 'encodeAttributeQuery'), $state);
        //WFLog::log("done building state: " . print_r($state, true));
        $rv =  join(self::NAVIGATION_QUERY_STATE_DELIMITER, $state);
        //WFLog::log("called u/j");
        return $rv;
    }

    /**
     *  Get the queryState for the current instance, WITHOUT the simpleQuery filter.
     *
     *  The result is plain-text; it will need to be urlencode() as necessary.
     *
     *  This is just a shortcut for getNavigationQueryState() configured to exclude the user query.
     *
     *  @return string
     */
    function getNavigationQueryStateWithoutUserQuery()
    {
        return $this->getNavigationQueryState(self::NAVIGATION_QUERY_STATE_USER_QUERY);
    }

    function navigationQueries()
    {
        return $this->navigationQueries;
    }

    /**
     * Add a navigation query constraint.
     * 
     * @param object WFFacetedSearchNavigationQuery
     * @return object WFFacetedSearch
     */
    function addNavigationQuery($navQuery)
    {
        $this->navigationQueries->addNavigationQuery($navQuery);

        return $this;
    }

    function setNavigationQueryMode($attribute, $mode)
    {
        $this->navigationQueries->setNavigationQueryMode($attribute, $mode);

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
        $this->hiddenQueries[] = $queryString;

        return $this;
    }

    /**
     * Set the UserQuery for the search.
     *
     * The UserQuery is a "basic search" language that is intended as a full-text-search input for users.
     *
     * It is typically presented in the interface as a KEYWORD search or Google-like search box.
     *
     * @param string The user-entered query string.
     * @return object WFFacetedSearch
     */
    function setUserQuery($userQuery)
    {
        $this->userQuery = $userQuery;

        return $this;
    }

    function userQuery()
    {
        return $this->userQuery;
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

    private function buildQuery()
    {
        $allQueries = $this->hiddenQueries;
        if (!$this->navigationQueries->isEmpty())
        {
            $allQueries[] = $this->navigationQueries->asNativeQueryString($this);
        }
        if ($this->userQuery)
        {
            $allQueries[] = $this->wrapUserQuery($this->userQuery);
        }

        return $this->joinNativeQueries($allQueries, WFFacetedSearch::QUERY_OP_AND);
    }

    // WFFacetedSearchService
    function setQuery($query)
    {
        $this->searchService->setQuery($query); return $this;
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
        $queryString = $this->buildQuery();
        $this->searchService->setQuery($queryString);
        $results = $this->searchService->find();

        // @todo munge result set to load objects...
        if ($this->objectLoaderF)
        {
            $allIds = $results->map('itemId');
            $objectsForIds = call_user_func($this->objectLoaderF, $allIds);
            $objectsById = WFArray::arrayWithArray($objectsForIds)->hash('mlsPropertyId');
            foreach ($results as $index => $hit) {
                $itemId = $hit->itemId();
                if (isset($objectsById[$itemId]))
                {
                    $hit->setObject($objectsById[$itemId]);
                }
                else
                {
                    $results->offsetUnset($index);
                }
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

class WFFacetedSearchFacetValueCollator
{
}

class WFFacetedSearchFacetValueRangeDefinition
{
    const OPT_START_INCLUSIVE    = 'startInclusive';
    const OPT_END_INCLUSIVE      = 'endInclusive';
    const OPT_TYPE               = 'type';
    const OPT_LOCALE             = 'locale';

    const VALUE_OPEN_ENDED_RANGE = NULL;

    const TYPE_STRING            = 'string';
    const TYPE_DATE              = 'date';
    const TYPE_NUMERIC           = 'numeric';

    protected $startValue;
    protected $startInclusive;
    protected $endValue;
    protected $endInclusive;
    protected $type;
    protected $locale;

    public function __construct($startValue, $endValue, $options = array())
    {
        $defaultOptions = array(
                self::OPT_START_INCLUSIVE => true,
                self::OPT_END_INCLUSIVE   => true,
                self::OPT_TYPE            => self::TYPE_STRING,
                self::OPT_LOCALE          => NULL,
        );
        $options = array_merge($defaultOptions, $options);

        $this->startValue = $startValue;
        $this->endValue = $endValue;

        $this->startInclusive = $options[self::OPT_START_INCLUSIVE];
        $this->endInclusive = $options[self::OPT_END_INCLUSIVE];
        $this->type = $options[self::OPT_TYPE];
        $this->locale = $options[self::OPT_LOCALE];
    }

    public function startValue() { return $this->startValue; }
    public function endValue() { return $this->endValue; }
    public function startInclusiveValue() { return $this->startInclusiveValue; }
    public function endInclusiveValue() { return $this->endInclusiveValue; }
    public function type() { return $this->type; }
    public function locale() { return $this->locale; }
}

/**
 * WFFacetedSearchFacet represents a defition of a facet that should be generated from the search results.
 *
 * WFFacetedSearchFacet allows specification on facet generation parameters, and after search will contain a populated WFFacetedSearchFacetResultSet.
 */
class WFFacetedSearchFacet extends WFObject
{
    const MAX_ROWS_UNLIMITED = -1;

    protected $attributeId;
    protected $isTaxonomy              = false;
    protected $taxonomyPath            = '';
    protected $maxRows                 = self::MAX_ROWS_UNLIMITED;
    protected $sortByFrequency         = true;
    protected $generateHitCounts       = true;
    protected $enableApproximateCounts = false;
    protected $includeZeroes           = false;

    protected $ranges;

    protected $resultSet;
    protected $facetedSearch;

    public function __construct($attributeId, $options = array())
    {
        parent::__construct();
        $this->attributeId = $attributeId;

        foreach (array_intersect(array('isTaxonomy', 'taxonmomyPath', 'maxRows', 'sortByFrequency', 'generateHitCounts', 'enableApproximateCounts', 'includeZeroes', 'ranges'), array_keys($options)) as $optKey) {
            $setter = "set{$optKey}";
            $this->$setter($options[$optKey]);
        }
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

    public function setEnableApproximateCounts($enable)
    {
        $this->enableApproximateCounts = $enable;
        return $this;
    }

    public function setRanges($in)
    {
        if (is_numeric($in))
        {
            $this->ranges = (int) $in;
        }
        else if (is_array($in))
        {
            $this->ranges = $in;
        }
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
    public function attributeId()
    {
        return $this->attributeId;
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

    /**
     * Get the range setup for this facet.
     *
     * @return mixed INTEGER - Create N equal-sized ranges.
     *               {@link WFFacetedSearchFacetValueRangeDefinition} - Specified ranges.
     */
    public function ranges()
    {
        return $this->ranges;
    }

    public function facetSearchOptions($includeAlternateFacets = false)
    {
        $facetSearchOptions = array(
                'currentSelection'       => $this->facetedSearch->navigationQueries()->navigationQueriesForAttribute($this->attributeId),
                //'allowMultipleSelection' => $this->options[self::OPT_ALLOW_MULTIPLE_SELECTION],
                'queryBase'              => $this->facetedSearch->getNavigationQueryState($this->attributeId),
                'hasMoreFacets'          => $this->resultSet->hasMore(),
                'facets'                 => array(),
        );

        // actual facet data
        if (count($facetSearchOptions['currentSelection']) == 0)
        {
            foreach ($this->resultSet as $fv) {
                $facetOptions = array(
                        'label'          => $fv->label(),         // inject formatter
                        'itemCount'      => $fv->hitCount(), // rename
                        'attributeQuery' => array(),
                );
                // build attributeQueries
                // @todo in the future this needs to account for range queries, both open-ended and normal, and different comparators as well
                if ($fv->value())
                {
                    $facetOptions['attributeQuery'][] = "EQ_{$this->attributeId}={$fv->value()}";
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
            $label .= $options[self::OPT_LABEL_FORMATTER]->formattedValue($this->value);
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
                $label .= $options[self::OPT_LABEL_FORMATTER]->formattedValue($this->secondValue);
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
