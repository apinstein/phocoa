Appcelerator.UI.registerUIComponent('layout','xy',
{
	/**
	 * The attributes supported by the layouts. This metadata is 
	 * important so that your layout can automatically be type checked, documented, 
	 * so the IDE can auto-sense the widgets metadata for autocomplete, etc.
	 */
	getAttributes: function()
	{
		var T = Appcelerator.Types;
		return [{name: 'top', optional: true, description: "top positioning",defaultValue: ''},
			    {name: 'bottom', optional: true, description: "bottom positioning",defaultValue: ''},
		        {name: 'left', optional: true, description: "left positioning",defaultValue: ''},
		        {name: 'right', optional: true, description: "right positioning",defaultValue: ''}
		];
	},
	/**
	 * The version of the layout. This will automatically be corrected when you
	 * publish the component.
	 */
	getVersion: function()
	{
		// leave this as-is and only configure from the build.yml file 
		// and this will automatically get replaced on build of your distro
		return '1.0';
	},
	/**
	 * The layout spec version.  This is used to maintain backwards compatability as the
	 * Widget API needs to change.
	 */
	getSpecVersion: function()
	{
		return 1.0;
	},
	/**
	 * This is called when the layout is loaded and applied for a specific element that 
	 * references (or uses implicitly) the layout.
	 */
	build:  function(element,options)
	{
		element.parentNode.style.position = "relative";
		element.style.position = "absolute";
		if (options['top']!=''){element.style.top = options['top'];}
		if (options['bottom']!=''){element.style.bottom = options['bottom'];}
		if (options['left']!=''){element.style.left = options['left'];}
		if (options['right']!=''){element.style.right = options['right'];}
	}
});
