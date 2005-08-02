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
 * Includes
 */
require_once('framework/widgets/WFWidget.php');

/**
 * A Password widget for our framework.
 */
class WFPassword extends WFWidget
{
    protected $maxLength;
    protected $size;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->maxLength = NULL;
        $this->size = NULL;
        $this->enabled = true;
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

        return '<input type="password" name="' . $this->valueForKey('name') . '" value=""' .
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
