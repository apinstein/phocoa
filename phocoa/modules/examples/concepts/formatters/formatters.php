<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

require_once APP_ROOT . '/modules/examples/widgets/exampleClasses.php';

class formatters extends WFModule
{
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
