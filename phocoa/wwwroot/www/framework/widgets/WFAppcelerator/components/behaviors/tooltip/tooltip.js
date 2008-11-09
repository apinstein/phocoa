Appcelerator.UI.registerUIComponent('behavior','tooltip',
{
	/**
	 * The attributes supported by the behaviors. This metadata is 
	 * important so that your behavior can automatically be type checked, documented, 
	 * so the IDE can auto-sense the widgets metadata for autocomplete, etc.
	 */
	getAttributes: function()
	{
		var T = Appcelerator.Types;
		return [
			{name: 'id', optional: false, description: "element id that triggers tooltip"},
			{name: 'position', optional: true, description: "position of tooltip - either relative or fixed", type: T.enumeration('fixed','relative'), defaultValue: 'relative'},
			{name: 'delay', optional: true, description: "delay before hiding", type: T.number, defaultValue: '500'},
			{name: 'showEffect', optional: true, description: "effect to use when showing", type: T.string},
			{name: 'hideEffect', optional: true, description: "effect to use when hiding", type: T.string}
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
	hide:function(id,parameters,data,scope,version,attrs,direction,action)
	{
		Appcelerator.Compiler.fireCustomCondition(id, 'hide', {'id': id});
	},
	show:function(id,parameters,data,scope,version,attrs,direction,action)
	{
		Appcelerator.Compiler.fireCustomCondition(id, 'show', {'id': id});
	},
	getActions: function()
	{
		return ['hide','show'];
	},
	
	/**
	 * This is called when the behavior is loaded and applied for a specific element that 
	 * references (or uses implicitly) the behavior.
	 */
	build:  function(element,options)
	{
		element.style.display = "none";
		var timer;
		var self = this;
		
		// setup shadow dependency
		var parent = null;
		var shadow = false
		Appcelerator.UI.addElementUIDependency(element,'behavior','tooltip','behavior','shadow', function(element)
		{
			shadow = true;
		});		
		
		var position = options['position'];
		var delay = Appcelerator.Util.DateTime.timeFormat(options['delay']);
		
		var hide = function(el)
		{
			var effect = options['hideEffect'];
			if (effect)
			{
				Effect[effect](el);
			}
			else
			{
				Element.hide(el);
			}
			self.hide(el.id);

		};
		var show = function(el)
		{
			var effect = options['showEffect'];
			if (effect)
			{
				Effect[effect](el);
			}
			else
			{
				Element.show(el);
			}
			self.show(el.id);
			
		};

		function startTimer(el)
		{
			cancelTimer();
			timer = setTimeout(function()
			{
				if (parent != null)Element.hide(parent);
				hide(el);
			}
			,delay);
		}
		function cancelTimer()
		{
			if (timer)
			{
				clearTimeout(timer);
				timer = null;
			}				
		}
		
		// we call this in a defer to allow processing to continue
		// and in case ID hasn't yet been seen or compiled
		(function()
		{
			Event.observe($(options['id']),'mouseover',function(e)
			{
				cancelTimer();
				if ((parent == null) && (shadow == true))
				{
					parent = $(element.id + "_shadow");
				}
				if (parent != null)
				{
					if (position == 'relative')
					{
						parent.style.position = "absolute";
						parent.style.zIndex = 1000;
						parent.style.top = Event.pointerY(e) + 10 + "px";
						parent.style.left = Event.pointerX(e) + 10 + "px";	
						
					}
					Element.show(parent)			

				}
				else
				{
					if (position == 'relative')
					{
						element.style.position = "absolute";
						element.style.zIndex = 1000;
						element.style.top = Event.pointerY(e) + 10 + "px";
						element.style.left = Event.pointerX(e) + 10 + "px";
					}
				}
				show(element);
			});
			Event.observe($(options['id']),'mouseout',function(e)
			{
				startTimer(element);
			});	
			Event.observe(element,'mouseover',function(e)
			{
				cancelTimer();
			});		
			Event.observe(element,'mouseout',function(e)
			{
				startTimer(element);
			});		
		}).defer();
	}
});

