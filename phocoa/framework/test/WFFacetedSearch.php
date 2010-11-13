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
    function wrapUserQuery($userQuery) { return "<userquery>{$userQuery}</userquery>"; }
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
            array(
                array(
                    new WFFacetedSearchUserQuery('foo bar'),
                ),
                array(),
                "<userquery>foo bar</userquery>"
            ),
        );
    }

    /**
     * @dataProvider navigationStateDataProvider
     */
    function testNavigationQueryState($queryState, $expectedQueryString, $expectedQueriesByAttribute, $message = NULL)
    {
        $this->fs->setNavigationQueryState($queryState)
                 ->find();
        $this->assertEquals($expectedQueryString, $this->fss->query(), $message);
        foreach ($expectedQueriesByAttribute as $attr => $queries) {
            $this->assertEquals($queries, $this->fs->navigationQueriesForId($attr));
        }
    }
    function navigationStateDataProvider()
    {
        return array(
            array(
                    'EQ_foo=bar',
                    'foo=bar',
                    array('foo' => array(new WFFacetedSearchNavigationQuery('foo', WFFacetedSearchNavigationQuery::COMP_EQ, 'bar'))),
                    "Single navigation query."
                 ),
            array(
                    'EQ_foo=bar|EQ_foo=baz',
                    '(foo=bar OR foo=baz)',
                    array('foo' => array(
                                            new WFFacetedSearchNavigationQuery('foo', WFFacetedSearchNavigationQuery::COMP_EQ, 'bar'),
                                            new WFFacetedSearchNavigationQuery('foo', WFFacetedSearchNavigationQuery::COMP_EQ, 'baz'),
                    )),
                    "Mulitiple queries for same attribute."
                 ),
            array(
                    'EQ_foo=bar|EQ_foo=baz|EQ_bar=foo|EQ_bar=baz',
                    '((foo=bar OR foo=baz) AND (bar=foo OR bar=baz))',
                    array('foo' => array(
                                            new WFFacetedSearchNavigationQuery('foo', WFFacetedSearchNavigationQuery::COMP_EQ, 'bar'),
                                            new WFFacetedSearchNavigationQuery('foo', WFFacetedSearchNavigationQuery::COMP_EQ, 'baz'),
                                        ),
                          'bar' => array(
                                            new WFFacetedSearchNavigationQuery('bar', WFFacetedSearchNavigationQuery::COMP_EQ, 'foo'),
                                            new WFFacetedSearchNavigationQuery('bar', WFFacetedSearchNavigationQuery::COMP_EQ, 'baz'),
                    )),
                    "Mulitiple queries for multiple attribute."
                 ),
            array(
                    'GT_foo=0|LT_foo=100',
                    '(foo>0 AND foo<100)',
                    array('foo' => array(
                                            new WFFacetedSearchNavigationQuery('foo', WFFacetedSearchNavigationQuery::COMP_GT, 0),
                                            new WFFacetedSearchNavigationQuery('foo', WFFacetedSearchNavigationQuery::COMP_LT, 100),
                    )),
                    "Range query should automatically use ALL mode."
                 ),
        );
    }

    function testComplexQuery()
    {
        $this->fs->addQuery(new WFFacetedSearchUserQuery('renovated kitchen'))
                 ->addQuery(new WFFacetedSearchNativeQuery('city=Miami'))
                 ->addQuery(new WFFacetedSearchNativeQuery('price>50000', 'price', true))
                 ->addQuery(new WFFacetedSearchNavigationQuery('price', WFFacetedSearchNavigationQuery::COMP_GE, 100000))
                 ->addQuery(new WFFacetedSearchNavigationQuery('price', WFFacetedSearchNavigationQuery::COMP_LE, 200000))
                 ->setQueryCombinerModeForId(WFFacetedSearchComposableQueryCollection::ALL, 'price')
                 ->find()
                 ;
        $this->assertEquals("(<userquery>renovated kitchen</userquery> AND city=Miami AND (price>50000 AND price>=100000 AND price<=200000))", $this->fs->query());
        // make sure the hidden price isn't included in the NavigationQueries.
        $this->assertEquals(array(
                                new WFFacetedSearchNavigationQuery('price', WFFacetedSearchNavigationQuery::COMP_GE, 100000),
                                new WFFacetedSearchNavigationQuery('price', WFFacetedSearchNavigationQuery::COMP_LE, 200000)
                            ), $this->fs->navigationQueriesForId('price'), "Wrong set of navigation queries returned.");
        $this->assertEquals(array(
                                new WFFacetedSearchUserQuery('renovated kitchen')
                            ), $this->fs->navigationQueriesForId(WFFacetedSearch::DEFAULT_USER_QUERY_ID), "Wrong UserQuery returned.");
    }
}
