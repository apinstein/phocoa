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


Appcelerator.Widget.Tooltip =
{
    getName: function()
    {
        return 'appcelerator tooltip';
    },
    getDescription: function()
    {
        return 'tooltip widget';
    },
    getVersion: function()
    {
        return 1.1;
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
        return 'app:tooltip';
    },
    getAttributes: function()
    {
		var T = Appcelerator.Types;        
        return [{
            name: 'element',
            optional: false,
            description: "element for tooltip"
        }, {
            name: 'backgroundColor',
            optional: true,
            defaultValue: '#FC9',
			type: T.color,
            description: "background color of tooltip"
        }, {
            name: 'borderColor',
            optional: true,
            defaultValue: '#c96',
			type: T.color,
            description: "border color of tooltip"
        }, {
            name: 'mouseFollow',
            optional: true,
            defaultValue: false,
			type: T.bool,
            description: "should tip follow mouse"
        }, {
            name: 'opacity',
            optional: true,
            defaultValue: '.75',
			type: T.number,
            description: "tooltip opacity"
        }, {
            name: 'textColor',
            optional: true,
            defaultValue: '#111',
			type: T.color,
            description: "text color of tooltip"
        }];
    },

	compileWidget: function(parameters)
	{
		var mouseFollow = (parameters['mouseFollow']||'true')=="true";
		var tip = new Tooltip($(parameters['element']), 
			{ backgroundColor:parameters['backgroundColor'],
			  borderColor:parameters['borderColor'],
			  textColor:parameters['textColor'],
			  mouseFollow:mouseFollow,
			  opacity:parameters['opacity']
			});
		tip.content = parameters['html'];
	},
    buildWidget: function(element,parameters)
    {
 		parameters['html'] = Appcelerator.Compiler.getHtml(element);
		return {
			'presentation' :'' ,
			'position' : Appcelerator.Compiler.POSITION_REPLACE,
			'parameters': parameters,
			'wire' : false,
			'compile':true
		};
    }
};

Appcelerator.Core.loadModuleCSS("app:tooltip","tooltips.css");
Appcelerator.Core.requireCommonJS('scriptaculous/builder.js',function()
{
    Appcelerator.Widget.registerWithJS('app:tooltip',Appcelerator.Widget.Tooltip,['tooltips.js']);
});

