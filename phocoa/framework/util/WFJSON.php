<?php

// pre-5.4 compatibility
if (!defined('JSON_PRETTY_PRINT'))
{
    define('JSON_PRETTY_PRINT', 128);
}

class WFJSON
{
    /**
     *  Encode PHP data into a JSON string.
     *
     *  @param mixed PHP data
     *  @return string JSON-encoded data.
     */
    public static function encode($data, $options = NULL)
    {
        $json = NULL;

        if (function_exists('json_encode'))
        {
            $json = json_encode($data, $options);
        }
        else
        {
            $json = Services_JSON::encode($data);
        }

        if ($options & JSON_PRETTY_PRINT && PHP_VERSION_ID < 50500)
        {
            $json = self::prettyPrintJson($json);
        }

        return $json;
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
    public static function json_encode($data, $options = NULL)
    {
        return self::encode($data, $options);
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

    /**
     * Pretty print JSON, from http://recursive-design.com/blog/2008/03/11/format-json-with-php/
     *
     * @param string Input json
     * @return string Pretty-printed JSON.
     */
    public static function prettyPrintJson($json)
    {
        $result      = '';
        $pos         = 0;
        $strLen      = strlen($json);
        $indentStr   = '  ';
        $newLine     = "\n";
        $prevChar    = '';
        $outOfQuotes = true;

        for ($i=0; $i<=$strLen; $i++) {

            // Grab the next character in the string.
            $char = substr($json, $i, 1);

            // Are we inside a quoted string?
            if ($char == '"' && $prevChar != '\\') {
                $outOfQuotes = !$outOfQuotes;

                // If this character is the end of an element, 
                // output a new line and indent the next line.
            } else if(($char == '}' || $char == ']') && $outOfQuotes) {
                $result .= $newLine;
                $pos --;
                for ($j=0; $j<$pos; $j++) {
                    $result .= $indentStr;
                }
            }

            // Add the character to the result string.
            $result .= $char;

            // If the last character was the beginning of an element, 
            // output a new line and indent the next line.
            if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
                $result .= $newLine;
                if ($char == '{' || $char == '[') {
                    $pos ++;
                }

                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }

            $prevChar = $char;
        }

        return $result;
    }
}

?>
