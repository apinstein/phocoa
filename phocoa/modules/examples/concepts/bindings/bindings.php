<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
require_once APP_ROOT . '/modules/examples/widgets/exampleClasses.php';

class bindings extends WFModule
{
    function defaultPage()
    {
        return 'example';
    }

    function sharedInstancesDidLoad()
    {
        $this->person->setContent(Person::personByID(1));
    }

    function example_SetupSkin($skin)
    {
        $skin->setTitle('PHOCOA Bindings Explanation and Examples');
    }
}
?>
