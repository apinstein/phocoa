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
 * A Hidden widget for our framework.
 *
 * Typically WFHidden is used to include values to be used as parameters for a given page, as hidden parameters are automatically searched for "parameters" to be passed to your module during the _PageDidLoad() routine.
 *
 * NOTE: It is very important to note that WFHidden does NOT push values back to the bound objects by default.
 * Therefore, if you are using WFHidden to carry state from one page to another, you'll need to set canPushValueBinding to true or manually extract the form data.
 *
 * Optional:
 * - {@link WFHidden::$canPushValueBinding}
 */
class WFHidden extends WFWidget
{
    /**
     * @var boolean TRUE to make the binding 2-way. By default, it is read-only.
     */
    protected $canPushValueBinding;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->canPushValueBinding = false;
    }

    function restoreState()
    {
        //  must call super
        parent::restoreState();

        if (isset($_REQUEST[$this->name]))
        {
            $this->value = $_REQUEST[$this->name];
        }
    }
    /********************* BINDINGS SETUP ************************/
    function setupExposedBindings()
    {
        $myBindings = parent::setupExposedBindings();
        $myBindings[] = new WFBindingSetup('value', 'The value of the hidden field.');
        return $myBindings;
    }

    function render($blockContent = NULL)
    {
        return '<input type="hidden" id="' . $this->id . '" name="' . $this->name . '" value="' . htmlspecialchars($this->value) . '" />';
    }

    function canPushValueBinding() { return $this->canPushValueBinding; }
}

?>
