<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

class WFDieselSearch41 implements WFFacetedSearchService
{
    // local setup
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
    }

    function nativeQueryToShowAll()
    {
        return '*';
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

    function find(WFFacetedSearch $fs)
    {
        try {
            $dpSearchRequest = new Java('com.dieselpoint.search.server.SearchRequest');
            $dpSearchRequest->setIndexNames($this->indexes);
            $dpSearchRequest->setQueryString($fs->query());
            $dpSearchRequest->setStartingItem($fs->offset());
            $dpSearchRequest->setNumberOfItemsRequested($fs->limit());
            $dpSearchRequest->setSort($fs->sortBy());

            // convert WFFacetedSearchFacet into DP objects
            $facetMap = array();
            foreach ($fs->facets() as $facet) {
                $dpFacet = new Java('com.dieselpoint.facet.StandardFacet', $facet->attribute());

                $dpFacet->setShowHitCount($facet->generateHitCounts());
                $dpFacet->setIncludeZeroes($facet->includeZeroes());
                $dpFacet->setIsTaxonomyAttr($facet->isTaxonomy());
                $dpFacet->setMaxRows($facet->maxRows());

                // deal with ranges
                $behavior = $facet->behavior();
                if ($behavior)
                {
                    switch (get_class($behavior)) {
                        case 'WFFacetedSearchFacetBehavior_NRanges':
                            $nRangeFilter = new Java('com.dieselpoint.facet.FacetFilterDefinedCount');
                            $nRangeFilter->setRangeCount($behavior->rangeCount());
                            $dpFacet->setFacetFilter($nRangeFilter);
                            break;
                        case 'WFFacetedSearchFacetBehavior_DefinedRanges':
                            $customRangeFilter = new Java('com.dieselpoint.facet.FacetFilterDefinedRanges');
                            switch ($facet->type()) {
                                case WFFacetedSearchFacet::TYPE_DATE:
                                    $customRangeFilter->setDate(true);
                                    break;
                                case WFFacetedSearchFacet::TYPE_NUMERIC:
                                    //print "customRangeFilter->setNumeric(true)\n";
                                    $customRangeFilter->setNumeric(true);
                                    break;
                            }
                            if ($facet->locale())
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
                            foreach ($behavior->ranges() as $rangeDef) {
                                list($startValue, $endValue, $label) = $rangeDef;
                                //print "customRangeFilter->addDefinedRange($label, $startValue, $endValue)\n";
                                $customRangeFilter->addDefinedRange($label, $startValue, $endValue);
                            }
                            $dpFacet->setFacetFilter($customRangeFilter);
                    }
                }

                $dpSearchRequest->addFacet($dpFacet);

                $facetMap[] = array('dpFacet' => $dpFacet, 'facet' => $facet);
            }

            // bootstrap server
            $ssClass = new JavaClass('com.dieselpoint.search.server.SearchServer');
            $ssServer = $ssClass->getServer();

            // execute search
            $dpSearchResults = $ssServer->getResults($dpSearchRequest);
            $dpResultSet = $dpSearchResults->getResultSet();

            // collect results
            $resultRows = array();
            while ($dpResultSet->next()) {
                $row = $dpResultSet->getItemResult();

                // load data from index
                $rowIndexData = array();
                foreach ($fs->attributesToLoadFromSearchIndex() as $c) {
                    $rowIndexData[$c] = (string) $dpResultSet->getString($c);
                }

                // create WFFacetedSearchResultHit instance
                $resultRows[] = new WFFacetedSearchResultHit((string) $row->getItemId(), (string) $row->getScore(), $rowIndexData);
            }

            $searchResults = new WFFacetedSearchResultSet($resultRows, $dpSearchResults->getTotalItems(), $dpSearchResults->getSearchTime());

            // populate facet results
            xdebug_break();
            foreach ($facetMap as $f) {
                $dpFacet = $f['dpFacet'];
                $facetDef = $f['facet'];

                $data = array();
                //print $dpFacet->getOutput();
                foreach ($dpFacet->getFacetValues()->getArray() as $fv) {
                    if ($fv === NULL) continue;
                    $data[] = new WFFacetedSearchFacetValue((string) $fv->getValue(), (int) $fv->getHits(), (int) $fv->getItemCount(), (boolean) $fv->isRange(), (string) $fv->getSecondValue()); // no children support yet...
                }
                $facetResultSet = new WFFacetedSearchFacetResultSet($data, $dpFacet->hasMore());
                $facetResultSet->debugOutput = (string) $dpFacet->getOutput();

                $facetDef->setResultSet($facetResultSet);
            }
        } catch (JavaException $e) {
            $this->handleJavaException($e);
        }

        return $searchResults;
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
