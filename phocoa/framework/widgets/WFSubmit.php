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
 * - {@link WFLink::$postSubmitLabel postSubmitLabel} The label for the button that will be shown after the button is clicked. Requires JS. This does NOT prevent duplicate submission.
 * - {@link WFLink::$duplicateSubmitMessage duplicateSubmitMessage} The message that will be displayed if the submit button is pressed more than once. This also prevents duplicate submission. NOTE: with AJAX forms, you cannot prevent duplicate submission.
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
     * @var object WFClickEvent The event fired when the button is submitted. Default action is to call the method named "id" of the submit with ($page, $params).
     */
    private $submitEvent;
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
        if (strtolower($id) === 'submit')
        {
            throw( new WFException("WFSubmit cannot have an ID of submit. It causes problems with javascript.") );
        }
        parent::__construct($id, $page);
        $this->submitEvent = new WFClickEvent( WFAction::ServerAction() );
        $this->setListener( $this->submitEvent );
        $this->setAction($id);
        $this->label = "Submit";
        $this->imagePath = NULL;
        $this->duplicateSubmitMessage = NULL;
        $this->postSubmitLabel = NULL;
    }

    function allConfigFinishedLoading()
    {
        $this->submitEvent->action()->setForm($this->parent());
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            'action',
            'label',
            'imagePath',
            'duplicateSubmitMessage',
            'postSubmitLabel',
            ));
    }

    function setImagePath($path)
    {
        $this->imagePath = $path;
    }

    function submitAction()
    {
        return $this->submitEvent->action();
    }

    function setAction($action)
    {
        $this->submitEvent->action()->setAction($action);
    }
    function setTarget($target)
    {
        $this->submitEvent->action()->setTarget($target);
    }

    /**
     * Switch the submit widget to using AJAX for submission.
     *
     * This function is really only here so that we can preserve the action/rpc setup when WFForm isAjax is true.
     * Previously, WFForm would just re-create the click event, but that created a bug where any customization of the action was lost. Particuarly, custom "action" methods were getting blown away.
     */
    function useAjax()
    {
        $this->submitEvent->action()->rpc()->setIsAjax(true);
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

        // get there reference to the named item
        // set the name / value
        // render
        $html = '<input type="' . ($this->imagePath ? 'image' : 'submit') . '"' .
                    ($this->imagePath ? ' src="' . $this->imagePath . '"' : '') .
                    ($this->class ? ' class="' . $this->class . '"' : '') .
                    ' id="' . $this->id() . '"' . 
                    ' name="action|' . $this->id() . '"' . 
                    ' value="' . $this->label() . '"' . 
                    $this->getJSActions() . 
                    "/>\n" . 
                    $this->jsStartHTML() . $this->getListenerJS() . $this->jsEndHTML();

        if ($this->duplicateSubmitMessage or $this->postSubmitLabel)
        {
            $duplicateSubmitMessage = ($this->duplicateSubmitMessage ? $this->duplicateSubmitMessage : '');
            $postSubmitLabel = ($this->postSubmitLabel ? $this->postSubmitLabel : '');
            $html .= $this->jsStartHTML() . "
            PHOCOA.namespace('widgets.{$this->id}');
            PHOCOA.widgets.{$this->id}.isSubmitted = false;
            $('{$this->id}').observe('click', function(e) {
                var duplicateSubmitMessage = " .  WFJSON::json_encode($duplicateSubmitMessage) . ";
                var postSubmitLabel = " .  WFJSON::json_encode($postSubmitLabel) . ";
                if (PHOCOA.widgets.{$this->id}.isSubmitted && duplicateSubmitMessage)
                {
                    alert(duplicateSubmitMessage);
                    e.stop();
                }
                if (postSubmitLabel)
                {
                    $('{$this->id}').setValue(postSubmitLabel);
                }
                PHOCOA.widgets.{$this->id}.isSubmitted = true;
            });
            " . $this->jsEndHTML();
        }

        return $html;
    }

    /**
     * Renders the button as hidden (for use with {@link WFForm::$defaultSubmitID defaultSubmitID}).
     *
     * @internal Some browsers by default submit the "first" button they see if ENTER is pressed in a text field. Thus we need to 
     *           insert an invisible "fake" version of the default button to ensure consistent cross-browser operation of defaultSubmitID.
     *           The widget must be both display: block and visibility: visible to ensure proper operation in all browsers.
     *           IIRC Safari requires visbility and IE requires display. FF is cool... The 0px is needed on the input as well to make it invisible on IE7.
     * @return string The HTML of the button's core attrs with no ID.
     */
    function renderDefaultButton()
    {
        return '<div style="position: relative; height: 0px; width: 0px;">
                <input type="submit"' .
                    ' name="action|' . $this->id() . '"' . 
                    ' value="' . $this->label() . '"' . 
                    ' style="position: absolute; left: -20000px; width: 0px;"' .
                    ' />
                </div>';
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
