<?php
/**
 * This file contains all classes related to the RPC/Ajax/client-event-model that PHOCOA implements.
 */

/**
 * The WFRPC class encapsulates a remote procedure call from the client/UI layer.
 *
 * This is the lowest-level of RPC data and is only related to the remote call. 
 *
 * Higher level classes like WFAction are used by widgets to coordinate RPC calls.
 *
 * WFRPC is used mainly by WFAction or low-level programming on the client side that needs lower-level AJAX access, or needs to make an RPC outside of the event model.
 * 
 * Often custom ajax widgets like YUI TreeView or YUI Autocomplete will need to make calls to the server outside of the event model, and will use WFRPC (or the JS WFRPC)
 * to do that.
 *
 * Since the WFRPC mechanism is inherently PHOCOA-specific, it assumes all AJAX callbacks are done on the current module/page and provides wiring to integrate
 * AJAX as cleanly as possible with the standard PHOCOA page life cycle. If you don't want this tight coupling, you can always just roll your own AJAX stuff for
 * specific instances. You can even use {@link WFAction::JSAction()} to bootstrap this.
 *
 * WFRPC API: All WFRPC calls made from JS will ultimately call a php function with the following prototype:
 *
 * (object WFActionResponse) ajaxCallback($page, $params, [$userArg1, $userArg2, ... $userArgN]);
 *
 * Since the prototype is effectively the same as the standard PHOCOA page action callback, you can use the same function for both circumstances. 
 * 
 * Note that you can detect whether or not you're in an ajax callback via {@link WFRequestController::isAjax() WFRequestController::sharedRequestController()->isAjax()} call.
 *
 * Has fluent interface for configuration.
 *
 * @todo Why add WWW_ROOT to the invocationPath checks? is this from the form or something? seems like it's not an invocationPath if it starts with that... document/comment when you figure out why.
 */
class WFRPC extends WFObject
{
    const TARGET_PAGE = '#page#';
    const TARGET_MODULE = '#module#';

    // fire the action specified in the request
    const PARAM_ENABLE = '__phocoa_rpc_enable';
    const PARAM_INVOCATION_PATH = '__phocoa_rpc_invocationPath';
    const PARAM_TARGET = '__phocoa_rpc_target';
    const PARAM_ACTION = '__phocoa_rpc_action';
    const PARAM_RUNS_IF_INVALID = '__phocoa_rpc_runsIfInvalid';
    const PARAM_IS_AJAX = '__phocoa_is_ajax';
    // parameters for actions
    const PARAM_ARGC = '__phocoa_rpc_argc';
    const PARAM_ARGV_PREFIX = '__phocoa_rpc_argv_';
    // misc
    const PARAM_ENABLE_AJAX_IFRAME_RESPONSE_MODE = '__phocoa_rpc_ajax_iframe_mode';

    /**
     * @var string The module path of the module/page that is producing the WFRPC.
     */
    protected $invocationPath;
    /**
     *  @var string A specially formatted string specifying the target object to call the action method on.
     *       This string takes on one of two formats:
     *       - #page# to bind to the page delegate
     *       - #page#keyPath to bind to a page instance
     *       - #module# to bind to the module
     *       - #module#keyPath to bind to a shared instance
     */
    protected $target;
    /**
     *  @var string The method name to call on the target.
     */
    protected $action;
    /**
     *  @var mixed An array of arguments to pass on to the target/action method.
     */
    protected $args;

    /**
     * @var boolean Should the action be executed IFF the bindings validated? Default FALSE.
     */
    protected $runsIfInvalid;
    /**
     * @var object The WFForm object for the action. Actions with helpers are executed by submitting the form. Actions without helpers are submitted solely via URL and no form state is restored.
     */
    protected $form;
    /**
     * @var boolean TRUE if the RPC wants the response to be an "ajax" response as opposed to the normal result. 
     *              An "AJAX Response" is one where json or xml etc is requested by the client. A "Non-AJAX Response" is one where the client wants back a "web page".
     *              NOTE that this is *not* whether or not the request was made over an AJAX channel. For that, see {@link WFRequestController::isAjax()}
     */
    protected $isAjax;

    public function __construct()
    {
        $this->invocationPath = NULL;
        $this->target = NULL;
        $this->action = NULL;
        $this->args = array();

        $this->runsIfInvalid = false;
        $this->isAjax = false;
        $this->form = NULL;
    }

    /**
     * Convenience constructor function, suitable for fluent configuration: WFRPC::WFRPC()->setForm('myForm') etc.
     *
     * @return object WFRPC A new instance of a WFRPC object.
     */
    public static function RPC()
    {
        return new WFRPC();
    }

    public function setInvocationPath($mp)
    {
        $this->invocationPath = $mp;
        return $this;
    }

    public function invocationPath()
    {
        return $this->invocationPath;
    }

    public function url()
    {
        return WWW_ROOT . '/' . $this->invocationPath();
    }
    
    /**
     * Set the target for the rpc. See {@link WFRPC::$target}.
     *
     * @param string The target of the WFRPC call. This should point to an object.
     * @return object WFRPC The current RPC instance, for fluent configuration.
     */
    public function setTarget($target)
    {
        if (!is_string($target)) throw( new WFException('Target must be string in #page#keyPath or #module#keyPath form.') );
        $this->target = $target;
        return $this;
    }

    /**
     * Set the action for the rpc. See {@link WFRPC::$action}.
     *
     * @param string The name of the method to call on the target.
     * @return object WFRPC The current RPC instance, for fluent configuration.
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * Set the arguments to pass along with the RPC.
     *
     * @param array The array of arguments in positional order. These will be passed to the target/action starting with argument #3. The first two are $page, $params.
     * @return object WFRPC The current RPC instance, for fluent configuration.
     */
    function setArguments($args)
    {
        $this->args = $args;
        return $this;
    }

    /**
     * Set the form associated with the RPC. RPC's with forms will submit to the PHOCOA form-processing infrastructure.
     *
     * @param object WFForm The form to submit with the RPC.
     * @return object WFRPC The current RPC instance, for fluent configuration.
     */
    function setForm($form)
    {
        if ($form !== NULL and !($form instanceof WFForm)) throw( new WFException("setForm requires a WFForm object.") );
        $this->form = $form;
        return $this;
    }

    /**
     * Tell the RPC to use either AJAX mode or simply perform a page refresh to implement the action.
     *
     * @param boolean TRUE to use an AJAX RPC. Default: false.
     * @return object WFRPC The current RPC instance, for fluent configuration.
     */
    function setIsAjax($isAjax)
    {
        $this->isAjax = $isAjax;
        return $this;
    }

    /**
     * Control whether the RPC will run the action method if the validation fails.
     *
     * @param boolean TRUE to run even if validation fails. FALSE for normal behavior to just return errors if validation fails. DEFAULT: false.
     */
    function setRunsIfInvalid($bool)
    {
        if (gettype($bool) == 'string')
        {
            $this->runsIfInvalid = !(strtolower($bool) === 'false');
        }
        else
        {
            $this->runsIfInvalid = $bool;
        }
        return $this;
    }

    /**
     *  Get an array of all form parameters that need to be submitted to effect the RPC on the server when submitted.
     *
     *  Will include the php session id in the url params since often this is used by 3rd party clients like flash,java that don't have access to cookies.
     *
     *  @return array An associative array of the phocoa RPC parameters. Will include form submission as well if the RPC has a form.
     */
    function rpcAsParameters()
    {
        $params = array();
        $params[WFRPC::PARAM_ENABLE] = 1;
        $params[WFRPC::PARAM_INVOCATION_PATH] = $this->url();
        $params[WFRPC::PARAM_TARGET] = $this->target;
        $params[WFRPC::PARAM_ACTION] = $this->action;
        $params[WFRPC::PARAM_RUNS_IF_INVALID] = ($this->runsIfInvalid ? 'true' : 'false');
        $params[WFRPC::PARAM_IS_AJAX] = ($this->isAjax ? 'true' : 'false');
        $params[WFRPC::PARAM_ARGC] = 0;
        $params[session_name()] = session_id();
        if ($this->form)
        {
            $params = array_merge($params, $this->form->phocoaFormParameters());
        }
        return $params;
    }
    
    /**
     * Are we in AJAX mode?
     *
     * @return boolean TRUE if in ajax mode.
     */
    function isAjax()
    {
        return $this->isAjax;
    }

    function form()
    {
        return $this->form;
    }

    function target()
    {
        return $this->target;
    }

    function action()
    {
        return $this->action;
    }

    function runsIfInvalid()
    {
        return $this->runsIfInvalid;
    }

    /**
     * Execute the RPC. This function is called by the PHOCOA infrastructure to "execute" the RPC request in the PHOCOA infrastructure.
     *
     * If the target/action method returns a {@link WFActionResponse} object, the response will be sent and execution will stop.
     * If the target/action method returns NULL, execution will fall through to the caller.
     *
     * @param object WFPage The current page.
     * @return void
     */
    function execute($page)
    {
        $bcMode = false;
        // setup backwqrds-compatibility for really old-school
        if (!$page->delegate() and strncmp($this->target, WFRPC::TARGET_PAGE, strlen(WFRPC::TARGET_PAGE)) == 0)
        {
            $bcMode = true;
            $this->action = $page->pageName() . '_' . $this->action . '_Action';
            $this->target = '#module#';
        }

        // calculate target
        $matches = array();
        if (!preg_match('/^(#page#|#module#)([^\.]+)?(.*)/', $this->target, $matches)) throw( new WFException("Couldn't parse target: {$this->target}.") );
        list($targetStr, $pageOrModule, $outletId, $keyPath) = $matches;

        $targetObj = NULL;
        switch ($pageOrModule) {
            case WFRPC::TARGET_PAGE:
                $targetObj = $page;
                break;
            case WFRPC::TARGET_MODULE:
                $targetObj = $page->module();
                break;
            default:
                throw(new WFException("Couldn't parse target: {$this->target}."));
        }

        if ($outletId)
        {
            $targetObj = $targetObj->outlet($outletId);
        }
        else if ($targetObj instanceof WFPage && $this->action !== 'noAction')  // #page# targets the page DELEGATE unless action=noAction; then we piggyback on the page->noAction()
        {
            $targetObj = $targetObj->delegate();
        }

        if ($keyPath)
        {
            $targetObj = $targetObj->valueForKeyPath($keyPath);
        }
        
        $rpcCall = array($targetObj, $this->action);
        if (!is_callable($rpcCall))
        {
            if ($bcMode)
            {
                // old-school
                throw( new WFException("Backwards-compatibility mode WFRPC: action is not callable: " . $this->action ) );
            }
            else
            {
                // new school
                throw( new WFException("WFRPC Invocation is not callable: " . $this->target . "->" . $this->action . "(). Please ensure that there is a method of that name on the specified object.") );
            }
        }
        $result = call_user_func_array($rpcCall, array_merge( array($page, $page->parameters()), $this->args ));
        if ($result !== NULL)
        {
            if (!$this->isAjax()) throw( new WFException("Functions shouldn't return WFActionResponse objects if not in AJAX mode.") );

            // if the action method returned a WFActionResponse
            if (gettype($result) == 'string')
            {
                $result = new WFActionResponsePlain($result);
            }
            if (!($result instanceof WFActionResponse)) throw( new WFException("Unexpected WFActionResponse.") );
            $result->send();
        }
    }

    /**
     * Detects from the HTTP request whether or not there is an RPC request for the passed invocationPath.
     *
     * NOTE: Will only return the RPC if the RPC in the parameters is for the passed invocation path.
     * This allows PHOCOA to distinguish if an AJAX request is intended for the current module. Otherwise composited modules would all try to respond.
     *
     * @return object WFRPC
     * @throws object WFException
     */
    static public function rpcFromRequest($invocationPath)
    {
        if (!isset($_REQUEST[self::PARAM_ENABLE])) return NULL;

        if (!isset($_REQUEST[self::PARAM_INVOCATION_PATH])) throw( new WFException('rpc invocationPath missing.') );
        if (!isset($_REQUEST[self::PARAM_TARGET])) throw( new WFException('rpc target missing.') );
        if (!isset($_REQUEST[self::PARAM_ACTION])) throw( new WFException('rpc method missing.') );
        if (!isset($_REQUEST[self::PARAM_RUNS_IF_INVALID])) throw( new WFException('rpc runsIfInvalid missing.') );
        if (!isset($_REQUEST[self::PARAM_IS_AJAX])) throw( new WFException('rpc isAjax missing.') );
        if (!isset($_REQUEST[self::PARAM_ARGC])) throw( new WFException('rpc argc missing.') );

        // not sure why this is with WWW_ROOT....
        $invocationPathWithWWW = WWW_ROOT . '/' . $invocationPath;
        // invocationPath comes from PATH_INFO which has not been urldecode'd or rawurldecode'd
        // the PARAM_INVOCATION_PATH is in request, which php automatically urldecode'd and rawurldecode'd, but since our infrastructure *also* rawurlencode'd it,
        // for this test we need to urldecode and then rawurldecode the invocationPath, and rawurldecode the PARAM_INVOCATION_PATH
        if (rawurldecode(urldecode($invocationPathWithWWW)) !== rawurldecode($_REQUEST[self::PARAM_INVOCATION_PATH])) return NULL;

        $rpc = WFRPC::RPC();
        $rpc->setInvocationPath($_REQUEST[self::PARAM_INVOCATION_PATH]);
        $rpc->setTarget($_REQUEST[self::PARAM_TARGET]);
        $rpc->setAction($_REQUEST[self::PARAM_ACTION]);
        $rpc->setRunsIfInvalid($_REQUEST[self::PARAM_RUNS_IF_INVALID] === 'true');
        $rpc->setIsAjax($_REQUEST[self::PARAM_IS_AJAX] === 'true');

        // assemble arguments
        $args = array();
        for ($i = 0; $i < $_REQUEST[self::PARAM_ARGC]; $i++) {
            $reqVarName = self::PARAM_ARGV_PREFIX . $i;
            if (!isset($_REQUEST[$reqVarName])) throw( new WFException("Missing action argument {$i}") );
            $args[$i] = ($_REQUEST[$reqVarName] === WFModuleInvocation::PARAMETER_NULL_VALUE ? NULL : $_REQUEST[$reqVarName]);
        }
        $rpc->setArguments($args);
        return $rpc;
    }
}

/**
 * The WFEvent hierarchy maps to DOM events that PHOCOA can recognize and act on.
 */
class WFEvent extends WFObject
{
    protected $name;    // event name, ie 'click'
    protected $action;  // object WFAction
    protected $widget;

    public function __construct($action = NULL)
    {
        if (!$action)
        {
            $action = WFAction::JSAction();
        }
        if ( !($action instanceof WFAction) ) throw( new WFException("Must pass a WFAction to WFEvent constructor.") );
        $this->action = $action;
        $this->action->setEvent($this);

        $this->name = NULL;
        $this->widget = NULL;
    }

    public function name()
    {
        return $this->name;
    }
    public function setWidget($w)
    {
        if (! ($w instanceof WFView) ) throw( new WFException("Widget must be a WFView subclass.") );
        $this->widget = $w;
    }
    public function widget()
    {
        return $this->widget;
    }
    public function action()
    {
        return $this->action;
    }
    public static function factory($event, $action)
    {
        switch (strtolower($event)) {
            case 'click':
                return new WFClickEvent($action);
            case 'mousedown':
                return new WFMousedownEvent($action);
            case 'mouseup':
                return new WFMouseupEvent($action);
            case 'mouseover':
                return new WFMouseoverEvent($action);
            case 'mouseout':
                return new WFMouseoutEvent($action);
            case 'mousedown':
                return new WFMousedownEvent($action);
            case 'mouseup':
                return new WFMouseupEvent($action);
            case 'change':
                return new WFChangeEvent($action);
            case 'focus':
                return new WFFocusEvent($action);
            case 'blur':
                return new WFBlurEvent($action);
            default:
                throw( new WFException("Unknown event: " . $event) );
        }
    }
}

/**
 * Maps to the DOM "click" event.
 */
class WFClickEvent extends WFEvent
{
    public function __construct($action = NULL)
    {
        parent::__construct($action);
        $this->name = 'click';
    }
}
/**
 * Maps to the DOM "mousedown" event.
 */
class WFMousedownEvent extends WFEvent
{
    public function __construct($action = NULL)
    {
        parent::__construct($action);
        $this->name = 'mousedown';
    }
}
/**
 * Maps to the DOM "mouseup" event.
 */
class WFMouseupEvent extends WFEvent
{
    public function __construct($action = NULL)
    {
        parent::__construct($action);
        $this->name = 'mouseup';
    }
}
/**
 * Maps to the DOM "mouseover" event.
 */
class WFMouseoverEvent extends WFEvent
{
    public function __construct($action = NULL)
    {
        parent::__construct($action);
        $this->name = 'mouseover';
    }
}
/**
 * Maps to the DOM "mousemove" event.
 */
class WFMousemoveEvent extends WFEvent
{
    public function __construct($action = NULL)
    {
        parent::__construct($action);
        $this->name = 'mousemove';
    }
}
/**
 * Maps to the DOM "mouseout" event.
 */
class WFMouseoutEvent extends WFEvent
{
    public function __construct($action = NULL)
    {
        parent::__construct($action);
        $this->name = 'mouseout';
    }
}
/**
 * Maps to the DOM "change" event.
 */
class WFChangeEvent extends WFEvent
{
    public function __construct($action = NULL)
    {
        parent::__construct($action);
        $this->name = 'change';
    }
}
/**
 * Maps to the DOM "focus" event.
 */
class WFFocusEvent extends WFEvent
{
    public function __construct($action = NULL)
    {
        parent::__construct($action);
        $this->name = 'focus';
    }
}
/**
 * Maps to the DOM "blur" event.
 */
class WFBlurEvent extends WFEvent
{
    public function __construct($action = NULL)
    {
        parent::__construct($action);
        $this->name = 'blur';
    }
}


/**
 * WFAction represents an action that is called when a WFEvent occurs on the client.
 *
 * WFAction offers 3 action types in response to an event:
 * - JSAction - perform a JS function. By default JSActions do NOT stop the default event processing. Use WFAction.stopEvent(event) to do this.
 * - ServerAction - cause a page refresh, and effect an action on the server during that refresh. Uses an RPC.
 * - AjaxAction - Effect an action on the server via an AJAX callback. Uses an RPC.
 *
 * WFAction sports a fluent interface for easy configuration.
 *
 * When a ServerAction or AjaxAction is executed on the server, the php function specified in target/action should have the following prototype:
 *
 * (object WFActionResponse) ajaxCallback($page, $params, $senderId, $eventName, [$userArg1, $userArg2, ... $userArgN]);
 *
 * This prototype allows your event-driver rpc callbacks to operate the same as the standard form/action methods, but take in additional parameters:
 * - $senderId - The string ID of the widget that triggered the event
 * - $eventName - The string name of the event that was triggered, i.e., "click".
 * - custom arguments
 *
 * Use {@link WFAction::JSAction()}, {@link WFAction::ServerAction()}, or {@link WFAction::AjaxAction()} to create a WFAction.
 */
class WFAction extends WFObject
{
    /**
     * @var object WFRPC The {@link WFRPC} object used by ServerAction and AjaxAction.
     */
    protected $rpc;

    /**
     * @var object WFEvent A reference to the {@link WFEvent} that will trigger this action.
     */
    protected $event;

    /**
     * @var boolean TRUE to stop the javascript event default behavior, FALSE to let the event flow through. Default TRUE.
     */
    protected $stopsEvent;

    protected $jsEventHandler;

    public function __construct()
    {
        $this->rpc = NULL;
        $this->event = NULL;
        $this->jsEventHandler = NULL;
        $this->stopsEvent = true;
    }

    /**
     * Whether or not the triggering javascript event should be stopped.
     *
     * @param boolean
     */
    public function setStopEvent($stopsEvent)
    {
        $this->stopsEvent = $stopsEvent;
    }

    /**
     * Link the {@link WFEvent} to this action.
     *
     * @param object WFEvent
     */
    public function setEvent($e)
    {
        $this->event = $e;
    }

    /**
     * Set the rpc target for this action. This passes through to the RPC.
     *
     * @param string The {@link WFRPC::setTarget target} for this action.
     * @return object WFAction The current WFAction instance, for fluent configuration.
     */
    public function setTarget($t)
    {
        $this->rpc()->setTarget($t);
        return $this;
    }

    /**
     * Set the rpc action for this WFAction. This passes through to the RPC.
     *
     * @param string The {@link WFRPC::setAction action} for this WFAction.
     * @return object WFAction The current WFAction instance, for fluent configuration.
     */
    public function setAction($a)
    {
        $this->rpc()->setAction($a);
        return $this;
    }

    /**
     * Set the rpc form for this WFAction. This passes through to the RPC.
     *
     * @param mixed A string ID of the form, or a WFForm instance.
     * @return object WFAction The current WFAction instance, for fluent configuration.
     */
    public function setForm($f)
    {
        $this->rpc()->setForm($f);
        return $this;
    }

    public function setJsEventHandler($js)
    {
        $this->jsEventHandler = $js;
    }

    /**
     * Add an internal RPC object for ServerAction and AjaxAction.
     *
     * @param boolean Is this an ajax RPC?
     * @return object WFAction The current WFAction instance, for fluent configuration.
     */
    protected function addRPC($isAjax = false)
    {
        if (!$this->rpc)
        {
            $this->rpc = WFRPC::RPC();
            $this->rpc->setTarget(WFRPC::TARGET_PAGE);
            $this->rpc->setIsAjax($isAjax);
        }
        return $this;
    }

    public function rpc()
    {
        return $this->rpc;
    }

    public function event()
    {
        return $this->event;
    }

    /**
     * Get the JS code needed to set up the action.
     * @return string The JS code which will boostrap the event/action linkage for this WFAction.
     */
    public function jsSetup()
    {
        // sanity check things

        $script = "function() {
                PHOCOA.namespace('widgets." . $this->event()->widget()->id() . ".events." . $this->event()->name() . "');
                var action = new PHOCOA.WFAction('" . $this->event()->widget()->id() . "', '" . $this->event()->name() . "');
                action.stopsEvent = " . ($this->stopsEvent ? 'true' : 'false') . ";
                action.callback = " . $this->jsEventHandler() . ";
            ";
        if ($this->rpc)
        {
            // sanity check things
            if (!$this->rpc->target()) throw( new WFException("AjaxAction requires a target.") );
            if (!$this->rpc->action())
            {
                // we use a default action name...
                $actionName = $this->event()->widget()->id() . "Handle" . ucfirst($this->event()->name());
                $this->rpc->setAction($actionName);
            }

            // calculate invocationPath, should vary based on FORM setting
            $invocationPath = WWW_ROOT . '/' . $this->event()->widget()->page()->module()->invocation()->invocationPath();
            $script .= "
                action.rpc = new PHOCOA.WFRPC('" . $invocationPath . "',
                                              '" . $this->rpc->target() . "',
                                              '" . $this->rpc->action() . "');
                if (" . $this->jsAjaxSuccess() . ")
                {
                    action.rpc.callback.success = " . $this->jsAjaxSuccess() . ";
                }
                if (" . $this->jsAjaxError() . ")
                {
                    action.rpc.callback.failure = " . $this->jsAjaxError() . ";
                }
                ";
            
            if ($this->event()->widget() instanceof WFSubmit)
            {
                $script .= "
                action.rpc.submitButton = '" . $this->event()->widget()->id() . "';
                ";
            }

            // set up form, if not set already
            if ($this->rpc() && !$this->rpc()->form())
            {
                $this->rpc()->setForm($this->event()->widget()->getForm());
            }
            $script .= "
                action.rpc.form = " .  ( $this->rpc->form() ? "'" . $this->rpc->form()->id() . "'" : 'null' ) . ";
                action.rpc.isAjax = " . ( $this->rpc->isAjax() ? 'true' : 'false') . ";
                action.rpc.runsIfInvalid = " . ( $this->rpc->runsIfInvalid() ? 'true' : 'false') . ";
                     ";
        }
        $script .= "}";
        return "({$script})();";
    }

    /**
     * The name of the JS event handler function that's called on event firing.
     *
     * The JS function PHOCOA.widgets.<id>.events.<eventName>.handleEvent(args) is called when the event fires.
     *
     * @return string
     */
    public function jsEventHandler()
    {
        if ($this->jsEventHandler !== NULL)
        {
            return $this->jsEventHandler;
        }
        else
        {
            return "PHOCOA.widgets." . $this->event()->widget()->id() . ".events." . $this->event()->name() . ".handleEvent";
        }
    }

    /**
     * The name of the JS event handler function that's called to collect the arguments to pass to the event handler.
     *
     * The JS function PHOCOA.widgets.<id>.events.<eventName>.collectArguments() is called to get an array of arguments to pass to handleEvent.
     *
     * Ultimately these arguments are passed back to the server.
     *
     * @return string
     */
    public function jsCollectArguments()
    {
        return "PHOCOA.widgets." . $this->event()->widget()->id() . ".events." . $this->event()->name() . ".collectArguments";
    }

    /**
     * The name of the JS event handler function that's called when an AJAX RPC succeeds.
     *
     * The JS function PHOCOA.widgets.<id>.events.<eventName>.ajaxSuccess() is called for AjaxAction when the ajax call completes successfully.
     *
     * @return string
     */
    public function jsAjaxSuccess()
    {
        return "PHOCOA.widgets." . $this->event()->widget()->id() . ".events." . $this->event()->name() . ".ajaxSuccess";
    }

    /**
     * The name of the JS event handler function that's called if an AJAX RPC fails.
     *
     * The JS function PHOCOA.widgets.<id>.events.<eventName>.ajaxError() is called if the AjaxAction fails.
     *
     * @return string
     */
    public function jsAjaxError()
    {
        return "PHOCOA.widgets." . $this->event()->widget()->id() . ".events." . $this->event()->name() . ".ajaxError";
    }

    /**
     * Create a new JSAction to reponsd to an event.
     * 
     * JSAction simply calls a Javascript function in response to an event.
     *
     * @return object WFAction
     */
    public static function JSAction()
    {
        $a = new WFAction();
        $a->setStopEvent(false);
        return $a;
    }
    /**
     * Create a new ServerAction to reponsd to an event.
     * 
     * Call an action on the server, done via complete page refresh.
     *
     * @return object WFAction
     */
    public static function ServerAction()
    {
        $a = new WFAction();
        $a->addRPC(false);
        return $a;
    }
    /**
     * Create a new AjaxAction to reponsd to an event.
     * 
     * Call an action on the server, done via XHR.
     *
     * @return object WFAction
     */
    public static function AjaxAction()
    {
        $a = new WFAction();
        $a->addRPC(true);
        return $a;
    }
}

/**
 * The WFActionResponse hierarchy encapsulates possible responses to Ajax actions.
 *
 * Different subclasses deal with commonly used response object types, such as JSON, Plain Text, XML, and a custom UI updater for PHOCOA.
 *
 * WFActionResponse is an abstract base class.
 */
abstract class WFActionResponse extends WFObject
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }
    public function data()
    {
        return $this->data;
    }
    public function send()
    {
        header('Content-Type: ' . $this->contentType());
        print $this->data();
        exit;
    }
    abstract public function contentType();
}

/**
 * A plain-text repsonse.
 */
class WFActionResponsePlain extends WFActionResponse
{
    public function contentType()
    {
        return 'text/plain';
    }
}

/**
 * A JSON response.
 */
class WFActionResponseJSON extends WFActionResponse
{
    public function __construct($data, $dataNeedsEncoding = true)
    {
        if ($dataNeedsEncoding)
        {
            $data = WFJSON::json_encode($data);
        }
        parent::__construct($data);
    }
    public function contentType()
    {
        return 'application/x-json';
    }
}

/**
 * An XML response.
 */
class WFActionResponseXML extends WFActionResponse
{
    public function contentType()
    {
        return 'text/xml';
    }
}

/**
 * A special XML response type that allows the caller to easily effect UI changes on the client in response to an ajax action.
 *
 * Offers a fluent interface. Use the {@link WFActionResponsePhocoaUIUpdater::WFActionResponsePhocoaUIUpdater()} static constructor to easily craft a response via fluent inteface.
 *
 */
class WFActionResponsePhocoaUIUpdater extends WFActionResponse
{
    public function __construct()
    {
        $this->data = array();
    }

    /**
     * Fluent constructor.
     *
     * @return object WFActionResponsePhocoaUIUpdater
     */
    public static function WFActionResponsePhocoaUIUpdater()
    {
        return new WFActionResponsePhocoaUIUpdater;
    }

    /**
     * Add an HTML update to be sent to the client.
     *
     * HTML Updates will replace the innerHTML of the specified element id with the passed HTML.
     *
     * @return object WFActionResponsePhocoaUIUpdater The current instance, for fluent configuration.
     */
    public function addUpdateHTML($id, $html)
    {
        $this->data['update'][$id] = $html;
        return $this;
    }

    /**
     * Add an HTML replace to be sent to the client.
     *
     * HTML Replaces will replace the element id with the passed HTML.
     *
     * @return object WFActionResponsePhocoaUIUpdater The current instance, for fluent configuration.
     */
    public function addReplaceHTML($id, $html)
    {
        $this->data['replace'][$id] = $html;
        return $this;
    }

    /**
     * Add a snippet of javascript code to be sent to the client.
     *
     * Script blocks will be executed on the client.
     *
     * @return object WFActionResponsePhocoaUIUpdater The current instance, for fluent configuration.
     */
    public function addRunScript($script)
    {
        $this->data['run'][] = $script;
        return $this;
    }

    public function contentType()
    {
        return 'application/x-json-phocoa-ui-updates';
    }

    public function data()
    {
        return WFJSON::json_encode($this->data);
    }
}

?>
