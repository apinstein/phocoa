/**
 * The PHOCOA global namespace
 * @constructor
 *
 * The PHOCOA namespace contains JS utility and support functions.
 */
window.PHOCOA = window.PHOCOA || {};

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
            alert('importJS: ' + path + ' failed to parse: (errNo: ' + err.number + ')' + err.message);
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
        eval( jsCode + "\n\nwindow. " + globalNamespace + " = " + localNamespace + ";" );
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
PHOCOA.runtime = PHOCOA.runtime || {};

PHOCOA.runtime.addObject = function(o, id)
{
    PHOCOA.runtime.setupObjectCache();
    var oid = id || o.id;
    if (!oid) throw "No ID could be found.";
    if (PHOCOA.runtime.objectList[oid])
    {
        alert('error - cannot add duplicate object: ' + oid);
        return;
    }
    PHOCOA.runtime.objectList[oid] = o;
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

