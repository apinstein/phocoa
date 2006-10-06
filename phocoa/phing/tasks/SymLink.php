<?php
require_once 'phing/Task.php';

/**
 * Task for setting up symlinks
 * 
 * <code>
 * <symlink source="absoulte path of source" destination="absolute path to destination" />
 * </code>
 * 
 * @author    Alan Pinstein <apinstein@mac.com>
 */
class SymLink extends Task {

    private $source;
    
    /** Base directory used for resolution. */
    private $destination;
    
    function setSource($path) {
        $this->source = $path;
    }
    
    function setDestination($path) {
        $this->destination = $path;
    }

    /**
     * Perform the resolution & set property.
     */
    public function main() {        
        
        if (!$this->source) {
            throw new BuildException("You must specify the source.", $this->getLocation());
        }
        if (!$this->destination) {
            throw new BuildException("You must specify the destination.", $this->getLocation());
        }
        
        $cmd = "ln -s '{$this->destination}' '{$this->source}'";

        $outArray = array();
        $retCode = -1;
        exec($cmd, $outArray, $retCode);
        if ($retCode == 0)
        {
            $this->log("Creating SymLink from  " . $this->source . " to " . $this->destination, PROJECT_MSG_INFO);
        }
        else
        {
            throw new BuildException("SymLink command failed:\n" . $cmd . "\n->" . $outArray, $this->getLocation());
        }
    }

}
