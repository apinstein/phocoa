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
  *
  * WFForm features built-in CSRF protection for form submissions, ajax form submissions, and WFAppcelerator service requests.
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
     * @var string The target to submit to. Default is ''.
     */
    protected $target;

    /**
     * @var boolean TRUE to disable form submission via enter key. Default FALSE.
     */
    protected $disableEnterKeySubmit;

    /**
     * @var string The ID of the "default" button. This is the button that should be used as the action if no button information is submitted.
     */
    protected $defaultSubmitID;
    const CALCULATED_DEFAULT_SUBMIT_ID_NONE = NULL;
    const CALCULATED_DEFAULT_SUBMIT_ID_CANNOT_DETERMINE = -1;
    private $calculatedDefaultSubmitID;
    private $numberOfSubmitButtons;

    /**
     * @var array An array of form parameters that a phocoa form needs to be correctly processed. Subclasses may need access to these to ensure proper phocoa form compatibility.
     */
    protected $phocoaFormParameters;

    /**
     * @var boolean Set to true to turn this into an ajax-enabled form.
     */
    protected $isAjax;

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
        $this->target = '';
        $this->disableEnterKeySubmit = false;
        $this->defaultSubmitID = $this->calculatedDefaultSubmitID = NULL;
        $this->numberOfSubmitButtons = 0;

        $this->isAjax = false;

        // set up the extra form parameters we need to enable phocoa form detection and processing...
        $this->phocoaFormParameters = array();
        $this->phocoaFormParameters['__modulePath'] = $this->page->module()->invocation()->modulePath() . '/' . $this->page->pageName();
        $this->phocoaFormParameters['__formName'] = $this->id;
        // Calculate CSRF Protetion
        $csrfParams = WFForm::calculateCSRFParams();
        $this->phocoaFormParameters['instanceid'] = $csrfParams['instanceid'];
        $this->phocoaFormParameters['auth'] = $csrfParams['auth'];
    }

    public static function calculateCSRFParams()
    {
        $instanceid = rand();
        $auth = md5(session_id() . $instanceid);
        return array('instanceid' => $instanceid, 'auth' => $auth);
    }

    public function phocoaFormParameters()
    {
        return $this->phocoaFormParameters;
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            'action',
            'method' => array(WFForm::METHOD_GET, WFForm::METHOD_POST),
            'target' => array('', '_top', '_blank', '_parent'),
            'defaultSubmitID',
            ));
    }

    function addChild(WFView $view)
    {
        parent::addChild($view);
        if ($view instanceof WFSubmit)
        {
            $this->numberOfSubmitButtons++;
            // if if the FIRST one, save it; otherwise, 
            if ($this->calculatedDefaultSubmitID === WFForm::CALCULATED_DEFAULT_SUBMIT_ID_NONE)
            {
                $this->calculatedDefaultSubmitID = $view->id();
            }
            else if (!$this->defaultSubmitID)
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

        // ajax-enable submit buttons
        if ($this->isAjax())
        {
            foreach ($this->children() as $id => $widget) {
                if ($widget instanceof WFSubmit)
                {
                    // switch existing action over to an ajax action; needed to add a function for this since if we re-create the action here we remove any customized settings (ie the ACTION)
                    $widget->useAjax();
                    //$widget->setListener( new WFClickEvent(WFAction::AjaxAction()->setAction($id)) );
                }
            }
        }
    }

    function isAjax()
    {
        return $this->isAjax;
    }

    function setIsAjax($b)
    {
        $this->isAjax = $b;
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
        $target = '';
        if (! ($this->target == '') )
        {
            $target = 'target="' . $this->target . '"';
        }
        // DEFAULT submit button correction; the DEFAULT button is the submit button that is "pressed" when someone submits a form by hitting ENTER
        // DEFAULT button is not something that is supported in HTML. Each browser behaves differently on enter-to-submit.
        // Because of browser differences in submit buttons, the default button of a form needs lots of "cleanup" to make it work as expected in PHOCOA.
        // In IE, when a form is submitted via "enter key press", NO buttons are sent in the form data
        //   - We fix this by having a defaultSubmitID, and WFPage will automatically call the action method for this button
        // In Safari/FF, when a form is submitted via "enter key press", a button will be included in the form data, but it will be the FIRST submit button in the DOM. To fix this for these browsers, we simply put a COPY of the "Default" button at the beginning of the form. This way the "default" button as set up in defaultSubmitID will work as expected.
        // NOTE: For Safari, the rule is technically that Safari will submit the first button in the DOM that is actually RENDERED/VISIBLE.
        // Thus we can't use 'display: none' to hide the first button, but must use some somewhat ugly css so that Safari "renders" the button, but it is positioned so that it doesn't affect layout.
        // XHR Form submissions: YAHOO also submits the first form in the DOM, so the FF/Safari fix works great for XHR as well.
        // see WFSubmit::renderDefaultButton() for actual submit button HTML code
        $defaultFormButtonHTML = NULL;
        if ($this->defaultSubmitID and $this->numberOfSubmitButtons >= 2)
        {
            if (!isset($this->children[$this->defaultSubmitID])) throw( new WFException("The default button specified: '" . $this->defaultSubmitID . '" does not exist.') );
            $defaultFormButtonHTML = "\n" . $this->children[$this->defaultSubmitID]->renderDefaultButton();
        }
        $html =  "\n" . '<form id="' . $this->id . '" action="' . $this->action . '" method="' . $this->method . '" ' . $target . ' ' . $encType . '>';
        foreach ($this->phocoaFormParameters as $k => $v) {
            $html .= "\n" . '<input type="hidden" name="' . $k . '" value="' . $v . '" />';
        }
        $html .= 
               $defaultFormButtonHTML .
               "\n" . $blockContent .
               "\n</form>\n" .
               "\n";

        if ($this->disableEnterKeySubmit)
        {
            $html .= $this->jsStartHTML() . "Element.observe('{$this->id}', 'keypress', function(e) {
                            if (e.element().type === 'text' && (e.keyCode === Event.KEY_RETURN || e.keyCode === 3)) // safari enter not normalized
                            {
                                e.stop();
                                e.cancelBubble = true;
                            }
                        } );" . $this->jsEndHTML();
        }
        // if no defaultSubmitID is specified, then we should eat the event to prevent unexpected things from happening.
        if ($this->isAjax() and !$this->disableEnterKeySubmit)
        {
            $html .= $this->jsStartHTML() . "Element.observe('{$this->id}', 'keypress', function(e) {
                            if (e.element().type === 'text' && (e.keyCode === Event.KEY_RETURN || e.keyCode === 3)) // safari enter not normalized
                            {
                                e.stop();
                                " . ($this->defaultSubmitID ? "$('{$this->defaultSubmitID}').click();" : "e.cancelBubble = true;") . "
                            }
                        } );" . $this->jsEndHTML();
        }
        return $html;
    }

    function canPushValueBinding() { return false; }
}

?>
