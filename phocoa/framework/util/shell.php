<?php

ini_set("memory_limit", '20M');
require_once(getenv('PHOCOA_PROJECT_CONF'));

class WFShell extends WFObject
{
    protected $lastResult = NULL;
    protected $lastCommand = NULL;
    protected $prompt = '> ';
    protected $autocompleteList = array();
    
    public function __construct()
    {
        $phpList = get_defined_functions();
        $this->autocompleteList = array_merge($this->autocompleteList, $phpList['internal']);
        $this->autocompleteList = array_merge($this->autocompleteList, get_defined_constants());
        $this->autocompleteList = array_merge($this->autocompleteList, get_declared_classes());
        $this->autocompleteList = array_merge($this->autocompleteList, get_declared_interfaces());
    }
    
    public function prompt()
    {
        return $this->prompt;
    }
    
    public function historyFile()
    {
        return '/tmp/.phocoaShellHistory';
    }
    
    public function readlineCallback($command)
    {
        if ($command === NULL) exit;
        $this->lastCommand = $command;
    }
    
    public function readlineCompleter($str)
    {
        return $this->autocompleteList;
    }
    
    public function doCommand($command)
    {
        if (trim($command) == '') return;

        if (!empty($command) and function_exists('readline_add_history'))
        {
            readline_add_history($command);
            readline_write_history($this->historyFile());
        }

        $command = preg_replace('/^\//', '$_', $command);  // "/" as a command will just output the last result.
        $parsedCommand = "return {$command};";
        #echo "  $parsedCommand\n";
        try {
            $_ = $this->lastResult;
            $this->lastResult = eval($parsedCommand);
            print "\n---\n{$this->lastResult}\n";

            // after the eval, we might have new classes. Only update it if real readline is enabled
            if (!empty($this->autocompleteList)) $this->autocompleteList = array_merge($this->autocompleteList, get_declared_classes());
        } catch (Exception $e) {
            print "Uncaught exception with command:\n{$e}\n";
        }
    }
    
    private function myReadline()
    {
        $this->lastCommand = NULL;
        readline_callback_handler_install($this->prompt, array($this, 'readlineCallback'));
        while ($this->lastCommand === NULL) {
            readline_callback_read_char();
        }
        return $this->lastCommand;
    }
    public function readline()
    {
        if (function_exists('readline'))
        {
            $command = $this->myReadline();
        }
        else
        {
            echo $this->prompt;
            $command = rtrim( fgets( STDIN ), "\n" );
            // catch ctl-d
            if (strlen($command) == 0)
            {
                exit;
            }
        }
        return $command;
    }

    public static function main()
    {
        $shell = new WFShell;
        
        print<<<END

Welcome to the PHOCOA shell! The PHOCOA shell is a simple interactive PHP shell for allowing you to experiment with your application interactively.
This shell is already integrated into your current project, meaning you can instantiate objects in your project.

Enter a php statement at the prompt, and it will be evaluated. The variable \$_ will contain the result.

Example:

new WFObject()
\$a = \$_
print_r(\$a)

END;
        // readline history
        if (function_exists('readline_read_history'))
        {
            readline_read_history($shell->historyFile());   // doesn't seem to work, even though readline_list_history() shows the read items!
        }
        // install tab-complete
        if (function_exists('readline_completion_function'))
        {
            readline_completion_function(array($shell, 'readlineCompleter'));
        }
        while (true)
        {
            $shell->doCommand($shell->readline());
        }
    }
}

WFShell::main();
?>
