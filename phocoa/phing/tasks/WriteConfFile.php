<?php
require_once 'phing/Task.php';

/**
 * Task for writing out an initial build.properties file
 * 
 * <code>
 * <writeConfFile>
 * </code>
 * 
 * @author    Alan Pinstein <apinstein@mac.com>
 */
class WriteConfFile extends Task {
    protected $ns;
    protected $file;

    public function setNamespace($ns)
    {
        $this->namespace = $ns;
    }

    public function setFile($file)
    {
        $this->file = $file;
    }

    public function main() {        
        if (file_exists($this->file))
        {
            $this->log("Destination conf file: {$this->file} already exists; will not overwrite.", PROJECT_MSG_WARN);
            return;
        }

        $text = NULL;
        $props = $this->project->getUserProperties();
        foreach ($props as $k => $v) {
            if (preg_match("/^{$this->namespace}\./", $k))
            {
                $text .= "{$k} = {$v}\n";
            }
        }

        $this->log("Writing conf file: {$this->file}.", PROJECT_MSG_INFO);
        file_put_contents($this->file, $text);
    }

}
