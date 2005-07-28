<?php

// Simple script to create a module from scratch.
// Also creates a default view page.

require_once('scriptComponents.php');

if ($argc != 3) die("Usage: createModule.php <moduleName> <defaultViewName>\n\nCreates a directory named <moduleName>, the <moduleName>.php file, and sets up the first view.\n");
$modName = $argv[1];
$pageName = $argv[2];
createModule($modName, $pageName);
$ok = chdir($modName);
if ($ok)
{
    createPage($pageName);
}

?>
