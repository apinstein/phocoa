<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

class module_dynamicHiding extends WFModule
{
    protected $People;

    function defaultPage()
    {
        return 'dynamicHiding';
    }

    function sharedInstancesDidLoad()
    {
        $this->People->setContent( array(
                                        new Person('Alan', 'Memphis', (isset($_REQUEST['hide']) && $_REQUEST['hide'] == 1 ? true : false) ),
                                        new Person('Bob', 'New York', false),
                                        new Person('David', 'Atlanta', (isset($_REQUEST['hide']) && $_REQUEST['hide'] == 1 ? true : false) ),
                                        new Person('Chris', 'St. Louis', false),
                                        )
                                    );
    }
}

class Person extends WFObject
{
    protected $name;
    protected $city;
    protected $hidden;
    protected $id;

    function __construct($name, $city, $hidden)
    {
        static $id = 0;
        $this->id = $id++;
        $this->name = $name;
        $this->city = $city;
        $this->hidden = $hidden;
    }
}
?>
