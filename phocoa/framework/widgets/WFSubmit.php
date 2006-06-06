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
 * A Submit widget for our framework.
 *
 * Bindable Properties:
 *  label       The text value to display.
 */
class WFSubmit extends WFWidget
{
    /**
    * @var string The "action" that will be performed on the view when the button is clicked.
    */
    protected $action;
    /**
    * @var string The label to show.
    */
    protected $label;
    /**
     * @var string THe image path to use. By default empty. If non-empty, turns the submit into an image submit.
     */
    protected $imagePath;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->action = $id;
        $this->label = "Submit";
        $this->imagePath = NULL;
    }

    function setImagePath($path)
    {
        $this->imagePath = $path;
    }

    function setAction($action)
    {
        $this->action = $action;
    }

    function action()
    {
        return $this->action;
    }

    function setLabel($label)
    {
        $this->label = $label;
    }

    function label()
    {
        return $this->label;
    }

    function render($blockContent = NULL)
    {
        if ($this->hidden) return NULL;

        // get there reference to the named item
        // set the name / value
        // render
        return '<input type="' . ($this->imagePath ? 'image' : 'submit') . '"' .
                    ($this->imagePath ? ' src="' . $this->imagePath . '"' : '') .
                    ($this->class ? ' class="' . $this->class . '"' : '') .
                    ' id="' . $this->id() . '"' . 
                    ' name="action|' . $this->id() . '"' . 
                    ' value="' . $this->label() . '"' . 
                    $this->getJSActions() . 
                    '/>';
    }

    /********************* BINDINGS SETUP ************************/
    function setupExposedBindings()
    {
        $myBindings = parent::setupExposedBindings();
        $myBindings[] = new WFBindingSetup('label', 'The text of the submit button.');
        return $myBindings;
    }

    function canPushValueBinding() { return false; }
}

?>
