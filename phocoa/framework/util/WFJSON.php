<?php

class WFJSON
{
    public static function json_encode($data)
    {
        if (function_exists('json_encode'))
        {
            return json_encode($data);
        }
        else
        {
            throw( new Exception("JSON library not installed. Eventually bundle JSON.php with PHOCOA and do this automatically.") );
            return Spyc::YAMLLoad($file);
        }
    }
}

?>
