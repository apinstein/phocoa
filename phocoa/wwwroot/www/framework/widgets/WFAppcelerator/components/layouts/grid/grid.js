Appcelerator.UI.registerUIComponent('layout','grid',
{
	/**
	 * The attributes supported by the layouts. This metadata is 
	 * important so that your layout can automatically be type checked, documented, 
	 * so the IDE can auto-sense the widgets metadata for autocomplete, etc.
	 */
	getAttributes: function()
	{
		var T = Appcelerator.Types;
		return [{name: 'size', optional: true, description: "number of columns",defaultValue:"12",type: T.enumeration('12','16')}];
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
	processChildren: function (element,size, children)
	{
		var nodes = element.childNodes;
		var divCount = 0;
		var lastIndex = 0;
		for (var i=0;i<nodes.length;i++)
		{
			if (nodes[i].nodeType == 1 && nodes[i].nodeName.toUpperCase() == 'DIV')
			{
				// ignore if no cols attribute
				if (!nodes[i].getAttribute("cols") && children==true)
				{
					nodes[i].style.marginBottom = "20px";									
						
					// unset parent margin
					if (nodes[i].parentNode.style)
					{
						nodes[i].parentNode.style.marginBottom = "0px"						
					}
					
					// unset top-level marginBottom if IE
					if (Appcelerator.Browser.isIE && element.parentNode.style)
					{
						element.parentNode.style.marginBottom = "0px";
					}
					
					continue;						
				}

				divCount++;
				
				//
				// set node styles
				//
				nodes[i].style.display = "inline";
				
				// set float
				if (Appcelerator.Browser.isIE)
				{
					nodes[i].style.styleFloat = "left";
				}
				else
				{
					nodes[i].style.cssFloat = "left";					
				}
				
				// first child has no left margin
				if (children == true && divCount == 1)
				{
					nodes[i].style.marginLeft = "0px";
					nodes[i].style.marginRight = "10px"
				}
				else
				{
					nodes[i].style.marginLeft = "10px";
					nodes[i].style.marginRight = "10px";
					nodes[i].style.marginBottom = "20px";
				}
				
				// determine width 
				var width = null;
				var cols = nodes[i].getAttribute('cols');
				switch (cols)
				{
					case '1':
						width = (size == "12")?"60px":"40px"
						break;
					case '2':
						width = (size == "12")?"140px":"100px"
						break;
					case '3':
						width = (size == "12")?"220px":"160px"
						break;
					case '4':
						width = (size == "12")?"300px":"220px"
						break;
					case '5':
						width = (size == "12")?"380px":"280px"
						break;
					case '6':
						width = (size == "12")?"460px":"340px"
						break;
					case '7':
						width = (size == "12")?"540px":"400px"
						break;
					case '8':
						width = (size == "12")?"620px":"460px"
						break;
					case '9':
						width = (size == "12")?"700px":"520px"
						break;
					case '10':
						width = (size == "12")?"780px":"580px"
						break;
					case '11':
						width = (size == "12")?"860px":"640px"
						break;
					case '12':
						width = (size == "12")?"940px":"700px"
						break;
					case '13':
						width = "760px"
						break;
					case '14':
						width = "820px"
						break;
					case '15':
						width = "880px"
						break;
					case '16':
						width = "940px"
						break;
					default:
						width = "100%";
						break;
					
				}
				nodes[i].style.width = width;
				lastIndex = i;
				
				// recurse children
				this.processChildren(nodes[i],size,true)

			}
		}
		
		// process last child of a grid
		if (children == true && nodes[lastIndex] && nodes[lastIndex].style && nodes[lastIndex].getAttribute('cols'))
		{
			nodes[lastIndex].style.marginRight = "0px"
			nodes[lastIndex].style.marginLeft = "10px"
			
		}
		
	},
	
	/**
	 * This is called when the layout is loaded and applied for a specific element that 
	 * references (or uses implicitly) the layout.
	 */
	build:  function(element,options)
	{
		// set container styles
		element.style.marginLeft = "auto";
		element.style.marginRight = "auto"
		if (Appcelerator.Browser.isIE6)
		{
			element.style.width = "980px"
		}
		else
		{
			element.style.width = "960px";			
		}

		if (Appcelerator.Browser.isIE)
		{
			element.style.marginBottom = "20px";
		}
		
		// process content
		this.processChildren(element,options['size'],false)
		
		// add clearing element
		var clear = document.createElement('div');
		clear.style.clear = "both";
		new Insertion.After(element,clear);
		
	}
});
