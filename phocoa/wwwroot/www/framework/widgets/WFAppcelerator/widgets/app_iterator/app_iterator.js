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


Appcelerator.Widget.Iterator =
{
	getName: function()
	{
		return 'appcelerator iterator';
	},
	getDescription: function()
	{
		return 'iterator widget';
	},
	getVersion: function()
	{
		return '1.0.1';
	},
	getSpecVersion: function()
	{
		return 1.0;
	},
	getAuthor: function()
	{
		return 'Jeff Haynie';
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
		return 'app:iterator';
	},
	getAttributes: function()
    {
		var T = Appcelerator.Types;
        return [{name: 'on', optional: true, type: T.onExpr,
		         description: "Used to execute the iterator"},
                {name: 'items', optional: true, type: T.json,
				 description: "Literal (or template-replaced) JSON array to iterate over"},
                {name: 'property', optional: true, type: T.identifier},
                
                {name: 'rowEvenClassName', optional: true, type: T.cssClass},
                {name: 'rowOddClassName', optional: true, type: T.cssClass},
                {name: 'table', optional: true, defaultValue: 'false', type: T.bool},
                {name: 'width', optional: true, defaultValue: '100%', type: T.cssDimension},
                {name: 'headers', optional: true, defaultValue: ',', type: T.commaSeparated},
                {name: 'cellspacing', optional: true, defaultValue: '0', type: T.cssDimension},
                {name: 'selectable', optional: true, type: T.bool}];
    },

	getActions: function()
	{
		return ['execute'];
	},	
	execute: function(id,parameterMap,data,scope)
	{
		var compiled = parameterMap['compiled'];
		var propertyName = parameterMap['property'];
		var items = parameterMap['items'];
		
		var table = parameterMap['table'];
		var width = parameterMap['width'];
		var headers = parameterMap['headers'];
		var selectable = parameterMap['selectable'];
		var array = null;
		
		if (!compiled)
		{
			compiled = eval(parameterMap['template'] + '; init_'+id);
			parameterMap['compiled'] = compiled;
		}
		
		if (items) 
		{
			data = items.evalJSON() || [];
		}
		
		if (propertyName)
		{
			array = Object.getNestedProperty(data,propertyName) || [];
		}
		else
		{
			array = data;
		}
		
		var html = '';
		
		if (!array)
		{
			html = compiled(data);
		}
		else
		{
			if (table)
			{				
				html+='<table width="'+width+'" cellspacing="'+parameterMap['cellspacing']+'"><tr>';
				headers.each(function(h)
				{
					html+='<th>'+h+'</th>';
				});
				html+='</tr>';
			}
         // this is in the case we pass in an object instead of 
			// an array, make it an array of length one so we can iterate
			if (!Object.isArray(array))
			{
				array = [array];
			}
			for (var c = 0, len = array.length; c < len; c++)
			{
				var o = array[c];
				if(typeof o != "object")
				{
					o = {'iterator_value': o};
				}
				o['iterator_index']=c;
				o['iterator_length']=len;
				o['iterator_odd_even']=(c%2==0)?'even':'odd';
				if (table)
				{
					if (o['iterator_odd_even'] == 'odd')
						html+='<tr class="'+parameterMap['rowOddClassName']+'">';
					else
						html+='<tr class="'+parameterMap['rowEvenClassName']+'">';
				}
				/* escape out the "'" so that works in IE */
				for (idx in o)
				{
					if (typeof o[idx] == 'string')
					{
						o[idx] = o[idx].replace(/'/,'\u2019');
					}
				}
				html += compiled(o);
				if (table)
				{
					html+='</tr>';
				}
			}
			if (table)
			{
				html+='</table>';
			}
		}
		var element = $(id);
		if (selectable)
		{
			element.setAttribute('selectable',selectable);
		}
		element.innerHTML = Appcelerator.Compiler.addIENameSpace(html);
		Appcelerator.Compiler.dynamicCompile(element);
	},
	compileWidget: function(params) 
	{
		// no message payload to pass for data,
		// maybe we should plumb the triggering message of a dynamic compile through?
        this.execute(params['id'], params, null, '');
	},
	buildWidget: function(element, parameters)
	{
		parameters['template'] = Appcelerator.Compiler.compileTemplate(Appcelerator.Compiler.getHtml(element),true,'init_'+element.id);
		parameters['table'] = parameters['table'] == 'true';
		if (parameters['table'])
		{
			parameters['headers'] = parameters['headers'].split(',');
		}
		
		var compile = !!(!parameters['on'] && parameters['items']);
		
		return {
			'presentation' : '',
			'position' : Appcelerator.Compiler.POSITION_REPLACE,
			'parameters': parameters,
			'wire' : true,
			'compile' : compile
		};
	}
};


Appcelerator.Widget.register('app:iterator',Appcelerator.Widget.Iterator);
