<?php

require_once('framework/WFObject.php');

class Person extends WFObject
{
    public $id;
    protected $bio;
    protected $name;
    protected $creationDTS;
    private $city;
    protected $favoriteColors;
    protected $gender;

    function __construct()
    {
        $this->creationDTS = time();
        $this->favoriteColors = array();
    }

    function __toString()
    {
        return "{$this->name} ({$this->id}) is from: " . $this->valueForKeyPath('city.name');
    }

    function personByID($id)
    {
        foreach (Person::allPeople() as $person) {
            if ($person->id == $id) return $person;
        }
        return NULL;
    }

    function cityID()
    {
        if ($this->city)
        {
            return $this->city->id;
        }
        else
        {
            return NULL;
        }
    }
    function setCityID($id)
    {
        $this->city = City::cityByID($id);
    }

    function allPeople()
    {
        static $people = NULL;
        if (!$people)
        {
            $person = new Person;
            $person->setValueForKey(1, 'id');
            $person->setValueForKey('Alan Pinstein', 'name');
            $person->setValueForKey('I am 31 and a Libra.', 'bio');
            $person->setValueForKey(array('blue', 'red'), 'favoriteColors');
            $person->setValueForKey('male', 'gender');
            $person->setCityID(2);
            $people[] = $person;

            $person = new Person;
            $person->setValueForKey(2, 'id');
            $person->setValueForKey('David Pieper', 'name');
            $person->setCityID(4);
            $people[] = $person;
        }
        return $people;
    }

    function genderValues()
    {
        return array('male', 'female');
    }

    function colorValues()
    {
        return array('blue', 'brown', 'green', 'red', 'yellow', 'orange');
    }
}

class City extends WFObject
{
    public $id;
    public $name;

    function __construct($id,$name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    function cityByID($id)
    {
        foreach (City::allCities() as $city) {
            if ($city->id == $id) return $city;
        }
        return NULL;
    }
    
    function allCities()
    {
        static $cities = NULL;
        if (!$cities)
        {
            $cities[] = new City(1, 'New York');
            $cities[] = new City(2, 'Atlanta');
            $cities[] = new City(3, 'Miami');
            $cities[] = new City(4, 'St. Louis');
        }
        return $cities;
    }
}

