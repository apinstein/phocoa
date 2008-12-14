/**
 * The PHOCOA global namespace
 * @constructor
 *
 * The PHOCOA namespace contains JS utility and support functions.
 */
window.PHOCOA = window.PHOCOA || {};

// Namespace functionality modeled on YAHOO.namespace().
// Conventions:
// phocoa.widgets.X where X is the unique ID of a page instance. For accessing JS version of widgets from anywhere.
PHOCOA.namespace = function() {
    var a=arguments, o=null, i, j, d;
    for (i=0; i<a.length; i=i+1) {
        d=a[i].split(".");
        o=PHOCOA;

        // PHOCOA is implied, so it is ignored if it is included
        for (j=(d[0] == "PHOCOA") ? 1 : 0; j<d.length; j=j+1) {
            o[d[j]]=o[d[j]] || {};
            o=o[d[j]];
        }
    }

    return o;
};

// programmatically include JS code from a URL
PHOCOA.importJS = function(path, globalNamespace, localNamespace) { 
    // synchronously download and eval() the js code at the given path
    // cache
    if (!PHOCOA.importJSCache)
    {
        PHOCOA.importJSCache = {};
    }
    if (PHOCOA.importJSCache[path])
    {
        return;
    }
    PHOCOA.importJSCache[path] = true;

    if (1)
    {
        // this method works synchronously, but the included code must be written in such a way that all definitions survive eval() to appear in the global scope
        var js = new Ajax.Request(
                path,
                {
                    asynchronous: false,
                    method: 'get'
                }
            );
        try {
            PHOCOA.sandbox(js.transport.responseText, globalNamespace);
        } catch (err) {
            if (typeof(console) != 'undefined' && console.warn)
            {
                console.warn('importJS: ' + path + ' failed to parse: (errNo: ' + err.number + ')' + err.message);
            }
        }
    }
//    else
//    {
//        // this method has no scoping problems, but isn't synchronous.
//        var head = document.getElementsByTagName("head")[0];
//        script = document.createElement('script');
//        script.type = 'text/javascript';
//        script.src = path;
//        head.appendChild(script);
//        //alert('adding script tag for: ' +script.src);
//    }
};

PHOCOA.sandbox = function(jsCode, globalNamespace, localNamespace) {
    if (globalNamespace)
    {
        if (!localNamespace) localNamespace = globalNamespace;
        eval( jsCode + "\n\nwindow." + globalNamespace + " = " + localNamespace + ";" );
    }
    else
    {
        eval(jsCode);
    }
};

// programmatically include CSS code from a URL
PHOCOA.importCSS = function(path) {
    var newCSS = document.createElement('link');
    newCSS.setAttribute('rel', 'stylesheet');
    newCSS.setAttribute('type', 'text/css');
    newCSS.setAttribute('href', path);
    document.getElementsByTagName('head')[0].appendChild(newCSS);
};

// set up the runtime - this is the interface that you use to access objects added by phocoa from individual pages
PHOCOA.namespace('runtime');
PHOCOA.runtime.addObject = function(o, id)
{
    PHOCOA.runtime.setupObjectCache();
    var oid = id || o.id;
    if (!oid) { throw "No ID could be found."; }
    if (PHOCOA.runtime.objectList[oid])
    {
        alert('error - cannot add duplicate object: ' + oid);
        return;
    }
    PHOCOA.runtime.objectList[oid] = o;
};

PHOCOA.runtime.removeObject = function(id)
{
    PHOCOA.runtime.setupObjectCache();
    delete PHOCOA.runtime.objectList[id];
};

PHOCOA.runtime.setupObjectCache = function()
{
    // object list
    if (!PHOCOA.runtime.objectList)
    {
        PHOCOA.runtime.objectList = {};
    }
};

PHOCOA.runtime.getObject = function(id)
{
    PHOCOA.runtime.setupObjectCache();
    var o = null;
    if (PHOCOA.runtime.objectList[id])
    {
        o = PHOCOA.runtime.objectList[id];
    }
    return o;
};

// RPC
PHOCOA.namespace('WFRPC');
PHOCOA.WFRPC = function(url, target, action) {
    this.target = '#page#';
    this.action = null;
    this.form = null;   // id of form
    this.runsIfInvalid = false;
    this.invocationPath = null;
    this.transaction = null;
    this.isAjax = true;
    this.submitButton = null;
    // yui-style callback
    this.callback = {
            success: this.ajaxCallbackSuccess,
            failure: this.ajaxCallbackFailure
        };

    if (url) this.invocationPath = url;
    if (target) this.target = target;
    if (action) this.action = action;
    return this;
};

PHOCOA.WFRPC.prototype = {
    ajaxCallbackSuccess: function() {
        alert('ajax callback succeeded (not yet implemented).');
    },

    ajaxCallbackFailure: function() {
        alert('ajax callback failed.');
    },

    actionURL: function() {
        return this.invocationPath;
    },
    actionURLParams: function(args, append) {
        args = args || [];
        append = append || false;
        var url = (append ? '&' : '');
        url += '__phocoa_rpc_enable=1';
        url += '&__phocoa_rpc_invocationPath=' + escape(this.invocationPath);
        url += '&__phocoa_rpc_target=' + escape(this.target);
        url += '&__phocoa_rpc_action=' + this.action;
        url += '&__phocoa_rpc_runsIfInvalid=' + this.runsIfInvalid;
        if (args.length)
        {
            for (var i = 0; i < args.length; i++) {
                var argvName = '__phocoa_rpc_argv_' + i;
                url += '&' + argvName + '=' + args[i];
            }
        }
        url += '&__phocoa_rpc_argc=' + args.length;
        return url;
    },

    // args should be an array of arguments
    actionAsURL: function(args) {
        return this.actionURL() + '?' + this.actionURLParams(args);
    },

    phocoaRPCParameters: function(args) {
        args = args || [];
        var params = {};
        params.__phocoa_rpc_enable = 1;
        params.__phocoa_rpc_invocationPath = this.invocationPath;
        params.__phocoa_rpc_target = this.target;
        params.__phocoa_rpc_action = this.action;
        params.__phocoa_rpc_runsIfInvalid = this.runsIfInvalid;
        if (args.length)
        {
            for (var i = 0; i < args.length; i++) {
                var argvName = '__phocoa_rpc_argv_' + i;
                params[argvName] = args[i];
            }
        }
        params.__phocoa_rpc_argc = args.length;
        return params;
    },

    // args passed into this will be passed on via the RPC
    execute: function() {
        // @todo Do we need to deal with serializing requests so that we can make sure to process responses in order?
        if (this.form)
        {
            // turn off all form errors...
            $$('.phocoaWFFormError').each( function(e) { e.update(null); } );
            if ( this.isAjax === false /* refreshPage */)
            {
                var theForm = $(this.form);
                // insert phocoa ajax elems, submit button pressed into form and submit the form
                if (this.submitButton)
                {
                    // add submit button to form before submitting
                    var submitEl = '<input type="hidden" name="' + $(this.submitButton).name + '" value="' + $(this.submitButton).value + '" />';
                    Element.insert(theForm, submitEl);
                }
                theForm.submit();
            }
            else /* ajax form submit */
            {
                // see Prototype.Form.request()
                this.transaction = $(this.form).request(   {
                                            method: 'GET',
                                            parameters: this.phocoaRPCParameters(this.execute.arguments),
                                            onSuccess: this.callback.success.bind(this.callback.scope),
                                            onFailure: this.callback.failure.bind(this.callback.scope),
                                            onException: this.callback.failure.bind(this.callback.scope)
                                        });
            }
        }
        else
        {
            var url = this.actionAsURL(this.execute.arguments);
            if (this.isAjax)
            {
                // set up XHR request & callback
                var successCallbackFixer = function(o) {
                    o.argument = this.callback.argument;
                    this.callback.success.apply(this.callback.scope, [o]);
                };
                var failureCallbackFixer = function(o) {
                    o.argument = this.callback.argument;
                    this.callback.failure.apply(this.callback.scope, [o]);
                };
				this.transaction = new Ajax.Request(url,
                                                        {
                                                            method : 'get',
                                                            asynchronous : true,
                                                            onSuccess : successCallbackFixer.bind(this),
                                                            onFailure: failureCallbackFixer.bind(this),
                                                            onException: failureCallbackFixer.bind(this)
                                                        }
                                                    );
            }
            else
            {
                document.location = url;
            }
        }
        return this.transaction;
    }
};

// JS Actions - potentially kill this object
PHOCOA.namespace('WFAction');
PHOCOA.WFAction = function(elId, eventName) {
    this.elId = elId;
    this.eventName = eventName;
    this.callback = PHOCOA.widgets[this.elId].events[this.eventName].handleEvent;
    this.rpc = null;
    this.stopsEvent = true;
	Event.observe(this.elId, this.eventName, this.yuiTrigger.bindAsEventListener(this));
    return this;
};

PHOCOA.WFAction.prototype = {
    stopEvent: function(event) {
        Event.stop(event);
    },

    yuiTrigger: function(event) {
        if (this.stopsEvent)
        {
            this.stopEvent(event);
        }
        this.execute(event);
    },

    execute: function(event) {
        // collect arguments - should be: (event, arg1, arg2)
        var args = [], jsCallbackArgs;
        if (PHOCOA.widgets[this.elId].events[this.eventName].collectArguments)
        {
            args = PHOCOA.widgets[this.elId].events[this.eventName].collectArguments();
        }
        // prepare the argument array used for the JS callbacks
        jsCallbackArgs = args.slice(0); // make a copy
        jsCallbackArgs.splice(0, 0, event);

        // is the callback an RPC or just a js function?
        if (this.rpc)
        {
            // call the handleEvent function first, so that the client can do any cleanup or prep work (such as hiding divs)
            if (this.callback)
            {
                this.callback.apply(this.jsCallbackArgs);
            }

            // the event callback for RPC is of the prototype: phpFunc($page, $sender, $event, [$arg1, $arg2, ..])
            // we just pass the senderID to the server; it will convert it to an object.
            // the event is also just passed as the event ID
            args.splice(0, 0, Event.element(event).identify(), event.type); // since we aren't using prototype Event.observe() here yet, on IE7 we must do Event.element(event) instead of just event.element() in order to get the "extended" methods. If we used prototype for observing here, we'd be able to use the cleaner syntax.
            // the callback is called after the RPC completes
            // @todo The RPC callback should be our wrapper that parses out the result based on mime type and passes stuff on to ajaxSuccess
            this.rpc.callback.argument = jsCallbackArgs;
            this.rpc.callback.success = this.rpcCallbackSuccess;
            this.rpc.callback.scope = this;
            
            this.rpc.execute.apply(this.rpc, args);
        }
        else
        {
            // the event callback for JS is of the prototype: JsFunc(event, [$arg1, $arg2, ..])
            // the Event object in JS contains the "sender" so no need to send it separately
            // the callback is just a JS function
            if (this.callback)
            {
                this.callback.apply(this, jsCallbackArgs);
            }
            else
            {
                if (typeof(console) != 'undefined' && console.warn)
                {
                    console.warn("Callback doesn't exist: PHOCOA.widgets." + event.target.identify() + ".events." + event.type);
                }
            }
        }
    },

    runScriptsInElement: function(el) {
        var scriptEls = el.getElementsByTagName('script');
        for (idx = 0; idx < scriptEls.length; idx++) {
            var node = scriptEls[idx];
            window.eval(node.innerHTML);
        }
    },

    doPhocoaUIUpdatesJSON: function(updateList) {
        var id, el;
        if (updateList.update)
        {
            for (id in updateList.update) {
                el = $(id);
                el.update(updateList.update[id]);
                this.runScriptsInElement(el);
                // need to add code to process style blocks
            }
        }
        if (updateList.replace)
        {
            for (id in updateList.replace) {
                el = $(id);
                el.replace(updateList.replace[id]);
                this.runScriptsInElement(el);
                // need to add code to process style blocks
            }
        }
        if (updateList.run)
        {
            for (id = 0; id < updateList.run.length; id++) {
                window.eval(updateList.run[id]);
            }
        }
    },

    // should the response parsing be in WFAction or WFRPC?
    rpcCallbackSuccess: function(o) {
        var theResponse;
        // the callback for ajaxSucces in JS is of the prototype: JsFunc(parsedResult, event, [$arg1, $arg2, ..])
        // where parseResult is the text from a text/plain reponse, xml from a text/xml response, and a JS Object from a JSON response.
        var contentType = null;
        if (typeof o.getResponseHeader == 'function')
        {
            // prototype
            contentType = o.getResponseHeader('Content-Type');
        }
        else
        {
            // yui
            contentType = o.getResponseHeader['Content-Type'];
        }
        contentType = contentType.strip();
        switch (contentType) {
            case 'application/x-json':
                theResponse = eval('(' + o.responseText + ')');
                break;
            case 'text/xml':
                theResponse = o.responseXML;
                break;
            case 'application/x-json-phocoa-ui-updates':
                theResponse = eval('(' + o.responseText + ')');
                this.doPhocoaUIUpdatesJSON(theResponse);
                return;
                break;
            case 'text/plain':
                theResponse = o.responseText;
                break;
            default: 
                theResponse = o.responseText;
                break;
        }
        // call the right callback, if one is supplied
        if (PHOCOA.widgets[this.elId].events[this.eventName].ajaxSuccess)
        {
            // use the argument data from the callback instead of the response object (works for both YUI response and Prototype response)
            var cbArgs = this.rpc.callback.argument.slice(0);   // make a copy
            cbArgs.splice(0, 0, theResponse);
            PHOCOA.widgets[this.elId].events[this.eventName].ajaxSuccess.apply(null, cbArgs);
        }
    }
};

/**
 * YUILoader proxy.
 * @todo Finish this... it isn't used presently
 */
PHOCOA.namespace('yuiloader');
PHOCOA.yuiloaderO = {
    callbacks: [],
    register: function(callback)
    {
        this.callbacks.push(callback);
    },
    doneLoading: function()
    {
        this.callbacks.each(function(cb) { cb(); } );
    },
    doLoading: function()
    {
        PHOCOA.yuiloader = new PHOCOA.yuiloaderO;

        // on-demand loading of YUILoader started causing bugs with PhocoaDialog, so for now we hard-code includsion of yuiloader in the head tag.
        //PHOCOA.importJS('" . WFView::yuiPath() . "/yuiloader/yuiloader-beta-" . ($this->debug() ? 'debug' : 'min') . ".js', 'YAHOO');
        var yl = new YAHOO.util.YUILoader();
        // @todo add customModules support back
        /*
        " . ($this->base() ? 'yl.base = "' . $this->base() . '";' : NULL) . "
        yl.require(" . join(',', $this->quotedRequired()) . ");
        yl.allowRollup = " . ($this->allowRollup() ? 'true' : 'false') . ";
        yl.loadOptional = " . ($this->loadOptional() ? 'true' : 'false') . ";
        yl.onSuccess = PHOCOA.yuiloader.doneLoading;
        yl.insert( { " . ($this->debug() ? 'filter: "DEBUG"' : NULL) . " } );
        */
    }
};
