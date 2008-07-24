/**
* PhocoaDialog is a YUI Panel subclass that allows existing form code to be easily dropped into to a Panel and submitted via AJAX.
* @namespace YAHOO.widget
* @class PhocoaDialog
* @extends YAHOO.widget.Panel
* @constructor
* @param {String}	el	The element ID representing the PhocoaDialog <em>OR</em>
* @param {HTMLElement}	el	The element representing the PhocoaDialog
* @param {Object}	userConfig	The configuration object literal containing the configuration that should be set for this PhocoaDialog. See configuration documentation for more details.
* @todo Add an "originalContent" variable to keep track of the "original" content before the form is submitted... this way we can allow the PhocoaDialog to be "Reset" to the original state in cases where that makes sense. Maybe this is a bad idea b/c the request itself can alter what the original content "should" be. Maybe instead a "reset" can request the original invocationPath through XHR?
*/
YAHOO.widget.PhocoaDialog = function(el, userConfig) {
	YAHOO.widget.PhocoaDialog.superclass.constructor.call(this, el, userConfig);

    this.moduleViewHasLoaded = false;
};

YAHOO.extend(YAHOO.widget.PhocoaDialog, YAHOO.widget.Panel);

/**
* Constant representing the PhocoaDialog's configuration properties
* @property YAHOO.widget.PhocoaDialog._DEFAULT_CONFIG
* @private
* @final
* @type Object
*/
YAHOO.widget.PhocoaDialog._DEFAULT_CONFIG = {

    "DEFER_MODULE_VIEW_LOADING": { 
       key: "deferModuleViewLoading", 
       value: false
    },              

    "CACHE_MODULE_VIEW": { 
       key: "cacheModuleView", 
       value: false
    },              

    "MODULE_VIEW_INVOCATION_PATH": {    
       key: "moduleViewInvocationPath",  
       value: null
    }                   

};                      


/**
* Constant representing the default CSS class used for a PhocoaDialog
* @property YAHOO.widget.PhocoaDialog.CSS_DIALOG
* @static
* @final
* @type String
*/
YAHOO.widget.PhocoaDialog.CSS_DIALOG = "yui-dialog";

/**
* Initializes the class's configurable properties which can be changed using the PhocoaDialog's Config object (cfg).
* @method initDefaultConfig
*/
YAHOO.widget.PhocoaDialog.prototype.initDefaultConfig = function() {
	YAHOO.widget.PhocoaDialog.superclass.initDefaultConfig.call(this);

	/**
	* The internally maintained callback object for use with the Connection utility
	* @property callback
	* @type Object
	*/
	this.callback = {
		/**
		* The function to execute upon success of the Connection submission
		* @property callback.success
		* @type Function
		*/
		success : this.callbackSuccess,
		/**
		* The function to execute upon failure of the Connection submission
		* @property callback.failure
		* @type Function
		*/
		failure : this.callbackFailure,
		/**
		* The arbitraty argument or arguments to pass to the Connection callback functions
		* @property callback.argument
		* @type Object
		*/
		argument: this,
        scope: this
	};

	// Add form dialog config properties //
    var DEFAULT_CONFIG = YAHOO.widget.PhocoaDialog._DEFAULT_CONFIG;
    
    this.cfg.addProperty(   
               DEFAULT_CONFIG.DEFER_MODULE_VIEW_LOADING.key,
                {
                    handler: this.configDeferModuleViewLoading,
                    value: DEFAULT_CONFIG.DEFER_MODULE_VIEW_LOADING.value
                }
            );                                          
                                                        
    this.cfg.addProperty(   
               DEFAULT_CONFIG.CACHE_MODULE_VIEW.key,
                {
                    handler: this.configCacheModuleView,
                    value: DEFAULT_CONFIG.CACHE_MODULE_VIEW.value
                }
            );                                          
                                                        
    this.cfg.addProperty(
               DEFAULT_CONFIG.MODULE_VIEW_INVOCATION_PATH.key,
                {
                   handler: this.configModuleViewInvocationPath,
                   value: DEFAULT_CONFIG.MODULE_VIEW_INVOCATION_PATH.value
                }
           );   

};

/**
* Default callback functions.
* @method callbackSuccess
*/
YAHOO.widget.PhocoaDialog.prototype.callbackSuccess = function(o) {
    var phocoaDialog = o.argument;

    // fix styles - do this BEFORE setting the content so it looks right immediately
    phocoaDialog.applyStyles(o.responseText);

    // update body of dialog with response (it's the HTML we want added)
    phocoaDialog.setBody(o.responseText);

    // evalScripts - do this after we set the HTML b/c the script might look for stuff in the HTML
    var bodyEl = YAHOO.util.Dom.get(phocoaDialog.id);
    var scriptEls = bodyEl.getElementsByTagName('script');
    for (idx = 0; idx < scriptEls.length; idx++) {
        var node = scriptEls[idx];
        window.eval(node.innerHTML);
    }

    // mark content as loaded
    this.moduleViewHasLoaded = true;
};

/**
* Default callback functions.
* @method callbackFailure
*/
YAHOO.widget.PhocoaDialog.prototype.callbackFailure = function(o) {
    alert('Problem submitting XHR request.');
};

/**
* Initializes the custom events for PhocoaDialog which are fired automatically at appropriate times by the PhocoaDialog class.
* @method initEvents
*/
YAHOO.widget.PhocoaDialog.prototype.initEvents = function() {
	YAHOO.widget.PhocoaDialog.superclass.initEvents.call(this);

	/**
	* CustomEvent fired prior to submission
	* @event beforeSumitEvent
	*/
	this.beforeSubmitEvent	= new YAHOO.util.CustomEvent("beforeSubmit");

	/**
	* CustomEvent fired after submission
	* @event submitEvent
	*/
	this.submitEvent		= new YAHOO.util.CustomEvent("submit");

	/**
	* CustomEvent fired after cancel
	* @event cancelEvent
	*/
	this.cancelEvent		= new YAHOO.util.CustomEvent("cancel");
};

/**
* The PhocoaDialog initialization method, which is executed for PhocoaDialog and all of its subclasses. This method is automatically called by the constructor, and  sets up all DOM references for pre-existing markup, and creates required markup if it is not already present.
* @method init
* @param {String}	el	The element ID representing the PhocoaDialog <em>OR</em>
* @param {HTMLElement}	el	The element representing the PhocoaDialog
* @param {Object}	userConfig	The configuration object literal containing the configuration that should be set for this PhocoaDialog. See configuration documentation for more details.
*/
YAHOO.widget.PhocoaDialog.prototype.init = function(el, userConfig) {
	YAHOO.widget.PhocoaDialog.superclass.init.call(this, el/*, userConfig*/);  // Note that we don't pass the user config in here yet because we only want it executed once, at the lowest subclass level

	this.beforeInitEvent.fire(YAHOO.widget.PhocoaDialog);

	YAHOO.util.Dom.addClass(this.element, YAHOO.widget.PhocoaDialog.CSS_DIALOG);

	this.cfg.setProperty("visible", false);

	if (userConfig) {
		this.cfg.applyConfig(userConfig, true);
	}

	this.initEvent.fire(YAHOO.widget.PhocoaDialog);

    this.changeBodyEvent.subscribe(function() {
                                    this.registerForms();
                                    this.focusFirst();
                                }, this, true);
};

/**
* Performs the submission of the PhocoaDialog form depending on the value of "postmethod" property.
* @method doSubmit
*/
YAHOO.widget.PhocoaDialog.prototype.doSubmit = function(form) {
    var method = form.getAttribute("method") || 'POST';
    method = method.toUpperCase();
    YAHOO.util.Connect.setForm(form);
    var cObj = YAHOO.util.Connect.asyncRequest(method, form.getAttribute("action"), this.callback);
};

/**
* Prepares the PhocoaDialog's internal FORM object, creating one if one is not currently present.
* @method registerForms
*/
YAHOO.widget.PhocoaDialog.prototype.registerForms = function() {
    // add submit listeners to all forms
    var forms = this.element.getElementsByTagName('form');
    for (i = 0; i < forms.length; i++) {
        var theForm = forms[i];
        // subscribe to the submit event of the form
        YAHOO.util.Event.addListener(theForm, 'submit', function(e) {
                                                            var submittedForm = YAHOO.util.Event.getTarget(e);
                                                            YAHOO.util.Event.stopEvent(e);
                                                            this.submit(submittedForm);
                                                            submittedForm.blur();
                                                          }, this, true);
        // override the submit() method of the form so our system still works even if the form is submitted programmatically
        theForm.submit = this.submit.bind(this, theForm);
    }
};

/**
* Pulls any <style typ="text/css"> blocks from the XHR and adds them to the current document.
* Firefox does this automatically.
* Safari and IE need help, but they work differently.
* @method applyStyles
*/
YAHOO.widget.PhocoaDialog.prototype.applyStyles = function(rawHTML) {
    if (this.browser == 'gecko') return;

    var headEl = null;  // lazy-load

    // find all styles in the string
    var styleFragRegex = '<style[^>]*>([\u0001-\uFFFF]*?)</style>';
    var matchAll = new RegExp(styleFragRegex, 'img');
    var matchOne = new RegExp(styleFragRegex, 'im');
    var styles = (rawHTML.match(matchAll) || []).map(function(tagMatch) {
                                                                            return (tagMatch.match(matchOne) || ['', ''])[1];
                                                                        });

    // add all found style blocks to the HEAD element.
    for (i = 0; i < styles.length; i++) {
        if (!headEl)
        {
            headEl = document.getElementsByTagName('head')[0];
            if (!headEl)
            {
                return;
            }
        }
        var newStyleEl = document.createElement('style');
        newStyleEl.type = 'text/css';
        if (this.browser == 'ie' || this.browser == 'ie7')
        {
            newStyleEl.styleSheet.cssText = styles[i];
        }
        else
        {
            var cssDefinitionsEl = document.createTextNode(styles[i]);
            newStyleEl.appendChild(cssDefinitionsEl);
        }
        headEl.appendChild(newStyleEl);
    }
};
// BEGIN BUILT-IN PROPERTY EVENT HANDLERS //

/**
* The default event handler fired when the "close" property is changed. The method controls the appending or hiding of the close icon at the top right of the PhocoaDialog.
* @method configClose
* @param {String} type	The CustomEvent type (usually the property name)
* @param {Object[]}	args	The CustomEvent arguments. For configuration handlers, args[0] will equal the newly applied value for the property.
* @param {Object} obj	The scope object. For configuration handlers, this will usually equal the owner.
*/
YAHOO.widget.PhocoaDialog.prototype.configClose = function(type, args, obj) {
	var val = args[0];

	var doCancel = function(e, obj) {
		obj.cancel();
	};

	if (val) {
		if (! this.close) {
			this.close = document.createElement("div");
			YAHOO.util.Dom.addClass(this.close, "container-close");

			this.close.innerHTML = "&#160;";
			this.innerElement.appendChild(this.close);
			YAHOO.util.Event.addListener(this.close, "click", doCancel, this);
		} else {
			this.close.style.display = "block";
		}
	} else {
		if (this.close) {
			this.close.style.display = "none";
		}
	}
};

/**            
* The default event handler for the "deferModuleViewLoading" configuration property
* @method configDeferModuleViewLoading
* @param {String} type  The CustomEvent type (usually the property name)
* @param {Object[]} args    The CustomEvent arguments. For configuration handlers, args[0] will equal the newly applied value for the property.
* @param {Object} obj   The scope object. For configuration handlers, this will usually equal the owner.
*/                          
YAHOO.widget.PhocoaDialog.prototype.configDeferModuleViewLoading = function(type, args, obj) {
    this.deferModuleViewLoading = args[0];
};

/**            
* The default event handler for the "cacheModuleView" configuration property
* @method configCacheModuleView
* @param {String} type  The CustomEvent type (usually the property name)
* @param {Object[]} args    The CustomEvent arguments. For configuration handlers, args[0] will equal the newly applied value for the property.
* @param {Object} obj   The scope object. For configuration handlers, this will usually equal the owner.
*/                          
YAHOO.widget.PhocoaDialog.prototype.configCacheModuleView = function(type, args, obj) {
    this.cacheModuleView = args[0];
};

/**            
* The default event handler for the "moduleViewInvocationPath" configuration property
* @method configModuleViewInvocationPath
* @param {String} type  The CustomEvent type (usually the property name)
* @param {Object[]} args    The CustomEvent arguments. For configuration handlers, args[0] will equal the newly applied value for the property.
* @param {Object} obj   The scope object. For configuration handlers, this will usually equal the owner.
*/                          
YAHOO.widget.PhocoaDialog.prototype.configModuleViewInvocationPath = function(type, args, obj) {
    this.moduleViewInvocationPath = args[0];
};


// END BUILT-IN PROPERTY EVENT HANDLERS //

/**
* Executes a submit of the PhocoaDialog followed by a hide, if validation is successful.
* @method submit
*/
YAHOO.widget.PhocoaDialog.prototype.submit = function(form) {
    this.beforeSubmitEvent.fire();
    this.doSubmit(form);
    this.submitEvent.fire();
    return true;
};

/**
* Executes the cancel of the PhocoaDialog followed by a hide.
* @method cancel
*/
YAHOO.widget.PhocoaDialog.prototype.cancel = function() {
	this.cancelEvent.fire();
	this.hide();
};

/**
* Executes the cancel of the PhocoaDialog followed by a hide.
* @method cancel
*/
YAHOO.widget.PhocoaDialog.prototype.show = function() {
    // need to load the initial content if deferModuleViewLoading is set
    if (this.deferModuleViewLoading && ( (this.cacheModuleView === false ) || (this.cacheModuleView === true && this.moduleViewHasLoaded === false)) )
    {
        this.setBody('Loading...');
        YAHOO.widget.PhocoaDialog.superclass.show.call(this);

        // load initial content
        YAHOO.util.Connect.asyncRequest('GET', this.moduleViewInvocationPath, this.callback);
    }
    else
    {
        YAHOO.widget.PhocoaDialog.superclass.show.call(this);
    }
};

/**
* The default event handler used to focus the first field of the form when the Dialog is shown.
* @method focusFirst
* @todo Eventually I suppose we could copy the behavior from DIALOG, which is to find the FIRST and LAST field in the forms to allow tab-focus-wrapping and such. But this covers the core of what people care about.
*/
YAHOO.widget.PhocoaDialog.prototype.focusFirst = function() {
    // find first element of first form to focus on
    var form = this.element.getElementsByTagName("form")[0];
    if (!form) return;
	var firstFormElement = null;
    for (var f=0;f<form.elements.length;f++ ) {
        var el = form.elements[f];
        if (el.focus && ! el.disabled) {
            if (el.type && el.type != "hidden" && el.type != 'submit') {
                firstFormElement = el;
                break;
            }
        }
    }
    firstFormElement.focus();
};

/**
* Returns a string representation of the object.
* @method toString
* @return {String}	The string representation of the PhocoaDialog
*/
YAHOO.widget.PhocoaDialog.prototype.toString = function() {
	return "PhocoaDialog " + this.id;
};

YAHOO.register("phocoaDialog", YAHOO.util.Panel, {version: "0.1", build: "1"}); 
