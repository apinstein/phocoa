Appcelerator.UI.registerUIComponent('layout','border',
{
	/**
	 * The attributes supported by the layouts. This metadata is 
	 * important so that your layout can automatically be type checked, documented, 
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
	build:  function(element,options)
	{
		var e = element.down('*[pos=east]');
		var w = element.down('*[pos=west]');
		var n = element.down('*[pos=north]');
		var s = element.down('*[pos=south]');
		var c = element.down('*[pos=center]');

		var html = "<table width='100%' class='borderlayout' border='0' cellpadding='0' cellspacing='0'>";
		
		if (n)
		{
			html+='<tr>';
			html+='<td colspan="3"';
			html += (n.getAttribute('valign'))?' valign="' + n.getAttribute('valign')+'" ': ' valign="middle" ';
			html += (n.getAttribute('align'))?' align="' + n.getAttribute('align')+'" ': ' align="center" ';
			html+='<div id="' + element.id + '_north" class="borderlayout_north"></div>';

			if (Appcelerator.Browser.isIE)
			{
				html += n.outerHTML;
			}
			else
			{				
				html += Appcelerator.Util.Dom.toXML(n,true,Appcelerator.Compiler.getTagname(n))
			}
			html+='</td>';
			html+='</tr>';
		}
		
		html+='<tr>';

		if (w)
		{
			html+='<td colspan="1"';
			html += (w.getAttribute('valign'))?' valign="' + w.getAttribute('valign')+'" ': ' valign="middle" ';
			html += (w.getAttribute('align'))?' align="' + w.getAttribute('align')+'" ': ' align="left" ';
			html+='<div id="' + element.id + '_west" class="borderlayout_west"></div>';

			if (Appcelerator.Browser.isIE)
			{
				html += w.outerHTML;
			}
			else
			{				
				html += Appcelerator.Util.Dom.toXML(w,true,Appcelerator.Compiler.getTagname(w))
			}
			html+='</td>';
		}

		if (c)
		{
			html+='<td colspan="1"';
			html += (c.getAttribute('valign'))?' valign="' + c.getAttribute('valign')+'" ': ' valign="middle" ';
			html += (c.getAttribute('align'))?' align="' + c.getAttribute('align')+'" ': ' align="center" ';
			html+='<div id="' + element.id + '_center" class="borderlayout_center"></div>';

			if (Appcelerator.Browser.isIE)
			{
				html += c.outerHTML;
			}
			else
			{				
				html += Appcelerator.Util.Dom.toXML(c,true,Appcelerator.Compiler.getTagname(c))
			}
			html+='</td>';
		}

		if (e)
		{
			html+='<td colspan="1"';
			html += (e.getAttribute('valign'))?' valign="' + e.getAttribute('valign')+'" ': ' valign="middle" ';
			html += (e.getAttribute('align'))?' align="' + e.getAttribute('align')+'" ': ' align="right" ';
			html+='<div id="' + element.id + '_east" class="borderlayout_easst"></div>';

			if (Appcelerator.Browser.isIE)
			{
				html += e.outerHTML;
			}
			else
			{				
				html += Appcelerator.Util.Dom.toXML(e,true,Appcelerator.Compiler.getTagname(e))
			}
			html+='</td>';
		}

		html+='</tr>';
		html+='<tr>';

		if (s)
		{
			html+='<tr>';
			html+='<td colspan="3"';
			html += (s.getAttribute('valign'))?' valign="' + s.getAttribute('valign')+'" ': ' valign="middle" ';
			html += (s.getAttribute('align'))?' align="' + s.getAttribute('align')+'" ': ' align="center" ';
			html+='<div id="' + element.id + '_south" class="borderlayout_south"></div>';

			if (Appcelerator.Browser.isIE)
			{
				html += s.outerHTML;
			}
			else
			{				
				html += Appcelerator.Util.Dom.toXML(s,true,Appcelerator.Compiler.getTagname(s))
			}
			html+='</td>';
			html+='</tr>';
		}

		html+='</table>';
		element.innerHTML = html;
		
	}
});
