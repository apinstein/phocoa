<p>This is an example of the skin {$skinName}.</p>
<p>Available themes for this skin:</p>
{section name=themes_i loop=$skinThemes}
    {if $smarty.section.themes_i.first}<ul>{/if}
    <li><a href="{WFURL page="previewSkin"}/{$currentSkinType}/{$skinName}/{$skinThemes[themes_i]}">{$skinThemes[themes_i]}</a></li>
    {if $smarty.section.themes_i.last}</ul>{/if}
{sectionelse}
    <p>No theme list available for this skin.</p>
{/section}
<p>Default theme: {$skinDefaultTheme}</p>
<p>Current theme: {$skinThemeName}</p>
<p>Current Theme Variables:</p>
<pre>{$skinThemeVars}</pre>
<p><a href="{WFURL page="skinTypes"}">Return to skin list.</a></p>
