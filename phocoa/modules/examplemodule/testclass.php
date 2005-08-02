<?php

require_once('framework/WFObject.php');

class Person extends WFObject
{
    protected $name;
    protected $favoriteNumbers = array();
    protected $cars = array();
    protected $creationDTS;

    function __construct()
    {
        $this->creationDTS = time();
    }

    function __toString()
    {
        return "My Name is: {$this->name} and my favorite numbers are: " . join(',',$this->favoriteNumbers) . '.';
    }
}

class Car extends WFObject
{
    protected $id;
    protected $name;

    function __construct($id,$name)
    {
        $this->id = $id;
        $this->name = $name;
        $this->creationDTS = time();
    }

    function allCars()
    {
        $cars[] = new Car(1, 'BMW 325');
        $cars[] = new Car(2, 'BMW 330');
        $cars[] = new Car(3, 'BMW 650');
        return $cars;
    }
}

