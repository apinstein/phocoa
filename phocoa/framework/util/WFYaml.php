<?php

class WFYaml
{
    public static function load($file)
    {
        if (function_exists('syck_load'))
        {
            // php-lib-c version, much faster!
            $yaml = syck_load(file_get_contents($file));
            if ($yaml === NULL)
            {
                $yaml = array();
            }
            return $yaml;
        }
        else
        {
            // php version
            return Spyc::YAMLLoad($file);
        }
    }
}

?>
