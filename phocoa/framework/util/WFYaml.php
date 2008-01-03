<?php

class WFYaml
{
    public static function load($file)
    {
        if (function_exists('syck_load'))
        {
            // php-lib-c version, much faster!
            // ******* NOTE: if using libsyck with PHP, you should install from pear/pecl (http://trac.symfony-project.com/wiki/InstallingSyck)
            // ******* NOTE: as it escalates YAML syntax errors to PHP Exceptions.
            // ******* NOTE: without this, if your YAML has a syntax error, you will be really confused when trying to debug it b/c syck_load will just return NULL.
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
            throw( new WFException("YAML Parsing Error: spyc doesn't work well enough to be supported yet... for now please install syck as a php extentsion. See http://trac.symfony-project.com/wiki/InstallingSyck.") );
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
