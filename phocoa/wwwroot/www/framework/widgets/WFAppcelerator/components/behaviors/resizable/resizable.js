Appcelerator.UI.registerUIComponent('behavior','resizable',
{
	/**
	 * The attributes supported by the behaviors. This metadata is 
	 * important so that your behavior can automatically be type checked, documented, 
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
		options.resize = function(e)
		{
			var listeners = element.resizeListeners;
			if (listeners && listeners.length > 0)
			{
				for (var c=0;c<listeners.length;c++)
				{
					var cb = listeners[c];
					cb.onResize(e);
				}
			}
		};
		
		element.resizable = new Resizeable(element.id, options);
		
		Appcelerator.Compiler.addTrash(element, function()
		{
			element.resizable.destroy();
		});
	}
});
