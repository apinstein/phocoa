<?php

class WFYaml
{
    public static function load($file)
    {
        return Spyc::YAMLLoad($file);
    }
}

?>
