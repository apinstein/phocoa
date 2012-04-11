<?php

/*
 * @package test
 */
 
require_once(getenv('PHOCOA_PROJECT_CONF'));
error_reporting(E_ALL);

class WFRPCTest extends PHPUnit_Framework_TestCase
{
    function detectRPCDataProvider()
    {
        $normalPercentEncodes = "|%,";
        $encodeURIComponentSkippedChrs = "-_.!~*()'";
        return array(
                //    invocationPath                                                rpcInvocationPath                                    expectIsRPC     explanation
                array('foo/bar',                                                    'foo/baz',                                           false,          "different base urls should not match"),
                array('foo/bar?char= ',                                             'foo/bar?char= ',                                    true,           "space character encoding"),
                array('foo/bar?char= ',                                             'foo/bar?char=+',                                    true,           "space character encoding"),
                array('foo/bar?char= ',                                             'foo/bar?char=%20',                                  true,           "space character encoding"),
                array('foo/bar?char=%20',                                           'foo/bar?char= ',                                    true,           "space character encoding"),
                array('foo/bar?char=%20',                                           'foo/bar?char=+',                                    true,           "space character encoding"),
                array('foo/bar?char=%20',                                           'foo/bar?char=%20',                                  true,           "space character encoding"),
                array('foo/bar?char=+',                                             'foo/bar?char= ',                                    true,           "space character encoding"),
                array('foo/bar?char=+',                                             'foo/bar?char=+',                                    true,           "space character encoding"),
                array('foo/bar?char=+',                                             'foo/bar?char=%20',                                  true,           "space character encoding"),

                // slashes
                array('foo/bar?char=/',                                             'foo/bar?char=%2F',                                  true,           "slash character encoding"),
                array('foo/bar?char=/',                                             'foo/bar?char=/',                                    true,           "slash character encoding"),
                array('foo/bar?char=%2f',                                           'foo/bar?char=%2F',                                  true,           "slash character encoding"),
                array('foo/bar?char=%2f',                                           'foo/bar?char=/',                                    true,           "slash character encoding"),

                // normal %-encoded chars
                array("foo/bar?char={$normalPercentEncodes}",                       "foo/bar?char=" . urlencode($normalPercentEncodes),  true,           "normal %-encoded characters"),
                array("foo/bar?char={$normalPercentEncodes}",                       "foo/bar?char={$normalPercentEncodes}",              true,           "normal %-encoded characters"),
                array('foo/bar?char=' . urlencode($normalPercentEncodes),           "foo/bar?char=" . urlencode($normalPercentEncodes),  true,           "normal %-encoded characters"),
                array('foo/bar?char=' . urlencode($normalPercentEncodes),           "foo/bar?char={$normalPercentEncodes}",              true,           "normal %-encoded characters"),

                // encodeURIchars
                array("foo/bar?char={$encodeURIComponentSkippedChrs}",              "foo/bar?char={$encodeURIComponentSkippedChrs}",     true,           "encodeURIchars special opt-out characters"),
                array('foo/bar?char=' . urlencode($encodeURIComponentSkippedChrs),  "foo/bar?char={$encodeURIComponentSkippedChrs}",     true,           "encodeURIchars special opt-out characters"),
            );
    }
    /**
     * @dataProvider detectRPCDataProvider
     */
    function testDetectRPC($invocationPath, $rpcInvocationPath, $expectIsRPC, $explanation)
    {
        $explanation .= str_pad("\nSimulated URL:", 40) . $invocationPath;
        $explanation .= str_pad("\nSimulated rpcInvocationPath:", 40) . $rpcInvocationPath;

        // FAKE RPC
        $fakeRPC = new WFRPC();
        $fakeRPC->setInvocationPath($rpcInvocationPath)
                ->setTarget('foo')
                ->setAction('bar')
                ;
        $_REQUEST = $fakeRPC->rpcAsParameters();

        $rpc = WFRPC::rpcFromRequest($invocationPath);
        if ($expectIsRPC)
        {
            $this->assertNotNull($rpc, $explanation);
        }
        else
        {
            $this->assertNull($rpc, $explanation);
        }
    }
}
