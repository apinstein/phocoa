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


Appcelerator.Widget.Script =
{
	getName: function()
	{
		return 'appcelerator script';
	},
	getDescription: function()
	{
		return 'script widget';
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
		return 'app:script';
	},
	getActions: function()
	{
		return ['execute'];
	},	
	getAttributes: function()
	{
        return [{
            name: 'on',
            optional: true,
			type: Appcelerator.Types.onExpr,
            description: "May be used to execute the script's content."
        }];
	},
	execute: function(id,parameterMap,data,scope,version)
	{
		var code = parameterMap['code'];
		var script = code.toFunction(true);
		if (script == true) return;
		script.call({data:data||{},scope:scope,version:version});
	},
	compileWidget: function(params)
	{
		eval(params['code']);
	},
	buildWidget: function(element,parameters)
	{
		var code = Appcelerator.Compiler.getHtml(element);
		code = code.replace(/\/\*.*\*\//g,'');
		
		if (code && code.trim().length > 0)
		{
			parameters['code'] = String.unescapeXML(code);

			if (parameters['on'])
			{
				return {
					'position' : Appcelerator.Compiler.POSITION_REMOVE
				};
			}
			else
			{
				return {
					'position' : Appcelerator.Compiler.POSITION_REMOVE,
					'compile' : true
				};
			}
		}

		return {
			'position' : Appcelerator.Compiler.POSITION_REMOVE
		};
	}
};

Appcelerator.Widget.register('app:script',Appcelerator.Widget.Script);
