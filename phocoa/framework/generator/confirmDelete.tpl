<h2>{{$entityName}}</h2>

<div class="form-container">
{WFViewBlock id="{{$confirmDeleteFormId}}"}
    {WFView id="{{$entity->valueForKey('primaryKeyProperty')}}"}
    {WFView id="confirmMessage"}

    <div class="buttonrow">
        {WFView id="cancel"}{WFView id="delete"}
    </div>
{/WFViewBlock}
</div>{* end form-container *}

