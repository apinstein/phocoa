<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package UI
 * @subpackage Widgets
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/**
 * A Password widget for our framework.
 * @todo Add property "enterPasswordValidateDuplicate" - will show 2 fields and automatically validate that they're the same (great for "sign up" or "change password" feature). default FALSE.
 * @todo Add property "blankOK" which will not consider it an error if ALL fields are blank (assume "no change"). default TRUE.
 */
class WFPassword extends WFWidget
{
    /**
     *  @var integer The max length of the text field
     */
    protected $maxLength;
    /**
     *  @var integer The size of the text field
     */
    protected $size;
    /**
     *  @var boolean Whether or not to "preserve" the input in the UI if the form is re-displayed. TRUE is useful for sign-up forms to prevent people from having to re-enter the PW if there's nothing wrong with that data.
     */
    protected $preserveInput;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->maxLength = NULL;
        $this->size = NULL;
        $this->enabled = true;
        $this->preserveInput = false;
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            'maxLength',
            'size',
            'preserveInput' => array(true, false),
            ));
    }

    function restoreState()
    {
        //  must call super
        parent::restoreState();

        // look for the things in the form I need to restore state...
        if (isset($_REQUEST[$this->name]))
        {
            $this->value = $_REQUEST[$this->name];
        }
    }

    function render($blockContent = NULL)
    {
        if ($this->hidden) return NULL;

        return '<input type="password" name="' . $this->valueForKey('name') . '" value="' . ($this->preserveInput ? $this->value : NULL) . '"' .
            ($this->valueForKey('size') ? ' size="' . $this->valueForKey('size') . '" ' : '') .
            ($this->valueForKey('maxLength') ? ' maxLength="' . $this->valueForKey('maxLength') . '" ' : '') .
            ($this->valueForKey('enabled') ? '' : ' disabled readonly ') .
            '/>';
    }

    /********************* BINDINGS SETUP ************************/
    function setupExposedBindings()
    {
        $myBindings = parent::setupExposedBindings();
        $myBindings[] = new WFBindingSetup('maxLength', 'The maxLength of the password field (in HTML).');
        $myBindings[] = new WFBindingSetup('size', 'The size of the password field (in HTML).');
        return $myBindings;
    }

    function canPushValueBinding() { return true; }
}

?>
