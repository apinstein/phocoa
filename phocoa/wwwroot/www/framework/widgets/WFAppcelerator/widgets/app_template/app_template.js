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


Appcelerator.Widget.Template =
{
	getName: function()
	{
		return 'appcelerator template';
	},
	getDescription: function()
	{
		return 'template widget';
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
		return 'app:template';
	},
	execute: function(id,parameterMap,data,scope)
	{
		Appcelerator.Widget.Template.fetch(id,parameterMap['src'],parameterMap['args']);
	},
	getAttributes: function()
	{
		var T = Appcelerator.Types;        
        return [{
            name: 'src',
            optional: false,
			type: T.pathOrUrl,
            description: "The source for the template file to load."
        }, {
            name: 'args',
            optional: true,
			type: T.json,
            description: "Used to replace text in the template file."
        }];
	},	
	buildWidget: function(element,parameters,state)
	{
		var src = parameters['src'];
		var args = parameters['args'];
		var payload = {};

		element.innerHTML = Appcelerator.Compiler.getHtml(element);		
		if (element.childNodes.length > 0)
		{
			for (var c=0;c<element.childNodes.length;c++)
			{
				var node = element.childNodes[c];
				if (node.nodeType == 1)
				{
					var id = node.getAttribute('id');
					payload[id] = Appcelerator.Compiler.getHtml(node,true);
				}
			}
		}

		Appcelerator.Widget.Template.fetch(element.id,element.scope,src,args,payload,state);

		return {
			'position' : Appcelerator.Compiler.POSITION_REPLACE,
			'presentation' : ''
		};
	},
	fetch: function (target,scope,src,args,payload,state)
	{
		state.pending++;
		
		Appcelerator.Util.IFrame.fetch(src,function(doc)
		{
			if (args)
			{
				var html = doc.innerHTML;
				// replace tokens in our HTML with our args
				var t = Appcelerator.Compiler.compileTemplate(html);
				html = t(args.evalJSON());
				doc.innerHTML = html;
			}
			
			for (var token in payload)
			{
				if (typeof token == 'string')
				{
					var item = doc.ownerDocument.getElementById(token);
					if (item)
					{
						item.innerHTML = payload[token];
					}
					else
					{
						Logger.warn('Element with id = '+token+' not found in '+src);
					}
				}
			}
			
			var t = $(target);
			t.setAttribute('scope',scope);
			t.innerHTML = Appcelerator.Compiler.getHtml(doc);
			state.pending--;
			Appcelerator.Compiler.compileElement(t,state,false);
			Appcelerator.Compiler.checkLoadState(state);
		});
	}
};

Appcelerator.Widget.register('app:template',Appcelerator.Widget.Template);
