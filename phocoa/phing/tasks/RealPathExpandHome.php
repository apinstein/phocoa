<?php
require_once 'phing/Task.php';

/**
 * Task for relative paths and setting absolute path in property value.
 *
 * Includes support for expanding ~ to home directory.
 *
 * Unlink resolvepath, this task does not use relative paths.
 * 
 * <code>
 *   <realpathexpandhome propertyName="phocoa.project.container" file="${phocoa.project.dir}/.."/>
 * </code>
 * 
 * @author    Alan Pinstein <apinstein@mac.com>
 */
class RealPathExpandHome extends Task {

    /** Name of property to set. */
    private $propertyName;
    
    /** The [possibly] relative file/path that needs to be resolved. */
    private $file;
    
    /** Base directory used for resolution. */
    private $dir;
    
    /**
     * Set the name of the property to set.
     * @param string $v Property name
     * @return void
     */
    public function setPropertyName($v) {
        $this->propertyName = $v;
    }
    
    /**
     * Sets a base dir to use for resolution.
     * @param PhingFile $d
     */
    function setDir(PhingFile $d) {
        $this->dir = $d;
    }
    
    /**
     * Sets a path (file or directory) that we want to resolve.
     * This is the same as setFile() -- just more generic name so that it's
     * clear that you can also use it to set directory.
     * @param string $f
     * @see setFile()
     */
    function setPath($f) {
        $this->file = $f;
    }
    
    /**
     * Sets a file that we want to resolve.
     * @param string $f
     */
    function setFile($f) {
        $this->file = $f;
    }

    /**
     * Perform the resolution & set property.
     */
    public function main() {        
        
        if (!$this->propertyName) {
            throw new BuildException("You must specify the propertyName attribute", $this->getLocation());
        }
        
        // Currently only files are supported
        if ($this->file === null) {
            throw new BuildException("You must specify a path to resolve", $this->getLocation());
        }
        
        $path = $this->file;
        if (! file_exists($path)) { // this chunk fixes a bug with realpath on non-BSD systems in which realpath fails if it finds a missing path section.
            mkdir($path, 0755);
        }

        if (isset($_ENV['HOME']))
        {
            $path = str_replace('~', $_ENV['HOME'], $path);
        }
        $path = realpath($path);
        $this->log("Resolved " . $this->file . " to " . $path, PROJECT_MSG_INFO);
        $this->project->setProperty($this->propertyName, $path);
    }

}
