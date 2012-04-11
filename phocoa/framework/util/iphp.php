<?php

ini_set("memory_limit", '200M');

/**
 * The iphp shell is an interactive PHP shell for working with your php applications.
 *
 * The shell includes readline support with tab-completion and history.
 *
 * To start the shell, simply run this script from your command line.
 *
 * Use ctl-d to exit the shell, or enter the command "exit".
 */
class iphp
{
    protected $lastResult = NULL;
    protected $lastCommand = NULL;
    protected $prompt = '> ';
    protected $autocompleteList = array();
    protected $tmpFileShellCommand = null;
    protected $tmpFileShellCommandRequires = null;
    protected $tmpFileShellCommandState = null;
    protected $options = array();

    const OPT_TAGS_FILE     = 'tags';
    const OPT_REQUIRE       = 'require';
    
    /**
     * Constructor
     *
     * @param array Options hash:
     *                  - OPT_TAGS_FILE: the path to a tags file produce with ctags for your project -- all tags will be used for autocomplete
     */
    public function __construct($options = array())
    {
        // merge opts
        $this->options = array_merge(array(
                                            // default options
                                            self::OPT_TAGS_FILE => NULL,
                                            self::OPT_REQUIRE => NULL,
                                          ), $options);

        // initialize temp files
        $this->tmpFileShellCommand = $this->tmpFileNamed('command');
        $this->tmpFileShellCommandRequires = $this->tmpFileNamed('requires');
        $this->tmpFileShellCommandState = $this->tmpFileNamed('state');

        // setup autocomplete
        $phpList = get_defined_functions();
        $this->autocompleteList = array_merge($this->autocompleteList, $phpList['internal']);
        $this->autocompleteList = array_merge($this->autocompleteList, get_defined_constants());
        $this->autocompleteList = array_merge($this->autocompleteList, get_declared_classes());
        $this->autocompleteList = array_merge($this->autocompleteList, get_declared_interfaces());

        // initialize tags
        $tagsFile = $this->options[self::OPT_TAGS_FILE];
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

        // process optional require files
        if ($this->options[self::OPT_REQUIRE])
        {
            if (!is_array($this->options[self::OPT_REQUIRE]))
            {
                $this->options[self::OPT_REQUIRE] = array($this->options[self::OPT_REQUIRE]);
            }
            file_put_contents($this->tmpFileShellCommandRequires, serialize($this->options[self::OPT_REQUIRE]));
        }
    }

    private function tmpFileNamed($name)
    {
        return tempnam(sys_get_temp_dir(), "iphp.{$name}.");
    }
    
    public function prompt()
    {
        return $this->prompt;
    }
    
    public function historyFile()
    {
        return getenv('HOME') . '/.iphpHistory';
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

        $requires = unserialize(file_get_contents($this->tmpFileShellCommandRequires));
        if (!is_array($requires))
        {
            $requires = array();
        }

        $parsedCommand = "<?php
//require_once(getenv('PHOCOA_PROJECT_CONF'));
foreach (" . var_export($requires, true) . " as \$file) {
    require_once(\$file);
}
\$__commandState = unserialize(file_get_contents('{$this->tmpFileShellCommandState}'));
if (is_array(\$__commandState))
{
    extract(\$__commandState);
}
ob_start();
\$_ = {$command};
\$__out = ob_get_contents();
ob_end_clean();
\$__allData = get_defined_vars();
unset(\$__allData['GLOBALS'], \$__allData['argv'], \$__allData['argc'], \$__allData['_POST'], \$__allData['_GET'], \$__allData['_COOKIE'], \$__allData['_FILES'], \$__allData['_SERVER']);
file_put_contents('{$this->tmpFileShellCommandRequires}', serialize(get_included_files()));
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

            // boostrap requires environment of command
            $requires = unserialize(file_get_contents($this->tmpFileShellCommandRequires));
            foreach ($requires as $require) {
                if ($require === $this->tmpFileShellCommand) continue;
                require_once($require);
            }
            
            $lastState = unserialize(file_get_contents($this->tmpFileShellCommandState));
            $this->lastResult = $lastState['_'];
            if ($lastState['__out'])
            {
                print $lastState['__out'] . "\n";
            }
            else
            {
                if (is_object($this->lastResult) && !is_callable(array($this->lastResult, '__toString')))
                {
                    print_r($this->lastResult) . "\n";
                }
                else
                {
                    print $this->lastResult . "\n";
                }
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

    public static function main($options = array())
    {
        $shell = new iphp($options);

        print<<<END

Welcome to the PHOCOA shell! The PHOCOA shell is a powerful interactive PHP shell for allowing you to experiment with your application interactively.

Features include:
- autocomplete (tab key)
- readline support w/history
- automatically wired into your project's autoload

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
