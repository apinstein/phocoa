/*
 * This file is part of Appcelerator.
 *
 * Copyright (C) 2006-2008 by Appcelerator, Inc. All Rights Reserved.
 * For more information, please visit http://www.appcelerator.org
 *
 * Appcelerator is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


Appcelerator.Widget.Datatable =
{	
	modulePath:null,
	setPath: function(path)
	{
		this.modulePath = path;
	},
	getName: function()
	{
		return 'appcelerator datatable';
	},
	getDescription: function()
	{
		return 'datatable widget';
	},
	getVersion: function()
	{
		return 1.0;
	},
	getSpecVersion: function()
	{
		return 1.0;
	},
	getAuthor: function()
	{
		return 'Amro Mousa';
	},
	getModuleURL: function ()
	{
		return 'http://www.appcelerator.org';
	},
	isWidget: function ()
	{
		return true;
	},
	getWidgetName: function()
	{
		return 'app:datatable';
	},
	getActions: function()
	{
		return ['execute', 'stop', 'start'];
	},
	getAttributes: function()
    {
		var T = Appcelerator.Types;
        return [{name: 'on', optional: true,
		         description: "Used to populate the data table.",
				 type: T.onExpr},
                {name: 'sort', optional: true, defaultValue: 'client',
				 description: "Where the responsibility for sorting items in the table rests"+
				              "Valid values are 'client', 'server', and 'off.'",
				 type: T.enumeration('client','server','off')},
                {name: 'sortRequest', optional: true, defaultValue: '',
				 description: "Message to be sent when the sort mode is 'server' and a column header is clicked.",
				 type: T.messageSend},
                {name: 'rowEvenClass', optional: true, defaultValue: '', type: T.cssClass},
                {name: 'rowOddClass', optional: true, defaultValue: '', type: T.cssClass},
                {name: 'width', optional: true, defaultValue: '100%',
				 description: "Width of entire table.",
				 type: T.cssDimension},
                {name: 'property', optional: true, defaultValue: '', type: T.identifier},
                {name: 'pagination', optional: true, defaultValue: 'false', type: T.bool},
                {name: 'maxRows', optional: true, defaultValue: 0, type: T.naturalNumber},
                {name: 'sortIndex', optional: true, defaultValue: -1, type: T.naturalNumber},
				{name: 'stickySort', optional: true, defaultValue: true, type: T.bool}];
    },
	createDataTable: function (id, pagination_direction)
	{		
		//list of on attributes to be parsed and evaluated at the end of create
		var on_array = [];

		var parameterMap = $(id).parameterMap;
		
		var scope = parameterMap['scope'];
		
		//Valid values are 'client', 'server', and 'off'
		var sort = parameterMap['sort'];
		
		//Really applies styles to the CELLS on the even and odd rows
		var rowEvenClass = parameterMap['rowEvenClass'];
		var rowOddClass = parameterMap['rowOddClass'];
		
		//Width % of entire table
		var width = parameterMap['width'];
			
		//Controls whether or not to add spacers to the header (true on initial render)
		var add_spacers_to_header = parameterMap['add_spacers_to_header'];
		
		var header_array = parameterMap['header_array'];
		var array = parameterMap['array'];
		
		var pagination = parameterMap['pagination'];
		var maxRows = parseInt(parameterMap['maxRows']);

		if (pagination == 'true' && !pagination_direction)
		{
			pagination_direction = 'forward';
			maxRows = maxRows == 0 ? 10 : maxRows;
		}

		if (pagination == 'false')
		{
			maxRows = array.length;
		}
		parameterMap['maxRows'] = maxRows;

		var html = '';
			
		//Default class names -- the only class names accepted directly by the table are rowEvenClass and rowOddClass
		//.table_row
		//.table_cell -- default IFF rowOddClass or rowEvenClass are not provided
		//.table_row_header
		//.table_cell_header
					
		var table_open = '<table cellspacing="0" border="0" width="'+width+'">';
		var table_close = '</table>';
		var table_header_content = '';
		var table_data_content = '';
		var arrow_up_img = '<img src="'+ Appcelerator.Widget.Datatable.modulePath + 'images/arrow_up.png" title="sort ascending" style="position:relative;top:-1px; left: 2px;" />';
		var arrow_down_img = '<img src="'+ Appcelerator.Widget.Datatable.modulePath + 'images/arrow_down.png" title="sort ascending" style="position:relative;top:-1px; left: 2px;" />';
		var spacer_img = '<img src="'+ Appcelerator.Widget.Datatable.modulePath + 'images/arrow_spacer.png" title="sort ascending" style="position:relative;top:-1px; left: 2px;" />';
	
		//Add spacers to the header (initially) and clear up any extra icons
		if (add_spacers_to_header)
		{
			for(var i = 0, len = header_array.length; i < len; i++)
			{
				header_array[i]['cell'] = header_array[i]['cell'].replace(arrow_up_img,'');
				header_array[i]['cell'] = header_array[i]['cell'].replace(arrow_down_img,'');
				header_array[i]['cell'] = header_array[i]['cell'].replace(spacer_img,'');
				header_array[i]['cell'] += spacer_img;
			}
		}
		
		//Create the header
		for (var x = 0, len = header_array.length; x < len; x++)
		{
			var header_info = header_array[x];
			var formatterFunction = eval(header_array[x]['formatter']);
			if (formatterFunction) {
				header_info.formatterFunction = formatterFunction;
			}
			
			var hclass = header_info['class'];
			if (hclass == '' || hclass == null)
			{
				hclass='table_cell_header';
			}
			
			var or = '';
			if (header_info['on'] != '')
			{
				or = ' or ';
			}
					
			var sort_string = '';
			if (sort == 'client')
			{
				sort_string = 'click then script[Appcelerator.Widget.Datatable.sortDataTableClient(' + x + ',' + '\'' + id + '\'' + ')]';
			} else if (sort == 'server')
			{
				sort_string = 'click then script[Appcelerator.Widget.Datatable.sortDataTableServer(' + x + ',' + '\'' + id + '\'' + ')]';
				
				if (parameterMap['current_server_sort_column'] == header_info['property'])
				{
					if (!parameterMap['sortBy'][parameterMap['current_server_sort_column']])
					{
						header_array[x]['cell'] = header_array[x]['cell'].replace(spacer_img,'');
						header_array[x]['cell'] += arrow_up_img;
					} else
					{
						header_array[x]['cell'] = header_array[x]['cell'].replace(spacer_img,'');
						header_array[x]['cell'] += arrow_down_img;
					}	
				}
			} else
			{
				sort_string = '';
			}	
				
			// Pass the index of the cell in header array
			var hw = header_info['width'];
			var td = '<td id="' + id + '_header_' + x + '" align="' + header_info['align'] + '" ' +(hw?'width="'+hw+'"':'')+' class="' + hclass + '"><span>' + header_info['cell'] + '</span></td>';
			
			if (sort_string != '')
			{
				var header_on_expression = header_info['on'] + or + sort_string;
				on_array.push({'id': id + '_header_' + x, 'on': header_on_expression});				
			}
			
			
			table_header_content += td;
		}
		table_header_content = '<tr class="table_row_header">' + table_header_content + '</tr>';
	
		//Create the table body
		var x = 0;
		var length = maxRows;

		if (pagination == 'true')
		{
			if (pagination_direction == 'forward')
			{
				if ($(id).initialLoad)
				{
					x = $(id).position;
					$(id).initialLoad = false;
				} else
				{
					x = $(id).position+maxRows >= array.length ? $(id).position : $(id).position+maxRows;					
				}
				length = x+maxRows > array.length ? array.length : x+maxRows;
			} else
			{
				if($(id).position-maxRows < 0)
				{
					x = 0;
					length = maxRows > array.length ? array.length : maxRows;
				} else
				{
					x = $(id).position-maxRows;
					length = x+maxRows > array.length ? array.length : x+maxRows;
				}
			}
			$(id).position = x;
		}
		
		var xrun = x;
		
		for (; xrun < length; xrun++)
		{
			table_data_content += '<tr class="table_row">';				
			for (var h = 0, lenH = header_array.length; h < lenH; h++)
			{
				var cell_class = (xrun % 2 == 0) ? rowEvenClass : rowOddClass;
				if (cell_class == '')
				{
					cell_class = 'table_cell';
				}
				
				//Get the column property needed to figure out what column from the current array item we need
				var column_property_name = header_array[h]['property'];
				var formatterFunction = header_array[h]['formatterFunction'];
				
				var cell_value = (Object.getNestedProperty(array[xrun],column_property_name)||'');
				if (formatterFunction) {
					cell_value = formatterFunction(cell_value,column_property_name, array[xrun]);
				} else {
					cell_value.toString().escapeHTML();
				}
				var td ='<td align="' + header_array[h]['align'] + '" class="' + cell_class + '"><span>' + cell_value +'</span></td>';
				table_data_content += td;
			}
			table_data_content += '</tr>';
		}
		html = table_open + table_header_content + table_data_content + table_close;	
		
		var myidback = id + "_pagination_back";
		var myidforward = id + "_pagination_forward";

		if (pagination == 'true')
		{
			var pag_html = '';
			var myid = "'" + id + "'";
			var forward = '"forward"';
			var backward = "'backward'";
			
			pag_html = '<div style="padding-bottom: 5px;">'+
								 '<a class="pagination_links" id="'+myidback+'"><img style="border: 0;" src="'+Appcelerator.Widget.Datatable.modulePath+'images/resultset_previous.png"/></a>&nbsp;' +
								 '<a class="pagination_links" id="'+myidforward+'"><img style="border: 0;" src="'+Appcelerator.Widget.Datatable.modulePath + 'images/resultset_next.png"/></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Showing '+(x+1)+'-'+(length)+' of '+array.length+'</div>';
			
			html = pag_html + html;
		}
		
		Appcelerator.Compiler.setHTML(id,html);

		if (pagination == 'true')
		{
			$(myidback).onclick = function(){Appcelerator.Widget.Datatable.createDataTable(id, 'backward');}.bind(this);
			$(myidforward).onclick = function(){Appcelerator.Widget.Datatable.createDataTable(id, 'forward');}.bind(this);
			$(myidback).onmousedown = function(){Appcelerator.Widget.Datatable.paginateContinuously(id, 'backward');}.bind(this);
			$(myidforward).onmousedown = function(){Appcelerator.Widget.Datatable.paginateContinuously(id, 'forward');}.bind(this);
			$(myidback).onmouseup = function(){Appcelerator.Widget.Datatable.stopContinuousPagination(id)}.bind(this);
			$(myidforward).onmouseup = function(){Appcelerator.Widget.Datatable.stopContinuousPagination(id)}.bind(this);
			$(myidback).onmouseout = function(){Appcelerator.Widget.Datatable.stopContinuousPagination(id)}.bind(this);
			$(myidforward).onmouseout = function(){Appcelerator.Widget.Datatable.stopContinuousPagination(id)}.bind(this);
		}
		var on_run_array = [];
		
		for (var i = 0, len = on_array.length; i < len; i++)
		{
			on_run_array.push(Appcelerator.Compiler.compileExpression($(on_array[i]['id']),on_array[i]['on'],false));
		}

		eval(on_run_array.join(';'));
	},
	compileWidget: function (parameters, element)
	{		
	},
	paginateContinuously: function (id, direction)
	{
		var paginator = function()
		{
			if (direction == 'forward') Appcelerator.Widget.Datatable.createDataTable(id,'forward');
			else Appcelerator.Widget.Datatable.createDataTable(id,'backward');
		};
		$(id).paginationInterval = setInterval(paginator,250);
	},
	stopContinuousPagination: function (id)
	{
		clearInterval($(id).paginationInterval);
	},
	sortDataTableClient: function (index, id, maintainSortOrder)
	{
		var parameterMap = $(id).parameterMap;
		var header_array = parameterMap['header_array'];
		var array = parameterMap['array'];
		var column_property_name = header_array[index]['property'];
		parameterMap['lastSortIndex'] = index;
		
		$(id).position = 0;
		$(id).initialLoad = true;
		
		var sortFunction = eval(header_array[index]['sorter']);
		
		//Are we sorting numbers or strings? Check only the column we're sorting by..
		var num_sort = false;
		
		if (!sortFunction) {
			num_sort = true;
			for (var x = 0, len = array.length; x < len; x++) {
				var cell_value = (Object.getNestedProperty(array[x],column_property_name) || '');
				
				if (isNaN(parseFloat(cell_value))) {
					num_sort = false;
					break;
				}
			}
		}
	
		//Initialize the object holding our previous per-column sort state
		if (parameterMap['sortBy'] == null)
		{
			parameterMap['sortBy'] = {};
			parameterMap['sortBy'][column_property_name] = false;
		}
	
		//Flip the per column sort state for this column
		parameterMap['sortBy'][column_property_name] = !maintainSortOrder ? !parameterMap['sortBy'][column_property_name] : parameterMap['sortBy'][column_property_name];
		
		//Perform the sort itself		
		if (sortFunction)
		{	var customSort = function (a,b)
			{
				var aval = Object.getNestedProperty(a,column_property_name);
				var bval = Object.getNestedProperty(b,column_property_name);
				return sortFunction(aval, bval);
			}
			array.sort(customSort);
			if (parameterMap['sortBy'][column_property_name] == false) array.reverse();
		} else if(num_sort)
		{
			var compareNum = function compare(a, b){
				var anum = Object.getNestedProperty(a,column_property_name);
				var bnum = Object.getNestedProperty(b,column_property_name);
				if (parameterMap['sortBy'][column_property_name] == true) {
					if (isNaN(parseFloat(anum)))
						return 1;
					else
						return parseFloat(anum) - parseFloat(bnum);
				} else {
					if (isNaN(parseFloat(bnum)))
						return 1;
					else
						return parseFloat(bnum) - parseFloat(anum);
				}
			};
			array.sort(compareNum);
		} else
		{
			var compareString = function compare(a, b)
		  {
				//compare all lower case strings so words starting with capital letters are not at the top/bottom of the list
				var astr = Object.getNestedProperty(a,column_property_name);
				var bstr = Object.getNestedProperty(b,column_property_name);
				if (bstr)
					bstr = bstr.toLowerCase();
				if (astr)
					astr = astr.toLowerCase();
			    return (parameterMap['sortBy'][column_property_name] == true) ? (bstr < astr) - (astr < bstr) : (astr < bstr) - (bstr < astr);
		  };
			array.sort(compareString);
		}	
		
		var arrow_up_img = '<img src="'+ Appcelerator.Widget.Datatable.modulePath + 'images/arrow_up.png" title="sort ascending" style="position:relative;top:-1px; left: 2px;" />';
		var arrow_down_img = '<img src="'+ Appcelerator.Widget.Datatable.modulePath + 'images/arrow_down.png" title="sort ascending" style="position:relative;top:-1px; left: 2px;" />';
		var spacer_img = '<img src="'+ Appcelerator.Widget.Datatable.modulePath + 'images/arrow_spacer.png" title="sort ascending" style="position:relative;top:-1px; left: 2px;" />';
		for(var i = 0, len = header_array.length; i < len; i++)
		{
			header_array[i]['cell'] = header_array[i]['cell'].replace(arrow_up_img,'');
			header_array[i]['cell'] = header_array[i]['cell'].replace(arrow_down_img,'');
			header_array[i]['cell'] = header_array[i]['cell'].replace(spacer_img,'');
			header_array[i]['cell'] += spacer_img;
		}
		
		if (!parameterMap['sortBy'][column_property_name])
		{
			header_array[index]['cell'] = header_array[index]['cell'].replace(spacer_img,'');
			header_array[index]['cell'] += arrow_up_img;
		} else
		{
			header_array[index]['cell'] = header_array[index]['cell'].replace(spacer_img,'');
			header_array[index]['cell'] += arrow_down_img;
		}
		
		parameterMap['add_spacers_to_header'] = false;
		
		Appcelerator.Widget.Datatable.createDataTable(id);
	},
	sortNan: function (astr,bstr)
	{
		if (!astr)
			astr=0;
		if (!bstr)
			bstr=0;
		if (!isNaN(astr) || "NaN" == astr && !isNaN(bstr) || "NaN" == bstr) {
			if (astr == "NaN")
				return -1;
			else if (bstr == "NaN")
			 	return 1;
			else 
				return parseFloat(astr) - parseFloat(bstr);
		} else {
		    return (bstr < astr) - (astr < bstr);
		}
		
	},
	sortDataTableServer: function (index, id)
	{
		var parameterMap = $(id).parameterMap;
		var header_array = parameterMap['header_array'];
		var array = parameterMap['array'];
		var column_property_name = header_array[index]['property'];
		
		//Initialize the object holding our previous per-column sort state
		if (parameterMap['sortBy'] == null)
		{
			parameterMap['sortBy'] = {};
			parameterMap['sortBy'][column_property_name] = false;
		}
		
		//Flip the per column sort state for this column
		parameterMap['sortBy'][column_property_name] = !parameterMap['sortBy'][column_property_name];
		parameterMap['current_server_sort_column'] = column_property_name;
		
		var sort_direction = '';
		if (parameterMap['sortBy'][column_property_name])
		{
			sort_direction = "desc"
		} else
		{
			sort_direction = "asc"
		}
			
		//Queue the sort msg on the message broker
		$MQ(parameterMap['sortRequest'],{'property': column_property_name, 'direction': sort_direction},parameterMap['scope']);
	},
	stop: function(id,parameters,data,scope,version)
    {
		$(id).stopped=true;
    },
	start: function(id,parameters,data,scope,version)
    {
		$(id).stopped=false;
    },
	execute: function(id,parameterMap,data,scope)
	{
		if ($(id).stopped) 
		{
			$D('not executing datatable for '+id);
			return;
		}
		$D('excuting datatable for '+id);
		//Data property
		var params = $(id).parameterMap||parameterMap;
		var propertyName = parameterMap['property'];
		var stickySort = parameterMap['stickySort'];
		var lastSortIndex = params['lastSortIndex'] || parameterMap['sortIndex'];
		lastSortIndex = parseInt(lastSortIndex);
		var array;
		$(id).position = 0;
		$(id).initialLoad = true;
		
		if (propertyName)
		{
			array = Object.getNestedProperty(data,propertyName) || [];
		}
		
		if (!Object.isArray(array))
		{
			array = [array]
		}
		
		parameterMap['array'] = array;
		parameterMap['scope'] = scope;

		$(id).parameterMap = parameterMap;	
		
		if (stickySort=="false")
			stickySort = false;
		if (stickySort=="true")
			stickySort=true;
		if (stickySort && (typeof lastSortIndex != 'undefined' && lastSortIndex != -1)) {
			Appcelerator.Widget.Datatable.sortDataTableClient(lastSortIndex, id, true);
			
		} else {
			Appcelerator.Widget.Datatable.createDataTable(id);
		}
	},
	buildWidget: function(element, parameters)
	{
		//Header array		
		var temp_element = document.createElement('div');
		temp_element.innerHTML = element.innerHTML.replace(/<HEADER/gi, '<div').replace(/<\/HEADER/gi,'</div');
		var element_children = temp_element.childNodes;
		var header_array = [];
		
		//Fill the header_array with a list of header columns and respective info
		//This must be done before entering the build function (headers are removed prior to entering build)		
		for (var i = 0, len = element_children.length; i < len; i++)
		{
			if (element_children[i].nodeName.toLowerCase() == 'div')
			{
				var header_object = {};
				//Header cell data				
				header_langid = element_children[i].getAttribute('langid')||'';
				if (header_langid == '')
				{
					header_object['cell'] = Appcelerator.Compiler.getHtml(element_children[i],true);					
				} else
				{
					header_object['cell'] = Appcelerator.Localization.get(header_langid);
				}
				//Header's 'on' attribute, compile it later
				header_object['on'] =  element_children[i].getAttribute('on')||''; 
				//Header's class attribute
				header_object['class'] = element_children[i].className||''; 
				//Header property to let us know what data to get from the array	
				header_object['property'] = element_children[i].getAttribute('property')||''; 
				//Header's 'align' property to go on all TD's making up this column
				header_object['align'] = element_children[i].getAttribute('align')||''; 

				//Header's sort function
				header_object['sorter'] = element_children[i].getAttribute('sorter')||''; 
				
				//Header's formatter function
				header_object['formatter'] = element_children[i].getAttribute('formatter')||''; 
				
				header_array.push(header_object);
			}
		}
		parameters['header_array'] = header_array;
		parameters['add_spacers_to_header'] = true;

		return {
			'presentation' : '',
			'position' : Appcelerator.Compiler.POSITION_REPLACE,
			'compile': true,
			'parameters': parameters
		};
	}
};

Appcelerator.Core.loadModuleCSS('app:datatable','datatable.css');
Appcelerator.Widget.register('app:datatable',Appcelerator.Widget.Datatable);
