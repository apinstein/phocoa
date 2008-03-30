/*
 * This file is part of Appcelerator.
 *
 * Copyright (C) 2006-2008 by Appcelerator, Inc. All Rights Reserved.
 * For more information, please visit http://www.appcelerator.org
 *
 * Appcelerator is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

Appcelerator.Widget.Pagination =
{
	getName: function()
	{
		return 'appcelerator pagination';
	},
	getDescription: function()
	{
		return 'pagination widget';
	},
	getVersion: function()
	{
		return 1.0;
	},
	getSpecVersion: function()
	{
		return 1.0;
	},
	getAuthor: function()
	{
		return 'Nolan Wright';
	},
	getModuleURL: function ()
	{
		return 'http://www.appcelerator.org';
	},
	isWidget: function ()
	{
		return true;
	},
	getWidgetName: function()
	{
		return 'app:pagination';
	},
	getAttributes: function()
	{
		var T = Appcelerator.Types;        
        return [{
            name: 'request',
            optional: true,
            type: T.messageSend
        }, {
            name: 'response',
            optional: true,
            type: T.messageReceive
        }, {
            name: 'startProperty',
            optional: true,
            defaultValue: 'start',
			type: T.identifier,
			description: 'Property on the response message that contains the index of the first item returned'
        }, {
            name: 'endProperty',
            optional: true,
            defaultValue: 'end',
			type: T.identifier,
			description: 'Property on the response message that contains the index of the last item returned'
        }, {
            name: 'totalProperty',
            optional: true,
            defaultValue: 'total',
            description: 'Property on the response message that contains the total number of items found'
        }, {
            name: 'nextText',
            optional: true,
            defaultValue: 'next',
			description: 'Text to show on the "Next Page" button, '+
			'in the absense of a localized string given by nextLangId'
        }, {
            name: 'prevText',
            optional: true,
            defaultValue: 'prev',
			description: 'Text to show on the "Previous Page" button, '+
            'in the absense of a localized string given by prevLangId'
        }, {
            name: 'nextLangId',
            optional: true,
			type: T.langId
        }, {
            name: 'prevLangId',
            optional: true,
			type: T.langId
        }, {
            name: 'resultsString',
            optional: true,
            defaultValue: 'Showing #{start} of #{end}'
        }, {
            name: 'totalsString',
            optional: true,
            defaultValue: '#{total} records found'
        }, {
            name: 'resultsLangId',
            optional: true,
			type: T.langId
        }, {
            name: 'totalsLangId',
            optional: true,
			type: T.langId
        }, {
            name: 'fieldset',
            optional: true,
            description: "Fieldset to be associated with the iterator for filtering"
        }, {
            name: 'showTotals',
            optional: true,
			type: T.bool
        }];
	},
	compileWidget: function(parameters)
	{
		var request = parameters['request'];
		var message = parameters['response'];
		var startProperty = parameters['startProperty'];
		var endProperty = parameters['endProperty'];
		var totalProperty = parameters['totalProperty'];
		var nextLangId = parameters['nextLangId'];
		var prevLangId = parameters['prevLangId'];
		var resultsString = parameters['resultsString'];
		var totalsString = parameters['totalsString'];
		var resultsLangId = parameters['resultsLangId'];
		var totalsLangId = parameters['totalsLangId'];
		var showTotals = parameters['showTotals'] == 'true';
		var id = parameters['id'];
		
		$MQL(message,
	    function(t, data, datatype, direction)
		{
			// Oh, Nolan is so clever!
			Element[ (data[startProperty]>1) ? 'show' : 'hide' ]('app_prev_'+id);
			Element[ (data[totalProperty]>data[endProperty]) ? 'show' : 'hide' ]('app_next_'+id);
			Element[ (data[startProperty]>1 && data[endProperty]<data[totalProperty]) ? 'show' : 'hide' ]('app_sep_'+id);
			var nextAnchor = $('app_next_'+id);
			var prevAnchor = $('app_prev_'+id);
			Appcelerator.Compiler.destroy(nextAnchor);
			Appcelerator.Compiler.destroy(prevAnchor);
			var total = data[totalProperty];
			var end = data[endProperty];
			var start = data[startProperty];
			
            $(id + "_start").value = start;
            $(id + "_end").value = end;
            
			if (resultsLangId)
			{
				var compiled = Appcelerator.Localization.getWithFormat(resultsLangId,resultsString,null,data);
				$('app_pagination_showing_'+id).innerHTML = compiled;
			}
			else
			{
				var resultsTemplate = Appcelerator.Compiler.compileTemplate(resultsString,true,'app_results_'+id);
				var compiled = eval(resultsTemplate + '; app_results_' + id + ';');
				$('app_pagination_showing_'+id).innerHTML = compiled(data);
			}
			
			if (showTotals)
			{
				if (totalsLangId)
				{
					var compiledTotals = Appcelerator.Localization.getWithFormat(totalsLangId,totalsString,null,data);
					$('app_pagination_totals_'+id).innerHTML = compiledTotals;
				}
				else
				{
					var totalsTemplate = Appcelerator.Compiler.compileTemplate(totalsString,true,'app_totals_'+id);
					var compiledTotals = eval(totalsTemplate + '; app_totals_' + id + ';');
					$('app_pagination_totals_'+id).innerHTML = compiledTotals(data);
				}
			}
			
			nextAnchor.setAttribute('on','click then '+request+'[dir=next]');
            prevAnchor.setAttribute('on','click then '+request+'[dir=previous]');
			Appcelerator.Compiler.dynamicCompile(nextAnchor);
			Appcelerator.Compiler.dynamicCompile(prevAnchor);
		}); // TODO: what should scope be?
	},
	buildWidget: function(element,parameters)
	{
		var request = parameters['request'];
		var response = parameters['response'];
		var startProperty = parameters['startProperty'];
		var endProperty = parameters['endProperty'];
		var totalProperty = parameters['totalProperty'];
		var nextText = parameters['nextText'];
		var prevText = parameters['prevText'];
		var nextLangId = parameters['nextLangId'];
		var prevLangId = parameters['prevLangId'];
		var resultsString = parameters['resultsString'];
		var totalsString = parameters['totalsString'];
		var resultsLangId = parameters['resultsLangId'];
		var totalsLangId = parameters['totalsLangId'];
		var showTotals = parameters['showTotals'];
        var fieldset = parameters['fieldset'];
		var id = parameters['id'];
        
        //Assign a default fieldset of the element's ID
        if(typeof fieldset == "undefined")
        {
            fieldset = element.id;
        }	
        			
		// build html
		var html = '<span style="display:none" on="'+response+'['+totalProperty+'!=0] then show else hide">';
		html += '<span id="app_pagination_showing_'+id + '"></span>';
        
        html += '<a style="padding-left:5px" id="app_prev_'+id +'"' + ' fieldset="' + fieldset + '">';

        html += (prevLangId) ? Appcelerator.Localization.get(prevLangId) : prevText;
        html += "</a>";
        
		html += '<span style="padding-left:3px;padding-right:3px" id="app_sep_'+id+'">|</span>';

		html += '<a style="padding-left:3px" id="app_next_'+id+'"' + ' fieldset="' + fieldset + '">';

        html += (nextLangId) ? Appcelerator.Localization.get(nextLangId) : nextText;
        html += "</a>";
        
		html += '<span style="padding-left:10px" id="app_pagination_totals_'+id+'"></span>';
		html += '</span>';
		html += '<input id="' + element.id + '_start" type="hidden" name="start" fieldset="' + fieldset + '" />';
		html += '<input id="' + element.id + '_end" type="hidden" name="end" fieldset="' + fieldset + '" />';
        
		return {
			'presentation' : html,
			'position' : Appcelerator.Compiler.POSITION_REPLACE,
			'compile' : true,
			'wire' : true
		};
	}
};


Appcelerator.Widget.register('app:pagination',Appcelerator.Widget.Pagination);
