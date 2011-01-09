<?php

/*
 * @package test
 */
 
require_once(getenv('PHOCOA_PROJECT_CONF'));
error_reporting(E_ALL);

require_once "TestObjects.php";

class TrivialDecorator extends WFDecorator
{
    public function decoratorLabel() { return 'decorator'; }
}


class KeyValueCodingTest extends PHPUnit_Framework_TestCase
{
    protected $parent;
    protected $child;
    protected $nodeTree;
    protected $objectHolder;

    function setUp()
    {
        $this->parent = new Person('John', 'Doe', 1);
        $this->child = new Person('John', 'Doe, Jr.', 2);
        
        // set up complex tree
        /**
         * Grandaddy            85
         *    Daddy             50
         *       Grandkid1      22
         *       Grandkid2      25
         *    Aunt              48
         *       Grandkid3      18
         */
        $granddaddy = new Node;
        $granddaddy->name = 'Granddaddy';
        $granddaddy->value = 85;

        $bro1 = new Node;
        $bro1->name = 'Daddy';
        $bro1->value = 50;
        $granddaddy->addChild($bro1);

        $sis1 = new Node;
        $sis1->name = 'Aunt';
        $sis1->value = 48;
        $granddaddy->addChild($sis1);

        $grandkid1 = new Node;
        $grandkid1->name = 'Grandkid1';
        $grandkid1->value = 22;
        $bro1->addChild($grandkid1);
        
        $grandkid2 = new Node;
        $grandkid2->name = 'Grandkid2';
        $grandkid2->value = 25;
        $bro1->addChild($grandkid2);

        $grandkid3 = new Node;
        $grandkid3->name = 'Grandkid3';
        $grandkid3->value = 18;
        $sis1->addChild($grandkid3);

        $this->nodeTree = $granddaddy;

        $objectHolder = new ObjectHolder;
        $objectHolder->myObject = $granddaddy;
        $this->objectHolder = $objectHolder;
    }


    // test @distinctUnionOfObjects
    function testDistinctUnionOfObjectsOperator()
    {
        $distinctUnionOfObjects = $this->nodeTree->valueForKeyPath('childrenDuplicated.@distinctUnionOfObjects.firstChild');
        self::assertTrue(count($distinctUnionOfObjects) == 2 and $distinctUnionOfObjects[0]->name == 'Grandkid1' and $distinctUnionOfObjects[1]->name == 'Grandkid3');
    }

    // test @distinctUnionOfArrays
    function testDistinctUnionOfArraysOperator()
    {
        $distinctUnionOfArrays = $this->nodeTree->valueForKeyPath('children.@distinctUnionOfArrays.childrenDuplicated');
        $names = array();
        foreach ($distinctUnionOfArrays as $node) {
            $names[] = $node->name;
        }
        sort($names);
        self::assertTrue(count($distinctUnionOfArrays) == 3 and $names[0] == 'Grandkid1' and $names[1] == 'Grandkid2' and $names[2] == 'Grandkid3');
    }

    // test @unionOfArrays
    function testUnionOfArraysOperator()
    {
        $unionOfArrays = $this->nodeTree->valueForKeyPath('children.@unionOfArrays.children');
        self::assertTrue(count($unionOfArrays) == 3);
    }

    // test @first
    function testFirstOperator()
    {
        self::assertEquals($this->nodeTree->valueForKeyPath('children.@first.children.@first.name'), 'Grandkid1');
    }

    // test @sum
    function testSumOperator()
    {
        $sum = $this->nodeTree->valueForKeyPath('children.@sum.value');
        self::assertTrue($sum == 98);
    }

    // test @min
    function testMinOperator()
    {
        $min = $this->nodeTree->valueForKeyPath('children.@min.value');
        self::assertTrue($min == 48);
    }

    // test @max
    function testMaxOperator()
    {
        $max = $this->nodeTree->valueForKeyPath('children.@max.value');
        self::assertTrue($max == 50);
    }

    // test @avg
    function testAverageOperator()
    {
        $avg = $this->nodeTree->valueForKeyPath('children.@avg.value');
        self::assertTrue($avg == 49);
    }

    // test @count
    function testCountOperator()
    {
        $g1Count = $this->nodeTree->valueForKeyPath('children.@count');
        $g1Count2 = $this->objectHolder->valueForKeyPath('myObject.children.@count');
        self::assertTrue($g1Count == $g1Count2 and $g1Count == 2);
    }

    function testCountOperatorWorksWithArrayObject()
    {
        $g1Count = $this->nodeTree->valueForKeyPath('childrenAsWFArray.@count');
        $g1Count2 = $this->objectHolder->valueForKeyPath('myObject.childrenAsWFArray.@count');
        self::assertTrue($g1Count == $g1Count2 and $g1Count == 2);
    }

    // test getting an array back from a keypath
    function testValueForKeyPathArrayFromKeyPath()
    {
        $result = $this->objectHolder->valueForKeyPath('myObject.children');
        self::assertTrue($result[0]->name == 'Daddy' and $result[1]->name == 'Aunt' and count($result) == 2);
    }

    // test getting an array back from first key
    function testValueForKeyPathArray()
    {
        $result = $this->nodeTree->valueForKeyPath('children');
        self::assertTrue($result[0]->name == 'Daddy' and $result[1]->name == 'Aunt' and count($result) == 2);
    }

    // test getting magic data back with no operator
    function testValueForKeyPathMagicArray()
    {
        $result = $this->nodeTree->valueForKeyPath('children.name');
        self::assertTrue($result[0] == 'Daddy' and $result[1] == 'Aunt' and count($result) == 2);
    }

    function testKeyPathWithDecorator()
    {
        $result = $this->objectHolder->valueForKeyPath('myObject[TrivialDecorator].decoratorLabel');
        self::assertEquals('decorator', $result);
    }

    function testKeyPathWithDecoratorOnMagicArrayOperators()
    {
        $result = $this->nodeTree->valueForKeyPath('children[TrivialDecorator].@first.decoratorLabel');
        $this->assertEquals('decorator', $result);
    }

    function testKeyPathWithDecoratorOnMagicArray()
    {
        $result = $this->nodeTree->valueForKeyPath('children[TrivialDecorator].decoratorLabel');
        $this->assertEquals('decorator', $result[0]);
        $this->assertEquals('decorator', $result[1]);
    }

    function testSetValueForKey()
    {
        $this->parent->setValueForKey('My Last Name', 'lastName');
        self::assertTrue($this->parent->lastName == 'My Last Name');

        $this->parent->setValueForKey($this->child, 'child');
        self::assertTrue($this->parent->child->uid == 2);
    }

    function testKeyValueValidationGeneratesErrorForBadValue()
    {
        $badvalue = 'badfirstname';
        $edited = false;
        $errors = array();
        $valid = $this->parent->validateValueForKey($badvalue, 'firstName', $edited, $errors);
        self::assertTrue($valid === false and count($errors) == 1);
    }

    function testValuesForKeys()
    {
        $keys = array('firstName', 'lastName');
        $shouldBe = array( 'firstName' => $this->parent->firstName, 'lastName' => $this->parent->lastName );
        $hash = $this->parent->valuesForKeys($keys);
        $this->assertEquals($shouldBe, $hash);
    }

    function testValuesForKeysMissingKey()
    {
        $this->setExpectedException('WFUndefinedKeyException');
        $keys = array('firstName', 'lastName', 'undefinedKey');
        $shouldBe = array( 'firstName' => $this->parent->firstName, 'lastName' => $this->parent->lastName );
        $hash = $this->parent->valuesForKeys($keys);
    }

    function testValuesForKeyPaths()
    {
        $keys = array('name', 'childrenFirstNames' => 'children.name');
        $shouldBe = array( 'name' => $this->nodeTree->name, 'childrenFirstNames' => array('Daddy', 'Aunt') );
        $hash = $this->nodeTree->valuesForKeyPaths($keys);
        $this->assertEquals($shouldBe, $hash);
    }

    function testValuesForKeyPathsMissingKey()
    {
        $this->setExpectedException('WFUndefinedKeyException');
        $keys = array('name', 'childrenFirstNames' => 'children.name', 'undefinedKeyPath');
        $shouldBe = array( 'name' => $this->nodeTree->name, 'childrenFirstNames' => array('Daddy', 'Aunt') );
        $hash = $this->nodeTree->valuesForKeyPaths($keys);
    }

    function testStaticKVCPropertyAccess()
    {
        $this->setExpectedException('WFException'); // this can't work until PHP 5.3
        $shouldBe = array(1,2,3);
        $this->assertEquals($shouldBe, StaticPerson::valueForStaticKeyPath('StaticPerson::staticVarWithNoStaticAccessor'));
    }

    function testStaticKVCMethodAccess()
    {
        $shouldBe = array(1,2,3);
        $this->assertEquals($shouldBe, StaticPerson::valueForStaticKeyPath('StaticPerson::staticVar'));
        $this->assertEquals($shouldBe, StaticPerson::valueForStaticKeyPath('StaticPerson::anotherStaticVar'));
        $this->assertEquals($shouldBe, StaticPerson::valueForStaticKeyPath('StaticPerson::getAnotherStaticVar'));
    }

    function testStaticKVCPropertyAccessFailsForProtectedMembers()
    {
        $this->markTestIncomplete();
        $shouldBe = array(1,2,3);
        $this->setExpectedException('WFUndefinedKeyException'); // this can't work until PHP 5.3
        $this->assertEquals($shouldBe, StaticPerson::valueForStaticKeyPath('StaticPerson::kvcInaccessible'));
    }

    function testStaticKVCKeyPathWithArrayMagic()
    {
        $shouldBe = array('John', 'Jane');
        $this->assertEquals($shouldBe, StaticPerson::valueForStaticKeyPath('StaticPerson::people.firstName'));
        $this->assertEquals('John', StaticPerson::valueForStaticKeyPath('StaticPerson::people.@first.firstName'));
    }

    function testValueForKeyPathThatBeginsWithStaticAccess()
    {
        $this->setExpectedException('WFUndefinedKeyException');
        $shouldBe = array(1,2,3);
        $p = new StaticPerson;
        $this->assertEquals($shouldBe, $p->valueForKeyPath('StaticPerson::staticVar'));
    }
    function testValueForStaticKey()
    {
        $shouldBe = array(1,2,3);
        $this->assertEquals($shouldBe, StaticPerson::valueForStaticKey('StaticPerson::staticVar'));
    }
    function testValueForStaticKeyPathWithoutStaticKVCKeyPath()
    {
        $this->setExpectedException('WFException');
        $shouldBe = array('John', 'Jane');
        $this->assertEquals($shouldBe, StaticPerson::valueForStaticKeyPath('people.firstName'));
    }

    function testValidatedSetValueForKey()
    {
        $p = new Person;
        $validValue = 'goodfirstname';
        $invalidValue = 'badfirstname';
        $this->assertTrue($p->validatedSetValueForKey($validValue, 'firstName', $edited, $errors));
        $this->assertEquals($p->valueForKey('firstName'), $validValue);

        $this->assertFalse($p->validatedSetValueForKey($invalidValue, 'firstName', $edited, $errors));
        $this->assertEquals($p->valueForKey('firstName'), $validValue);   // still previous first name
    }
    function testValidatedSetValueForKeyPath()
    {
        $p = new Person;
        $validValue = 'goodfirstname';
        $invalidValue = 'badfirstname';
        $this->assertTrue($p->validatedSetValueForKeyPath($validValue, 'firstName', $edited, $errors));
        $this->assertEquals($p->valueForKey('firstName'), $validValue);

        $this->assertFalse($p->validatedSetValueForKeyPath($invalidValue, 'firstName', $edited, $errors));
        $this->assertEquals($p->valueForKey('firstName'), $validValue);   // still previous first name
    }

    function testCoalescingKeyPathsUseSemiColonAsDelimeter()
    {
        $p = new Person('Alan', 'Pinstein', 1);
        $this->assertNull($p->valueForKeyPath('alwaysNull'), "Coalescing doesn't run without a ; delimiter in the keyPath.");
        $this->assertEquals('', $p->valueForKeyPath('alwaysNull;'), "Default default defaults to empty string.");
        $this->assertEquals('No Name', $p->valueForKeyPath('alwaysNull;No Name'), "Uses default default value if key return null.");
        $this->assertEquals('No Name', $p->valueForKeyPath('alwaysNull;alwaysNull;No Name'), "Uses default default value if keys return null.");
        $this->assertEquals('ok;escaped delimeter', $p->valueForKeyPath('alwaysNull;ok\;escaped delimeter'), "SemiColon can be used in default default if escaped with backslash.");
    }
    function testCoalescingKeyPaths()
    {
        $p = new Person('Alan', 'Pinstein', 1);
        $this->assertEquals('Alan', $p->valueForKeyPath('firstName;No Name'), "Uses First Non-Null Value.");
        $this->assertEquals('Alan', $p->valueForKeyPath('alwaysNull;firstName;No Name'), "Uses First Non-Null Value.");
    }
    function testEscapeHatch()
    {
        $node = new Node;
        $node->name = 'Some name.';
        $node->value = NULL;

        $this->assertNull($node->valueForKeyPath('value^.whatever'), "Escape hatch failed to detect null for scalar.");
    }
}
