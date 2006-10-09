<?php
require_once 'phing/Task.php';
require_once 'phing/input/InputRequest.php';

/**
 * Task for selecting an executable.
 * 
 * <code>
 * <selectExecutable name="propel-gen" propertyName="propel.bin.propel-gen" />
 * </code>
 * 
 * @author    Alan Pinstein <apinstein@mac.com>
 */
class SelectExecutable extends Task {

    private $name;
    private $propertyName;
    
    function setName($name) {
        $this->name = $name;
    }
    
    function setPropertyName($propertyName) {
        $this->propertyName = $propertyName;
    }
    
    /**
     * Perform the resolution & set property.
     */
    public function main() {        
        
        if (!$this->name) {
            throw new BuildException("You must specify the name of the executable you want to select.", $this->getLocation());
        }
        if (!$this->propertyName) {
            throw new BuildException("You must specify the property name to store the path in.", $this->getLocation());
        }

        $execPath = NULL;
        
        $cmd = "which {$this->name}";

        $outArray = array();
        $retCode = -1;
        $whichResult = exec($cmd, $outArray, $retCode);
        if ($retCode == 0)
        {
            $execPath = $whichResult;
        }
        // verify path
        $request = new InputRequest("Select the path for executable: {$this->name}: ");
        $request->setDefaultValue($execPath);
        do {
            $this->project->getInputHandler()->handleInput($request);
            $execPath = $request->getInput();
        } while (!file_exists($execPath));

        $this->project->setProperty($this->propertyName, $execPath);
        $this->log("Using {$this->name} at {$execPath}", PROJECT_MSG_INFO);
    }

}
