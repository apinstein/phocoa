<?php

require_once('scriptComponents.php');

// Simple script to create all of the files needed for a View for the framework.
// Creates the .tpl. .instances, and .config file for the given view name in the current directory.

if ($argc != 2) die("You must enter the view name as an argument to createPage.\n\nphp createPage.php myViewName\n");
$pageName = $argv[1];
createPage($pageName);

?>
