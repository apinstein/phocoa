<?php

// Simple script to create all of the files needed for a View for the framework.
// Creates the .tpl. .instances, and .config file for the given view name in the current directory.

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

if ($argc != 2) die("You must enter the view name as an argument to createView.\n\nphp createView.php myViewName\n");
$viewName = $argv[1];

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

?>
