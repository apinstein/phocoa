<?php

class WFYaml
{
    public static function load($file)
    {
        if (function_exists('syck_load'))
        {
            // php-lib-c version, much faster!
            return syck_load(file_get_contents($file));
        }
        else
        {
            // php version
            return Spyc::YAMLLoad($file);
        }
    }
}

?>
