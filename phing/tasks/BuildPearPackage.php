<?php

require_once "phing/Task.php"; 
require_once('PEAR/PackageFileManager.php');

class BuildPearPackage extends Task
{
    /**
      * @var string The path to the root directory of the phocoa project; used as the root to find all files for the PEAR package.
      */
    protected $baseFilesDir;
    /**
      * @var string The path to the directory containing our input package.xml file.
      */
    protected $packageFileDir;

    public function init()
    {
        $this->packageFileDir = '.';
    }

    public function setBaseFilesDir($v)
    {
        $this->baseFilesDir = $v;
    }

    public function setPackageFileDir($v)
    {
        $this->packageFileDir = $v;
    }

    public function main()
    {
        $packagexml = new PEAR_PackageFileManager;
        $e = $packagexml->setOptions(
                array(
                    // package info here
                    'version' => '0.5',
                    'state' => 'alpha',
                    'notes' => 'Preview release.',

                    // package builder info here
                    'pathtopackagefile' => $this->packageFileDir,
                    'packagedirectory' => $this->baseFilesDir,
                    'baseinstalldir' => 'phocoa',
                    'simpleoutput' => true,

                    // file discovery configuration
                    'filelistgenerator' => 'file', // generate from cvs, use file for directory
                    'ignore' => array(), 
                    'installexceptions' => array('phpdocs' => '/*'),
                    'dir_roles' => array('phpdocs' => 'doc'),
            )
        );
        if (PEAR::isError($e)) {
            echo $e->getMessage();
            return;
        }
        $e = $packagexml->writePackageFile();
        if (PEAR::isError($e)) {
            echo $e->getMessage();
        }
    }

}

?>
