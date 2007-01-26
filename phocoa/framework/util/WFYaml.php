<?php

class WFYaml
{
    public static function load($file)
    {
        if (function_exists('syck_load'))
        {
            // php-lib-c version, much faster!
            $yaml = NULL;
            $yamlfile = file_get_contents($file);
            if (strlen($yamlfile) != 0)
            {
                $yaml = syck_load($yamlfile);
            }
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
