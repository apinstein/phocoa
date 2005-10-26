<?php

// create a skeleton module and UI based on a Propel object

class PHPArrayDumper
{
    /**
     *  Given a PHP array, output a string which, when eval'd, reproduces the array.
     *
     *  Recursive algorithm. Doens't support objects or resources.
     *
     *  @param array The array to dump.
     *  @return string PHP code for the array in format "array(...);".
     *  @throws Exception if an unsupported type is encountered.
     */
    function arrayToPHPSource($array, $indent = 0)
    {
        $arrayStr = '';
        $indentTpl = "   ";
        $indentStr = str_repeat($indentTpl, $indent + 1);

        foreach ($array as $key => $value) {
            switch (gettype($value)) {
                case 'integer':
                case 'float':
                case 'double':
                    $arrayStr .= "$indentStr'$key' => $value,\n";
                    break;
                case 'NULL':
                    $arrayStr .= "$indentStr'$key' => NULL,\n";
                    break;
                case 'boolean':
                    $arrayStr .= "$indentStr'$key' => " . ((boolean) $value ? 'true' : 'false') . ",\n";
                    break;
                case 'string':
                    $arrayStr .= "$indentStr'$key' => '$value',\n";
                    break;
                case 'array':
                    $arrayStr .= "$indentStr'$key' => " . PHPArrayDumper::arrayToPHPSource($value, $indent + 1) . ",\n";
                    break;
                default:
                    throw( new Exception("Unsupported value type: " . gettype($value) . " encountered.") );
                    break;
            }
        }

        if ($indent == 0)
        {
            $arrayStr = "array(\n" . $arrayStr . ');';
            return $arrayStr;
        }
        else
        {
            $arrayStr = "array(\n" . $arrayStr . str_repeat($indentTpl, $indent) . ')';
            return $arrayStr;
        }
    }

    /**
     *  Similar to {@link arrayToPHPSource} but gives results in "$myVarName = array(...);" format.
     *
     *  @param array The array to dump.
     *  @return string PHP code for the array in format "$myVarName = array(...);".
     *  @throws Exception if an unsupported type is encountered.
     */
    function arrayToPHPVariableSource($array, $varName)
    {
        return "\${$varName} = " . PHPArrayDumper::arrayToPHPSource($array);
    }

    function arrayToPHPFileWithArray($array, $varName, $filePath)
    {
        $source = "<?php\n" . PHPArrayDumper::arrayToPHPVariableSource($array, $varName) . "\n?>\n";
        file_put_contents($filePath, $source);
    }
}

?>
