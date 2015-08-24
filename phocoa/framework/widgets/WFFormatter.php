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
 * Formatters should always allow EMPTY values cleanly. If the developer wants to enforce a non-empty value, that should be done via validation.
 *
 * @see WFWidget::setFormatter(), WFWidget::formatter()
 * @todo Now that valueForString() can return NULL as a legitimate value, need to look at existing formatters to make sure they're compatible.
 */
abstract class WFFormatter extends WFObject
{
    function __construct() {}

    /**
    * Retrieve a string for the passed in value.
    *
    * @param mixed The value to convert to a string. Each subclass knows how to properly handle the data types it is intended for.
    * @return string A valid string representing the data passed, or NULL if the conversion is not possible.
    */
    abstract function stringForValue($value);

    /**
    * Retrieve a string for the passed in value. This is the string that the user would EDIT.
    *
    * The default implementation simply calls {@link WFFormatter::stringForValue() stringForValue}.
    *
    * An example of why you would implement this function would be if the value to edit is different from the value to display.
    * For instance, for a dollar value of 5.00 you might prefer to display "$5.00" and edit "5.00".
    *
    * @param mixed The value to convert to a string. Each subclass knows how to properly handle the data types it is intended for.
    * @return string A valid string representing the data passed, or NULL if the conversion is not possible.
    * @todo Make the UI system use this call instead of stringForValue()
    */
    function editingStringForValue($value)
    {
        return $this->stringForValue($value);
    }

    /**
    * Retreive a value for the passed in string.
    *
    * Errors on conversion are detected by the presence of an errorMessage or errorCode.
    *
    * @param string The string value. Each subclass has algorithms for converting to the particular type of object represented.
    * @param object An empty WFError object. Fill out the {@link WFError::setErrorCode()} or the {@link WFError::setErrorMessage()} if there is an error converting.
    * @return mixed The value represented for a string. If there was an error in converting the string to a value, the return value will be ignored.
    *               For instance, a WFDateFormatter will return a UNIX EPOCH TIME, while a WFNumberFormatter will return float, int, etc.
    */
    abstract function valueForString($string, &$error);
}

abstract class WFBaseDateFormatter extends WFFormatter
{
    /**
    * @var string The format string (passed to {@link date}) to use.
    */
    protected $formatString;
    protected $relativeDateFormatString;

    function __construct()
    {
        parent::__construct();
        $this->formatString = 'r';
        $this->relativeDateFormatString = 'l, F j, Y';
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

    /**
    * Set the format string (see {@link date}) to use for the relative data format string if it can't be represented with a relative date (ie, Yesterday, Tomorrow, Today, etc).
    *
    * @param string The format string to use in formatting the relative date.
    */
    function setRelativeDateFormatString($fmt)
    {
        $this->relativeDateFormatString = $fmt;
    }

    public function replaceRelativeDates($formattedString, $unixTS)
    {
        if (strstr($formattedString, '+++'))
        {
            $relDate = $this->relativeDate($unixTS);
            $formattedString = str_replace('+++', $relDate, $formattedString);
        }
        return $formattedString;
    }

    /**
     * Converts a timestamp into a human readable relative time. E.g "in 2 days", "1 month ago", etc.
     */
    public function relativeDate($time)
    {
        $today = strtotime(date('M j, Y'));
        $reldays = ($time - $today)/86400;

        if ($reldays >= 0 && $reldays < 1)
        {
            return 'Today';
        }
        else if ($reldays >= 1 && $reldays < 2)
        {
            return 'Tomorrow';
        }
        else if ($reldays >= -1 && $reldays < 0)
        {
            return 'Yesterday';
        }


        if ($reldays >= 0) {
            $timelinePrefix = 'in ';
            $timelineSuffix = '';
        } else {
            $timelinePrefix = '';
            $timelineSuffix = ' ago';
        }


        if (abs($reldays) < 7)
        {
            if ($reldays > 0)
            {
                $reldays = floor($reldays);
                return 'in ' . $reldays . ' day' . ($reldays != 1 ? 's' : '');
            }
            else
            {
                $reldays = abs(floor($reldays));
                return $reldays . ' day'  . ($reldays != 1 ? 's' : '') . ' ago';
            }
        }
        else if (abs($reldays) < 28)
        {
            $relweeks = abs(floor($reldays / 7));
            return $timelinePrefix . $relweeks . ' week'  . ($relweeks != 1 ? 's' : '') . $timelineSuffix;
        }
        else if (abs($reldays) < 365)
        {
            $relmonths = abs(floor($reldays / 30));
            return $timelinePrefix . $relmonths . ' month'  . ($relmonths != 1 ? 's' : '') . $timelineSuffix;
        }
        else
        {
            $relyears = abs(floor($reldays / 365));
            return $timelinePrefix . $relyears . ' year'  . ($relyears != 1 ? 's' : '') . $timelineSuffix;
        }

        return date($this->relativeDateFormatString, $time ? $time : time());
    }
}

/**
 * The UNIX date formatter converts between human-readable dates and UNIX time.
 *
 * Default formatString is 'r', example: "Thu, 21 Dec 2000 16:01:07 +0200".
 * IMPORTANT! Please be aware that some date formats are not reversible! that is, they can be shown human-readable, but not reversed into a valid time.
 */
class WFUNIXDateFormatter extends WFBaseDateFormatter
{
    function stringForValue($value)
    {
        // allow empty values
        if (trim($value) == '')
        {
            return '';
        }
        $formattedString = date($this->formatString, $value);
        $formattedString = $this->replaceRelativeDates($formattedString, $value);
        return $formattedString;
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
        if ($result == -1 or $result === false)
        {
            $error->setErrorMessage("Could not determine time for that date/time string.");
            return NULL;
        }
        else
        {
            return $result;
        }
    }
}

/**
 * The DateTime formatter converts between human-readable dates and PHP 5.2's DateTime object.
 *
 * Default formatString is 'r', example: "Thu, 21 Dec 2000 16:01:07 +0200".
 * IMPORTANT! Please be aware that some date formats are not reversible! that is, they can be shown human-readable, but not reversed into a valid time.
 *
 * IMPORTANT! WFDateTimeFormatter expects a DateTime object. However, oftentimes the formatter is used in conjunction with widgets that normally work with strings,
 * and this the "value" will often be a multi-value string binding. Therefore often when using WFDateTimeFormatter you should use the 'Formatter' binding option
 * rather than the formatter property of the widget. If you are getting BLANK values for your timestamps, and an WFException in the log, this is probably the problem.
 */
class WFDateTimeFormatter extends WFUNIXDateFormatter
{
    function stringForValue($dtObject)
    {
        // allow empty values
        if ($dtObject === NULL)
        {
            return '';
        }
        if (!($dtObject instanceof DateTime)) throw( new WFException("Parameter passed must be a DateTime object.") );
        $formattedString = $dtObject->format($this->formatString);
        $formattedString = $this->replaceRelativeDates($formattedString, $dtObject->format('U'));
        return $formattedString;
    }

    function valueForString($string, &$error)
    {
        $string = trim($string);
        // first check for empty
        if ($string == '')
        {
            return NULL;
        }
        $dts = new DateTime($string);
        if ($dts === false)
        {
            $error->setErrorMessage("Could not determine time for that date/time string.");
            return NULL;
        }
        else
        {
            return $dts;
        }
    }
}

/**
 * The SQL Date formatter converts between SQL date format and human-readable dates.
 *
 * Default formatString is 'r', example: "Thu, 21 Dec 2000 16:01:07 +0200".
 * IMPORTANT! Please be aware that some date formats are not reversible! that is, they can be shown human-readable, but not reversed into a valid time.
 */
class WFSQLDateFormatter extends WFBaseDateFormatter
{
    /**
    * Convert a SQL date/time string into a nicely formatted string.
    */
    function stringForValue($value)
    {
        // allow empty values
        if (trim($value) == '')
        {
            return '';
        }
        $timeStr = substr($value, 0, 19);
        $result = strtotime($timeStr);
        if ($result === false) throw( new Exception("Error converting string '$timeStr' into time.") );
        $formattedString = date($this->formatString, $result);
        $formattedString = $this->replaceRelativeDates($formattedString, $result);
        return $formattedString;
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
}

/**
 * The Number format converts between "pretty" numbers with formatting and PHP numeric types.
 *
 * Default is 2 decimal places, ',' for thousands, and '.' for decimal.
 *
 * @todo Implement full suite of formatting styles, like that of NSNumberFormatter. There are 5 basic styles, None, Decimal, Currency, Percent, and Scientific.
 * @todo Add editingStringForValue capability
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
    /**
    * @var boolean TRUE to add cardinality to the end (ie 1st, 2nd, 3rd)
    */
    protected $addOrdinality;

    const WFNumberFormatterNoStyle = 'None';
    const WFNumberFormatterDecimalStyle = 'Decimal';
    const WFNumberFormatterCurrencyStyle = 'Currency';
    const WFNumberFormatterPercentStyle = 'Percent';
    private $style;

    private $currencySymbol;

    function __construct()
    {
        parent::__construct();
        $this->decimalPlaces = 2;
        $this->decimalPoint = '.';
        $this->thousandsSep = ',';
        $this->style = WFNumberFormatter::WFNumberFormatterDecimalStyle;
        $this->currencySymbol = '$';
        $this->addOrdinality = false;
    }

    /**
     *  Set the formatting style. Supported styles are Decimal, Currency, Percent, and Scientific.
     *
     *  NOTE: At this time, only Decimal and Currency are supported.
     *
     *  @param string Style to use.
     */
    function setStyle($style)
    {
        $this->style = $style;
    }

    function setCurrencySymbol($s)
    {
        $this->currencySymbol = $s;
    }

    function stringForValue($value)
    {
        $outValue = NULL;
        switch ($this->style) {
            case WFNumberFormatter::WFNumberFormatterPercentStyle:
                if ($value == '') return NULL;
                $outValue = number_format( ($value * 100) , $this->decimalPlaces, $this->decimalPoint, $this->thousandsSep) . '%';
                break;
            case WFNumberFormatter::WFNumberFormatterNoStyle:
                if ($value == '') return '';
                $outValue = $value;
                break;
            case WFNumberFormatter::WFNumberFormatterDecimalStyle:
                if ($value == '') return '';
                $outValue = number_format($value, $this->decimalPlaces, $this->decimalPoint, $this->thousandsSep);
                break;
            case WFNumberFormatter::WFNumberFormatterCurrencyStyle:
                if ($value == '') return '';
                $num = number_format($value, $this->decimalPlaces, $this->decimalPoint, $this->thousandsSep);
                $outValue = $this->currencySymbol . $num;
                break;
            default:
                throw( new Exception("Unsupported WFNumberFormatter style: " . $this->style) );
        }
        if ($this->addOrdinality)
        {
            $lastChr = substr($value, -1);
            switch ($lastChr) {
                case '0';
                case '4';
                case '5';
                case '6';
                case '7';
                case '8';
                case '9';
                    $outValue .= 'th';
                    break;
                case '1':
                    $outValue .= 'st';
                    break;
                case '2':
                    $outValue .= 'nd';
                    break;
                case '3':
                    $outValue .= 'rd';
                    break;
                default:
                    throw( new Exception('Value passed ctype_digit test yet does not end with 0-9. Should never happen.') );
            }
        }
        return $outValue;
    }

    /**
     * Convert a string (hopefully looking like a number) into a PHP numeric format.
     */
    function valueForString($string, &$error)
    {
        switch ($this->style) {
            case WFNumberFormatter::WFNumberFormatterNoStyle:   // use WFNumberFormatterDecimalStyle, all it does is normalize anyway.
            case WFNumberFormatter::WFNumberFormatterDecimalStyle:
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
                break;
            case WFNumberFormatter::WFNumberFormatterCurrencyStyle:
                // clear out the currency symbol
                $string = str_replace($this->currencySymbol, '', $string);
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
                break;
            case WFNumberFormatter::WFNumberFormatterPercentStyle:
                // clear out the percent symbol
                $origString = $string;
                $string = str_replace('%', '', $string);
                // first check for illegal characters
                if (preg_match("/[^0-9{$this->decimalPoint}{$this->thousandsSep}]/", $string))
                {
                    $error->setErrorMessage("Could not determine number for the string: '$origString' due to invalid characters.");
                    return NULL;
                }
                // normalize string first
                $string = preg_replace('/[^0-9\.]/', '', trim($string));
                if ($string != '' and !is_numeric($string))
                {
                    $error->setErrorMessage("Could not determine number for the string: '$origString'.");
                    return NULL;
                }
                else
                {
                    return ($string / 100);
                }
                break;
            default:
                throw( new Exception("Unsupported WFNumberFormatter style: " . $this->style) );
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

/**
 * The Boolean formatter converts between boolean values and YES / NO equivalents.
 */
class WFBooleanFormatter extends WFFormatter
{
    /**
    * @var string The YES value.
    */
    protected $yesValue;
    /**
    * @var string The NO value.
    */
    protected $noValue;

    function __construct()
    {
        parent::__construct();
        $this->yesValue = 'Yes';
        $this->noValue = 'No';
    }

    function stringForValue($value)
    {
        if ($value) return $this->yesValue;
        return $this->noValue;
    }

    /**
     * Convert a string value into the boolean equivalent.
     */
    function valueForString($string, &$error)
    {
        if ($string === NULL) return NULL;

        $string = trim($string);
        if (strtolower($string) == strtolower($this->yesValue))
        {
            return true;
        }
        else if (strtolower($string) == strtolower($this->noValue))
        {
            return false;
        }
        else
        {
            $error->setErrorMessage("Could not determine boolean for the string: '$string'. Value must be either '{$this->yesValue}'  or '{$this->noValue}'.");
            return NULL;
        }
    }

    /**
    * Set the YES value.
    *
    * @param string
    */
    function setYesValue($s)
    {
        $this->yesValue = $s;
    }

    /**
    * Set the NO value.
    *
    * @param string
    */
    function setnoValue($s)
    {
        $this->noValue = $s;
    }
}

/**
 * The SensitiveData formatter takes a string of sensitive data and blocks out certain pieces of the info.
 */
class WFSensitiveDataFormatter extends WFFormatter
{
    /**
    * @var integer The number of characters at the end of the string to reveal.
    */
    protected $showEndCharacters = 4;
    /**
    * @var string The number of characters as the beginning of the string to reveal.
    */
    protected $showBeginCharacters = 0;
    /**
    * @var string The character to use in place of redacted chars.
    */
    protected $redactedChr = 'X';

    /**
     * @var string The value to return from valueForString() if the input string matches the obsfucation pattern. This allows for WFSensitiveDataFormatter to be used on
     *             editable fields in conjunction with {@link WFBinding::OPTION_DO_NOT_PUSH_VALUE_SEMAPHORE}.
     */
    protected $notModifiedSemaphore = NULL;

    function stringForValue($value)
    {
        $displayString = NULL;
        $strlen = strlen($value);

        if ($this->showBeginCharacters === 0)
        {
            $displayString = str_repeat($this->redactedChr, max(0, $strlen - $this->showEndCharacters));
        }
        else
        {
            $displayString = substr($value, 0, $this->showBeginCharacters);
            $displayString .= str_repeat($this->redactedChr, max(0, $strlen - ($this->showBeginCharacters + $this->showEndCharacters)));
        }

        if ($this->showEndCharacters)
        {
            $displayString .= substr($value, -$this->showEndCharacters);
        }

        return $displayString;
    }

    /**
     * Returns {@link WFSensitiveDataFormatter::$notModifiedSemaphore} if the input pattern matches the obsfucation pattern (ie, the input string was not modified).
     *
     * @see WFFormatter::valueForString()
     */
    function valueForString($string, &$error)
    {
        if ($this->notModifiedSemaphore !== NULL)
        {
            $isObscuredRegex = '/^.{' . $this->showBeginCharacters . '}[\\' . $this->redactedChr . ']+.{' . $this->showEndCharacters . '}$/';
            if (preg_match($isObscuredRegex, $string))
            {
                return $this->notModifiedSemaphore;
            }
            else
            {
                return $string;
            }
        }
        else
        {
            $error->setErrorMessage("SensitiveDataFormatter cannot be used in reverse.");
        }
        return NULL;
    }

    /**
    * Set the number of characters at the end of the string to reveal.
    *
    * @param integer
    */
    function setShowEndCharacters($s)
    {
        $this->showEndCharacters = $s;
    }

    /**
    * Set the number of characters at the beginning of the string to reveal.
    *
    * @param integer
    */
    function setShowBeginCharacters($s)
    {
        $this->showBeginCharacters = $s;
    }
}
?>
