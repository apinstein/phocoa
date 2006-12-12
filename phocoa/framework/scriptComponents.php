<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

function createModule($modName, $pageName)
{

    $moduleTemplate = "<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

class module_{$modName} extends WFModule
{
    function sharedInstancesDidLoad()
    {
    }
";

    if ($pageName === NULL)
    {
        $moduleTemplate .= "
    function defaultPage()
    {
        return 'defaultPage';
    }
";
    }
    else
    {
        $moduleTemplate .= "
    function defaultPage()
    {
        return '{$pageName}';
    }

//    uncomment as needed
//    function {$pageName}_ParameterList()
//    {
//        return array();
//    }
//
//    function {$pageName}_SetupSkin(\$skin)
//    {
//    }
//
//    function {$pageName}_PageDidLoad(\$page, \$params)
//    {
//    }
";
    }
    $moduleTemplate .= "
}
?>";

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

function createPage($pageName)
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
        $configFile = "";

        $templateFile = "{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
HTML Goes Here.
";
    }

    if (!file_exists($pageName . '.tpl'))
    {
        print "Writing {$pageName}.tpl\n";
        file_put_contents($pageName . '.tpl', $templateFile);
    }
    else
    {
        print "Skipping .tpl file because it already exists.\n";
    }

    if (!file_exists($pageName . '.yaml'))
    {
        print "Writing {$pageName}.yaml\n";
        file_put_contents($pageName . '.yaml', $configFile);
    }
    else
    {
        print "Skipping .yaml file because it already exists.\n";
    }

    print "Done!\n";
}
?>
