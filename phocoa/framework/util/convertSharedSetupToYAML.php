<?php

require_once($_ENV['PHOCOA_PROJECT_CONF']);

$modulePath = rtrim($argv[1], '/');
$dir = $modulePath;

print "About to convert shared setup to YAML for module at: '{$modulePath}'\n";


$instancesFile = "{$dir}/shared.instances";
$configFile = "{$dir}/shared.config";

if (!file_exists($instancesFile))
{
    print "No instances file for module, thus no YAML conversion needed.\n";
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

foreach ($__instances as $id => $class) {
    $combined[$id]['class'] = $class;
    if (isset($__config[$id]))
    {
        $combined[$id]['properties'] = $__config[$id]['properties'];
    }
}

require_once(FRAMEWORK_DIR . '/libs/spyc.php5');
$yaml = Spyc::YAMLDump($combined);
$bytes = file_put_contents("{$dir}/shared.yaml", $yaml);
if ($bytes === false)
{
    print "Failed saving yaml file: {$dir}/shared.yaml\n";
    exit(1);
}
print "Saved YAML for module to file: {$dir}/shared.yaml\n";
exit(0);

?>
