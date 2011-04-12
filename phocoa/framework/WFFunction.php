<?php

/**
 * Adapted from: http://techblog.triptic.nl/simulating-closures-in-php-versions-prior-to-php-5-3/
 */
class WFFunction
{

    private $code          = NULL;
    private $argumentNames = NULL;
    private $arguments     = NULL;

    public function __construct()
    {
        $this->argumentNames = array();
        $this->arguments     = array();
    }

    public static function create($code)
    {
        $func = new WFFunction();
        $func->code = $code;
        return $func;
    }

    public function withArguments()
    {
        $this->argumentNames = func_get_args();
        return $this;
    }

    public function curry()
    {
        $this->arguments = func_get_args();
        return $this;
    }

    public function call()
    {
        $orderedArguments = $this->getArgumentNamesFormatted();
        $callback = create_function($orderedArguments, $this->code);
        $calledArgs = func_get_args();
        $arguments = array_merge($calledArgs, $this->arguments);
        return call_user_func_array($callback, $arguments);
    }

    private function getArgumentNamesFormatted()
    {
        // Turn argumentNames into '$foo,$bar' format
        $argNames = array();
        foreach ($this->argumentNames as $argName)
        {
            array_push($argNames, "\${$argName}");
        }
        return implode(',', $argNames);
    }

}
