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
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * - {@link WFLink::$label label} The label for the button.
 * - {@link WFLink::$postSubmitLabel postSubmitLabel} The label for the button that will be shown after the button is clicked. Requires JS.
 * - {@link WFLink::$duplicateSubmitMessage duplicateSubmitMessage} The message that will be displayed if the submit button is pressed more than once. This also prevents duplicate submission.
 * 
 * <b>Optional:</b><br>
 * - {@link WFLink::$class class} The class of the <a> tag.
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
     * @var string The message to display if someone presses the button more than once.
     */
    protected $duplicateSubmitMessage;
    /**
     * @var string The new text for the button once submit is pressed.
     */
    protected $postSubmitLabel;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->action = $id;
        $this->label = "Submit";
        $this->imagePath = NULL;
        $this->duplicateSubmitMessage = NULL;
        $this->postSubmitLabel = NULL;
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

    function setDuplicateMessage($str)
    {
        $this->duplicateSubmitMessage = $str;
    }

    function render($blockContent = NULL)
    {
        if ($this->hidden) return NULL;

        // onClick function
        if ($this->duplicateSubmitMessage or $this->postSubmitLabel)
        {
            $duplicateSubmitMessage = ($this->duplicateSubmitMessage ? $this->duplicateSubmitMessage : '');
            $postSubmitLabel = ($this->postSubmitLabel ? $this->postSubmitLabel : '');
            $onClickFunc = "
            <script type=\"text/javascript\">
            var {$this->id}_isSubmitted = false;
            function {$this->id}_onClick_handler()
            {
                duplicateSubmitMessage = '{$duplicateSubmitMessage}';
                postSubmitLabel = '{$postSubmitLabel}';
                btn = document.getElementById('{$this->id}');
                if ({$this->id}_isSubmitted && duplicateSubmitMessage)
                {
                    alert(duplicateSubmitMessage);
                    return false;
                }
                if (postSubmitLabel)
                {
                    btn.value = postSubmitLabel;
                }
                {$this->id}_isSubmitted = true;
                return true;
            }
            </script>
            ";
            $this->page()->module()->invocation()->rootSkin()->addHeadString($onClickFunc);
            $this->setJSonClick("return {$this->id}_onClick_handler();");
        }

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
