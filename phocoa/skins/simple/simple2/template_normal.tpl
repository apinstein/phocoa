<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
{$skinHead}
{WFSkinCSS file="main.css"}
</head>
<body>
    <div id="header">
        {foreach name=mainMenu from=$skin->namedContent('mainMenu') key=name item=url}
            {if !$smarty.foreach.mainMenu.first} | {/if}
            <a href="{$url}">{$name}</a>
        {/foreach}
    </div>

    <div id="content">
    {$skinBody}
    </div>

    <div id="footer">
    <p>{$skin->namedContent('copyright')}</p>
    </div>
</body>
</html>
