<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

class formatters extends WFModule
{
    function __construct($inv)
    {
        parent::__construct($inv);
        $path = $this->pathToModule() . '/../../widgets/exampleClasses.php';
        require $path;
    }

    function defaultPage()
    {
        return 'example';
    }

    function sharedInstancesDidLoad()
    {
        $this->person->setContent(Person::personByID(1));
    }
}
?>
