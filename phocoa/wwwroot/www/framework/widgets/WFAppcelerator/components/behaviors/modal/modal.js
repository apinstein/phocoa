Appcelerator.UI.registerUIComponent('behavior','modal',
{
	getAttributes: function()
	{
		var T = Appcelerator.Types;
		return [{name: 'modal-background-color', optional: true, description: "background color for modal",defaultValue: '#222'},
	       	    {name: 'opacity', optional: true, description: "opacity for modal background",defaultValue: 0.6}
				];
	},
	
	hide:function(id,parameters,data,scope,version,attrs,direction,action)
	{
		this._hideModal(id);
		Appcelerator.Compiler.fireCustomCondition(id, 'hide', {'id': id});
	},
	show:function(id,parameters,data,scope,version,attrs,direction,action)
	{
		window.scrollTo(0,0)
		Element.show($(id + "_modal_container"));
		Element.show($(id + "_modal_dialog"));
		Element.show($(id));
		if (Appcelerator.Browser.isIE)
		{
			document.documentElement.style.overflow = "hidden";
		}
		// SAFARI CANNOT DYNAMICALLY CHANGE TO OVERFLOW
		else if (!Appcelerator.Browser.isSafari)
		{
			document.body.style.overflow = "hidden";
		}
		Appcelerator.Compiler.fireCustomCondition(id, 'show', {'id': id});
	},
	
	_hideModal: function(id)
	{
		Element.hide($(id + "_modal_container"));
		Element.hide($(id + "_modal_dialog"));
		Element.hide($(id));
		if (Appcelerator.Browser.isIE)
		{
			document.documentElement.style.overflow = "auto";
		}
		// SAFARI CANNOT DYNAMICALLY CHANGE TO OVERFLOW
		else if (!Appcelerator.Browser.isSafari)
		{
			document.body.style.overflow = "auto";
		}
	},
	
	getActions: function()
	{
		return ['hide','show'];
	},	
	
	build: function(element,options)
	{
		var on = element.getAttribute("on");

		// window size
		var windowHeight = (Appcelerator.Browser.isIE)?document.documentElement.clientHeight:window.outerHeight;
		var windowWidth = (Appcelerator.Browser.isIE)?document.documentElement.clientWidth:window.innerWidth;
		var width = (Appcelerator.Browser.isIE6)? windowWidth + "px":"100%";
		
		var elementHeight = Element.getStyle(element,'height');

		// modal container
		var modalContainer = document.createElement('div');
		modalContainer.id = element.id + "_modal_container";
		modalContainer.className = 'behavior modal';	
		modalContainer.style.display = "none";
		modalContainer.style.position = "absolute";
		modalContainer.style.top = "0px";
		modalContainer.style.left = "0px";
		modalContainer.style.zIndex = "2000";
		modalContainer.style.width = width;
		modalContainer.style.height = "1200px";
		modalContainer.style.backgroundColor = options['modal-background-color'];
		modalContainer.style.opacity = options['opacity'];
		modalContainer.style.filter = "alpha( opacity = "+options['opacity']*100+")";
		
		// modal content
		var overlayDataHTML = document.createElement("div");
		overlayDataHTML.id = element.id + "_modal_dialog";
		overlayDataHTML.style.position = "absolute";
		overlayDataHTML.style.zIndex = "2001";
		overlayDataHTML.style.top = "100px";
		overlayDataHTML.style.width = "95%"
		overlayDataHTML.style.display = "none";
		overlayDataHTML.setAttribute('align','center');

		new Insertion.Bottom(document.body,overlayDataHTML);
		new Insertion.Bottom(document.body,modalContainer);

		// process hide/show
		if (on)
		{
			modalContainer.setAttribute('on',on);
			overlayDataHTML.setAttribute('on',on);
			Appcelerator.Compiler.dynamicCompile(modalContainer);
			Appcelerator.Compiler.dynamicCompile(overlayDataHTML);
		}

		// if element already has a shadow then use it
		if ($(element.id + "_shadow"))
		{
			overlayDataHTML.appendChild($(element.id+"_shadow"));			
		}
		else
		{
			overlayDataHTML.appendChild(element);			
		}
		
		var self = this;
		Appcelerator.UI.addElementUIDependency(element,'behavior','modal','control', 'panel', function(element)
		{
			Appcelerator.Compiler.registerConditionListener(element,'hide',function(el,condition)
			{
				self._hideModal(element.id);
			});
		});		
		
		
	}
});
