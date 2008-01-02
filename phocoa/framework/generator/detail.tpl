{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
<h2>{{$entityName}}</h2>

<table border="0" cellpadding="3" cellspacing="0" class="datadetail">
{{foreach name=widgets from=$widgets key="widgetId" item="property"}}
    <tr>
        <td valign="top">{{$property->valueForKey('name')}}:</td>
        <td valign="top">{WFView id="{{$widgetId}}"}</td>
    </tr>
{{/foreach}}
</table>
