Appcelerator.UI.registerUIComponent('control','input',
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
			},
			{name: 'width', optional: true, description: "container width for select ",defaultValue: 'auto'}
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
		var theme = options['theme'] || Appcelerator.UI.UIManager.getDefaultTheme('input')
		Element.addClassName(element,"input_" + theme + "_input");
		
		var span = document.createElement('span');
		span.className = "input_" + theme + "_container";

		var cssText = (Appcelerator.Browser.isIE)?element.style.cssText:element.getAttribute('style');
		element.removeAttribute('style');

		if (Appcelerator.Browser.isIE)
		{
			span.style.cssText = cssText
		}
		else
		{			
			span.setAttribute('style',cssText);
		}
		
		// add width style
		element.style.width = options['width'];
		
		new Insertion.Before(element,span);
		
		// wrap input with two images (left and right)
		var img1 = document.createElement('img');
		var blankImg = Appcelerator.Core.getModuleCommonDirectory() + '/images/appcelerator/blank.gif';
		img1.src= blankImg;
		img1.className = "input_" + theme + "_left";		
		var img2 = document.createElement('img');
		img2.src = blankImg;
		img2.className = "input_" + theme + "_right";

		// add elements
		span.appendChild(img1);
		span.appendChild(element);
		span.appendChild(img2);

		// fix PNGs
		if (Appcelerator.Browser.isIE6)
		{
			img1.addBehavior(Appcelerator.Core.getModuleCommonDirectory() + '/images/appcelerator/iepngfix.htc');
			img2.addBehavior(Appcelerator.Core.getModuleCommonDirectory() + '/images/appcelerator/iepngfix.htc');
		}
		
		Appcelerator.Core.loadTheme('control','input',theme,element,options);	
	}
});
