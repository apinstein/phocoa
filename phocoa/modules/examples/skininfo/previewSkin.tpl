<p>This is an example of the skin <b>{$skinName}</b>, template type <b>{$skinTemplateType}</b>, for the skin type <b>{$skinTypeName}</b>.</p>
<h3>Themes</h3>
<p>Available themes for this skin:</p>
<ul>
{section name=themes_i loop=$skinThemes}
    <li><a href="{WFURL page="previewSkin"}/{$skinTypeName}/{$skinName}/{$skinThemes[themes_i]}">{$skinThemes[themes_i]}</a></li>
{sectionelse}
    <li>No theme list available for this skin.</li>
{/section}
</ul>
<p>Default theme: {$skinDefaultTheme}<br />
Current theme: {$skinThemeName}<br />
Current Theme Variables:</p>
<pre style="margin-left: 25px;">{$skinThemeVars}</pre>
<h3>Template Types</h3>
<p>Available Template Types for this skin:</p>
<ul>
{section name=templateType_i loop=$skinTemplates}
    <li><a href="{WFURL page="previewSkin"}/{$skinTypeName}/{$skinName}/{$skinThemeName}/{$skinTemplates[templateType_i]}">{$skinTemplates[templateType_i]}</a></li>
{sectionelse}
    <li>No templateType list available for this skin.</li>
{/section}
</ul>
<p><a href="{WFURL page="skinTypes"}">Return to skin list.</a></p>
