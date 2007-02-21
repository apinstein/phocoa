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

    /**
     *  Given a php structure, returns a valid YAML string representation.
     *
     *  @param mixed PHP data
     *  @return string YAML equivalent.
     */
    public static function dump($phpData)
    {
        if (function_exists('syck_dump'))
        {
            // php-lib-c version, much faster!
            return syck_dump($phpData);
        }
        else
        {
            // php version
            return Spyc::YAMLDump($phpData);
        }
    }
}

?>
