Appcelerator.Core.registerTheme('control','tabpanel','box',
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
		if (options.behavior)
		{
			var tabs = Appcelerator.UI.getTabs(element);
			var tabCount = (tabs.length)/3;
			for (var i=1;i<=tabCount;i++)
			{
				(function()
				{
					var id = element.id;
					var tab = $(id + '_tabmid_' + i);
					switch (options.behavior)
					{
						case 'harddrop':
						{
							Event.observe(tab,'click',function(event)
							{
								new Effect.Morph(Event.element(event),{style:'height:30px',duration:1.0,transition:Effect.Transitions.Bounce});
								for (var j=1;j<=tabCount;j++)
								{
									var tab = $(id + '_tabmid_' + j)
									if (tab.id != Event.element(event).id)
									{
										new Effect.Morph(tab,{style:'height:10px',duration:1.0,transition:Effect.Transitions.Bounce});
									}
								}
							});
							break;
						}
						case 'wires':
						{
							var string = document.createElement('li');
							new Insertion.Before(tab,string);
							string.id = id + '_tabmid_' + i+ '_string_' + i;
							Element.addClassName(string,'tabpanel_box_wire');
							Event.observe(tab,'click',function(event)
							{
								var tabNum = Event.element(event).id.substring(Event.element(event).id.length-1);
								new Effect.Morph(Event.element(event),{style:'top:30px',duration:1.0,transition:Effect.Transitions.BouncePast});
								new Effect.Morph($(Event.element(event).id + "_string_" + tabNum),{style:'height:60px',duration:0.5});

								for (var j=1;j<=tabCount;j++)
								{
									(function()
									{
										var xtab = $(id + '_tabmid_' + j);
										var xstring = $(id + '_tabmid_'+j+'_string_' + j);
										if (xtab.id != Event.element(event).id)
										{
											new Effect.Morph(xtab,{style:'top:0px',duration:1.0,transition:Effect.Transitions.BouncePast});
											new Effect.Morph(xstring,{style:'height:20px',duration:0.5});
										}
									})();
								}
							});
							break;
						}
					}
				})();
			}
		}
	}
});
