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


Appcelerator.Widget.Button =
{
	getName: function()
	{
		return 'appcelerator button';
	},
	getDescription: function()
	{
		return 'button widget';
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
		return 'app:button';
	},
	getActions: function()
	{
		return ['enable', 'disable'];
	},	
	getAttributes: function()
	{
		var T = Appcelerator.Types;
		return [{name: 'on', optional: true, type: T.onExpr},
				{name: 'width', optional: true, defaultValue: 200, type: T.cssDimension},
				{name: 'disabled', optional: true, defaultValue: 'false', type: T.bool},
				{name: 'corner', optional: true, defaultValue: 'round', type: T.enumeration('round','square')},
				{name: 'color', optional: true, defaultValue: 'dark', type: T.enumeration('dark','light')},
				{name: 'icon', optional: true, defaultValue: '', type: T.enumeration('add','delete','edit','save','')},
				{name: 'fieldset', optional: true, type: T.fieldset},
				{name: 'activators', optional: true}];
	},
	dontParseOnAttributes: function()
	{
		return true;
	},
	//Helper function to check if the button is disabled.  Firefox, Safari and IE do different
	//things
	isEnabled: function(params)
	{
		return !(params['disabled'] == "true" || params['disabled'] == true);
	},
	compileWidget: function(parameters)
	{
		var id = parameters['id'];
		var button = $(id);
		var left = $(id+'_left');
		var middle = $(id+'_middle');
		var right = $(id+'_right');
		var corner = parameters['corner'];
		var color = parameters['color'];
		var disabled = '';
		if (parameters['disabled'] == 'true')
		{
			$(id).disabled = true;
			disabled = '_disabled';
		}
		
		button.onmouseover = function()
		{
			if(Appcelerator.Widget.Button.isEnabled(parameters))
			{
				left.className = 'button_'+color+'_'+corner+'_left_over';
				middle.className = 'button_'+color+'_'+corner+'_middle_over button_'+color+'_text';
				right.className = 'button_'+color+'_'+corner+'_right_over';
				if (Appcelerator.Browser.isIE6)
				{
					Appcelerator.Browser.fixBackgroundPNG(left);
					Appcelerator.Browser.fixBackgroundPNG(middle);
					Appcelerator.Browser.fixBackgroundPNG(right);
				}
			}
		};
		
		button.onmouseout = function()
		{
			if (Appcelerator.Widget.Button.isEnabled(parameters))
			{
				left.className = 'button_'+color+'_'+corner+'_left';
				middle.className = 'button_'+color+'_'+corner+'_middle button_'+color+'_text';
				right.className = 'button_'+color+'_'+corner+'_right';
				if (Appcelerator.Browser.isIE6)
				{
					Appcelerator.Browser.fixBackgroundPNG(left);
					Appcelerator.Browser.fixBackgroundPNG(middle);
					Appcelerator.Browser.fixBackgroundPNG(right);
				}
			}
		};
		
		button.onmousedown = function()
		{
			if (Appcelerator.Widget.Button.isEnabled(parameters))
			{
				left.className = 'button_'+color+'_'+corner+'_left_press';
				middle.className = 'button_'+color+'_'+corner+'_middle_press button_'+color+'_text';
				right.className = 'button_'+color+'_'+corner+'_right_press';
				if (Appcelerator.Browser.isIE6)
				{
					Appcelerator.Browser.fixBackgroundPNG(left);
					Appcelerator.Browser.fixBackgroundPNG(middle);
					Appcelerator.Browser.fixBackgroundPNG(right);
				}
			}
		};		

		button.onmouseup = function()
		{
			if (Appcelerator.Widget.Button.isEnabled(parameters))
			{
				left.className = 'button_'+color+'_'+corner+'_left_over';
				middle.className = 'button_'+color+'_'+corner+'_middle_over button_'+color+'_text';
				right.className = 'button_'+color+'_'+corner+'_right_over';
				if (Appcelerator.Browser.isIE6)
				{
					Appcelerator.Browser.fixBackgroundPNG(left);
					Appcelerator.Browser.fixBackgroundPNG(middle);
					Appcelerator.Browser.fixBackgroundPNG(right);
				}
			}
		};		

		if (parameters['activators'])
		{
			button.onActivatorsDisable = function()
			{
				Appcelerator.Widget.Button.disable(id, parameters);
			};
			button.onActivatorsEnable = function()
			{
				Appcelerator.Widget.Button.enable(id, parameters);
			};
		}
	},
	enable: function(id,parameters,data,scope,version)
	{
		var id = parameters['id'];
		var button = $(id);
		var color = parameters['color'];
		var corner = parameters['corner'];
		var left = $(id+'_left');
		var middle = $(id+'_middle');
		var right = $(id+'_right');

		button.disabled = false;
		left.className = 'button_'+color+'_'+corner+'_left';
		middle.className = 'button_'+color+'_'+corner+'_middle button_'+color+'_text';
		right.className = 'button_'+color+'_'+corner+'_right';
		button.className = 'button_widget';
		if (Appcelerator.Browser.isIE6)
		{
			Appcelerator.Browser.fixBackgroundPNG(left);
			Appcelerator.Browser.fixBackgroundPNG(middle);
			Appcelerator.Browser.fixBackgroundPNG(right);
		}
		$(id).parentNode.disabled = false;	
       	
	},
	disable: function(id,parameters,data,scope,version)
	{
		var id = parameters['id'];
		var button = $(id);
		var corner = parameters['corner'];
		var color = parameters['color'];
		var left = $(id+'_left');
		var middle = $(id+'_middle');
		var right = $(id+'_right');

		button.disabled = true;
		left.className = 'button_'+color+'_'+corner+'_left_disabled';
		middle.className = 'button_'+color+'_'+corner+'_middle_disabled button_'+color+'_text_disabled';
		right.className = 'button_'+color+'_'+corner+'_right_disabled';
		button.className = 'button_widget_disabled';
		if (Appcelerator.Browser.isIE6)
		{
			Appcelerator.Browser.fixBackgroundPNG(left);
			Appcelerator.Browser.fixBackgroundPNG(middle);
			Appcelerator.Browser.fixBackgroundPNG(right);
		}
		$(id).parentNode.disabled = true;
	},
	buildWidget: function(element,parameters)
	{
		var elementText = Appcelerator.Compiler.getHtml(element);
		var corner = parameters['corner'];
		var color = parameters['color'];
		var icon = parameters['icon'];
		if (parameters['icon'] != '')
		{
			icon = 'button_icon_' + parameters['icon'];
		}
		
		var disabled = '';
		if (!Appcelerator.Widget.Button.isEnabled(parameters))
		{
			disabled = '_disabled';
		}
		
		var html = '<button class="button_widget'+disabled+'" id="'+element.id+'" style="width:'+parameters['width']+'px"';
        if(typeof parameters['on'] != 'undefined') 
        {
            html += ' on="'+parameters['on']+'"';
        } 
       	if (parameters['fieldset'])
		{
			html += ' fieldset="'+parameters['fieldset']+'"';
		}
		if (parameters['activators'])
		{
			html += ' activators="'+parameters['activators']+'"';
		}
		if('' != disabled)
		{
			html += ' disabled="true"';
		}
        html += '>';
        
		html += '<table class="button_table" border="0" cellpadding="0" cellspacing="0" width="100%">';
		html += '<tr>';
		html += '<td class="button_'+color+'_'+corner+'_left'+disabled+'" id="'+element.id+'_left">';
		html += '</td>';
		html += '<td class="button_'+color+'_'+corner+'_middle'+disabled+' button_'+color+'_text'+disabled+'" id="'+element.id+'_middle" align="middle">';
		html += '<div align="center"><table cellpadding="0" cellspacing="0" border="0"><tr><td><div'; 
        if(icon != '')
        {
            html += ' style="margin-right: 10px"';
        }
        html += '>'+elementText+'</div></td>';
		html += '<td class="'+icon+'"></td></tr></table></div></td>';
		html += '<td class="button_'+color+'_'+corner+'_right'+disabled+'" id="'+element.id+'_right">';
		html += '</td>';
		html += '</tr>';
		html += '</table>';
		html += '</button>';

		return {
			'presentation' : html,
			'position' : Appcelerator.Compiler.POSITION_REPLACE,
			'compile' : true,
			'wire' : true
		};
	}
};

Appcelerator.Core.loadModuleCSS('app:button','button.css');
Appcelerator.Widget.register('app:button',Appcelerator.Widget.Button);
