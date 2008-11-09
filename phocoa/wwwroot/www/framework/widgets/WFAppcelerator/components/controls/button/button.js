Appcelerator.UI.registerUIComponent('control','button',
{
	/**
	 * The attributes supported by the controls. This metadata is 
	 * important so that your control can automatically be type checked, documented, 
	 * so the IDE can auto-sense the widgets metadata for autocomplete, etc.
	 */
	getAttributes: function()
	{
		var T = Appcelerator.Types;
		return [
				{name: 'width', optional: true, description: " width for panel" ,defaultValue: 'auto', type: T.cssDimension}];
	},
	/**
	 * The version of the control. This will automatically be corrected when you
	 * publish the component.
	 */
	getVersion: function()
	{
		// leave this as-is and only configure from the build.yml file 
		// and this will automatically get replaced on build of your distro
		return '1.2';
	},
	/**
	 * The control spec version.  This is used to maintain backwards compatability as the
	 * Widget API needs to change.
	 */
	getSpecVersion: function()
	{
		return 1.0;
	},
	getConditions: function()
    {
        return ['change'];
    },
	disable: function(id,parameters,data,scope,version,attrs,direction,action)
	{		
		var theme = $(id).theme;
		Element.addClassName(id + "_left", "button_" + theme + "_left_disabled");
		Element.addClassName(id,"button_" + theme + "_middle_disabled");
		Element.addClassName(id + "_right","button_" + theme + "_right_disabled");
		$(id).setAttribute("disabled","true");
	},
	enable: function(id,parameters,data,scope,version,attrs,direction,action)
	{
		var theme = $(id).theme;
		Element.removeClassName(id + "_left"  ,"button_" + theme + "_left_disabled");
		Element.removeClassName(id,"button_" + theme + "_middle_disabled");
		Element.removeClassName(id + "_right" ,"button_" + theme + "_right_disabled");		
		$(id).removeAttribute("disabled");
	},
	getActions: function()
	{
		return ['disable','enable'];
	},
	/**
	 * This is called when the control is loaded and applied for a specific element that 
	 * references (or uses implicitly) the control.
	 */
	build:  function(element,options)
	{
		var theme = options['theme'] || Appcelerator.UI.UIManager.getDefaultTheme('button');
		Element.addClassName(element,"button_" + theme + "_middle");
		element.theme = theme;
		
		var span = document.createElement('span');
		if (Appcelerator.Browser.isIE)
		{
			span.style.cssText = element.style.cssText;
		}
		else
		{			
			span.setAttribute('style',element.getAttribute('style'));
		}
		span.className = "button_" + theme + "_container";

		var width = options['width'];
		
		// remove style from button
		element.removeAttribute('style');

		element.style.width = (width)?width:'auto';

		new Insertion.Before(element,span);
		
		var img1 = document.createElement('img');
		var blankImg = Appcelerator.Core.getModuleCommonDirectory() + '/images/appcelerator/blank.gif';
		img1.src= blankImg;
		img1.id = element.id + "_left";
		img1.className = "button_" + theme + "_left";		
		var img2 = document.createElement('img');
		img2.src = blankImg;
		img2.id = element.id + "_right";
		img2.className = "button_" + theme + "_right";
		
		// add elements
		span.appendChild(img1);
		span.appendChild(element);
		span.appendChild(img2);
		
		// fix PNGs
		if (Appcelerator.Browser.isIE6)
		{
			img1.addBehavior(Appcelerator.Core.getModuleCommonDirectory() + '/images/appcelerator/iepngfix.htc');
			img2.addBehavior(Appcelerator.Core.getModuleCommonDirectory() + '/images/appcelerator/iepngfix.htc');
			element.addBehavior(Appcelerator.Core.getModuleCommonDirectory() + '/images/appcelerator/iepngfix.htc');
			
		}
		var self = this;
		
		// check initial state of button
		if (element.disabled)
		{
			self.disable(element.id);	
		}
		
		Appcelerator.Core.loadTheme('control','button',theme,element,options);
		
		if (element.getAttribute('activators'))
		{
			element.onActivatorsDisable = function()
			{
				self.disable(element.id);
			};
			element.onActivatorsEnable = function()
			{
				self.enable(element.id);
			};
		}
		
	}
});
