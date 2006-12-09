<?php

require_once($_ENV['PHOCOA_PROJECT_CONF']);

$pagePath = $argv[1];
$pageName = basename($argv[1], '.tpl');
$dir = dirname($pagePath);

print "About to convert page '{$pageName}' at '{$pagePath}' to YAML\n";


$instancesFile = "{$dir}/{$pageName}.instances";
$configFile = "{$dir}/{$pageName}.config";

if (!file_exists($instancesFile))
{
    print "No instances file for '{$pageName}' thus no YAML conversion needed.\n";
    exit(0);
}

include($instancesFile);
if (file_exists($configFile))
{
    include($configFile);
}
else
{
    $__config = array();
}

$combined = array();

foreach ($__instances as $id => $inst) {
    mergeConfig($id, $inst);
}

require_once(FRAMEWORK_DIR . '/libs/spyc.php5');
$yaml = Spyc::YAMLDump($combined);
file_put_contents("{$dir}/{$pageName}.yaml", $yaml);
print "Saved YAML for page '{$pageName}' to file: {$dir}/{$pageName}.yaml\n";
exit(0);

function mergeConfig($id, $inst) {
    global $__config, $combined;

    static $curInstPath = array();

    // copy instance over
    print "Processing '$id' at " . join(' > ', $curInstPath) . "\n";
    $addToHere =& $combined;
    foreach ($curInstPath as $pid) {
        $addToHere =& $addToHere[$pid]['children'];
    }

    // copy over class and config
    $addToHere[$id]['class'] = $inst['class'];
    if (isset($__config[$id]['properties']))
    {
        $addToHere[$id]['properties'] = $__config[$id]['properties'];
    }
    if (isset($__config[$id]['bindings']))
    {
        $addToHere[$id]['bindings'] = $__config[$id]['bindings'];
    }

    // recurse into children
    if (isset($inst['children']))
    {
        array_push($curInstPath, $id);
        foreach ($inst['children'] as $childID => $childInst) {
            mergeConfig($childID, $childInst);
        }
        array_pop($curInstPath);
    }
}



?>
