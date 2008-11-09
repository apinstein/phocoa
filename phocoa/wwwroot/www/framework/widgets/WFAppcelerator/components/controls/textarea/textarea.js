Appcelerator.UI.registerUIComponent('control','textarea',
{
	/**
	 * The attributes supported by the controls. This metadata is 
	 * important so that your control can automatically be type checked, documented, 
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
		
		return [
			{
				'name': 'theme',
				'optional': true,
				'description': 'name of theme to use for this control',
				'type': T.identifier
			}
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
	/**
	 * This is called when the control is loaded and applied for a specific element that 
	 * references (or uses implicitly) the control.
	 */
	build:  function(element,options)
	{
		var theme = options['theme'] || Appcelerator.UI.UIManager.getDefaultTheme('textarea')
		Element.addClassName(element,"textarea_" + theme);
		
		// wrap input with two images (left and right)
		var img1 = document.createElement('img');
		var blankImg = Appcelerator.Core.getModuleCommonDirectory() + '/images/appcelerator/blank.gif';
		img1.src= blankImg;
		img1.className = "textarea_" + theme + "_left";		
		new Insertion.Before(element,img1);	
		var img2 = document.createElement('img');
		img2.src = blankImg;
		img2.className = "textarea_" + theme + "_right";
		new Insertion.After(element,img2);

		// fix PNGs
		if (Appcelerator.Browser.isIE6)
		{
			img1.addBehavior(Appcelerator.Core.getModuleCommonDirectory() + '/images/appcelerator/iepngfix.htc');
			img2.addBehavior(Appcelerator.Core.getModuleCommonDirectory() + '/images/appcelerator/iepngfix.htc');
		}
		
		Appcelerator.Core.loadTheme('control','textarea',theme,element,options);	
	}
});
