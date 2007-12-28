{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
<h2>{{$entityName}}</h2>
<div class="form-container">
{WFView id="statusMessage"}
{WFShowErrors id={{$editFormId}}}

{WFViewBlock id="{{$editFormId}}"}
    <fieldset>
    <legend>{{$entityName}} Detail</legend>

    {{foreach name=widgets from=$widgets key="widgetId" item="attribute"}}
        {{if $attribute->valueForKey('name') == $entity->valueForKey('primaryKeyAttribute')}}
            {WFView id="{{$widgetId}}"}
        {{else}}
            <div>
                <label for="{{$widgetId}}">{{$attribute->valueForKey('name')}}:</label>
                {WFView id="{{$widgetId}}"}{WFShowErrors id="{{$widgetId}}"}
            </div>
        {{/if}}
    {{/foreach}}
    <div class="buttonrow">
        {WFView id="new"}{WFView id="save"}{WFView id="delete"}
    </div>
    </fieldset>
{/WFViewBlock}
</div>{* end form-container *}
