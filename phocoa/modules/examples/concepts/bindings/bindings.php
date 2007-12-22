<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

class bindings extends WFModule
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

    function example_SetupSkin($skin)
    {
        $skin->setTitle('PHOCOA Bindings Explanation and Examples');
        $skin->setMetaDescription('PHOCOA Bindings: How the PHOCOA PHP framework makes your life easy through data bindings.');
    }
}
?>
