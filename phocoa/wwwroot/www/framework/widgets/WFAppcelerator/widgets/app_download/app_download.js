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


Appcelerator.Widget.Download =
{
	getName: function()
	{
		return 'appcelerator download';
	},
	getDescription: function()
	{
		return 'download widget';
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
		return 'Hamed Hashemi';
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
		return 'app:download';
	},
	getActions: function()
	{
		return ['execute'];
	},	
	getAttributes: function()
	{
		return [{name: 'service', optional: false},
				{name: 'fileProperty', optional: true, defaultValue: 'file'},
				{name: 'mimetype', optional: true, defaultValue: 'application/octet-stream'},
				{name: 'mimetypeProperty', optional: true, defaultValue: 'type'},
				{name: 'error', optional: true}];
	},
	execute: function(id,parameters,data,scope)
	{
		parameters['idcount'] = parameters['idcount'] + 1;
		var targetid = parameters['id']+'_target_'+parameters['idcount'];
		
		for (var k in data)
		{
			if (typeof(data[k]) == 'string')
			{
				parameters['src'] += '&amp;' + k + '=' + data[k];
			}
		}
		
		var html = '<iframe name="'+ targetid+'" id="'+targetid+'" width="1" height="1" src="'+parameters['src']+'" style="position:absolute;top:-400px;left:-400px;width:1px;height:1px;"></iframe>';
		new Insertion.Bottom(document.body, html);
	},
	buildWidget: function(element,parameters,state)
	{
		var download = Appcelerator.ServerConfig['download'].value;
		var fileProperty = parameters['fileProperty'];
		var mimetype = parameters['mimetype'];
		var mimetypeProperty = parameters['mimetypeProperty'];
		var src = download+'?type='+parameters['service']+'&amp;fileProperty='+fileProperty+'&amp;mimetype='+mimetype+'&amp;mimetypeProperty='+mimetypeProperty;

		if (parameters['error'])
		{
			src += '&amp;error=' + error;
		}
		parameters['src'] = src;
		parameters['idcount'] = 0;
		
		return {
			'position' : Appcelerator.Compiler.POSITION_REPLACE,
			'presentation' : ''
		};
	}
};

Appcelerator.Widget.register('app:download',Appcelerator.Widget.Download);
