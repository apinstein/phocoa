Appcelerator.Core.registerTheme('behavior','shadow','curved',
{
	/**
	 * The attributes supported by the themes. This metadata is 
	 * important so that your theme can automatically be type checked, documented, 
	 * so the IDE can auto-sense the widgets metadata for autocomplete, etc.
	 */
	getAttributes: function()
	{
		var T = Appcelerator.Types;
		
		return [{name: 'width', optional: true, description: "width of element that needs shadow",
		         type: T.enumeration('960', '960px'), defaultValue:'960'},
				{name: 'align', optional: true, description: "is your element center or normal",
				         type: T.enumeration('normal', 'center'), defaultValue:'normal'}
		
		]
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
		// create shadow element
		var shadow = document.createElement('div');		
		new Insertion.After($(element.id + "_shadow"),shadow);
		
		var width = '960';
		if (options['width'])
		{
			width = options['width']
		}
		else if (element.parentNode)
		{
			width = Element.getStyle(element.parentNode,'width');
		}

		// select image based on width attribute
		switch (width)
		{
			case '140':
			case '140px':
			{
				Element.addClassName(shadow,"shadow_curved_140");
				break;
			}

			case '160':
			case '160px':
			{
				Element.addClassName(shadow,"shadow_curved_160");
				break;
			}

			case '220':
			case '220px':
			{
				Element.addClassName(shadow,"shadow_curved_220");
				break;
			}

			case '460':
			case '460px':
			{
				Element.addClassName(shadow,"shadow_curved_460");
				break;
			}
			
			case '700':
			case '700px':
			{
				Element.addClassName(shadow,"shadow_curved_700");
				break;
			}

			case '940':
			case '940px':
			{
				Element.addClassName(shadow,"shadow_curved_940");
				break;
			}
			default:
			{
				if (Appcelerator.Browser.isIE6)
				{
					if (parseInt(width)>140 && parseInt(width)<160)
					{
						Element.addClassName(shadow,"shadow_curved_140");
						
					}
					else if (parseInt(width)>160 && parseInt(width)<220)
					{
						Element.addClassName(shadow,"shadow_curved_160");
						
					}
					else if (parseInt(width)>220 && parseInt(width)<460)
					{
						
						Element.addClassName(shadow,"shadow_curved_220");
					}
					else if ((parseInt(width)>460 && parseInt(width)<700))
					{
						Element.addClassName(shadow,"shadow_curved_460");
						
					}
					else if ((parseInt(width)>700 && parseInt(width)<940))
					{
						Element.addClassName(shadow,"shadow_curved_700");
						
					}
					else
					{
						Element.addClassName(shadow,"shadow_curved_940");
						
					}
					break;
				}
				Element.addClassName(shadow,"shadow_curved_940");
				break;				
			}
		}
		
		// if we are in a grid, we need to override the bottom margin for an element
		Element.addClassName(element,"shadow_curved_no_margin");
		
		
		// add alignment of background image
		var align = (!options['align'])?'normal':options['align'];
		Element.addClassName(shadow,(align=='normal')?'shadow_curved_left':'shadow_curved_center');
	}
});
