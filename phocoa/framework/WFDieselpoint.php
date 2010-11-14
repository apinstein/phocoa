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
        return "[{$navQ->attribute()}] {$comparator} \"" . addslashes($navQ->value()) . "\"";
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
