/* jActivating v.1.1.2 (compressed) - http://jactivating.sourceforge.net */
var jActivating = { isMSIE : (document.all && !window.opera) ? true : false, reinsertContent : function()
{ var totalNodes = new Array(3); totalNodes['object'] = document.getElementsByTagName('object').length; totalNodes['embed'] = document.getElementsByTagName('embed').length; totalNodes['applet'] = document.getElementsByTagName('applet').length; for(var tagName in totalNodes)
{ var counter = totalNodes[tagName] - 1; for(var node; node = document.getElementsByTagName(tagName)[counter]; counter--)
{ sourceCode = jActivating.getSourceCode(node); if(sourceCode)
{ node.outerHTML = sourceCode;}
}
}
jActivating.isMSIE = null;}, getSourceCode : function(node)
{ var sourceCode = node.outerHTML; switch(node.nodeName.toLowerCase())
{ case 'embed':
return sourceCode; break; case 'object':
case 'applet':
var openTag = sourceCode.substr(0, sourceCode.indexOf('>') + 1); var closeTag = sourceCode.substr(sourceCode.length - 9).toLowerCase(); if(closeTag != '</object>' && closeTag != '</applet>')
{ return null;}
if(jActivating.isMSIE)
{ var innerCode = jActivating.getInnerCode(node); sourceCode = openTag + innerCode + closeTag;}
return sourceCode; break;}
}, getInnerCode : function(node)
{ var innerCode = ''; var totalChilds = node.childNodes.length - 1; for(var counter = totalChilds, child; child = node.childNodes[counter]; counter--)
{ innerCode += child.outerHTML;}
return innerCode;}
}
if(document.attachEvent)
{ if(window.opera)
{ document.attachEvent("DOMContentLoaded", jActivating.reinsertContent);}
else
{ jActivating.reinsertContent();}
}
