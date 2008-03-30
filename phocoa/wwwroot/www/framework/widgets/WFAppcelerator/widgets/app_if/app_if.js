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


Appcelerator.Widget.If =
{
	getName: function()
	{
		return 'appcelerator if';
	},
	getDescription: function()
	{
		return 'if widget';
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
		return 'app:if';
	},
	getAttributes: function()
	{        
        return [{
            name: 'expr',
            optional: false,
            type: Appcelerator.Types.javascriptExpr,
            description: "The javascript expression to execute"
        }];
	},
	getChildNodes: function()
	{        
        return [{
            name: 'else',
			optional: true,
			maxNumber: 1
        }, {
			name: 'elseif',
			optional: true,
			attributes: [{
				name: 'expr',
				optional: false,
				type: Appcelerator.Types.javascriptExpr
			}]
		}];
	},
	compileWidget: function(params)
	{
		var id = params['id'];
		
		if (eval(Appcelerator.Widget.If.generateConditional(params['ifcond']['cond'])))
		{
			$(id).innerHTML = params['ifcond']['code'];
			Appcelerator.Compiler.dynamicCompile($(id));
		}
		else
		{
			for (var c=0;c<params['elseifconds'].length;c++)
			{
				var condition = params['elseifconds'][c];
				
				if (eval(Appcelerator.Widget.If.generateConditional(condition['cond'])))
				{
					$(id).innerHTML = condition.code;
					Appcelerator.Compiler.dynamicCompile($(id));
					return;
				}
			}
			
			var elsecond = params['elsecond'];
			if (elsecond)
			{
				$(id).innerHTML = elsecond.code;
				Appcelerator.Compiler.dynamicCompile($(id));
			}
		}
	},
	uniqueFunctionId: 0,
	generateConditional: function(code)
	{
		Appcelerator.Widget.If.uniqueFunctionId++;
		var fname = 'app_if_function'+'_'+Appcelerator.Widget.If.uniqueFunctionId;
		var code = 'var '+fname+' = function () { if ('+code+')';
		code += '{ return true; }';
		code += 'else { return false; }};';
		code += fname+'();';
		return code;		
	},
	buildWidget: function(element,params)
	{
		var ifcond = {code: '', cond: params['expr']};
		var elseifconds = [];
		var elsecond;
		
		if (Appcelerator.Browser.isIE)
		{
			// NOTE: in IE, you have to append with namespace
			var newhtml = element.innerHTML;
			newhtml = newhtml.replace(/<ELSEIF/g,'<APP:ELSEIF').replace(/\/ELSEIF>/g,'/APP:ELSEIF>');
			newhtml = newhtml.replace(/<ELSE/g,'<APP:ELSE').replace(/\/ELSE>/g,'/APP:ELSE>');
			element.innerHTML = newhtml;
		}
		
        if (Appcelerator.Browser.isOpera)
        {
            // NOTE: opera returns case-sensitive tag names, causing the conditions to fail
            var newhtml = element.innerHTML;
            newhtml = newhtml.replace(/<ELSEIF/gi,'<ELSEIF').replace(/\/ELSEIF>/gi,'/ELSEIF>');
            newhtml = newhtml.replace(/<ELSE/gi,'<ELSE').replace(/\/ELSE>/gi,'/ELSE>');
            element.innerHTML = newhtml;
        }
        
		for (var c=0; c<element.childNodes.length; c++)
		{
			(function()
			{
				var code, cond;
				var node = element.childNodes[c];
				
				if (node.nodeType == 1 && node.nodeName == 'ELSEIF')
				{
					if (elsecond)
					{
						throw ('syntax error: elseif after an else detected.');
					}
					elseifconds.push({code: Appcelerator.Compiler.getHtml(node), cond: node.getAttribute('expr')});
				}
				else if (node.nodeType == 1 && node.nodeName == 'ELSE')
				{
					if (elsecond)
					{
						throw ('syntax error: more than one else statement detected.');
					}
					elsecond = {code: Appcelerator.Compiler.getHtml(node)};
				}
				else if (node.nodeType == 1)
				{
					if (elsecond || elseifconds.length > 0)
					{
						throw ('syntax error: html code after an else or elseif detected.');
					}
					ifcond['code'] += Appcelerator.Compiler.convertHtml(Appcelerator.Util.Dom.toXML(node, true), true);
				}
				else if (node.nodeType == 3)
				{
					var val = node.nodeValue.trim();
					if ((elsecond || elseifconds.length > 0) && val.length > 0)
					{
						throw ('Html code after an else or elseif detected.');
					}
					ifcond['code'] += val;					
				}
			})();
		}
		
		params['ifcond'] = ifcond;		
		params['elseifconds'] = elseifconds;
		params['elsecond'] = elsecond;

		return {
			'presentation' : '',
			'position' : Appcelerator.Compiler.POSITION_REPLACE,
			'compile' : true
		};
	}
};

Appcelerator.Widget.register('app:if',Appcelerator.Widget.If);
