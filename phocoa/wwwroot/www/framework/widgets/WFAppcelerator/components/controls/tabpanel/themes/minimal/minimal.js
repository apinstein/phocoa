Appcelerator.Core.registerTheme('control','tabpanel','minimal',
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
		var tabs = Appcelerator.UI.getTabs(element);
		// create hoverbox
		var hoverBox = document.createElement('li');
		Element.addClassName(hoverBox,'tabpanel_minimal_hoverbox');
		hoverBox.id = element.id + "_hoverbox";
		hoverBox.style.position = "relative";
		hoverBox.style.left = -(109 * (tabs.length/3)) +"px";
		new Insertion.After(tabs[(tabs.length -1)],hoverBox);

		var tabCount = (tabs.length)/3;
		var id = element.id;

		// add tab listeners
		for (var i=1;i<=tabCount;i++)
		{
			(function()
			{
				var tab = $(id + '_tabmid_' + i);
				Event.observe(tab,'mouseover',function(event)
				{
					var newSlot = Event.element(event).offsetLeft;
					new Effect.MoveBy($(id + "_hoverbox"), 0, newSlot-($(id + "_hoverbox").offsetLeft-5),{duration: 2, transition: Effect.Transitions.SwingTo});
					return false;
				});
			})();
		}
	}
});
