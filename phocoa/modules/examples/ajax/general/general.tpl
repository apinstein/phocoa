{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}

<h1>PHOCOA AJAX Infrastructure Demo</h1>
<h2>AJAX Capabilities</h2>
<p>PHOCOA's integrated AJAX capabilities allow you to seamlessly tap into the DOM event model using standard PHOCOA programming models.</p>
<p>For any DOM event, you can configure a PHOCOA widget to:
<ul>
    <li>Call a javascript function (a JSAction)</li>
    <li>Execute a method on the server via a full page refresh (ServerAction)</li>
    <li>Execute a method on the server via AJAX, and have that method send data to the client, or effect UI updates (AjaxAction)</li>
</ul>
</p>

<p>All of this functionality uses the same PHOCOA programming model as standard form/action programming, and requires very little effort to set up, making adding AJAX functionality to your app very easy.</p>
<p>PHOCOA also includes many YUI widgets that have AJAX capabilities, such as AutoComplete and TreeView, that have been plugged in nicely to PHOCOA for easy use and require even less setup.</p>

<h2>PHOCOA/Javascript Programming Model</h2>
<p>PHOCOA's Javascript integration follows a few basic principles.
<ul>
    <li>Anyting worth responding to in the DOM will have a DOM id.</li>
    <li>PHOCOA uses a delegation paradigm to let you easily attach functions to the DOM event model.
        <ul>
            <li>PHOCOA.widgets.domID.events.eventName.collectArguments() is called when the event you're listening to fires, to give you a chance to tell the infrastructure what arguments to pass to your Javascript handler.</li>
            <li>PHOCOA.widgets.domID.events.eventName.handleEvent(args) is called when the event you're listening to fires, with the arguments from collectArguments().</li>
            <li>PHOCOA.widgets.domID.events.eventName.ajaxSuccess() is called if an AJAX RPC occurred, and succeeded.</li>
            <li>PHOCOA.widgets.domID.events.eventName.ajaxError() is called if an AJAX RPC occurred, and failed.</li>
        </ul>
    </li>
</ul>
</p>

<h2>Examples</h2>
<h3>JSAction - call a Javascript function when an event fires</h3>
{literal}
<script>
PHOCOA.namespace('widgets.localAction.events.click');
PHOCOA.widgets.localAction.events.click.collectArguments = function() { return ['myArg1', 'myArg2']; };
PHOCOA.widgets.localAction.events.click.handleEvent = function(e, myArg1, myArg2) {
    alert("I've been clicked!\nThe first argument to the callback is the event: " + e + "\nFollowed by all arguments from collectArguments(): " + myArg1 + ", " + myArg2); 
};
</script>
{/literal}
<p>{WFView id="localAction"}</p>
<p>The code to set this up is simple. In PHP, you simply attach an event/action to the WFLink object:
<blockquote><pre>$page->outlet('localAction')->setListener( new WFClickEvent() );</pre></blockquote>
And, in Javascript, set up the delegate functions:
<blockquote>
{literal}
<pre>
PHOCOA.namespace('widgets.localAction.events.click');
PHOCOA.widgets.localAction.events.click.collectArguments = function() { return ['myArg1', 'myArg2']; };
PHOCOA.widgets.localAction.events.click.handleEvent = function(e, myArg1, myArg2) {
    alert("I've been clicked!\nThe first argument to the callback is the event: "
    + e + "\nFollowed by all arguments from collectArguments(): " + myArg1 + ", " + myArg2);
};
</pre>
{/literal}
</blockquote>
</p>

<h3>AjaxAction - make an AJAX call when an event fires</h3>
<p>{WFView id="rpcPageDelegate"} <span id="ajaxTarget">The server will stick a random number in this space when you click the link above.</span></p>
<p>The code for this is also trivially simple. In PHP, you simply attach the event/action to the WFLink object:
<blockquote><pre>$page->outlet('rpcPageDelegate')->setListener( new WFClickEvent( WFAction::ServerAction() ) );</pre></blockquote>
Then, you define the PHP callback function for the event. For simplicity, the default callback name (the rpc action) is &lt;widgetId&gt;Handle&lt;EventName&gt;:
{literal}
<blockquote><pre>
function rpcPageDelegateHandleClick($page, $params, $senderId, $eventName)
{
    return WFActionResponsePhocoaUIUpdater::WFActionResponsePhocoaUIUpdater()
        ->addUpdateHTML('ajaxTarget', 'I am the server and this is my random number: ' . rand());
}
</pre></blockquote>
{/literal}
</p>
<p>In this case, the function name is rpcPageDelegateHandleClick. Notice the arguments passed to the PHP callback. All AjaxAction callbacks use this prototype. You can also pass back additional arguments, which will be appended to the parameters following $eventName.</p>
<p>To effect the UI updates on the server, this callback returns a WFActionResponsePhocoaUIUpdater object. This object has addUpdateHTML(), addReplaceHTML(), and addRunScript() methods that make it easy for you to update the innerHTML of any element, replace any element, and run Javascript code in response to an AjaxAction.</p>

<h3>AJAX Events</h3>
<p>The blocks below demonstrate using PHOCOA-AJAX integrations on various UI widgets and all supported DOM events:

<script>
{literal}
PHOCOA.namespace('widgets.eventClick.events.click');
PHOCOA.widgets.eventClick.events.click.handleEvent = function() { alert('Click triggered'); };

PHOCOA.namespace('widgets.eventMouseover.events.mouseover');
PHOCOA.widgets.eventMouseover.events.mouseover.handleEvent = function() { alert('Mouseover triggered'); };

PHOCOA.namespace('widgets.eventMouseout.events.mouseout');
PHOCOA.widgets.eventMouseout.events.mouseout.handleEvent = function() { alert('Mouseout triggered'); };

PHOCOA.namespace('widgets.eventMousedown.events.mousedown');
PHOCOA.widgets.eventMousedown.events.mousedown.handleEvent = function() { alert('Mousedown triggered'); };

PHOCOA.namespace('widgets.eventMouseup.events.mouseup');
PHOCOA.widgets.eventMouseup.events.mouseup.handleEvent = function() { alert('Mouseup triggered'); };

PHOCOA.namespace('widgets.eventChange.events.change');
PHOCOA.widgets.eventChange.events.change.handleEvent = function() { alert('Change triggered'); };

PHOCOA.namespace('widgets.eventFocus.events.focus');
PHOCOA.widgets.eventFocus.events.focus.handleEvent = function() { alert('Focus triggered'); };

PHOCOA.namespace('widgets.eventBlur.events.blur');
PHOCOA.widgets.eventBlur.events.blur.handleEvent = function() { alert('Blur triggered'); };

{/literal}
</script>

{WFForm id="eventForm"}
<ul>
    <li>{WFView id="eventClick"}</li>
    <li>{WFView id="eventMouseover"}</li>
    <li>{WFView id="eventMouseout"}</li>
    <li>{WFView id="eventMousedown"}</li>
    <li>{WFView id="eventMouseup"}</li>
    <li>Change: {WFView id="eventChange"}</li>
    <li>Focus: {WFView id="eventFocus"}</li>
    <li>Blur: {WFView id="eventBlur"}</li>
</ul>
{/WFForm}
</p>

<h3>Form Integration</h3>

<p>The PHOCOA programming model for form submission is extended with our Ajax integration. Everything works the same way, except that you can return WFActionResponse objects to effect changes on the server. Even PHOCOA's validation infrastructure works with Ajax.</p>
<p>Below is an example form with two fields. The first field requires any string but "bad" and the second field requires any string but "worse". If there are no errors, the two strings will be interpolated into a single response and updated in the UI.</p>
<p>The submit button is a normal form submit. The link will submit the form via Ajax.</p>

{literal}
<script>
PHOCOA.namespace('widgets.ajaxFormSubmitAjax.events.click');
PHOCOA.widgets.ajaxFormSubmitAjax.events.click.handleEvent = function(e) {
    $('ajaxFormResult').update();
};
</script>
{/literal}

{WFShowErrors id="ajaxForm"}<br />
{WFForm id="ajaxForm"}
    Enter some text: {WFView id="textField"}<br />
    <em>type 'bad' to trigger an error</em><br />
    {WFShowErrors id="textField"}<br />
    <br />
    Enter some other text: {WFView id="textField2"}<br />
    <em>type 'worse' to trigger an error</em><br />
    {WFShowErrors id="textField2"}<br />
    {WFView id="ajaxFormSubmitNormal"} {WFView id="ajaxFormSubmitAjax"}
    <br />
{/WFForm}
<div id="ajaxFormResult">{$formResult}</div>

{literal}
<p>Once again, the code to build this Ajax functionality is quite simple.</p>
<p>In PHP, we set up our link to trigger the form submission:
<blockquote><pre>
$page->outlet('ajaxFormSubmitAjax')
     ->setListener( new WFClickEvent( WFAction::AjaxAction()
                                        ->setForm('ajaxForm')
                                        ->setAction('ajaxFormSubmitNormal') 
                                     )
                   );
</pre></blockquote>
We also implement our ajaxFormSubmitNormal action handler, which in this example, we use for both normal and ajax form submission:
<blockquote><pre>
function ajaxFormSubmitNormal($page, $params, $senderId, $eventName)
{
    $result = 'You said: "' . $page->outlet('textField')->value() . '" and "' . $page->outlet('textField2')->value() . '".';
    if (WFRequestController::sharedRequestController()->isAjax())
    {
        return WFActionResponsePhocoaUIUpdater::WFActionResponsePhocoaUIUpdater()
            ->addUpdateHTML('ajaxFormResult', $result);
    }
    else
    {
        $page->assign('formResult', $result);
    }
}
</pre></blockquote>
When combined with the template code:
<blockquote><pre>
&lt;div id="ajaxFormResult"&gt;{$formResult}&lt;/div&gt;
</pre></blockquote>
The proper result is displayed either by Ajax or by traditional template programming.
</p>
<p>In Javascript, we have an eventHandler to "remove" our "result" when the request is submitted. This prevents previous results from showing if there is an error with the current submission.
<blockquote><pre>
PHOCOA.namespace('widgets.ajaxFormSubmitAjax.events.click');
PHOCOA.widgets.ajaxFormSubmitAjax.events.click.handleEvent = function(e) {
    $('ajaxFormResult').update();
};
</pre></blockquote>
{/literal}
