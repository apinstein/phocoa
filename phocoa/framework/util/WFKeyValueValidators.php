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
 */
class WFKeyValueValidators extends WFObject
{
    /**
     *  Validate email addresses.
     *
     * @param mixed A reference to value to check. Passed by reference so that the implementation can normalize the data.
     * @param boolean A reference to a boolean. This value will always be FALSE when the method is called. If the implementation edits the $value, set to TRUE.
     * @param array An array of WFError objects describing the error. The array is empty by default; you can add new error entries.
     * @return boolean TRUE indicates a valid value, FALSE indicates an error.
     */
    public static function validateEmail(&$value, &$edited, &$errors)
    {
        //  normalize
        $value = trim($value);
        $edited = true;

        if (!preg_match('/^[_A-Za-z0-9-]+(\\.[_A-Za-z0-9-]+)*@[A-Za-z0-9-]+(\\.[A-Za-z0-9-]+)*$/', $value))
        {
            $errors[] = new WFError("That doesn't seem to be a email address. Please try again in the format 'email@domain.com'.");
            return false;
        }
        return true;
    }

    /**
     *  Validate a phone number.
     *
     *  Pretty flexible; allows any character(s) as separators. Just tries to be sure that there are the right number of digits in the right number of groups.
     *
     *  Doesn't allow extensions.
     *
     * @param mixed A reference to value to check. Passed by reference so that the implementation can normalize the data.
     * @param boolean A reference to a boolean. This value will always be FALSE when the method is called. If the implementation edits the $value, set to TRUE.
     * @param array An array of WFError objects describing the error. The array is empty by default; you can add new error entries.
     * @param string The country code you want to use for validation. Example: "US". Presently only US codes are supported. All other countries will cause the value to be assumed valid.
     * @return boolean TRUE indicates a valid value, FALSE indicates an error.
     */
    public static function validatePhone(&$value, &$edited, &$errors, $country)
    {
        //  normalize
        $value = trim($value);
        $edited = true;

        // check format based on country
        switch ($country) {
            case 'US':
                if (!preg_match('/^1?[^\d]*(\d{3}[^\d]*\d{4})|(\d{3}[^\d]*\d{3}[^\d]*\d{4})$/', $value))
                {
                    $errors[] = new WFError("That doesn't seem to be a valid US phone number. Please try again, in the format 1-555-555-1212.");
                    return false;
                }
                return true;
                break;
            default:
                return true;
        }
    }

    /**
     *  Validate a postal code.
     *
     * @param mixed A reference to value to check. Passed by reference so that the implementation can normalize the data.
     * @param boolean A reference to a boolean. This value will always be FALSE when the method is called. If the implementation edits the $value, set to TRUE.
     * @param array An array of WFError objects describing the error. The array is empty by default; you can add new error entries.
     * @param string The country code you want to use for validation. Example: "US". Presently only US codes are supported. All other countries will cause the value to be assumed valid.
     * @return boolean TRUE indicates a valid value, FALSE indicates an error.
     */
    public static function validatePostalCode(&$value, &$edited, &$errors, $country)
    {
        //  normalize
        $value = str_replace(' ', '', $value);
        $edited = true;

        // check format based on country
        switch ($country) {
            case 'US':
                if (!preg_match('/^(\d{5}-\d{4})|(\d{5})$/', $value))
                {
                    $errors[] = new WFError("US Postal Codes must be in format XXXXX or XXXXX-XXXX.");
                    return false;
                }
                return true;
                break;
            default:
                return true;
        }
    }
}
?>
