<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/** 
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 * @package UI
 * @subpackage Formatters
 */

/** 
 * WFFormatter
 *
 * Formatters provide a way for {@link WFWidget} objects to convert to and from text. Formatters are used by widgets to convert their "value" object
 * into what is displayed and vice-versa. For instance, you could assign a DATE object (ie int) to a widget, and also a WFDateFormatter, and it
 * would automatically convert the INT into a nice human-readable format. The user could edit this, and then the WFDateFormatter would convert
 * the string back into a date object before proceeding.
 *
 * @see WFWidget::setFormatter(), WFWidget::formatter()
 */
abstract class WFFormatter extends WFObject
{
    function __construct() {}

    /**
    * Retrieve a string for the passed in value.
    *
    * @param mixed The value to convert to a string. Each subclass knows how to properly handle the data types it is intended for.
    * @return string A valid string representing the data passed, or NULL if the convefrsion is not possible.
    */
    abstract function stringForValue($value);

    /**
    * Retreive a value for the passed in string.
    *
    * @param string The string value. Each subclass has algorithms for converting to the particular type of object represented.
    * @param object An empty WFError object. Fill out the {@link WFError::setErrorCode()} or the {@link WFError::setErrorMessage()} if there is an error converting.
    * @return mixed The value represented for a string.
    *               For instance, a WFDateFormatter will return a UNIX EPOCH TIME, while a WFNumberFormatter will return float, int, etc.
    */
    abstract function valueForString($string, &$error);
}

/**
 * The UNIX date formatter converts between human-readable dates and UNIX time.
 *
 * Default formatString is 'r', example: "Thu, 21 Dec 2000 16:01:07 +0200".
 * IMPORTANT! Please be aware that some date formats are not reversible! that is, they can be shown human-readable, but not reversed into a valid time.
 */
class WFUNIXDateFormatter extends WFFormatter
{
    /**
    * @var string The format string (passed to {@link date}) to use.
    */
    protected $formatString;

    function __construct()
    {
        parent::__construct();
        $this->formatString = 'r';
    }

    function stringForValue($value)
    {
        return date($this->formatString, $value);
    }

    function valueForString($string, &$error)
    {
        $string = trim($string);
        // first check for empty
        if ($string == '')
        {
            return '';
        }
        $result = strtotime($string);
        if ($result == -1)
        {
            $error->setErrorMessage("Could not determine time for that date/time string.");
            return NULL;
        }
        else
        {
            return $result;
        }
    }

    /**
    * Set the format string (see {@link date}) to use for the formatter.
    *
    * @param string The format string to use in formatting the date.
    */
    function setFormatString($fmt)
    {
        $this->formatString = $fmt;
    }
}

/**
 * The SQL Date formatter converts between SQL date format and human-readable dates.
 *
 * Default formatString is 'r', example: "Thu, 21 Dec 2000 16:01:07 +0200".
 * IMPORTANT! Please be aware that some date formats are not reversible! that is, they can be shown human-readable, but not reversed into a valid time.
 */
class WFSQLDateFormatter extends WFFormatter
{
    /**
    * @var string The format string (passed to {@link date}) to use.
    */
    protected $formatString;

    function __construct()
    {
        parent::__construct();
        $this->formatString = 'r';
    }

    /**
    * Convert a SQL date/time string into a nicely formatted string.
    */
    function stringForValue($value)
    {
        $timeStr = substr($value, 1, 18);
        $result = strtotime($timeStr);
        if ($result === false) throw( new Exception("Error converting string '$timeStr' into time.") );
        return date($this->formatString, $result);
    }

    /**
     * Convert a string (hopefully looking like a date/time) into a SQL-compliant date string.
     */
    function valueForString($string, &$error)
    {
        $string = trim($string);
        // first check for empty
        if ($string == '')
        {
            return '';
        }
        $result = strtotime($string);
        if ($result == -1)
        {
            $error->setErrorMessage("Could not determine time for that date/time string.");
            return NULL;
        }
        else
        {
            return date('r', $result);
        }
    }

    /**
    * Set the format string (see {@link date}) to use for the formatter.
    *
    * @param string The format string to use in formatting the date.
    */
    function setFormatString($fmt)
    {
        $this->formatString = $fmt;
    }
}

/**
 * The Number format converts between "pretty" numbers with formatting and PHP numeric types.
 *
 * Default is 2 decimal places, ',' for thousands, and '.' for decimal.
 */
class WFNumberFormatter extends WFFormatter
{
    /**
    * @var int The number of decimal places to use.
    */
    protected $decimalPlaces;
    /**
    * @var string The decimal point character.
    */
    protected $decimalPoint;
    /**
    * @var string The thousands separator character.
    */
    protected $thousandsSep;

    function __construct()
    {
        parent::__construct();
        $this->decimalPlaces = 2;
        $this->decimalPoint = '.';
        $this->thousandsSep = ',';
    }

    function stringForValue($value)
    {
        return number_format($value, $this->decimalPlaces, $this->decimalPoint, $this->thousandsSep);
    }

    /**
     * Convert a string (hopefully looking like a number) into a PHP numeric format.
     */
    function valueForString($string, &$error)
    {
        // first check for illegal characters
        if (preg_match("/[^0-9{$this->decimalPoint}{$this->thousandsSep}]/", $string))
        {
            $error->setErrorMessage("Could not determine number for the string: '$string' due to invalid characters.");
            return NULL;
        }
        // normalize string first
        $string = preg_replace('/[^0-9\.]/', '', trim($string));
        if ($string != '' and !is_numeric($string))
        {
            $error->setErrorMessage("Could not determine number for the string: '$string'.");
            return NULL;
        }
        else
        {
            return $string;
        }
    }

    /**
    * Set the number of decimal places to use.
    *
    * @param string
    */
    function setDecimalPlaces($dp)
    {
        $this->decimalPlaces = $dp;
    }

    /**
    * Set the thousands separator to use, or NULL to not use one.
    *
    * @param string
    */
    function setThousandsSeparator($ts)
    {
        $this->thousandsSep = $ts;
    }

    /**
    * Set the decimal point to use.
    *
    * @param string
    */
    function setDecimalPoint($char)
    {
        $this->decimalPoint = $char;
    }
}
?>
