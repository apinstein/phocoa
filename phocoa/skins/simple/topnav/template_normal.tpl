<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" 
"http://www.w3.org/TR/html4/strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    {$skinHead}
    <link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/2.4.0/build/reset-fonts-grids/reset-fonts-grids.css"> 
    <link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/2.4.0/build/base/base-min.css">
    {WFSkinCSS file="default.css"}
</head>
<body class="yui-skin-sam">
<div id="doc4">
    <div id="hd">
        <a href="/"><img src="{$skinDirShared}/phocoa-logo.png" alt="PHOCOA - a php web framework" /></a>
    </div>
    {WFSkinModuleView invocationPath="menu/menu/mainMenu/1"}
    <div id="bd">
        <div class="yui-b">
            {$skinBody}
        </div>
    </div>
    <div id="ft">
        {$skin->namedContent('copyright')}
    </div>
</div>
</body>
</html>
