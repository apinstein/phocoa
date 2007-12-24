{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}

<h1>PHOCOA AJAX Integration</h1>
<h2>Core Capabilities</h2>
<p>PHOCOA's integrated AJAX infrastructure makes adding dynamic Javascript and AJAX features to your application easy.</p>
<p>For any DOM event, you can configure a PHOCOA widget to:
<ul>
    <li>Call a javascript function (a JSAction)</li>
    <li>Execute a method on the server via a full page refresh (ServerAction)</li>
    <li>Execute a method on the server via AJAX, and have that method send data to the client, or effect UI updates (AjaxAction)</li>
</ul>
</p>

<p>All of this functionality uses the same PHOCOA programming model as standard form/action programming, and requires very little effort to set up.</p>
<p>PHOCOA also includes many YUI widgets that have AJAX capabilities, such as AutoComplete, TreeView, and PhocoaDialog (an AJAX-loading YUI Dialog) that have been plugged in nicely to PHOCOA for easy use and require even less setup. All you have to do is supply a PHP callback to provide dynamically loaded data. No Javascript code is required at all.</p>

<h2>AJAX Integration Basics</h2>
<p>At the highest level, PHOCOA provides an "onEvent" property for all classes in the WFView hierarchy that is used to attach Javascript behaviors to your application. Since the onEvent interface takes in a string as a parameter, you can configure AJAX behaviors via YAML with no PHP coding. If you need more complex behavior, you can always use the PHP API, but 95% of the time you'll find that onEvent works perfectly.</p>
<p>The basic syntax is:</p>
<blockquote>onEvent: &lt;eventName&gt; do &lt;typeOfAction&gt;[:&lt;target&gt;][:&lt;action&gt;]</blockquote>
<ul>
    <li><strong>eventName</strong> - The event name to listen for, i.e., <em>click</em>, <em>change</em>, <em>blur</em>, etc.</li>
    <li><strong>typeOfAction</strong> - <em>j</em> for JSAction, <em>s</em> for ServerAction, or <em>a</em> for AjaxAction.</li>
    <li><strong>target</strong> - The <em>target</em> setting used for ServerAction or AjaxAction. The default is <em>#page#</em> (the page delegate). You can use this optional setting to target the object of the action to <em>#page#outlet.keyPath</em> or <em>#module#keyPath</em>.</li>
    <li><strong>action</strong> - The <em>action</em> to be called on the target object.<br />
        <blockquote>
        <strong>JSAction</strong><br />
        The default is the Javascript function PHOCOA.widgets.&lt;widgetId&gt;.events.&lt;eventName&gt;.handleEvent.<br />
        If you put in your own action, it will be executed as an anonymous Javascript function.<br />
        <br />
        <strong>ServerAction and AjaxAction</strong><br />
        The default is the php method &lt;widgetId&gt;Handle&lt;eventName&gt;.<br />
        If you put in your own action, it will be interpreted as the php method call of that name on the target object.
        </blockquote>
    </li>
</ul>
<p><strong>A few examples (all added to the widget of id myWidget):</strong></p>
<ul>
    <li><em>onEvent: click do j</em>
        <blockquote>Will call the Javascript function PHOCOA.widgets.myWidget.events.click.handleEvent.</blockquote>
    </li>
    <li><em>onEvent: click do j: myFunc()</em>
        <blockquote>Will call the Javascript function myFunc.</blockquote>
    </li>
    <li><em>onEvent: click do j: alert("HI");</em>
        <blockquote>Will execute alert("HI").</blockquote>
    </li>
    <li><em>onEvent: click do s</em>
        <blockquote>Will refresh the page and execute the server action #page#myWidgetHandleClick (which simply calls the myWidgetHandleClick method of the page delegate).</blockquote>
    </li>
    <li><em>onEvent: click do s:myPhpFunc</em>
        <blockquote>Will refresh the page and execute the server action #page#myPhpFunc (which simply calls the myPhpFunc method of the page delegate).</blockquote>
    </li>
    <li><em>onEvent: click do a:#module#:myPhpFunc</em>
        <blockquote>Will make an AJAX request, executing the server action #module#myPhpFunc (which simply calls the myPhpFunc method of the module).</blockquote>
    </li>
</ul>

<h2>Advanced AJAX Integration</h2>
<p>PHOCOA uses a delegation paradigm to implement the AJAX integration. We have already looked at the default handleEvent delegate method above. There are a few additional delegate methods that you can implement if you want to pass arguments to your handleEvent function, or have specialized success or error handlers.</p>
<ul>
    <li>PHOCOA.widgets.&lt;widgetId&gt;.events.&lt;eventName&gt;.collectArguments() is called when the event you're listening to fires, to give you a chance to tell the infrastructure what arguments to pass to your Javascript handler.</li>
    <li>PHOCOA.widgets.&lt;widgetId&gt;.events.&lt;eventName&gt;.ajaxSuccess() is called if an AJAX RPC occurred, and succeeded.</li>
    <li>PHOCOA.widgets.&lt;widgetId&gt;.events.&lt;eventName&gt;.ajaxError() is called if an AJAX RPC occurred, and failed.</li>
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
<p>The setup for this is done in the YAML file by specifying the <em>onEvent</em> property:</p>
<blockquote><pre>onEvent: click do j</pre></blockquote>
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

<h3>ServerAction - refresh the page to execute an action on the server when an event fires</h3>
<p>{WFView id="rpcPageDelegateServer"} {WFView id="ajaxTarget"}</p>
<p>The setup for this is also trivially simple. In the YAML file:
<blockquote><pre>onEvent: click do s</pre></blockquote>
In PHP, we implement the default callback method &lt;widgetId&gt;Handle&lt;EventName&gt;:
{literal}
<blockquote><pre>
function rpcPageDelegateServerHandleClick($page, $params, $senderId, $eventName)
{
    if (WFRequestController::sharedRequestController()->isAjax())
    {
        return WFActionResponsePhocoaUIUpdater::WFActionResponsePhocoaUIUpdater()
            ->addUpdateHTML('ajaxTarget', 'I am the server and this is my random number: ' . rand());
    }
    else
    {
        $page->outlet('ajaxTarget')->setValue('I am the server and this is my random number: ' . rand());
    }
}
</pre></blockquote>
{/literal}
</p>
<p>You will notice that we handle the event different based on whether the call is an AJAX call or not...</p>
<p>For the ServerAction, we need only update the widget's value. This will be reflected in the HTML response that is sent to the client, just as done in normal PHOCOA action handlers.</p>
<p>For the AjaxAction, to effect the UI updates on the client, we return a WFActionResponsePhocoaUIUpdater object. This object has addUpdateHTML(), addReplaceHTML(), and addRunScript() methods that make it easy for you to update the innerHTML of any element, replace any element, and run Javascript code in response to an AjaxAction.</p>

<h3>AjaxAction - make an AJAX call when an event fires</h3>
<p>We are using the same example as above, but turning it into an AJAX action.</p>
<p>{WFView id="rpcPageDelegate"} Click the link and look to the right of the "ServerAction" link above... </p> 
<p>For this example, we want to call a PHP method other than the default, since we've already set up the method we need for the above example:</p>
<blockquote><pre>onEvent: click do a:rpcPageDelegateServerHandleClick</pre></blockquote>

<h3>Event and Widget Support</h3>
<p>The PHOCOA Ajax integration supports several DOM events, which are allowed on most of the WFView subclasses. The blocks below demonstrate various UI widgets and DOM events:</p>

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

<h3>Form Integration</h3>

<p>The PHOCOA programming model for form submission is extended with our Ajax integration. Everything works the same way, except that you can return WFActionResponse objects to effect UI changes from the server. Even PHOCOA's validation infrastructure works with Ajax.</p>
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
<div id="ajaxFormResult" style="color: blue; margin: 10px;">{$formResult}</div>

<p>Once again, the code to build this Ajax functionality is quite simple.</p>
<p>In YAML, we set up our link to trigger the form submission:
<blockquote><pre>onEvent: click do a:ajaxFormSubmitNormal</pre></blockquote>
We also implement our ajaxFormSubmitNormal action handler, which in this example, we use for both normal and ajax form submission:
<blockquote><pre>
{literal}
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
