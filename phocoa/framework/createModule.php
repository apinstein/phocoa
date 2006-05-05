<?php

// Simple script to create a module from scratch.
// Also creates a default view page.

require_once('scriptComponents.php');

if ($argc != 3 and $argc != 2) die("Usage: createModule.php <moduleName> <defaultViewName>\n\nCreates a directory named <moduleName>, the <moduleName>.php file, and sets up the first view.\n");
$modName = $argv[1];
$pageName = (isset($argv[2]) ? $argv[2] : NULL);
createModule($modName, $pageName);
$ok = chdir($modName);
if ($ok && $pageName)
{
    createPage($pageName);
}

?>
