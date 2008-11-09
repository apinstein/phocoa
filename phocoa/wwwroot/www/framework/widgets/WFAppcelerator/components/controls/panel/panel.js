Appcelerator.UI.registerUIComponent('control','panel',
{
	/**
	 * The attributes supported by the controls. This metadata is 
	 * important so that your control can automatically be type checked, documented, 
	 * so the IDE can auto-sense the widgets metadata for autocomplete, etc.
	 */
	getAttributes: function()
	{
		var T = Appcelerator.Types;
		
		return [{name: 'theme', optional: true, description: "theme for the panel",defaultValue: Appcelerator.UI.UIManager.getDefaultTheme('panel')},
				{name: 'height', optional: true, description: " height for panel" ,defaultValue: 'auto', type: T.cssDimension},		 
				{name: 'width', optional: true, description: " width for panel" ,defaultValue: 'auto', type: T.cssDimension},		 
				{name: 'footer', optional: true, description: " footer for panel" , defaultValue: ''},
				{name: 'title', optional: true, description: " title for panel" , defaultValue: ''},
				{name: 'closeable', optional: true, description: " is panel closeable" , defaultValue: true}
				];
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
	title:function(id,parameters,data,scope,version,attrs,direction,action)
	{
		var key = 'value';
		if (attrs && typeof(v) == 'string')
		{
			attrs = attrs.evalJSON();
		}
		if (attrs && attrs.length > 0)
		{
			key = attrs[0].key;
		}
		$(id + '_title').innerHTML = Object.getNestedProperty(data,key) || '';
	},
	
	footer:function(id,parameters,data,scope,version,attrs,direction,action)
	{
		var key = 'value';
		if (attrs && typeof(v) == 'string')
		{
			attrs = attrs.evalJSON();
		}
		if (attrs && attrs.length > 0)
		{
			key = attrs[0].key;
		}
		$(id + '_footer').innerHTML = Object.getNestedProperty(data,key) || '';
	},

	getActions: function()
	{
		return ['title','footer'];
	},
	
	build: function(element,options)
	{
		var classPrefix = "panel_" + options['theme'];
		
		var html = '<div id="'+element.id+'_panel" style="width:'+options['width']+'" class="'+classPrefix+'">';
		html += '<div id="'+element.id+'_hdr" class="' + classPrefix + '_hdr">';				
		html += '<div  id="'+element.id+'_tl" class="'+classPrefix+'_tl"></div>';
		html += '<div id="' + element.id + '_title" class="'+classPrefix+'_title">'+options['title']+'</div>';			

		if (options['closeable'] == true)
		{
			html += '<div id="' + element.id + '_close" class="'+classPrefix+'_close"></div>';			
		}
		html += '<div  id="'+element.id+'_tr" class="'+classPrefix+'_tr"></div>';
		html += '</div>';
		
		if (Appcelerator.Browser.isIE6)
		{
			options['height'] = (options['height']=='auto')?'30px':options['height'];
		}
		html += '<div  class="panel_body '+classPrefix+'_body" style="height:'+options['height']+'">';
		html += element.innerHTML;
		html += '</div>';
		html += '<div  id="'+element.id+'_btm" class="'+classPrefix+'_btm">';
		html += '<div  id="'+element.id+'_bl" class="'+classPrefix+'_bl"></div>';
		html += '<div id="' + element.id + '_footer" class="'+classPrefix+'_btm_text ">'+options['footer']+'</div>';			
		html += '<div  id="'+element.id+'_br" class="'+classPrefix+'_br"></div>';
		html += '</div>';			
		html += '</div>';			
		element.innerHTML = html;
		element.style.width = options['width'];
		element.style.height = "auto";
		Element.addClassName(element,classPrefix);

		// wire close	
		if (options['closeable'] == true)
		{
			var mainHandler = Element.observe(element.id+'_close','click',function(e)
			{
				Element.hide(element.id);
				Appcelerator.Compiler.fireCustomCondition(element.id, 'hide', {'id': element.id});
				
			});			

		}

		// if tooltip and IE need width
		if (Appcelerator.Browser.isIE)
		{
			Appcelerator.UI.addElementUIDependency(element,'control','panel','behavior', 'tooltip', function(element)
			{
				if (options['width']=='auto')
				{
					$(element.id + "_panel").style.width = "300px";
					element.style.width = "300px";			
				}
			});
		}
		// IE and FF shadow + panel needs width
		if (Appcelerator.Browser.isIE6 || Appcelerator.Browser.isGecko)
		{
			var parentWidth = Element.getStyle(element.parentNode,'width');
			var width = "220px"
			
			// use parent if we are in a grid layout
			if (parentWidth && parentWidth.endsWith('px') && element.parentNode.getAttribute('cols'))
			{
				width = parentWidth;
			}

			// always set if IE6
			if (Appcelerator.Browser.isIE6 && options['width']=='auto')
			{
				$(element.id + "_panel").style.width =width;	
				element.style.width = width;
			}

			// set for shadow for all IEs
			Appcelerator.UI.addElementUIDependency(element,'control','panel','behavior', 'shadow', function(element)
			{
				// must set width on IE with shadow
				if (options['width']=='auto')
				{
					$(element.id + "_panel").style.width = width;
					element.style.width = width;

				}
			});
		}

		// fix PNGs
		if (Appcelerator.Browser.isIE6)
		{
			$(element.id + "_tl").addBehavior(Appcelerator.Core.getModuleCommonDirectory() + '/images/appcelerator/iepngfix.htc');
			$(element.id + "_hdr").addBehavior(Appcelerator.Core.getModuleCommonDirectory() + '/images/appcelerator/iepngfix.htc');
			$(element.id + "_tr").addBehavior(Appcelerator.Core.getModuleCommonDirectory() + '/images/appcelerator/iepngfix.htc');
			$(element.id + "_bl").addBehavior(Appcelerator.Core.getModuleCommonDirectory() + '/images/appcelerator/iepngfix.htc');
			$(element.id + "_btm").addBehavior(Appcelerator.Core.getModuleCommonDirectory() + '/images/appcelerator/iepngfix.htc');
			$(element.id + "_br").addBehavior(Appcelerator.Core.getModuleCommonDirectory() + '/images/appcelerator/iepngfix.htc');
			if (options['closeable'] == true)
			{
				$(element.id + "_close").addBehavior(Appcelerator.Core.getModuleCommonDirectory() + '/images/appcelerator/iepngfix.htc');
			}
		}

		Appcelerator.Core.loadTheme('control','panel',options['theme'],element,options);	
	}
});
