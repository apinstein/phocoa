Appcelerator.UI.registerUIComponent('layout','vertical',
{
	/**
	 * The attributes supported by the layouts. This metadata is 
	 * important so that your layout can automatically be type checked, documented, 
	 * so the IDE can auto-sense the widgets metadata for autocomplete, etc.
	 */
	getAttributes: function()
	{
		var T = Appcelerator.Types;
		
		return [{name: 'width', optional: true, description: "width for vertical layout",defaultValue: '100%'},
			    {name: 'cellspacing', optional: true, description: "cell spacing for vertical layout",defaultValue: '0'},
		        {name: 'cellpadding', optional: true, description: "cell padding for vertical layout",defaultValue: '0'},
				{name: 'hintPos', optional:true, description:"location of hint text",defaultValue:'bottom', type:T.enumeration('bottom','right','input','top')},
				{name: 'errorPos', optional:true, description:"location of error text", defaultValue:'top', type:T.enumeration('bottom','right','top')}				
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
	build: function(element,options)
	{
		var html = '';

		// determine if layout if for form
		if (element.tagName.toLowerCase() == 'form')
		{
			html += Appcelerator.UI.LayoutManager._formatTable(options);
			formOptions = {'element':element,'childNodes':element.childNodes,'html':html,'align':'vertical','colspan':'1','hintPos':options['hintPos'],'errorPos':options['errorPos']};
			html = Appcelerator.UI.LayoutManager._buildForm(formOptions);
			element.innerHTML = html;
			Appcelerator.Core.loadTheme('layout','vertical','form',element,options);	
			
		}
		// otherwise treat like a div
		else
		{
			html += Appcelerator.UI.LayoutManager._formatTable(options);

			for (var c=0,len=element.childNodes.length;c<len;c++)
			{
				var node = element.childNodes[c];
				if (node.nodeType == 1)
				{
					html += '<tr><td ';
					html +=  (node.getAttribute("width"))?' width="'+node.getAttribute('width')+'"':'';
					html +=  (node.getAttribute("valign"))?' valign="'+node.getAttribute('valign')+'" ':' valign="middle" ';
					html +=  (node.getAttribute("align"))?' align="'+node.getAttribute('align')+'" ':' align="left" ';
					html += '>';
					if (Appcelerator.Browser.isIE)
					{
						html += node.outerHTML;	
					}
					else
					{
						html += Appcelerator.Util.Dom.toXML(node,true,Appcelerator.Compiler.getTagname(node));	
					}
					html += '</td></tr>';
				}
			}
			html +="</table>";
			element.innerHTML = html;
		}
	}
});
