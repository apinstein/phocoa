<?php

/*
 * @package test
 */
error_reporting(E_ALL);

// the CLI doesn't set the CWD to where the script it, but rather where it's run from. To get all of our relative include working, need to chdir to the framework dir.
require_once('/Users/alanpinstein/dev/sandbox/phocoadev/phocoadev/conf/webapp.conf');
require_once('framework/WFWebApplication.php');

require_once "PHPUnit2/Framework/TestCase.php";
require_once "framework/WFObject.php";
require_once "framework/WFArrayController.php";

require_once "TestObjects.php";

class WFArrayControllerTest extends PHPUnit2_Framework_TestCase
{
    private $personArray;
    private $personIDArray;
    private $personID_Identifiers = array('issuingState', 'idNumber');
    private $ac;

    public function setUp()
    {
        $this->personArray = array( new Person('John', 'Doe', 1), new Person('Jane', 'Doe', 3) );
        $this->personIDArray = array( new PersonID('GA', '1234'), new PersonID('NY', '1234') );
        $this->ac = new WFArrayController();
    }

    /**
     * Test the Iterator inteface for WFArrayController 
     */
    function testIterator()
    {
        // setup
        $this->ac->setClass('Person');
        $this->ac->setContent($this->personArray);

        // test once
        $i = 0;
        $ok = false;
        foreach ($this->ac as $person) {
            if ($i == 0) $ok = true;    // make sure we get inside the loop!
            if ($i == 0 and $person->uid != 1) $ok = false;
            if ($i == 1 and $person->uid != 3) $ok = false;
            $i++;
        }
        
        // test again to be sure rewind etc works once done at least once
        $i = 0;
        foreach ($this->ac as $person) {
            if ($i == 0 and $person->uid != 1) $ok = false;
            if ($i == 1 and $person->uid != 3) $ok = false;
            $i++;
        }

        self::assertTrue($ok);
    }

    // test round-trip of simple data
    public function testSimpleArrayRoundTrip()
    {
        $this->ac->setClass('Person');
        $this->ac->setContent($this->personArray);

        $result = $this->ac->arrangedObjects();

        self::assertTrue($result == $this->personArray);
    }

    public function testSetSingleKeyClassIdentifiers()
    {
        $this->ac->setClass('Person');
        $this->ac->setClassIdentifiers('uid');

        $result = $this->ac->classIdentifiers();

        self::assertTrue($result == array('uid'));
    }

    public function testSetMultiKeyClassIdentifiers()
    {
        $this->ac->setClass('PersonID');
        $this->ac->setClassIdentifiers($this->personID_Identifiers);

        $result = $this->ac->classIdentifiers();

        self::assertTrue($result == $this->personID_Identifiers);
    }

    public function testSingleKeyID_IdentifierValuesForObject()
    {
        $this->ac->setClass('Person');
        $this->ac->setClassIdentifiers('uid');

        $person = new Person('Jane', 'Doe', '1');
        $person->uid = 1;

        $result = $this->ac->identifierValuesForObject($person);
        self::assertTrue($result == 1);
    }

    public function testMultiKeyID_IdentifierValuesForObject()
    {
        $this->ac->setClass('PersonID');
        $this->ac->setClassIdentifiers($this->personID_Identifiers);

        $person = new PersonID('GA', '1234');

        $result = $this->ac->identifierValuesForObject($person);

        self::assertTrue($result == array('GA', '1234'));
    }

    public function testSingleKeyID_IdentifierHashForValues()
    {
        $this->ac->setClass('Person');
        $this->ac->setClassIdentifiers('uid');

        $result = $this->ac->identifierHashForValues(1);

        self::assertTrue($result == '1');
    }

    public function testMultiKeyID_IdentifierHashForValues()
    {
        $this->ac->setClass('PersonID');
        $this->ac->setClassIdentifiers($this->personID_Identifiers);

        $result = $this->ac->identifierHashForValues(array('GA', '1234'));

        self::assertTrue($result == 'GA' . WFArrayController::ID_DELIMITER . '1234');
    }

    public function testSingleKeyID_IdentifierHashForObject()
    {
        $this->ac->setClass('Person');
        $this->ac->setClassIdentifiers('uid');

        $person = new Person('Jane', 'Doe', 1);
        $person->uid = 1;

        $result = $this->ac->identifierHashForObject($person);

        self::assertTrue($result == '1');
    }

    public function testSingleKeyIDArray()
    {
        $this->ac->setClass('Person');
        $ids = 'uid';
        $this->ac->setClassIdentifiers($ids);
        $this->ac->setContent($this->personArray);

        $result = $this->ac->arrangedObjects();   // SEEMS TO BE ACLLING ADDOBJECT??
        self::assertTrue($result == $this->personArray);
    }

    public function testAutomaticallyPrepareContentOff()
    {
        $this->ac->setClass('Person');
        $this->ac->setAutomaticallyPreparesContent(false);
        $result = $this->ac->arrangedObjects();
        self::assertTrue(count($result) == 0);
    }

    public function testAutomaticallyPrepareContentOn()
    {
        $this->ac->setClass('Person');
        $result = $this->ac->arrangedObjects();
        self::assertTrue(count($result) == 1);
        self::assertTrue($result[0] instanceof Person);
    }

    public function testStartsWithNoSelection()
    {
        $this->ac->setClass('Person');
        $this->ac->setClassIdentifiers('uid');
        $this->ac->setContent($this->personArray);

        $result = $this->ac->selection();
        self::assertTrue($result == NULL);

        $result = $this->ac->selectedObjects();
        self::assertTrue(is_array($result) and count($result) == 0);

    }

    public function testSingleKey_SelectObjectsByIDs()
    {
        $this->ac->setClass('Person');
        $this->ac->setClassIdentifiers('uid');
        $this->ac->setContent($this->personArray);

        // ensure no selection
        $result = $this->ac->selectionIdentifiers();
        self::assertTrue(count($result) == 0);

        // select both items
        $this->ac->setSelectionIdentifiers(array(1,3));

        $result = $this->ac->selectionIdentifiers();
        self::assertTrue($result == array(1,3));

        $result = $this->ac->selectedObjects();
        self::assertTrue(count($result) == 2 and $result[0]->uid == 1 and $result[1]->uid == 3);
    }

    public function testSingleSelectionWorks()
    {
        $this->ac->setClass('PersonID');
        $this->ac->setClassIdentifiers($this->personID_Identifiers);
        $this->ac->setContent($this->personIDArray);

        // ensure no selection
        $result = $this->ac->selectionIdentifiers();
        self::assertTrue(count($result) == 0);

        // select one items
        $this->ac->setSelectionIdentifiers(
                array(
                    array('GA', '1234')
                    )
                );

        $good = false;
        try {
            $result = $this->ac->selection();
            self::assertTrue($result instanceof PersonID);
            $good = true;
        } catch (Exception $e) {
        }
        self::assertTrue($good);
    }

    public function testMultiSelectionCausesSelectionException()
    {
        $this->ac->setClass('PersonID');
        $this->ac->setClassIdentifiers($this->personID_Identifiers);
        $this->ac->setContent($this->personIDArray);

        // ensure no selection
        $result = $this->ac->selectionIdentifiers();
        self::assertTrue(count($result) == 0);

        // select both items
        $this->ac->setSelectionIdentifiers(
                array(
                    array('GA', '1234'),
                    array('NY', '1234')
                    )
                );

        $good = false;
        try {
            $this->ac->selection();
        } catch (Exception $e) {
            $good = true;
        }
        self::assertTrue($good);
    }
    
    public function testMultiKey_SelectObjectsByIDs()
    {
        $this->ac->setClass('PersonID');
        $this->ac->setClassIdentifiers($this->personID_Identifiers);
        $this->ac->setContent($this->personIDArray);

        // ensure no selection
        $result = $this->ac->selectionIdentifiers();
        self::assertTrue(count($result) == 0);

        $selectionIDsArray = 
                array(
                    array('GA', '1234'),
                    array('NY', '1234')
                    );

        // select both items
        $this->ac->setSelectionIdentifiers($selectionIDsArray);

        // ensure round-trip of ids
        $result = $this->ac->selectionIdentifiers();
        self::assertTrue($result == $selectionIDsArray);

        // ensure setting ids set up selection properly too
        $result = $this->ac->selectedObjects();
        self::assertTrue(count($result) == 2 and $result[0]->issuingState == 'GA' and $result[1]->issuingState == 'NY');
    }
}

?>
