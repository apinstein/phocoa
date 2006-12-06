<?php

require $_ENV['PHOCOA_PROJECT_CONF'];

print<<<END

Welcome to the PHOCOA shell! The PHOCOA shell is a simple interactive PHP shell for allowing you to experiment with your application interactively.
This shell is already integrated into your current project, meaning you can instantiate objects in your project.

Enter a php statement at the prompt, and it will be evaluated. The variable \$_ will contain the result.

Example:

new WFObject()
\$a = \$_
print_r(\$a)

END;

$_ = NULL;
while (true)
{
    $cmd = readline('> ');
    if (trim($cmd) == '') continue;
    $cmd = preg_replace('/^\//', '$_', $cmd);
    $parsedCommand = "return {$cmd};";
    #echo "  $parsedCommand\n";
    try {
        $_ = eval($parsedCommand);
        print "{$_}\n";
    } catch (Exception $e) {
        print "Uncaught exception with command:\n{$e}\n";
    }
}

function readline( $prompt = '' )
{
    echo $prompt;
    return rtrim( fgets( STDIN ), "\n" );
}

?>
