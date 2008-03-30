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


Appcelerator.Widget.ProgressBar =
{
	getName: function()
	{
		return 'appcelerator progressbar';
	},
	getDescription: function()
	{
		return 'progressbar widget';
	},
	setPath: function(path)
	{
		this.modulePath = path;
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
		return 'Martin Robinson';
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
		return 'app:progressbar';
	},
    getAttributes: function()
    {
		var T = Appcelerator.Types;
        return [{name: 'borderClassName', optional: true, defaultValue: '', type: T.cssClass},
                {name: 'fillClassName', optional: true, defaultValue: '', type: T.cssClass},
                {name: 'width', optional: true, defaultValue: '500px', type: T.cssDimension},
                {name: 'height', optional: true, defaultValue: '30px', type: T.cssDimension},
                {name: 'message', optional: true, type: T.messageReceive},
                {name: 'property', optional: true, type: T.identifier},
                {name: 'animate', optional: true, defaultValue: 'true', type: T.bool}];
    },
	compileWidget: function(params)
	{
		var id = params['id'];
		var animate = params['animate'] == 'true';
		var element = $(id);
		
		var progressbar_border = $(id+'_progressbar_border');
		var progressbar_fill = $(id+'_progressbar_fill');
		
		Appcelerator.Compiler.addTrash(element,function()
		{
		});

		var message = params['message'];
		if (message)
		{
			message = Appcelerator.Compiler.convertMessageType(message);

			var property = params['property'];
			
			$MQL(message,
			function (type, data, datatype, direction)
			{
				$D('received message = '+direction+':'+type+',data='+Object.toJSON(data));
				var value = property ? Object.getNestedProperty(data,property) : data;
                value = Math.max(0, value);
                value = Math.min(100, value);

                if (animate) {
                    $(id+'_progressbar_fill').morph('width: ' + value + '%');
                } else {
				    progressbar_fill.style.width = value + "%";
                }
			},
			element.scope, element);
		}		
	},
	buildWidget: function(element, parameters)
	{
		var borderClassName = parameters['borderClassName'];
		var fillClassName = parameters['fillClassName'];
		var width = parameters['width'];
		var height = parameters['height'];
		var id = element.id;

		var html = '';

		var borderstyle = '"width: ' + width + '; height: ' + height + '"';
		var borderid = '"' + id + '_progressbar_border"';
		html += '<div style=' + borderstyle + ' id=' + borderid;

		if (borderClassName != '') {
			html += ' class="' + borderClassName + '">';
        } else {
			html += ' class="progressbar_border">'
        }

		var fillstyle = '"height: 100%; width: 0%;"';
		var fillid = '"' + id + '_progressbar_fill"';
		html += '<div style=' + fillstyle + ' id=' + fillid;

		if (fillClassName != '') {
			html += ' class="' + fillClassName + '">';
        } else {
			html += ' class="progressbar_fill">';
        }

		html += '</div></div>';
		
		parameters['id'] = element.id;
		
		return {
			'presentation' : html,
			'position' : Appcelerator.Compiler.POSITION_REPLACE,
			'compile' : true,
			'wire' : true
		};
	}
};


Appcelerator.Core.loadModuleCSS('app:progressbar','progressbar.css');
Appcelerator.Widget.register('app:progressbar',Appcelerator.Widget.ProgressBar);
