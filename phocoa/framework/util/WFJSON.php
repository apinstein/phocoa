<?php

class WFJSON
{
    /**
     *  Encode PHP data into a JSON string.
     *
     *  @param mixed PHP data
     *  @return string JSON-encoded data.
     */
    public static function encode($data)
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

    /**
     *  Decode JSON string into PHP data.
     *
     *  @param string JSON-encoded data.
     *  @return mixed PHP data.
     */
    public static function decode($data, $asHash = true)
    {
        if (function_exists('json_decode'))
        {
            return json_decode($data, $asHash);
        }
        else
        {
            return Services_JSON::decode($data);
        }
    }

    /**
     *  Encode PHP data into a JSON string.
     *
     *  @see WFJSON::encode
     *  @deprecated
     */
    public static function json_encode($data)
    {
        return self::encode($data);
    }

    /**
     *  Decode a JSON string into PHP data.
     *
     *  @see WFJSON::decode
     *  @deprecated
     */
    public static function json_decode($data)
    {
        return self::decode($data);
    }
}

?>
