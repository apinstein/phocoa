<?php

ini_set("memory_limit", '20M');
require_once(getenv('PHOCOA_PROJECT_CONF'));

/**
 * The phocoa shell is an interactive PHP shell for working with your phocoa applications.
 *
 * The phocoa shell includes readline support with tab-completion and history.
 *
 * To start the shell, simply run "phocoa shell" from your command line. The shell is automatically
 * bootstrapped into your phocoa application, so you can instantiate your classes and work with them immediately.
 *
 * For each command you enter, the result will be displayed and also assigned to $_.
 *
 * If you have a tags file for your project, keep it in APP_ROOT and the shell will also include
 * autocomplete support from your project.
 *
 * Use ctl-d to exit the shell, or enter the command "exit".
 */
class WFShell extends WFObject
{
    protected $lastResult = NULL;
    protected $lastCommand = NULL;
    protected $prompt = '> ';
    protected $autocompleteList = array();
    protected $tmpFileShellCommand = null;
    protected $tmpFileShellCommandState = null;
    
    public function __construct()
    {
        $phpList = get_defined_functions();
        $this->autocompleteList = array_merge($this->autocompleteList, $phpList['internal']);
        $this->autocompleteList = array_merge($this->autocompleteList, get_defined_constants());
        $this->autocompleteList = array_merge($this->autocompleteList, get_declared_classes());
        $this->autocompleteList = array_merge($this->autocompleteList, get_declared_interfaces());
        // look for a tags file in the root of the project
        $tagsFile = APP_ROOT . '/tags';
        if (file_exists($tagsFile))
        {
            $tags = array();
            $tagLines = file($tagsFile);
            foreach ($tagLines as $tag) {
                $matches = array();
                if (preg_match('/^([A-z0-9][^\W]*)\W.*/', $tag, $matches))
                {
                    $tags[] = $matches[1];
                }
            }
            $this->autocompleteList = array_merge($this->autocompleteList, $tags);
        }

        $this->tmpFileShellCommand = tempnam(sys_get_temp_dir(), 'phocoa.shell.');
        $this->tmpFileShellCommandState = tempnam(sys_get_temp_dir(), 'phocoa.shell.');
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
        print "\n";
        if (trim($command) == '')
        {
            return;
        }

        if (!empty($command) and function_exists('readline_add_history'))
        {
            readline_add_history($command);
            readline_write_history($this->historyFile());
        }

        $command = preg_replace('/^\//', '$_', $command);  // "/" as a command will just output the last result.
        $parsedCommand = "<?php
require_once(getenv('PHOCOA_PROJECT_CONF'));
extract(unserialize(file_get_contents('{$this->tmpFileShellCommandState}')));
ob_start();
\$_ = {$command};
\$__out = ob_get_contents();
ob_end_clean();
\$__allData = get_defined_vars();
unset(\$__allData['GLOBALS'], \$__allData['argv'], \$__allData['argc'], \$__allData['_POST'], \$__allData['_GET'], \$__allData['_COOKIE'], \$__allData['_FILES'], \$__allData['_SERVER']);
file_put_contents('{$this->tmpFileShellCommandState}', serialize(\$__allData));
";
        #echo "  $parsedCommand\n";
        try {
            $_ = $this->lastResult;
            file_put_contents($this->tmpFileShellCommand, $parsedCommand);

            $result = NULL;
            $output = array();
            $lastLine = exec("/opt/local/bin/php {$this->tmpFileShellCommand} 2>&1", $output, $result);
            if ($result != 0) throw( new Exception("Fatal error executing php: " . join("\n", $output)) );
            
            $lastState = unserialize(file_get_contents($this->tmpFileShellCommandState));
            $this->lastResult = $lastState['_'];
            if ($lastState['__out'])
            {
                print $lastState['__out'] . "\n";
            }
            else
            {
                print $this->lastResult . "\n";
            }

            // after the eval, we might have new classes. Only update it if real readline is enabled
            if (!empty($this->autocompleteList)) $this->autocompleteList = array_merge($this->autocompleteList, get_declared_classes());
        } catch (Exception $e) {
            print "Uncaught exception with command:\n" . $e->getMessage() . "\n";
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

> new WFArray(array(1,2))
Array:
  0 => 1
  1 => 2
END Array

> \$_[0] + 1
2


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
