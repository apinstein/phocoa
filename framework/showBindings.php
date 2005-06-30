<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * Simple php script to show all exposed bindings for a given widget.
 *
 * Usage: CD to framework/
 * php showBindings.php WFCheckbox
 *
 * @package UI
 * @subpackage Bindings
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/**
 * Includes
 */

require_once('../conf/webapp.conf');
require_once('framework/WFWebApplication.php');
require_once('framework/WFPage.php');
require_once('framework/WFModule.php');


/**
 * Fake some things.
 * @ignore
 */
class MyModule extends WFModule
{
    function defaultPage() { return 'nothing'; }
}

// Simple script that prints out all bindings options for a given class.

if ($argc != 2) die("You must enter the class name as an argument that you want to view bindings for. Must call this script from the framework directory.\n\nphp showBindings.php myClass\n");
$className = $argv[1];

require_once('widgets/' . $className . '.php');
$myObj = new $className('test', new WFPage(new MyModule('')));
$exposedBindings = $myObj->exposedBindings();
print "Exposed bindings for class:\n$className";
if (count($exposedBindings) > 0)
{
    foreach ($exposedBindings as $bindingSetup) {
        print "\n  " . $bindingSetup->boundProperty() . ' - ' . $bindingSetup->description();
        if (count($bindingSetup->options()) > 0)
        {
            foreach ($bindingSetup->options() as $optName => $optDefault) {
                print "\n                          $optName => " . prettyValue($optDefault);
            }
        }
        else
        {
            // no options
        }
    }
}
else
{
    print "\nNo bindings exposed.";
}

print "\n";
exit;

function prettyValue($val)
{
    switch (gettype($val)) {
        case 'boolean':
            if ($val) return 'true';
            return 'false';
        case 'string':
            return "'$val'";
        default:
            return $val;
    }
}

?>
