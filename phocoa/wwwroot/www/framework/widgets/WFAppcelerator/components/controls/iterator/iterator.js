Appcelerator.UI.registerUIComponent('control','iterator',
{
	/**
	 * The attributes supported by the controls. This metadata is 
	 * important so that your control can automatically be type checked, documented, 
	 * so the IDE can auto-sense the widgets metadata for autocomplete, etc.
	 */
	getAttributes: function()
	{
		var T = Appcelerator.Types;
        return [{name: 'on', optional: true, type: T.onExpr,
		         description: "Used to execute the iterator"},
                {name: 'items', optional: true, type: T.json,
				 description: "Literal (or template-replaced) JSON array to iterate over"},
                {name: 'property', optional: true, type: T.identifier},         
                {name: 'rowEvenClassName', optional: true, type: T.cssClass},
                {name: 'rowOddClassName', optional: true, type: T.cssClass},
                {name: 'table', optional: true, defaultValue: 'false', type: T.bool},
                {name: 'width', optional: true, defaultValue: '100%', type: T.cssDimension},
                {name: 'headers', optional: true, defaultValue: ',', type: T.commaSeparated},
                {name: 'cellspacing', optional: true, defaultValue: '0', type: T.cssDimension},
                {name: 'selectable', optional: true, type: T.bool}];
	},
	/**
	 * The version of the control. This will automatically be corrected when you
	 * publish the component.
	 */
	getVersion: function()
	{
		// leave this as-is and only configure from the build.yml file 
		// and this will automatically get replaced on build of your distro
		return '1.0';
	},
	/**
	 * The control spec version.  This is used to maintain backwards compatability as the
	 * Widget API needs to change.
	 */
	getSpecVersion: function()
	{
		return 1.0;
	},
	getActions: function()
	{
		return ['execute'];
	},	
	execute: function(id,parameterMap,data,scope)
	{
		var compiled = parameterMap['compiled'];
		var propertyName = parameterMap['property'];
		var items = parameterMap['items'];
		
		var table = parameterMap['table'];
		var width = parameterMap['width'];
		var headers = parameterMap['headers'];
		var selectable = parameterMap['selectable'];
		var array = null;
		
		if (!compiled)
		{
			compiled = eval(parameterMap['template'] + '; init_'+id);
			parameterMap['compiled'] = compiled;
		}
		
		if (items) 
		{
			data = items.evalJSON() || [];
		}
		
		if (propertyName)
		{
			array = Object.getNestedProperty(data,propertyName) || [];
		}
		else
		{
			array = data;
		}
		
		var html = '';
		
		if (!array)
		{
			html = compiled(data);
		}
		else
		{
			if (table)
			{				
				html+='<table width="'+width+'" cellspacing="'+parameterMap['cellspacing']+'"><tr>';
				headers.each(function(h)
				{
					html+='<th>'+h+'</th>';
				});
				html+='</tr>';
			}
         	// this is in the case we pass in an object instead of 
			// an array, make it an array of length one so we can iterate
			// !Object.isArray(array) fails in some cases so we don't use it (it's poorly implemented)
			if (array.length != 0 && array[0] == undefined)
			{
				array = [array];
			}
			for (var c = 0, len = array.length; c < len; c++)
			{
				var o = array[c];
				if(typeof o != "object")
				{
					o = {'iterator_value': o};
				}
				o['iterator_index']=c;
				o['iterator_length']=len;
				o['iterator_odd_even']=(c%2==0)?'even':'odd';
				if (table)
				{
					if (o['iterator_odd_even'] == 'odd')
						html+='<tr class="'+parameterMap['rowOddClassName']+'">';
					else
						html+='<tr class="'+parameterMap['rowEvenClassName']+'">';
				}
				/* escape out the "'" so that works in IE */
				for (var idx in o)
				{
					if (typeof o[idx] == 'string')
					{
						o[idx] = o[idx].replace(/'/,'\u2019');
					}
				}
				html += compiled(o);
				if (table)
				{
					html+='</tr>';
				}
			}
			if (table)
			{
				html+='</table>';
			}
		}
		var element = $(id);
		if (selectable)
		{
			element.setAttribute('selectable',selectable);
		}

        Appcelerator.Compiler.destroyContent(element);
		element.innerHTML = Appcelerator.Compiler.addIENameSpace(html);
		Appcelerator.Compiler.dynamicCompile(element);
	},
	/**
	 * This is called when the control is loaded and applied for a specific element that 
	 * references (or uses implicitly) the control.
	 */
	build: function(element, parameters)
	{
		parameters['template'] = Appcelerator.Compiler.compileTemplate(Appcelerator.Compiler.getHtml(element),true,'init_'+element.id);
		parameters['table'] = parameters['table'] == 'true';
		if (parameters['table'])
		{
			parameters['headers'] = parameters['headers'].split(',');
		}
	}
});
