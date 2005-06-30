<p>Installed Skin Types:</p>
<ul>
{section name=skin_i loop=$skinTypes}
    <li><a href="{WFURL page="skinTypes"}/{$skinTypes[skin_i]}">{$skinTypes[skin_i]}</a></li>
{/section}
</ul>
<p>Click a skin type to preview it and see more information.</p>

<hr />
<p>Current Skin Delegate Information:</p>
<p>Delegate Class Name: {$skinDelegateClassName}</p>
<p>Installed Skins:</p>
<ul>
{section name=skin_i loop=$skins}
    <li><a href="{WFURL page="previewSkin"}/{$currentSkinType}/{$skins[skin_i]}">{$skins[skin_i]}</a></li>
{/section}
</ul>
<p>Available content:
<ul>
{foreach from=$namedContentInfo key=contentName item=contentValue}
    <li>{$contentName}:
        <pre>
            {$contentValue}
        </pre>
    </li>
{/foreach}
</ul>
</p>
