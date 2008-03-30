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


Appcelerator.Widget.Modaldialog =
{
	getName: function()
	{
		return 'appcelerator modaldialog';
	},
	getDescription: function()
	{
		return 'modaldialog widget';
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
		return 'app:modaldialog';
	},
	getActions: function()
	{
		return ['execute'];
	},	
    getAttributes: function()
    {
        var T = Appcelerator.Types;
        return [{
            name: 'on',
            optional: false,
            type: T.onExpr,
            description: "Used to show the modal dialog"
        }, {
            name: 'property',
            optional: true,
            type: T.identifier,
			description: 'Property of triggering message to use as namespace when'+
	         ' template-replacing body on exxecute'
        }, {
            name: 'top',
            optional: true,
            type: T.number,
			description: 'Something related to distance from the top of the page'
        }];
    },  

	execute: function(id,parameterMap,data,scope)
	{
		var compiled = parameterMap['compiled'];
		var propertyName = parameterMap['property'];
		var array = null;
		
		if (!compiled)
		{
			compiled = eval(parameterMap['template'] + '; init_'+id);
			parameterMap['compiled'] = compiled;
		}
		
		if (propertyName)
		{ 
			array = Object.getNestedProperty(data,propertyName) || [];
		}
		
		var html = '';
		if (!array || array.length == 0)
		{
			html = compiled(data);
		}
		else
		{
			html = compiled(array[0]);
		}
		
		html = '<div scope="' + (scope || element.scope) + '">' + html + '</div>';
		
		var overlay = $('overlay');
		var overlaydata = $('overlay_data');
		
		overlaydata.innerHTML = html;

		Appcelerator.Compiler.dynamicCompile(overlaydata);
		
		var arrayPageSize = Element.getDocumentSize();
		overlay.style.height = arrayPageSize[3] + 250 + 'px';
		Element.show(overlay);

		var dataTop = 0;
		if (!parameterMap['top'])
		{
			var arrayPageScroll = Element.getPageScroll();
			var dataTop = Math.min(80,arrayPageScroll + (arrayPageSize[3] / 5));
			$D('modaldialog: dataTop='+dataTop+',arrayPageScroll='+arrayPageScroll+',arrayPageSize[3]='+arrayPageSize[3]);
		}
		else
		{
			dataTop = parseInt(parameterMap['top']);
		}
		overlaydata.style.top = dataTop + 'px';
		Element.show(overlaydata);
	},
	buildWidget: function(element,parameters,state)
	{
		var hidemessage = 'l:appcelerator.modaldialog.hide';

		var overlay = $('overlay');
		if (!overlay)
		{
			var overlayHtml = '<div id="overlay" style="display: none" on="' + hidemessage + ' then hide" scope="*"></div>';
			new Insertion.Bottom(document.body, overlayHtml);
			overlay = $('overlay');
			overlay.modaldialog_compiled = 1;
			Appcelerator.Compiler.compileElement(overlay,state);
		}
		else
		{
			// allow overlay to be added in the doc but we attach to it
			if (!overlay.modaldialog_compiled)
			{
				overlay.setAttribute('scope','*');
				overlay.setAttribute('on',hidemessage+' then hide');
				Appcelerator.Compiler.compileElement(overlay,state);
				overlay.modaldialog_compiled = 1;
			}
		}
		
		var overlaydata = $('overlay_data');
		if (!overlaydata)
		{
			var overlayDataHtml = '<div on="' + hidemessage + ' then hide" class="overlay_data" id="overlay_data" style="display: none" scope="*"></div>'
			new Insertion.Bottom(document.body, overlayDataHtml);
			overlaydata = $('overlay_data');
			Appcelerator.Compiler.compileElement(overlaydata,state);
		}
		
		parameters['template'] = Appcelerator.Compiler.compileTemplate(Appcelerator.Compiler.getWidgetHTML(element),true,'init_'+element.id);
		
		return {
			'position' : Appcelerator.Compiler.POSITION_REMOVE,
			'parameters': parameters,
			'wire':true			
		};
	}
};

Appcelerator.Core.loadModuleCSS('app:modaldialog','modaldialog.css');
Appcelerator.Widget.register('app:modaldialog',Appcelerator.Widget.Modaldialog);
