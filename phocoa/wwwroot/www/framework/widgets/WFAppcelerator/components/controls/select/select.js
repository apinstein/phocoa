Appcelerator.UI.registerUIComponent('control','select',
{
	//TODOS:
	// 3. add support for selectOption action

	// track initial options
	selectOptions: {},

	// current set of sorted options
	currentOptions: {},

	// track theme for each select
	activeThemes:{},

	// tracking vars for each select
	activeSelects:{},

	// select count number (decrements - this is for IE bug)
	selectCount: 1000,

	/**
	 * The attributes supported by the controls. This metadata is 
	 * important so that your control can automatically be type checked, documented, 
	 * so the IDE can auto-sense the widgets metadata for autocomplete, etc.
	 */
	getAttributes: function()
	{
		var T = Appcelerator.Types;
		return [{name: 'width', optional: true, description: "container width for select ",defaultValue: '100px'}
        ];
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
	
	/**
	 *  Value action - populate select based on message payload
	 *
	 */
	value:function(id,params,data,scope,version,attrs,direction,action)
	{
		// pull out properties
		var ar = Appcelerator.Compiler.findParameter(attrs,'property');
		var row = Appcelerator.Compiler.findParameter(attrs,'row');
		var value = Appcelerator.Compiler.findParameter(attrs,'value') || 'value';
		var text = Appcelerator.Compiler.findParameter(attrs,'text') || 'text';
		if (!ar) throw "required parameter named 'property' not found in value parameter list";
		if (!text) text = value;

		var values = [];
		var html = '';
		var dropdown = $(id + "_combo_box");
		if (ar)
		{
			// loop through data
			dropdown.innerHTML = '';
		    for (var c=0;c<ar.length;c++)
		    {
		        if (row)
		        {
		            var rowData = Object.getNestedProperty(ar[c],row);
		        }
		        else
		        {
		            var rowData = ar[c];
		        }
		        if (rowData)
		        {
					html += '<div id="'+id+'_combo_'+c+'" class="select_'+this.activeThemes[id]+'_dropdown_item" on="click then l:'+id+'_combo_rowclick[row='+c+'] or mouseover then add[class=select_'+this.activeThemes[id]+'_dropdown_hover] and l:'+id+'_combo_mouseover[row='+c+'] or mouseout then remove[class=select_'+this.activeThemes[id]+'_dropdown_hover]">'+Object.getNestedProperty(rowData,text)+'</div>';
					values.push({'index':c,'id':id+'_combo_'+c,'value':Object.getNestedProperty(rowData,value),'text':Object.getNestedProperty(rowData,text)});
		        }
		    }

			// reset dropdown values
			dropdown.innerHTML = html;

			// recompile
			Appcelerator.Compiler.destroy(dropdown);
			Appcelerator.Compiler.dynamicCompile(dropdown);

			// set tracking variables
			this.selectOptions[id] = values;
			this.currentOptions[id] = values;
			this.activeSelects[id].selectedIndex=0;
			this.activeSelects[id].lastOption=ar.length - 1;

			// select first value
			Appcelerator.Compiler.fireCustomCondition(id, 'change', {'id': id,'value':this.currentOptions[id][this.activeSelects[id].selectedIndex].text});
			this._setValue(id,this.currentOptions[id][this.activeSelects[id].selectedIndex].text );
			
			// resize dropdown
			$(id+"_combo_box").style.height = this._getDropdownHeight(ar.length);
		}
	},
	
	/**
	 *  Reset action - set dropdown to first option
	 *
	 */
	reset:function(id,params,data,scope,version,attrs,direction,action)
	{
		this.activeSelects[id].selectedIndex = 0;
		this._setValue(id,this.currentOptions[id][this.activeSelects[id].selectedIndex].text );		
		Appcelerator.Compiler.fireCustomCondition(id, 'change', {'id': id,'value':this.currentOptions[id][this.activeSelects[id].selectedIndex].text});
	},

	/**
	 *  selectOption action - select dropdown option based on message payload
	 *
	 */
	selectOption:function(id,params,data,scope,version,attrs,direction,action)
	{
		var key = attrs[0].key;
		var value = data[key];
		var curOptions = this.selectOptions[id];
		for (var i=0;i<curOptions.length;i++)
		{
			if (curOptions[i].value == value)
			{
				// set active select, fire change condition and set value
				Element.removeClassName($(id+"_combo_" + this.activeSelects[id].selectedIndex),'select_'+this.activeThemes[id]+'_dropdown_hover');			
				this.activeSelects[id].selectedIndex = i;				
				Appcelerator.Compiler.fireCustomCondition(id, 'change', {'id': id,'value':this.currentOptions[id][this.activeSelects[id].selectedIndex].text});
				this._setValue(id,curOptions[i].text );
				return;
			}
		}
	},
	
	getActions: function()
	{
		return ['value','reset','selectOption'];
	},
	
	toggle_list:function(id,params,data,scope,version)
	{
		// TODO: FIGURE OUT WHY FRAMEWORK IS REQUIRING THIS DEF
	},

	change: function(id,parameters,data,scope,version)
    {
		// TODO: FIGURE OUT WHY FRAMEWORK IS REQUIRING THIS DEF
    },

	getConditions: function()
	{
		return ['change','toggle_list'];
	},

	
	/**
	 * This is called when the control is loaded and applied for a specific element that 
	 * references (or uses implicitly) the control.
	 */
	build: function(element,options)
	{	
		// get theme
		var theme = options['theme'] || Appcelerator.UI.UIManager.getDefaultTheme('select');

		var self = this;

		// build new select 
		var on = (element.getAttribute("on"))?'on="'+element.getAttribute("on")+'"':'';

		// record options
		var selectOptions = element.options;

		// record tracking variables		
		self.activeThemes[element.id] = theme;
		self.activeSelects[element.id] = {};
		self.activeSelects[element.id].selectedIndex = -1;
		self.activeSelects[element.id].lastOption = (element.options)?element.options.length-1:-1;

		// IE Z-index Hack 
		// reduce the container z-index for each select in order to properly show layers
		var html = null;
		
		if (Appcelerator.Browser.isIE)
		{
			html = '<span id="'+element.id+'"  class="select_'+theme+'" '+on+' style="z-index:'+	self.selectCount+'">';
			self.selectCount--;
		}
		else
		{
			html = '<span id="'+element.id+'"  class="select_'+theme+'" '+on+'>';
		}

		// build new select component markup
		var blankImg = Appcelerator.Core.getModuleCommonDirectory() + '/images/appcelerator/blank.gif';
		html += '<span id="'+element.id+'_container" class="select_'+theme+'" style="position:relative">';
		html += '<input style="width:'+options['width']+';" type="text" id="'+element.id+'_input" class="select_' + theme + '_input"/>';
		html += '<img id="'+element.id+'_combo_img" src="'+blankImg+'" class="select_' + theme + '_arrow"/>';
		html +='<div id="'+element.id+'_combo_box" style="display:none;width:'+options['width']+';" class="select_'+theme+'_dropdown">';
		html += self._getOptions(element,theme);
		html += '</div></span></span>';
		new Insertion.Before(element,html);

		// track click in dropdown img
		Event.observe($(element.id+"_combo_img"),'click',function(ev)
		{
			Appcelerator.Compiler.fireCustomCondition(element.id, 'toggle_list', {'id': element.id});
			self._toggleList(element.id);
		});
		
		// track individual dropdown row clicks
		$MQL(element.id + "_combo_rowclick",function(type,data,datatype,from)
		{
			Appcelerator.Compiler.fireCustomCondition(element.id, 'change', {'id': element.id,'value':self.currentOptions[element.id][self.activeSelects[element.id].selectedIndex].text});
			Appcelerator.Compiler.fireCustomCondition(element.id, 'toggle_list', {'id': element.id});
			self._setValue(element.id,self.currentOptions[element.id][data.row].text );
			self.activeSelects[element.id].selectedIndex = data.row;				
		});
		
		// track row mouseovers
		$MQL(element.id + "_combo_mouseover",function(type,data,datatype,from)
		{
			if (data.row != self.activeSelects[element.id].selectedIndex)
			{
				// remove from old mouseovered row
				Element.removeClassName($(element.id+"_combo_" + self.activeSelects[element.id].selectedIndex),'select_'+theme+'_dropdown_hover');			
				self.activeSelects[element.id].selectedIndex = data.row;				
			}
		});
		
		// track click input field
		Event.observe($(element.id+"_input"),'click',function(ev)
		{
			Appcelerator.Compiler.fireCustomCondition(element.id, 'toggle_list', {'id': element.id});
			self._toggleList(element.id);
			Event.stop(ev);
		});

		
		// track document clicks to trigger closing 
		// of the dropdown when outside dropdown area
		Event.observe(document,'click',function(ev)
		{
			ev = Event.getEvent(ev);
			var target = Event.element(ev);
			if (($(element.id + "_combo_box").style.display != "none") && (target.id !== element.id +"_combo_img"))
			{
				Appcelerator.Compiler.fireCustomCondition(element.id, 'toggle_list', {'id': element.id});
				self._toggleList(element.id);
				Event.stop(ev);
			}
		});

		// create handle for input keystrokes
		$(element.id+"_input").onkeyup =  function (event)
		{
			(function(){
				event = event || window.event;

				switch(event.keyCode) 
				{
					// close dropdown
					case Event.KEY_TAB:
					case Event.KEY_LEFT:
					case Event.KEY_RIGHT:
					case Event.KEY_ESC:
					{
						Appcelerator.Compiler.fireCustomCondition(element.id, 'toggle_list', {'id': element.id});
						self._toggleList(element.id);

						// set value to start value
						Appcelerator.Compiler.fireCustomCondition(element.id, 'change', {'id': element.id,'value':self.activeSelects[element.id].startValue});						
						self._setValue(element.id,self.activeSelects[element.id].startValue);
						Element.removeClassName($(element.id+"_combo_" + self.activeSelects[element.id].selectedIndex),'select_'+theme+'_dropdown_hover');
						self.activeSelects[element.id].selectedIndex = self.activeSelects[element.id].startIndex;
						Event.stop(event);
						return;
					}

					// select current value
					case Event.KEY_RETURN:
					{
						Appcelerator.Compiler.fireCustomCondition(element.id, 'change', {'id': element.id,'value':self.currentOptions[element.id][self.activeSelects[element.id].selectedIndex].text});
						Appcelerator.Compiler.fireCustomCondition(element.id, 'toggle_list', {'id': element.id});
						self._setValue(element.id,self.currentOptions[element.id][self.activeSelects[element.id].selectedIndex].text );
						self._toggleList(element.id);
						Event.stop(event);
						return;
					}

					// update selected index and styles
					case Event.KEY_UP:
					{
						if (self.activeSelects[element.id].selectedIndex > 0)
						{
							Element.removeClassName($(element.id+"_combo_" + self.activeSelects[element.id].selectedIndex),'select_'+theme+'_dropdown_hover');
							self.activeSelects[element.id].selectedIndex--;
							Appcelerator.Compiler.fireCustomCondition(element.id, 'change', {'id': element.id,'value':self.currentOptions[element.id][self.activeSelects[element.id].selectedIndex].text});
							self._setValue(element.id,self.currentOptions[element.id][self.activeSelects[element.id].selectedIndex].text );
							Element.addClassName($(element.id+"_combo_" + self.activeSelects[element.id].selectedIndex),'select_'+theme+'_dropdown_hover');							
						}
						Event.stop(event);
						return;
					}

					// update selected index and styles
					case Event.KEY_DOWN:
					{
						if (self.activeSelects[element.id].lastOption != self.activeSelects[element.id].selectedIndex)
						{
							Element.removeClassName($(element.id+"_combo_" + self.activeSelects[element.id].selectedIndex),'select_'+theme+'_dropdown_hover');
							self.activeSelects[element.id].selectedIndex++;
							Appcelerator.Compiler.fireCustomCondition(element.id, 'change', {'id': element.id,'value':self.currentOptions[element.id][self.activeSelects[element.id].selectedIndex].text});
							self._setValue(element.id,self.currentOptions[element.id][self.activeSelects[element.id].selectedIndex].text );
							Element.addClassName($(element.id+"_combo_" + self.activeSelects[element.id].selectedIndex),'select_'+theme+'_dropdown_hover');	
						}
						Event.stop(event);
						return;
					}

					// dynamically sort list based on current value
					default:
					{
						if ($(element.id + "_combo_box").style.display == "none")
						{
							Appcelerator.Compiler.fireCustomCondition(element.id, 'toggle_list', {'id': element.id});
							self._toggleList(element.id);
						}
						var values = self.selectOptions[element.id];
						var options = [];
						for (var i=0;i<values.length;i++)
						{
							if (values[i].text.toLowerCase().startsWith($(element.id+"_input").value.toLowerCase()) == true)
							{
								options.push(values[i])
							}
						}

						// update tracking variables
						self._updateOptions(element,options,theme);
						self.currentOptions[element.id] = options;
						self.activeSelects[element.id].lastOption = options.length -1;
						self.activeSelects[element.id].selectedIndex = 0;

						// resize dropdown
						$(element.id+"_combo_box").style.height = self._getDropdownHeight(options.length);

						Element.addClassName($(element.id+"_combo_0"),'select_'+theme+'_dropdown_hover');							
					}
				}
			})();
		};

		// remove original element
		Appcelerator.Compiler.removeElementId(element.id);		
		Element.remove(element);

		// compile new element
		Appcelerator.Compiler.dynamicCompile($(element.id +"_container"));

		// setup "getValue" function for fieldsets
		$(element.id).widget = {};
		$(element.id).widget.getValue = function(id, parms)
		{
			var idx = self._getIndexForValue(element.id, $(element.id + "_input").value);
			return self.currentOptions[element.id][idx].value;
		}

		// resize dropdown
		$(element.id+"_combo_box").style.height = this._getDropdownHeight(selectOptions.length);

		// set initial value if exists
		this._setValue(element.id,this.currentOptions[element.id][this.activeSelects[element.id].selectedIndex].text);
		
		
		// load select theme
		Appcelerator.Core.loadTheme('control','select',theme,element,options);	
	},

	// update options based on current text
	_updateOptions: function(element,options,theme)
	{
		var selectBox = $(element.id+"_combo_box");
		var html = '';
		for (var i=0;i<options.length;i++)
		{
			html += '<div id="'+element.id+'_combo_'+i+'" class="select_'+theme+'_dropdown_item" on="click then l:'+element.id+'_combo_rowclick[row='+i+'] or mouseover then add[class=select_'+theme+'_dropdown_hover] and l:'+element.id+'_combo_mouseover[row='+i+'] or mouseout then remove[class=select_'+theme+'_dropdown_hover]">'+options[i].text+'</div>';
		}

		// update and recompile
		selectBox.innerHTML = html;
		Appcelerator.Compiler.destroy(selectBox);
		Appcelerator.Compiler.dynamicCompile(selectBox);

	},
	
	// size dropdown based on number of options
	_getDropdownHeight: function(length)
	{
		if (length<20)
		{
			return (length *17) + "px";	
		}

	},

	// load initial options if specified
	_getOptions: function(element,theme)
	{
		if (element.options)
		{
			var html = '';
			var values = [];
			for (var i=0;i<element.options.length;i++)
			{
				html += '<div id="'+element.id+'_combo_'+i+'" class="select_'+theme+'_dropdown_item" on="click then l:'+element.id+'_combo_rowclick[row='+i+'] or mouseover then add[class=select_'+theme+'_dropdown_hover] and l:'+element.id+'_combo_mouseover[row='+i+'] or mouseout then remove[class=select_'+theme+'_dropdown_hover]">'+element.options[i].text+'</div>';
				values.push({'index':i,'id':element.id+'_combo_'+i,'value':element.options[i].value,'text':element.options[i].text});

			}
			this.selectOptions[element.id] = values;
			this.currentOptions[element.id] = values;
			this.activeSelects[element.id].selectedIndex = 0;

			return html;
		}
		return '';
	},
	
	// toggle dropdown list visibility
	_toggleList: function(id)
	{
		if ($(id + "_combo_box").style.display == 'none')
		{
			// record start value and index based on input value
			this.activeSelects[id].startValue = $(id +"_input").value;
			this.activeSelects[id].startIndex = this.activeSelects[id].selectedIndex = this._getIndexForValue(id,this.activeSelects[id].startValue);
			Element.addClassName($(id+"_combo_" + this.activeSelects[id].selectedIndex),'select_'+this.activeThemes[id]+'_dropdown_hover');									
		}
		Element.toggle(id + "_combo_box");
	},	
	
	// set input value
	_setValue: function(id,value)
	{
		$(id + "_input").value = value;
	},
	
	// get index for value
	_getIndexForValue: function(id, value)
	{
		for (var i=0;i<this.currentOptions[id].length;i++)
		{
			if (this.currentOptions[id][i].text == value)
			{
				return i;
			}
		}
		return -1;
	}	
});
