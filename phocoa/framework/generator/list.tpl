{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
<h2>{{$entityName}}</h2>

<div class="form-container">
{WFViewBlock id="{{$listFormId}}"}
    {WFView id="paginatorState"}
    <fieldset>
		<legend>{{$entityName}} Search</legend>
		
		<div>
			<label for="query">{{$descriptiveColumnName}}:</label>
			{WFView id="query"}
		</div>
    </fieldset>

    <div class="buttonrow">
	    {WFView id="search"} <a href="{WFURL action="edit"}">Add a new {{$entityName}}.</a>
	</div>
{/WFViewBlock}
</div>{* end form-container *}

<p>{WFView id="paginatorPageInfo"} {WFView id="paginatorNavigation"}</p>

<table border="0" cellspacing="0" cellpadding="5" class="datagrid">
{section name=items loop=$__module->valueForKeyPath('{{$sharedEntityId}}.arrangedObjectCount')}
    {if $smarty.section.items.first}
    <tr>
        <th>{{$entityName}}</th>
        <th></th>
    </tr>
    {/if}
    <tr>
        <td>{WFView id="{{$descriptiveColumnName}}"}</td>
        <td>{WFView id="editLink"} {WFView id="deleteLink"}</td>
    </tr>
{sectionelse}
    <tr><td>No {{$entityName}}(s) found.</td></tr>
{/section}
</table>

<script>
{literal}
Event.observe(window, 'load', function() { document.forms.{{$listFormId}}.query.focus(); });
{/literal}
</script>

