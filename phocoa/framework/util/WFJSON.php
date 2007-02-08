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
            return Services_JSON::encode($data);
        }
    }
}

?>
