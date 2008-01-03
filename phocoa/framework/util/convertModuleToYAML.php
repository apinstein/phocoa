<?php

require_once(getenv('PHOCOA_PROJECT_CONF'));

$modulePath = rtrim($argv[1], '/');

print "Converting shared setup to YAML for module at: '{$modulePath}'\n";
$phpCommand = 'php';
if (getenv('PHP_COMMAND')) $phpCommand = getenv('PHP_COMMAND');

// loop through module directory, and convert shared.* as well as all .tpl files
// SHARED setup
$convertSharedCmd = "$phpCommand " . FRAMEWORK_DIR . "/framework/util/convertSharedSetupToYAML.php $modulePath";
`$convertSharedCmd`;

// Pages
$it = new DirectoryIterator($modulePath);
foreach ($it as $file) {
    print "Checking $file\n";
    if (preg_match('/.*\.tpl$/', $file))
    {
        print "Converting page: $file to YAML\n";
        $convertPageCmd = "$phpCommand  " . FRAMEWORK_DIR . "/framework/util/convertPageSetupToYAML.php $modulePath/$file";
        `$convertPageCmd`;
    }
}

?>
