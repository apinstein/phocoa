<?php

/**
 * This script reads the config/instances files used by PHOCOA and exports the data as XML to STDOUT.
 * PHOCOA Builder uses this script when it "opens" files so that it can build up a data model
 * that represents the current settings. This is much easier than parsing the PHP code directly. Go NSTask!
 */
require_once('XML/Serializer.php');
require_once('Console/GetOpt.php');

//options
$opt = new Console_GetOpt;
$args = $opt->readPHPArgv();
array_shift($args);
$shortoptions = "f:";
$longoptions = array("file=");
$options = $opt->getopt($args, $shortoptions, $longoptions);
if (PEAR::isError($options))
{
    die("Error in command line: " . $options->getMessage() . "\n");
}

$theFile = NULL;

foreach ($options[0] as $o) {
    switch ($o[0]) {
        case 'f':
        case 'file':
            $theFile = $o[1];
            break;
    }
}

// which file to serialize?
if (!$theFile) die("Must pass a file with -f/--file.\n");

// open the file
if (!file_exists($theFile)) die("FATAL: File $theFile does not exist.\n");

switch (basename($theFile)) {
    case 'shared.instances':
        export_instances($theFile);
        break;
    case 'shared.config':
        export_config($theFile);
        break;
    default:
        if (preg_match('/\.instances$/', $theFile))
        {
            export_instances($theFile);
        }
        else if (preg_match('/\.config$/', $theFile))
        {
            export_config($theFile);
        }
        else
        {
            die("The file $theFile is not a .config or .instances file.\n");
        }
        break;
}

function export_instances($theFile)
{
    include($theFile);
    export_data($__instances);
}

function export_config($theFile)
{
    include($theFile);
    export_data($__config);
}
function export_data($data)
{
    $serializer = new XML_Serializer(array('typeHints' => true, 'indent' => "\t"));
    $status = $serializer->serialize($data);
    if (PEAR::isError($status))
    {
        die("Serializer error: " . $status->getMessage() . "\n");
    }
    print $serializer->getSerializedData();
    print "\n";
}

?>
