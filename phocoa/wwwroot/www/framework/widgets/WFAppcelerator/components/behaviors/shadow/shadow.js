Appcelerator.UI.registerUIComponent('behavior','shadow',
{
	/**
	 * The attributes supported by the behaviors. This metadata is 
	 * important so that your behavior can automatically be type checked, documented, 
	 * so the IDE can auto-sense the widgets metadata for autocomplete, etc.
	 */
	getAttributes: function()
	{
		var T = Appcelerator.Types;
		return [{name: 'theme', optional: true, description: "theme for the shadow",defaultValue: Appcelerator.UI.UIManager.getDefaultTheme('shadow')}];
	},
	/**
	 * The version of the behavior. This will automatically be corrected when you
	 * publish the component.
	 */
	getVersion: function()
	{
		// leave this as-is and only configure from the build.yml file 
		// and this will automatically get replaced on build of your distro
		return '1.0';
	},
	/**
	 * The behavior spec version.  This is used to maintain backwards compatability as the
	 * Widget API needs to change.
	 */
	getSpecVersion: function()
	{
		return 1.0;
	},
	/**
	 * This is called when the behavior is loaded and applied for a specific element that 
	 * references (or uses implicitly) the behavior.
	 */
	build:  function(element,options)
	{
		var newContainer = document.createElement('div');	
		newContainer.id = element.id + "_shadow";	
		Element.addClassName(newContainer,"shadow");
		Element.addClassName(newContainer,'shadow_' + options['theme']);
		element.wrap(newContainer);	
	

		// add modal dependency
		Appcelerator.UI.addElementUIDependency(element,'behavior','shadow','behavior', 'modal', function(element)
		{
			if (element.style.width == '')
			{
				throw "Shadow behavior requires an explicit width via the 'style' attribute when used with the modal behavior";
				return;
			}
			newContainer.style.width = element.style.width;	
		});		

		// add tooltip dependency
		Appcelerator.UI.addElementUIDependency(element,'behavior','shadow','behavior', 'tooltip', function(element)
		{
			Element.hide(newContainer);
		});		

		// add rounded dependency
		Appcelerator.UI.addElementUIDependency(element,'behavior','shadow','behavior', 'rounded', function(element)
		{
			element.style.height = "auto";
			
		});		

		// add dependency for closeable panel
		Appcelerator.UI.addElementUIDependency(element,'behavior','shadow','control', 'panel', function(element)
		{
			Appcelerator.Compiler.registerConditionListener(element,'hide',function(el,condition)
			{
				Element.hide(newContainer);
			});
		});		

		Appcelerator.Core.loadTheme('behavior','shadow',options['theme'],element,options);	

	}
});
