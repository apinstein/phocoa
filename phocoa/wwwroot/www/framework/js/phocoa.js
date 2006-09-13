/**
 * The PHOCOA global namespace
 * @constructor
 *
 * The PHOCOA namespace contains JS utility and support functions.
 */
window.PHOCOA = window.PHOCOA || {};

// programmatically include JS code from a URL
PHOCOA.importJS = function(path) { 
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

    var js = new Ajax.Request(
            path,
            {
                asynchronous: false,
                method: 'get'
            }
        );
    eval(js.transport.responseText);
}

// programmatically include CSS code from a URL
PHOCOA.importCSS = function(path) {
    var newCSS = document.createElement('link');
    newCSS.setAttribute('rel', 'stylesheet');
    newCSS.setAttribute('type', 'text/css');
    newCSS.setAttribute('href', path);
    document.getElementsByTagName('head')[0].appendChild(newCSS);
}

// set up the runtime - this is the interface that you use to access objects added by phocoa from individual pages
PHOCOA.runtime = PHOCOA.runtime || {};

PHOCOA.runtime.addObject = function(o)
{
    // object list
    if (!PHOCOA.runtime.objectList)
    {
        PHOCOA.runtime.objectList = {};
    }

    if (PHOCOA.runtime.objectList[o.id])
    {
        alert('error - cannot add duplicate object: ' + o.id);
        return;
    }
    PHOCOA.runtime.objectList[o.id] = o;
}

PHOCOA.runtime.getObject = function(id)
{
    var o = null;
    if (PHOCOA.runtime.objectList[id])
    {
        o = PHOCOA.runtime.objectList[id];
    }
    return o;
}

