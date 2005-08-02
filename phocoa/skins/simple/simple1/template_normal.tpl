<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
{$skinHead}
</head>
<body style="background-color: gray; color: white;">
    <div id="header">
        {foreach name=mainMenu from=$skin->namedContent('mainMenu') key=name item=url}
            {if !$smarty.foreach.mainMenu.first} | {/if}
            <a href="{$url}">{$name}</a>
        {/foreach}
    </div>

    <div style="width: 80%; margin: 15px auto; border: 2px solid brown; padding: 10px">
    {$skinBody}
    </div>

    <div id="footer">
    <p style="font-size: small;">{$skin->namedContent('copyright')}</p>
    </div>
</body>
</html>
