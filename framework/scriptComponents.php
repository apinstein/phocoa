<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

function createModule($modName, $viewName)
{

    $moduleTemplate = "<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

class $modName extends WFModule
{
    function defaultPage()
    {
        return '{$viewName}';
    }

    // Uncomment additional functions as needed
//    function sharedInstancesDidLoad()
//    {
//    }
//
//    function {$viewName}_ParameterList()
//    {
//        return array();
//    }
//
//    function {$viewName}_SetupSkin(\$skin)
//    {
//    }
//
//    function {$viewName}_PageDidLoad(\$page, \$params)
//    {
//    }
}
?>
";

    // check and make dir
    if (!file_exists("./$modName"))
    {
        mkdir('./' . $modName);
    }
    $modFilePath = "{$modName}/{$modName}.php";
    if (!file_exists($modFilePath))
    {
        print "Writing $modFilePath\n";
        file_put_contents($modFilePath, $moduleTemplate);
    }
    else
    {
        print "Skipping module $modName because it already exists.\n";
    }

    print "Done building module $modName!\n";
}

function createView($viewName)
{
    $includeHints = false;
    if ($includeHints)
    {
        $configFile = "<?php
        /* vim: set expandtab tabstop=4 shiftwidth=4 syntax=php: */
        \$__config = array(
                    'instance_or_controller_id' => array(
                            'properties' => array(
                                'propName' => 'propValue'
                                ),
                            'bindings' => array(
                                'propName' => array(
                                    'instanceID' => 'controller instance id',       // or can be '#module#' to bind to the current module
                                    'controllerKey' => 'the controller key to use',
                                    'modelKeyPath' => 'the modelKeyPath to use',
                                    'options' => array(
                                        'bindingOption1' => 'bindingOptionValue'
                                        )
                                    )
                                )
                        )
                );

        ?>";

        $instancesFile = "<?php
        /* vim: set expandtab tabstop=4 shiftwidth=4 syntax=php: */

        \$__instances = array(
                'widgetID' => array(
                    'class' => 'WFWidget subclass',
                    'children' => array()
                    ),
                'controllerID' => array('class' => 'WFObjectController subclass')
                );

        ?>";

        $templateFile = "
{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
HTML Goes Here.
";
    }
    else
    {
        $configFile = "<?php
        /* vim: set expandtab tabstop=4 shiftwidth=4 syntax=php: */
        \$__config = array(
                );

        ?>";

        $instancesFile = "<?php
        /* vim: set expandtab tabstop=4 shiftwidth=4 syntax=php: */

        \$__instances = array(
                );

        ?>";

        $templateFile = "{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
HTML Goes Here.
";
    }

    if (!file_exists($viewName . '.tpl'))
    {
        print "Writing {$viewName}.tpl\n";
        file_put_contents($viewName . '.tpl', $templateFile);
    }
    else
    {
        print "Skipping .tpl file because it already exists.\n";
    }

    if (!file_exists($viewName . '.instances'))
    {
        print "Writing {$viewName}.instances\n";
        file_put_contents($viewName . '.instances', $instancesFile);
    }
    else
    {
        print "Skipping .instances file because it already exists.\n";
    }

    if (!file_exists($viewName . '.config'))
    {
        print "Writing {$viewName}.config\n";
        file_put_contents($viewName . '.config', $configFile);
    }
    else
    {
        print "Skipping .config file because it already exists.\n";
    }

    print "Done!\n";
}
?>
