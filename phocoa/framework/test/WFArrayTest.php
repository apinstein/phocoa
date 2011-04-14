<?php

require_once(getenv('PHOCOA_PROJECT_CONF'));
require_once(dirname(__FILE__) . '/TestObjects.php');
error_reporting(E_ALL);

/*
 * @package test
 */
class WFArrayTest extends PHPUnit_Framework_TestCase
{
    public $array;

    public function setUp()
    {
        $this->array = new WFArray();
        $this->array['one'] = 1;
        $this->array['subWFArray'] = new WFArray( array('two' => 2) );
        $this->array['subArray'] = array('two' => 2);
        $this->array['complexArray'] = array(
            'fiveNumbers' => array(1,2,3,4,5)
        );
        $this->array['hashData'] = new WFArray(array(
            new Person('alan', 'pinstein', 1),
            new Person('joe', 'schmoe', 2),
            new Person('john', 'doe', 3),
        ));
        $this->array['chunkData'] = new WFArray(array(
            new Person('alan', 'pinstein', 1),
            new Person('alan', 'schmoe',   2),
            new Person('joe',  'schmoe',   3),
            new Person('john', 'doe',      4),
        ));
    }

    public function testHashObjects()
    {
        $hash = $this->array['hashData']->hash('uid');
        $this->assertEquals(array(1,2,3), array_keys($hash), "Hash keys didn't match.");
        $this->assertEquals($this->array['hashData']->values(), array_values($hash), "Hash values didn't match.");
    }

    public function testHashObjectsInArrayMode()
    {
        $hash = $this->array['hashData']->hash(NULL);
        $this->assertEquals(array(0,1,2), array_keys($hash), "Hash keys didn't match.");
        $this->assertEquals($this->array['hashData']->values(), array_values($hash), "Hash values didn't match.");
    }

    public function testChunkObjects()
    {
        $chunked = $this->array['chunkData']->chunk('firstName');
        $this->assertEquals(
            new WFArray(array(
                'alan' => new WFArray(array(
                    $this->array['chunkData'][0],
                    $this->array['chunkData'][1]
                )),
                'joe'  => new WFArray(array(
                    $this->array['chunkData'][2]
                )),
                'john' => new WFArray(array(
                    $this->array['chunkData'][3]
                ))
            )),
            $chunked,
            "Chunked data didn't match"
        );
    }

    public function testMap()
    {
        $this->assertEquals($this->array['hashData']->map('uid'), $this->array['hashData']->hash(NULL, 'uid'));
    }

    public function testSubArrayWithKeys()
    {
        $refArray = new WFArray(array(
            'a'         => 1,
            'b'         => 2,
            'c'         => 3,
            'd'         => 4
        ));
        $this->assertEquals(new WFArray(array('a' => 1, 'c' => 3)), $refArray->subArrayWithKeys(array('a','c')));
    }

    public function testHashObjectsWithSingleKey()
    {
        $hash = $this->array['hashData']->hash('uid', 'firstName');
        $this->assertEquals(array(1,2,3), array_keys($hash), "Hash keys didn't match.");
        $this->assertEquals(array('alan', 'joe', 'john'), array_values($hash), "Hash values didn't match.");
    }

    public function testHashObjectsWithMultipleKeys()
    {
        $hash = $this->array['hashData']->hash('uid', array('firstName', 'lastName'));
        $this->assertEquals(array(1,2,3), array_keys($hash), "Hash keys didn't match.");
        $this->assertEquals(array('firstName' => 'alan', 'lastName' => 'pinstein'), $hash[1], "Hash values didn't match.");
        $this->assertEquals(array('firstName' => 'joe', 'lastName' => 'schmoe'), $hash[2], "Hash values didn't match.");
        $this->assertEquals(array('firstName' => 'john', 'lastName' => 'doe'), $hash[3], "Hash values didn't match.");
    }

    public function testSupportsValueForKey()
    {
        $this->assertEquals(1, $this->array->valueForKey('one'));
    }
    public function testValueForKeyThrowsWFUndefinedKeyExceptionIfKeyDoesNotExist()
    {
        $this->setExpectedException('WFUndefinedKeyException');
        $this->array->valueForKey('dne');
    }
    public function testSupportsValueForKeyPath()
    {
        $this->assertEquals(1, $this->array->valueForKeyPath('one'));
    }
    public function testSupportsNestedValueForKeyPath()
    {
        // this test fails presently
        $this->markTestIncomplete();
        // see branch array-kvc for attempted solution
        $this->assertEquals(2, $this->array->valueForKeyPath('subWFArray.two'));
        $this->assertEquals(2, $this->array->valueForKeyPath('subArray.two'));
    }
    public function testValueForKeyPathThrowsWFUndefinedKeyExceptionIfKeyDoesNotExist()
    {
        $this->setExpectedException('WFUndefinedKeyException');
        $this->array->valueForKeyPath('one.dne');
    }
    public function testSupportsValueForKeyPathMagic()
    {
        // this test fails presently
        $this->markTestIncomplete();
        $this->assertEquals(1, $this->array->valueForKeyPath('complexArray.fiveNumbers.values.@first'));
        $this->assertEquals(15, $this->array->valueForKeyPath('complexArray.fiveNumbers.values.@sum'));
    }
}
