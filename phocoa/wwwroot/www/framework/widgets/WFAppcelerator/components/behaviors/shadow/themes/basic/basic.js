Appcelerator.Core.registerTheme('behavior','shadow','basic',
{
	/**
	 * The attributes supported by the themes. This metadata is 
	 * important so that your theme can automatically be type checked, documented, 
	 * so the IDE can auto-sense the widgets metadata for autocomplete, etc.
	 */
	getAttributes: function()
	{
		var T = Appcelerator.Types;
		
		/*
		Example: 
		return [{name: 'mode', optional: false, description: "Vertical or horizontal alignment",
		         type: T.enumeration('vertical', 'horizontal')}]
		*/
		
		//TODO
		return [];
	},
	/**
	 * The version of the theme. This will automatically be corrected when you
	 * publish the component.
	 */
	getVersion: function()
	{
		// leave this as-is and only configure from the build.yml file 
		// and this will automatically get replaced on build of your distro
		return '1.0';
	},
	/**
	 * The theme spec version.  This is used to maintain backwards compatability as the
	 * Widget API needs to change.
	 */
	getSpecVersion: function()
	{
		return 1.0;
	},
	/**
	 * This is called when the theme is loaded and applied for a specific element that 
	 * references (or uses implicitly) the theme.
	 */
	build: function(element,options)
	{
		var container = $(element.id + "_shadow");	
		container.style.position = "relative";
		container.style.width = Element.getStyle(element,'width');
		element.style.position = "relative";
		element.style.zIndex = "2";
		
		// add shadow elements
		var s1 = document.createElement('div')
		s1.className = "shadow_basic_1";
		
		// needed for IE6 - need to set height for
		// this element in other behaviors
		s1.id = element.id + "_shadow_basic_main";
		
		var s2 = document.createElement('div')
		s2.className = "shadow_basic_2";
		var s3 = document.createElement('div')
		s3.className = "shadow_basic_3";
		
		container.appendChild(s1);
		container.appendChild(s2);
		container.appendChild(s3);
		
		// set height and process top and right PNG files
		if (Appcelerator.Browser.isIE6)
		{
			s1.style.height = Element.getStyle(element,'height');
			
			// set height if rounded
			Appcelerator.UI.addElementUIDependency(element,'behavior','shadow','behavior', 'rounded', function(element)
			{
				s1.style.height = Element.getStyle(element,'height');
			});		
			// set height on modal (must be when shown - height is zero when hidden)
			Appcelerator.UI.addElementUIDependency(element,'behavior','shadow','behavior', 'modal', function(element)
			{
				Appcelerator.Compiler.registerConditionListener(element,'show',function(element,condition)
				{
					s1.style.height = Element.getStyle(element,'height');
				});
			});		
			// set height on tooltip (must be when shown - height is zero when hidden)
			Appcelerator.UI.addElementUIDependency(element,'behavior','shadow','behavior', 'tooltip', function(element)
			{
				Appcelerator.Compiler.registerConditionListener(element,'show',function(element,condition)
				{
					s1.style.height = Element.getStyle(element,'height');
				});
			});		
			// set height on panel
			Appcelerator.UI.addElementUIDependency(element,'behavior','shadow','control', 'panel', function(element)
			{
				s1.style.height = Element.getStyle(element,'height');
			});		

			// process PNGs
			$(s2).addBehavior(Appcelerator.Core.getModuleCommonDirectory() + '/images/appcelerator/iepngfix.htc');
			$(s3).addBehavior(Appcelerator.Core.getModuleCommonDirectory() + '/images/appcelerator/iepngfix.htc');
		
		}
		
	}
});
