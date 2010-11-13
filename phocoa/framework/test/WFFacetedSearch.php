<?php

/*
 * @package test
 */
require_once(getenv('PHOCOA_PROJECT_CONF'));
error_reporting(E_ALL);

class MockFacetedSearchService implements WFFacetedSearchService
{
    protected $query;

    function setQuery($query) { $this->query = $query; }
    function setLimit($limit) {}
    function setOffset($offset) {}
    function setSortBy($sortBy, $ascending = true) {}
    function addFacetToGenerate(WFFacetedSearchFacet $facet) {}
    function setSelectDataFromSearchIndexForAttributes($attributes) {}
    function find() {}
    function resultSet()
    {
        $resultRows = array(); 
        $resultRows[] = new WFFacetedSearchResultHit(1, 1);
        $resultRows[] = new WFFacetedSearchResultHit(2, 1);
        $resultRows[] = new WFFacetedSearchResultHit(3, 1);
        $resultRows[] = new WFFacetedSearchResultHit(4, 1);
        $rs = new WFFacetedSearchResultSet($resultRows, 100, 1);
        return $rs;
    }
    function totalItems() { return 1000; }
    function convertNavigationQueryToNativeQuery($navQ) { return "{$navQ->attribute()}{$navQ->comparator()}{$navQ->value()}"; }
    function query() { return $this->query; }
    function queryDescription() { return "QUERY DESCRIPTION"; }
    function joinNativeQueries($queries, $joinOperator)
    {
        return "(" . join(" {$joinOperator} ", $queries) . ")";
    }
    function wrapUserQuery($userQuery) { return "keywords: {$userQuery}"; }
}

class WFFacetedSearchTest extends PHPUnit_Framework_TestCase
{
    protected $fss;

    function setup()
    {
        $this->fss = new MockFacetedSearchService;
        $this->fs = new WFFacetedSearch($this->fss);
    }

    function tearDown()
    {
        $this->fss = NULL;
    }

    function testSetQueryThenFindExecutesSetQuery()
    {
        $query = "foo=bar";
        $this->fs->setQuery($query)
                 ->find();
        $this->assertEquals($query, $this->fss->query());
    }

    /**
     * @dataProvider buildQueryDataProvider
     */
    function testBuildQuery($queries, $queryModes, $expectedQueryString, $message = NULL)
    {
        foreach ($queries as $q) {
            $this->fs->addQuery($q);
        }
        foreach ($queryModes as $id => $mode) {
            $this->fs->setQueryCombinerModeForId($mode, $id);
        }
        $this->fs->find();
        $this->assertEquals($expectedQueryString, $this->fss->query(), $message);
    }
    function buildQueryDataProvider()
    {
        return array(
            array(
                array(
                    new WFFacetedSearchNativeQuery('foo=bar'),
                    new WFFacetedSearchNativeQuery('boo=baz')
                ),
                array(),
                "(foo=bar OR boo=baz)"
            ),
            array(
                array(
                    new WFFacetedSearchNativeQuery('foo=bar'),
                    new WFFacetedSearchNativeQuery('boo=baz')
                ),
                array(WFFacetedSearch::DEFAULT_NATIVE_QUERY_ID => WFFacetedSearchComposableQueryCollection::ALL),
                "(foo=bar AND boo=baz)"
            ),
            array(
                array(
                    new WFFacetedSearchNavigationQuery('foo', WFFacetedSearchNavigationQuery::COMP_EQ, 'bar'),
                    new WFFacetedSearchNavigationQuery('foo', WFFacetedSearchNavigationQuery::COMP_EQ, 'baz'),
                ),
                array(),
                "(foo=bar OR foo=baz)"
            ),
            array(
                array(
                    new WFFacetedSearchNavigationQuery('foo', WFFacetedSearchNavigationQuery::COMP_EQ, 'bar'),
                    new WFFacetedSearchNavigationQuery('foo', WFFacetedSearchNavigationQuery::COMP_EQ, 'baz'),
                    new WFFacetedSearchNavigationQuery('bar', WFFacetedSearchNavigationQuery::COMP_EQ, 'foo'),
                    new WFFacetedSearchNavigationQuery('bar', WFFacetedSearchNavigationQuery::COMP_EQ, 'faz'),
                ),
                array(
                    'foo' => WFFacetedSearchComposableQueryCollection::ALL
                ),
                "((foo=bar AND foo=baz) AND (bar=foo OR bar=faz))"
            ),
        );
    }
}
