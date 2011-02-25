<?php

class WFYaml
{
    public static function loadFile($file)
    {
        if (function_exists('yaml_parse_file'))
        {
            return yaml_parse_file($file);
        }
        else if (function_exists('syck_load'))
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
            return Horde_Yaml::loadFile($file);
        }
    }

    /**
     * NOTE: libsyck extension doesn't have a 'string' loader, so we have to write a tmp file. Kinda slow... in any case though shouldn't really use YAML strings
     * for anything but testing stuff anyway
     *
     * @param 
     * @return
     * @throws
     */
    public static function loadString($string)
    {
        if (function_exists('yaml_parse'))
        {
            return yaml_parse($string);
        }
        else if (function_exists('syck_load'))
        {
            // extension version
            $file = tempnam("/tmp", 'syck_yaml_tmp_');
            file_put_contents($file, $string);
            return self::loadFile($file);
        }
        else
        {
            // php version
            return Horde_Yaml::load($string);
        }
    }

    /**
     * @deprecated Use loadFile()
     */
    public static function load($file)
    {
        return self::loadFile($file);
    }

    /**
     *  Given a php structure, returns a valid YAML string representation.
     *
     *  @param mixed PHP data
     *  @return string YAML equivalent.
     */
    public static function dump($phpData)
    {
        if (function_exists('yaml_emit'))
        {
            return yaml_emit($phpData);
        }
        else if (function_exists('syck_dump'))
        {
            // php-lib-c version, much faster!
            return syck_dump($phpData);
        }
        else
        {
            // php version
            return Horde_Yaml::dump($phpData);
        }
    }
}
