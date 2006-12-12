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
  * A wrapper for HTML Form Elements.
  *
  * Technical note: Since PHOCOA depends on knowing the button that was used to submit the form to call the correct action method, things can get tricky because
  * browsers will "guess" which button is the default button if a user presses RETURN/ENTER in a single-line field.
  *
  * Firefox and Safari seem to "guess" somehow which is the default button, even if there is more than one button. I am not sure how they decide which, but they do.
  *
  * IE on the other hand, does NOT select a default button, yet it still submits the form, WITHOUT ANY SUBMIT INFO. Thus this caused PHOCOA errors since PHOCOA
  * didn't call the expected action. Basically phocoa would have a submitted form, but then no action would be called. This is never supposed to happen, and caused
  * some phocoa modules to have unexpected results. 
  * 
  * To fix this, WFForm has a concept of a {@link WFForm::$defaultSubmitID defaultSubmitID}. This is the ID of the button that should be used as the default action. If your form
  * has exactly one submit button, the default button will be automatically selected. Otherwise, you'll need to specify it by configuring a defaultSubmitID for your
  * form. What this means in practice is that if you have a form with 2+ buttons, you really should set a {@link WFForm::$defaultSubmitID defaultSubmitID} to avoid
  * bugs in some browsers.
  */
class WFForm extends WFWidget
{
    /**
     * @const Use the GET HTTP method for submitting the form.
     */
    const METHOD_GET = 'get';
    /**
     * @const Use the POST HTTP method for submitting the form.
     */
    const METHOD_POST = 'post';

    /**
     * @var string The action attribute of the HTML form. By default, the current URI.
     */
    protected $action;
    /**
     * @var string The submit method to use. Default is METHOD_POST.
     * @see METHOD_POST, METHOD_GET
     */
    protected $method;
    /**
     * @var string The ID of the "default" button. This is the button that should be used as the action if no button information is submitted.
     */
    protected $defaultSubmitID;
    const CALCULATED_DEFAULT_SUBMIT_ID_NONE = NULL;
    const CALCULATED_DEFAULT_SUBMIT_ID_CANNOT_DETERMINE = -1;
    private $calculatedDefaultSubmitID;

    /**
      * Constructor.
      *
      * Sets up the smarty object for this module.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);

        if ($this->page->module()->invocation()->targetRootModule() and !$this->page->module()->invocation()->isRootInvocation())
        {
            // invocation path of "root" module
            $this->action = WWW_ROOT . '/' . $this->page->module()->invocation()->rootInvocation()->invocationPath();
        }
        else
        {
            // invocation path of the current page, with the current invocation paramenters (these can be overridden by form variables of the same name)
            $this->action = WWW_ROOT . '/' . $this->page->module()->invocation()->modulePath() . '/' . $this->page->pageName() . $this->page->module()->invocation()->parametersAsPathInfo();
        }

        $this->method = WFForm::METHOD_POST;
        $this->defaultSubmitID = $this->calculatedDefaultSubmitID = NULL;
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            'action',
            'method' => array(WFForm::METHOD_GET, WFForm::METHOD_POST),
            'defaultSubmitID',
            ));
    }

    function addChild(WFView $view)
    {
        parent::addChild($view);
        if ($view instanceof WFSubmit)
        {
            // if if the FIRST one, save it; otherwise, 
            if ($this->calculatedDefaultSubmitID === WFForm::CALCULATED_DEFAULT_SUBMIT_ID_NONE)
            {
                $this->calculatedDefaultSubmitID = $view->id();
            }
            else
            {
                $this->calculatedDefaultSubmitID = WFForm::CALCULATED_DEFAULT_SUBMIT_ID_CANNOT_DETERMINE;
                WFLog::log("Form id: '{$this->id}' is unable to determine the default button for the form. You should set one via defaultSubmitID to avoid errors in some browsers.", WFLog::WARN_LOG);
            }
        }
    }

    function allConfigFinishedLoading()
    {
        // calculate default submit button
        if (!$this->defaultSubmitID and $this->calculatedDefaultSubmitID and $this->calculatedDefaultSubmitID !== WFForm::CALCULATED_DEFAULT_SUBMIT_ID_CANNOT_DETERMINE)
        {
            $this->defaultSubmitID = $this->calculatedDefaultSubmitID;
        }
    }

    function defaultSubmitID()
    {
        return $this->defaultSubmitID;
    }

    function setAction($action)
    {
        $this->action = $action;
    }
    
    function setMethod($method)
    {
        $this->method = $method;
    }

    function render($blockContent = NULL)
    {
        $encType = '';
        if (strtolower($this->method) == WFForm::METHOD_POST)
        {
            $encType = 'enctype="multipart/form-data"';
        }
        return "\n" . '<form id="' . $this->id . '" action="' . $this->action . '" method="' . $this->method . '" ' . $encType . '>' .
               "\n" . '<input type="hidden" name="__modulePath" value="' . $this->page->module()->invocation()->modulePath() . '/' . $this->page->pageName() . '" />' .
               //"\n" . '<input type="hidden" name="__currentModule" value="' . $this->page->module()->invocation()->modulePath() . '" />' .
               //"\n" . '<input type="hidden" name="__currentPage" value="' . $this->page->pageName() . '" />' .
               "\n" . '<input type="hidden" name="__formName" value="' . $this->id . '" />' .
               "\n" . $blockContent .
               "\n</form>\n" .
               "\n";
    }

    function canPushValueBinding() { return false; }
}

?>
