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


Appcelerator.Widget.Content =
{
	getName: function()
	{
		return 'appcelerator content';
	},
	getDescription: function()
	{
		return 'content widget support modularizing of code by breaking them into separate files which can be loaded either at load time or dynamically based on a message';
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
		return 'app:content';
	},
	getActions: function()
	{
		return ['execute'];
	},	
	getAttributes: function()
	{
		var T = Appcelerator.Types;
		return [{name: 'on', optional: true, type: T.onExpr,
		         description: "May be used to execute/load the content."},
				{name: 'src', optional: false, type: T.pathOrUrl,
				 description: "The source for the content file to load."},
				{name: 'args', optional: true, type: T.json,
				 description: "Used to replace text in the content file."},
				{name: 'lazy', optional: true, defaultValue: 'false', type: T.bool,
				 description: "Indicates whether the content file should be lazy loaded."},
				{name: 'reload', optional: true, defaultValue: 'false', type: T.bool,
				 description: "Indicates whether the content file should be refetched and reloaded on every execute. If false, execute will do nothing if already executed."},
				{name: 'onload', optional: true, type: T.messageSend,
				 description: "Fire this message when content file is loaded."},
				{name: 'onfetch', optional: true, type: T.messageSend,
				 description: "Fire this message when content file is fetched but before being loaded."},
				{name:'useframe', optional: true, type: T.bool, 
				 description: "Use a hidden iframe when fetching the content, instead of an Ajax request. This is normally not required."}
		];
	},
	execute: function(id,parameterMap,data,scope)
	{
		if (!parameterMap['reload'])
		{
			if (!$(id).fetched && !parameterMap['fetched'])
			{
				Appcelerator.Widget.Content.fetch(id,parameterMap['src'],parameterMap['args'],parameterMap['onload'],parameterMap['onfetch'],parameterMap['useframe']);
				$(id).fetched = true;
			}
		}
		else
		{
			Appcelerator.Widget.Content.fetch(id,parameterMap['src'],parameterMap['args'],parameterMap['onload'],parameterMap['onfetch'],parameterMap['useframe']);
		}
	},
	compileWidget: function(parameters)
	{
		if (!(parameters['lazy'] == 'true'))
		{
			Appcelerator.Widget.Content.fetch(parameters['id'],parameters['src'],parameters['args'],parameters['onload'],parameters['onfetch'],parameters['useframe']);
			parameters['fetched'] = true;
		}
	},
	buildWidget: function(element,parameters,state)
	{
		parameters['reload'] = (parameters['reload'] == 'true');
		
		return {
			'position' : Appcelerator.Compiler.POSITION_REPLACE,
			'presentation' : '',
			'compile' : true
		};
	},
	fetch: function (target,src,args,onload,onfetch,useframe)
	{
        target = $(target);
        target.style.visibility='hidden';

		if (!useframe)
		{
			new Ajax.Request(src,
			{
				asynchronous:true,
				method:'get',
				onSuccess:function(resp)
				{
					if (onfetch)
					{
						$MQ(onfetch,{'src':src,'args':args});
					}
					var scope = target.getAttribute('scope') || target.scope;
					var state = Appcelerator.Compiler.createCompilerState();
					var html = resp.responseText;
					var match = /<body[^>]*>([\s\S]*)?<\/body>/mg.exec(html);
					if (match)
					{
						html = match[1];
					}
					html = Appcelerator.Compiler.addIENameSpace('<div>'+html+'</div>');
					if (args)
					{
						// replace tokens in our HTML with our args
						var t = Appcelerator.Compiler.compileTemplate(html);
						html = t(args.evalJSON());
					}
					// turn off until we're done compiling
					target.innerHTML = html;
					state.onafterfinish=function()
					{
						 // turn it back on once we're done compiling
					     target.style.visibility='visible';
			             if (onload)
			             {
			                $MQ(onload,{'src':src,'args':args});
			             }
					};
					Appcelerator.Compiler.compileElement(target.firstChild,state);
					state.scanned=true;
					Appcelerator.Compiler.checkLoadState(state);
				}
			});
		}
		else
		{
			Appcelerator.Util.IFrame.fetch(src,function(doc)
			{
				if (onfetch)
				{
					$MQ(onfetch,{'src':src,'args':args});
				}

				var scope = target.getAttribute('scope') || target.scope;
				doc.setAttribute('scope',scope);
				doc.scope = scope;
				Appcelerator.Compiler.getAndEnsureId(doc);
				var state = Appcelerator.Compiler.createCompilerState();
				var html = '<div>'+doc.innerHTML+'</div>';
				if (args)
				{
					// replace tokens in our HTML with our args
					var t = Appcelerator.Compiler.compileTemplate(html);
					html = t(args.evalJSON());
				}
				// turn off until we're done compiling
				target.innerHTML = html;
				state.onafterfinish=function()
				{
					 // turn it back on once we're done compiling
				     target.style.visibility='visible';
		             if (onload)
		             {
		                $MQ(onload,{'src':src,'args':args});
		             }
				};
				Appcelerator.Compiler.compileElement(target.firstChild,state);
				state.scanned=true;
				Appcelerator.Compiler.checkLoadState(state);
			},true,true);
		}
	}
};

Appcelerator.Widget.register('app:content',Appcelerator.Widget.Content);
