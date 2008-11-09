Appcelerator.UI.registerUIComponent('behavior','draggable',
{
	/**
	 * The attributes supported by the behaviors. This metadata is 
	 * important so that your behavior can automatically be type checked, documented, 
	 * so the IDE can auto-sense the widgets metadata for autocomplete, etc.
	 */
	getAttributes: function()
	{
		var T = Appcelerator.Types;
		return [{name: 'starteffect', optional: true, description: "effect when dragging starts "},
				{name: 'endeffect', optional: true, description: "effect when dragging ends "},
				{name: 'revert', optional: true, description: "revert to original position"},
		        {name: 'ghosting', optional: true, description: "leave original container in place while dragging ",defaultValue: false},
		        {name: 'handle', optional: true, description: "id of handle that is draggable "},
		        {name: 'constraint', optional: true, description: "constrain draggable direction ",type: T.enumeration('horizontal','vertical')}		
		];
		
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
		// we want to wait until we're compiled before we attached our draggable
		// given that we can have a handle reference id inside the container we 
		// want to use and we need to make sure we can get the ID before we pass
		// to draggable
		element.observe('element:compiled:'+element.id,function(a)
		{
			//var container = element.up('.container');
			if (options.endeffect)
			{
				var name = options.endeffect;
				options.endeffect = function()
				{
					 element.visualEffect(name,options);
				};
			}
			if (options.starteffect)
			{
				var name = options.starteffect;
				options.starteffect = function()
				{
					 element.visualEffect(name,options);
				};
			}
			if (options.reverteffect)
			{
				var name = options.reverteffect;
				options.reverteffect = function()
				{
					 element.visualEffect(name,options);
				};
			}
			if (options.revert)
			{
				var f = window[options.revert];
				if (Object.isFunction(f))
				{
					options.revert = f;
				}
			}
			var d = new Draggable(element.id,options);
			Appcelerator.Compiler.addTrash(element,function()
			{
				d.destroy();
			});

			// add shadow dependency
			Appcelerator.UI.addElementUIDependency(element,'behavior','draggable','behavior', 'shadow', function(element)
			{
				d.destroy();

				if (!Appcelerator.Browser.isIE6)
				{
					var s = new Draggable(element.id + "_shadow",options);
					Appcelerator.Compiler.addTrash(element,function()
					{
						s.destroy();
					});					
				}
			});		

		});


	}
});
