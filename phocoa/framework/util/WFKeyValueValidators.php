<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package KeyValueCoding
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/** 
 * Built-in Key-Value Validators.
 *
 * The WFKeyValueValidators class provides a bunch of commonly used validators.
 *
 * @see WFObject::validateValueForKey() , WFObject::validateValueForKeyPath()
 * @todo WRITE TESTS!
 */
class WFKeyValueValidators extends WFObject
{
    /**
     *  Validate email addresses.
     *
     *  Options:
     *  required: Whether to make the value required. Default false.
     *  key: What to display as the "field" title in error messages. Default: "Email".
     *
     * @param mixed A reference to value to check. Passed by reference so that the implementation can normalize the data.
     * @param boolean A reference to a boolean. This value will always be FALSE when the method is called. If the implementation edits the $value, set to TRUE.
     * @param array An array of WFError objects describing the error. The array is empty by default; you can add new error entries.
     * @param array An array of options.
     * @return boolean TRUE indicates a valid value, FALSE indicates an error.
     */
    public static function validateEmail(&$value, &$edited, &$errors, $options = array())
    {
        $options = array_merge(array(
                                    'required' => true,
                                    'key' => 'Email',
                                    ), $options);

        //  normalize
        $value = filter_var($value, FILTER_SANITIZE_EMAIL);
        $edited = true;

        if (empty($value))
        {
            if ($options['required'])
            {
                $errors[] = new WFError("{$options['key']} is required.");
                return false;
            }
            else
            {
                return true;
            }
        }

        $okFilter = filter_var($value, FILTER_VALIDATE_EMAIL);
        if (!$okFilter)
        {
            $errors[] = new WFError("{$options['key']} doesn't seem to be a email address. Please try again in the format 'email@domain.com'.");
            return false;
        }
        return true;
    }

    /**
     * Validate URL.
     *
     * Uses filter_var($value, FILTER_VALIDATE_URL) to verify a URL. Will also try pre-pending http:// if no scheme is present.
     *
     *  Options:
     *  required: Whether to make the value required. Default false.
     *  key: What to display as the "field" title in error messages. Default: "Email".
     *
     * @param mixed A reference to value to check. Passed by reference so that the implementation can normalize the data.
     * @param boolean A reference to a boolean. This value will always be FALSE when the method is called. If the implementation edits the $value, set to TRUE.
     * @param array An array of WFError objects describing the error. The array is empty by default; you can add new error entries.
     * @param array An array of options.
     * @return boolean TRUE indicates a valid value, FALSE indicates an error.
     */
    public static function validateUrl(&$value, &$edited, &$errors, $options = array())
    {
        $options = array_merge(array(
                                    'required' => true,
                                    'key' => 'URL',
                                    ), $options);
        
        // normalize
        $value = filter_var($value, FILTER_SANITIZE_URL);
        $edited = true;

        if (empty($value))
        {
            if ($options['required'])
            {
                $errors[] = new WFError("{$options['key']} is required.");
                return false;
            }
            else
            {
                return true;
            }
        }

        if (strncasecmp('http://', $value, 7) !== 0)
        {
            $value = 'http://' . $value;
        }

        $okFilter = filter_var($value, FILTER_VALIDATE_URL);
        if (!$okFilter)
        {
            $errors[] = new WFError("{$options['key']} is not valid.");
        }
        return $okFilter;
    }

    /**
     * Validate a phone number.
     *
     * Pretty flexible; allows any character(s) as separators. Just tries to be sure that there are the right number of digits in the right number of groups.
     *
     * Doesn't allow extensions.
     *
     *  Options:
     *  required: Whether to make the value required. Default false.
     *  key: What to display as the "field" title in error messages. Default: "Email".
     *  country: Country Code to use for validation. Default US.
     *
     * @param mixed A reference to value to check. Passed by reference so that the implementation can normalize the data.
     * @param boolean A reference to a boolean. This value will always be FALSE when the method is called. If the implementation edits the $value, set to TRUE.
     * @param array An array of WFError objects describing the error. The array is empty by default; you can add new error entries.
     * @param array An array of options.
     * @return boolean TRUE indicates a valid value, FALSE indicates an error.
     */
    public static function validatePhone(&$value, &$edited, &$errors, $options = array())
    {
        if (!is_array($options))
        {
            $options = array('country' => $options);
        }

        $options = array_merge(array(
                                    'required' => true,
                                    'key' => 'Phone #',
                                    'country' => 'US',
                                    ), $options);
        

        //  normalize
        $value = trim($value);
        $edited = true;

        if (empty($value))
        {
            if ($options['required'])
            {
                $errors[] = new WFError("{$options['key']} is required.");
                return false;
            }
            else
            {
                return true;
            }
        }

        // check format based on country
        switch ($options['country']) {
            case 'US':
                if (!preg_match('/^1?[^\d]*(\d{3}[^\d]*\d{4})|(\d{3}[^\d]*\d{3}[^\d]*\d{4})$/', $value))
                {
                    $errors[] = new WFError("That doesn't seem to be a valid US phone number. Please try again, in the format 1-555-555-1212.");
                    return false;
                }
                return true;
                break;
            default:
                $errors[] = new WFError("WFKeyValueValidators::validatePhone only works for country=US at present.");
                return false;
        }
    }

    /**
     * Validate a postal code.
     *
     *  Options:
     *  required: Whether to make the value required. Default false.
     *  key: What to display as the "field" title in error messages. Default: "Email".
     *  country: Country Code to use for validation. Default US.
     *  unknownCountryIsAlwaysValid: Whether or not to accept as "valid" postal codes for unknown countries. Default true.
     *
     * @param mixed A reference to value to check. Passed by reference so that the implementation can normalize the data.
     * @param boolean A reference to a boolean. This value will always be FALSE when the method is called. If the implementation edits the $value, set to TRUE.
     * @param array An array of WFError objects describing the error. The array is empty by default; you can add new error entries.
     * @param array An array of options.
     * @return boolean TRUE indicates a valid value, FALSE indicates an error.
     */
    public static function validatePostalCode(&$value, &$edited, &$errors, $options = array())
    {
        if (!is_array($options))
        {
            $options = array('country' => $options);
        }

        $options = array_merge(array(
                                    'required' => true,
                                    'key' => 'Postal Code',
                                    'country' => 'US',
                                    'unknownCountryIsAlwaysValid' => true
                                    ), $options);
        

        // normalize
        $value = str_replace(' ', '', $value);
        $edited = true;

        if (empty($value))
        {
            if ($options['required'])
            {
                $errors[] = new WFError("{$options['key']} is required.");
                return false;
            }
            else
            {
                return true;
            }
        }

        // check format based on country
        switch ($options['country']) {
            case 'US':
                if (!preg_match('/^(\b\d{5}-\d{4}\b)$|^(\b\d{5}\b)$/', $value))
                {
                    $errors[] = new WFError("US Postal Codes must be in format XXXXX or XXXXX-XXXX.");
                    return false;
                }
                return true;
                break;
            default:
                if ($options['unknownCountryIsAlwaysValid']) return true;
                $errors[] = new WFError("Country Code {$options['country']} is not recognized.");
                return false;
        }
    }
}
