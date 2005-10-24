{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
<p>Select an object to continue:</p>

{section name=object_i loop=$propelObjects}
<a href="{WFURL page="browse"}/{$propelObjects[object_i]}">{$propelObjects[object_i]}</a><br />
{sectionelse}
<p>No objects found.</p>
{/section}
