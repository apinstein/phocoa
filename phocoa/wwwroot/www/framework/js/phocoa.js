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
