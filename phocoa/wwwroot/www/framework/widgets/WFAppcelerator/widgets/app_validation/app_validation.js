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


Appcelerator.Compiler.registerCustomCondition(
{
    conditionNames: ['valid', 'invalid']
},
function (element,condition,action,elseAction,delay,ifCond)
{
	switch (condition)
	{
		case 'valid':
		case 'invalid':
		{
			if (Appcelerator.Compiler.getTagname(element,true)!='app:validation')
			{
				throw condition+' condition can only be used on app:validation widget, found on: '+element.nodeName;
			}
			Appcelerator.Widget.Validation.create(element,condition,action,elseAction,delay,ifCond);
			return true;
		}
		default:
		{
			return false;
		}
	}
});

Appcelerator.Widget.Validation =
{
	getName: function()
	{
		return 'appcelerator validation';
	},
	getDescription: function()
	{
		return 'validation widget';
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
		return 'app:validation';
	},
	getAttributes: function()
	{
		return [];
	},	
	buildWidget: function(element,parameters,state)
	{
		return {
			'position' : Appcelerator.Compiler.POSITION_REPLACE,
			'presentation':''
		};
	},
	create: function (element,condition,action,elseAction,delay,ifCond)
	{
		Element.cleanWhitespace(element);
		var newhtml = element.innerHTML;
		newhtml = newhtml.replace(/<MEMBERS/g,'<APP:MEMBERS').replace(/\/MEMBERS>/g,'/APP:MEMBERS>');
		element.innerHTML = newhtml;
		
		var me = element.firstChild;
		if (!me || me.length <= 0)
		{
			throw "required 'members' element not found for "+element.nodeName;
		}
		
		var value = Appcelerator.Compiler.getHtml(me,false);
		var tokens = value.split(/[ ,]/);
		var id = element.id;

		var validAction;
		var invalidAction;
		if (condition=='valid')
		{
			validAction = Appcelerator.Compiler.makeConditionalAction(id,action,ifCond);
		}
		else if (elseAction)
		{
			validAction = Appcelerator.Compiler.makeConditionalAction(id,elseAction,ifCond);
		}
		if (condition=='invalid')
		{
			invalidAction = Appcelerator.Compiler.makeConditionalAction(id,action,ifCond);
		}
		else if (elseAction)
		{
			invalidAction = Appcelerator.Compiler.makeConditionalAction(id,elseAction,ifCond);
		}
		
		var obj = 
		{
			members:[],
			invalid: null,

			listener: function (elem,valid)
			{
				if (this.invalid!=null && (valid && !this.invalid))
				{
					// no change
					return;
				}
				var invalid = true;
				if (valid)
				{
					invalid = false;
					for (var c=0,len=this.members.length;c<len;c++)
					{
						if (!this.members[c].validatorValid)
						{
							invalid = true;
							break;
						}
					}
				}
				if (this.invalid!=null && (this.invalid == invalid))
				{
					// no change
					return;
				}
				this.invalid = invalid;
				if (invalid && invalidAction)
				{
					Appcelerator.Compiler.executeAfter(invalidAction,delay);
				}
				else if (!invalid)
				{
					Appcelerator.Compiler.executeAfter(validAction,delay);
				}
			}
		};
		obj.listenerFunc = obj.listener.bind(obj);
		var valid = true;
		for (var c=0,len=tokens.length;c<len;c++)
		{
			var token = tokens[c].trim();
			if (token.length > 0)
			{
				var elem = $(token);
				if (!elem)
				{
					throw "couldn't find validation member with ID: "+token;
				}
				if (!Appcelerator.Compiler.getFunction(elem,'addValidationListener'))
				{
					if (elem.field)
					{
						elem = elem.field;
					}
					else
					{
						throw "element with ID: "+token+" doesn't have a validator";
					}
				}
				obj.members.push(elem);
				Appcelerator.Compiler.executeFunction(elem,'addValidationListener',[obj.listenerFunc]);
				valid = valid && elem.validatorValid;
			}
		}
		// make the initial call
		obj.listenerFunc(obj.members[0],valid);
		return obj;
	}
};

Appcelerator.Widget.register('app:validation',Appcelerator.Widget.Validation);
