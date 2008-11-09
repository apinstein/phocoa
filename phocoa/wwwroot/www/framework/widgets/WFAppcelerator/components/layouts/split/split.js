Appcelerator.Widget.requireCommonJS('scriptaculous/builder.js',function()
{
	/**
	 * 
	 * @author p.mouawad@ubik-ingenierie.com
	 * Depends on prototype.js, builder.js and dragdrop.js
	 *
	 * with lots of modifications by jhaynie@appcelerator.com
	 * 
	 */
	UI = {id:1};
 
	UI.BaseSplitter = function() {};
	UI.BaseSplitter.prototype = {
		initialize: function(container, options) {
			this.container = $(container);
			this.setOptions(options);
			this._buildComponent();
			this._attachBehaviors();
		},
		ensureId:function(el)
		{
			if (!el.id)
			{
				el.id = 'splitter_'+(UI.id++);
			}
		},
		fwkGetBrother: function(elt,nodeName) {
			var brother = elt;
			if (brother)
			{
				do 
				{
					brother = brother.nextSibling;
				}
				while (brother && brother != null && brother.nodeName != nodeName);		
				if (brother)
				{
					this.ensureId(brother);
					return brother;
				}
			}
			return null;
		},	
		fwkGetChild: function(elt,nodeName) {
			var childs = elt.childNodes;
			for (var i = 0;i < childs.length;i++) {
				if (childs[i].nodeName == nodeName) {
					this.ensureId(childs[i]);
					return childs[i];
				}
			}
			return null;
		},	
		setOptions: function(options) {
			this.options = {
				position		: 'absolute',
				top				: null,
				left			: null,
				height			: null,
				width			: null,
				panel1Prop		: 0.5,
				panel2Prop		: 0.5,
				splitterWidth 	: 10,
				closeOnLeftUp	: true,
				withImage		: true,
				onPanelsResize	: Prototype.emptyFunction,
				imageDir		: 'fwk/image/splitter/',
				splitterBg		: '#ccc',
				splitterBgHover : '#999',
				cursorBg		: '#0099CD',
				cursorBgOn		: '#00CCFE',
				handleOnHover   : false,
				livedragging    : true
			}
			Object.extend(this.options, options || {});
		},
		_attachBehaviors: function(elt) {	
			this.splitterDiv.onmouseover = this._changeCursorMouseOver.bindAsEventListener(this);
			this.splitterDiv.onmouseout = this._changeCursorMouseOut.bindAsEventListener(this);
			this.splitterDiv.ondblclick = this._splitterOnMouseClick.bindAsEventListener(this);
		
			this.oldPosition = null;
			this.draggable = new Draggable(this.splitterDiv.id,{
				ghosting:false,
				snap: this._getSnap()
			});
			Droppables.add(this.firstDiv.id);
			Droppables.add(this.secondDiv.id);		
			Draggables.addObserver(this);
		},
		dispose: function() {
			Droppables.remove(this.firstDiv.id);
			Droppables.remove(this.secondDiv.id);
			Draggables.removeObserver(this);
			this.firstDiv = null;
			this.secondDiv = null;
			this.splitterCursorDiv = null;
			this.splitterDiv = null;
			this.draggable = null;
			this.container = null;
		},
		onStart: function(eventName, draggable, event) {
		    if (draggable.element.id == this.splitterDiv.id)
			{
				draggable.element.style.border = this.options.dragBorder || '1px dotted #999';
			}
		},
		onDrag: function(eventName, draggable, event) {
		    if (this.options.livedragging && (draggable.element.id == this.splitterDiv.id))
			{
				this._onDropCallback(draggable.element);
			}
		},
		onEnd: function(eventName, draggable, event) {
		    if (draggable.element.id == this.splitterDiv.id)
			{
				draggable.element.style.border='';
				this._onDropCallback(draggable.element);
			}
		},
		_changeCursorMouseOver:function(evt) {
			if (this.options.withImage)
			{
				var img = this.fwkGetChild(this.splitterCursorDiv, 'IMG');
				img.src=this._getOnImage();
			}
			else
			{
				this.splitterCursorDiv.style.background=this.options.cursorBgOn;
			}
			if (this.options.splitterBgHover)
			{
				this.splitterDiv.style.background=this.options.splitterBgHover;
			}
			if (this.options.handleOnHover)
			{
				this.cursorImage.style.visibility='visible';
			}
		},
		_changeCursorMouseOut:function(evt) {
			if (this.options.withImage)
			{
				var img = this.fwkGetChild(this.splitterCursorDiv, 'IMG');
				img.src=this._getOutImage();
			}
			else
			{
				this.splitterCursorDiv.style.background=this.options.cursorBg;
			}
			if (this.options.splitterBgHover)
			{
				this.splitterDiv.style.background=this.options.splitterBg;
			}
			if (this.options.handleOnHover)
			{
				this.cursorImage.style.visibility='hidden';
			}
		}
	}

	UI.HorizontalSplitter = Class.create();
	UI.HorizontalSplitter.prototype  = Object.extend(new UI.BaseSplitter(), {
	   	_buildComponent: function() {
	   		var baseId = this.container.id;
   		
	   		Element.setStyle(this.container.id, {
	   			position:this.options.position,
	   			left:this.options.left,
	   			top:this.options.top,
	   			width:this.options.width, 
	   			height:this.options.height});
		
			// Calcul des dimensions de l'objet
			var dimensions = Element.getDimensions(this.container.id);
			// Calcul de la position de base par rapport a la page
			var basePosition = Position.page(this.container);
			var splitterWidth = this.options.splitterWidth;
			var part1Width = (dimensions.width * this.options.panel1Prop)- (splitterWidth/2);
			var part2Width = (dimensions.width * this.options.panel2Prop)- (splitterWidth/2);
		
			this.firstDiv = this.fwkGetChild(this.container,'DIV');
			this.secondDiv = this.fwkGetBrother(this.firstDiv, 'DIV');
			
	   		Element.setStyle(this.firstDiv, {
	   			overflow:'auto',
	   			position:'absolute',
	   			left:0, 
	   			top:0, 
	   			width:part1Width, 
	   			height:this.options.height});
	   		Element.setStyle(this.secondDiv, {
	   			overflow:'auto', 
	   			position:'absolute', 
	   			left:part1Width+splitterWidth, 
	   			top:0, 
	   			width:part2Width, 
	   			height:this.options.height});

			var cursor = this.options.cursor || 'w-resize';

			this.splitterDiv = Builder.node('div',{id:baseId+'Splitter',
				style:'cursor:'+cursor+';text-align:center;margin:auto;background:'+this.options.splitterBg+';height:'+this.options.height+';width:'+splitterWidth});
			this.container.insertBefore(this.splitterDiv, this.secondDiv);
			Element.setStyle(this.splitterDiv.id, {position:'absolute',left:part1Width,top:0});

			this.splitterCursorDiv = Builder.node('div',{id:baseId+'CursorDiv', style:'position:absolute;padding-left:2px;left:0;top:50%;'});
			this.splitterDiv.appendChild(this.splitterCursorDiv);
		
			if (this.options.withImage)
			{
				this.cursorImage = Builder.node('IMG', {id:baseId+'CursorImg',src:this._getOutImage(),align:'middle'});		
				this.splitterCursorDiv.appendChild(this.cursorImage);	
				if (this.options.handleOnHover)
				{
					this.cursorImage.style.visibility='hidden';
				}	
			}
			else
			{
				Element.setStyle(this.splitterDiv, {background:this.options.splitterBg,borderLeft:'1px solid white', borderRight:'1px solid black'});		
				Element.setStyle(this.splitterCursorDiv, {background:this.options.cursorBg});
			}
		},
		_splitterOnMouseClick:function(evt) {
			this.draggable.finishDrag(evt, false);
			Event.stop(evt);

			if (this.oldPosition === null)
			{
				this.oldPosition = Position.positionedOffset(this.splitterDiv);
				var dimensions = Element.getDimensions(this.container.id);
				if (this.options.closeOnLeftUp)
				{
					var div2NewLeft = this.options.splitterWidth;	
					var div2Width = dimensions.width-this.options.splitterWidth;
					Element.toggle(this.firstDiv.id);
					this._setNewSize(0, div2Width, div2NewLeft, 0);
				}
				else
				{
					var splitterNewLeft = dimensions.width - this.options.splitterWidth;	
					var div1Width = dimensions.width-this.options.splitterWidth;
					Element.toggle(this.secondDiv.id);
					this._setNewSize(div1Width, 0, dimensions.width, splitterNewLeft);
				}
			}
			else
			{
				var dimensions = Element.getDimensions(this.container.id);
				var basePosition = this.oldPosition;
				this.oldPosition = null;
				var firstDivPosition = Position.positionedOffset(this.firstDiv);		
				var div1Width = basePosition[0];
				var div2NewLeft = basePosition[0]+ this.options.splitterWidth;	
				var div2Width = dimensions.width-(div1Width+this.options.splitterWidth);
				if (this.options.closeOnLeftUp)
				{
					Element.toggle(this.firstDiv.id);
				}
				else
				{
					Element.toggle(this.secondDiv.id);
				}
				this._setNewSize(div1Width, div2Width, div2NewLeft, div2NewLeft-this.options.splitterWidth);			
			}
		},
		_onDropCallback:function(element) {
			var dimensions = Element.getDimensions(this.container.id);
			var basePosition = Position.positionedOffset(this.splitterDiv);
			var div1Width = basePosition[0];
			var div2NewLeft = basePosition[0]+ this.options.splitterWidth;	
			var div2Width = dimensions.width-(div1Width+this.options.splitterWidth);
			this._setNewSize(div1Width, div2Width, div2NewLeft, div2NewLeft-this.options.splitterWidth);
		},
		_setNewSize:function(firstDivWidth, secondDivWidth, secondDivLeft, splitterLeft) {
			Element.setStyle(this.secondDiv.id,{width:secondDivWidth,left:secondDivLeft});
			Element.setStyle(this.firstDiv.id,{width:firstDivWidth});
			Element.setStyle(this.splitterDiv.id, {left:splitterLeft});
			Element.setStyle(this.splitterCursorDiv.id, {left:0});	
			this.options.onPanelsResize(0, firstDivWidth, secondDivLeft, secondDivWidth);
		},
		_getOnImage:function() {
			if (this.options.closeOnLeftUp)
			{
				return this.options.imageDir+'h_grabber.gif';
			}
			else
			{
				return this.options.imageDir+'h_grabber.gif';		
			}
		},
		_getOutImage:function() {
			if (this.options.closeOnLeftUp)
			{
				return this.options.imageDir+'h_grabber.gif';
			}
			else
			{
				return this.options.imageDir+'h_grabber.gif';		
			}	
		},
		_getSnap: function() {
			var dimensions = Element.getDimensions(this.container.id);
			var basePosition = Position.page(this.container);
			var left = basePosition[0];
			var verticality = basePosition[1];
			var right = basePosition[0]+dimensions.width-this.options.splitterWidth;
			return function(x,y) {
		      return[
		        (x < left) ? (left) : ((x > right) ? (right) : x),
		        verticality];
		    };
		}
	});


	UI.VerticalSplitter = Class.create();
	UI.VerticalSplitter.prototype  = Object.extend(new UI.BaseSplitter(), {
	   	_buildComponent: function() {
	   		var baseId = this.container.id;
	   		Element.setStyle(this.container.id, {
	   			position:this.options.position,
		   		left:this.options.left,
		   		top:this.options.top,
		   		width:this.options.width, 
		   		height:this.options.height});

			// Calcul des dimensions de l'objet
			var dimensions = Element.getDimensions(this.container.id);
			// Calcul de la position de base par rapport a la page
			var basePosition = Position.page(this.container);
			var splitterWidth = this.options.splitterWidth;
			var part1Height = (dimensions.height * this.options.panel1Prop)- (splitterWidth/2);
			var part2Height = (dimensions.height * this.options.panel2Prop)- (splitterWidth/2);
			
			this.firstDiv = this.fwkGetChild(this.container,'DIV');
			this.secondDiv = this.fwkGetBrother(this.firstDiv, 'DIV');

			Logger.info('dimensions='+Object.toJSON(dimensions));
			Logger.info('part1Height='+part1Height);
			Logger.info('part2Height='+part2Height);
			Logger.info('splitterWidth='+splitterWidth);

			
			var topOpt = {
	   			overflow:'auto', 
	   			position:'absolute',
	   			left:0,
	   			top:0, 
	   			width:this.options.width, 
	   			height:part1Height};
	
			Logger.info(Object.toJSON(topOpt));
			
	   		Element.setStyle(this.firstDiv, topOpt);

			var bottomOpt = {
	   			overflow:'auto', 
	   			position:'absolute',
	   			left:0, 
	   			top:(part1Height+splitterWidth), 
	   			width:this.options.width, 
	   			height:part2Height};
	
				Logger.info(Object.toJSON(bottomOpt));

	   		Element.setStyle(this.secondDiv, bottomOpt);

			var cursor = this.options.cursor || 'n-resize';

			this.splitterDiv = Builder.node('div',{id:baseId+'Splitter',
				style:'cursor:'+cursor+';background:'+this.options.splitterBg+';height:'+this.options.splitterWidth+';width:'+this.options.width});
			this.container.insertBefore(this.splitterDiv, this.secondDiv);
			Element.setStyle(this.splitterDiv.id, 
				{position:'absolute',left:0,top:part1Height, width:this.options.width,height:this.options.splitterWidth});

			this.splitterCursorDiv = Builder.node('div',{id:baseId+'CursorDiv', 
					style:'text-align:center;margin:auto;position:absolute;padding-top:2px;top:0;left:0;width:99%;height:'+this.options.splitterWidth});
			this.splitterDiv.appendChild(this.splitterCursorDiv);

			if (this.options.withImage)
			{
				this.cursorImage = Builder.node('IMG', {id:baseId+'CursorImg',src:this._getOutImage(),align:'middle'});		
				this.splitterCursorDiv.appendChild(this.cursorImage);		
				if (this.options.handleOnHover)
				{
					this.cursorImage.style.visibility='hidden';
				}	
			}
			else
			{
				Element.setStyle(this.splitterDiv, {
					background:this.options.splitterBg,
					borderTop:'1px solid white', 
					borderBottom:'1px solid black'});
				Element.setStyle(this.splitterCursorDiv, {
					background:this.options.cursorBg});
			}
		},
		_splitterOnMouseClick:function(evt) {
			this.draggable.finishDrag(evt, false);
			Event.stop(evt);

			if (this.oldPosition === null)
			{
				this.oldPosition = Position.positionedOffset(this.splitterDiv);
				var dimensions = Element.getDimensions(this.container.id);			
				if (this.options.closeOnLeftUp)
				{
					var div2NewTop = this.options.splitterWidth;	
					var div2Height = dimensions.height-this.options.splitterWidth;
					this._setNewSize(0, div2Height, div2NewTop, 0);
					Element.toggle(this.firstDiv.id);
				}
				else
				{
					var splitterNewTop = dimensions.height - this.options.splitterWidth;	
					var div1Height = dimensions.height-this.options.splitterWidth;
					this._setNewSize(div1Height, 0, dimensions.height, splitterNewTop);
					Element.toggle(this.secondDiv.id);
				}
			}
			else
			{
				var dimensions = Element.getDimensions(this.container.id);				
				var basePosition = this.oldPosition;
				this.oldPosition = null;
				var div1Height = basePosition[1];
				var div2NewTop = basePosition[1]+ this.options.splitterWidth;	
				var div2Height = dimensions.height-(div1Height+this.options.splitterWidth);
				if (this.options.closeOnLeftUp)
				{
					Element.toggle(this.firstDiv.id);
				}
				else
				{
					Element.toggle(this.secondDiv.id);
				}
				this._setNewSize(div1Height, div2Height, div2NewTop, div2NewTop-this.options.splitterWidth);
			}
		},
		_onDropCallback:function(element) {
			var dimensions = Element.getDimensions(this.container.id);
			var basePosition = Position.positionedOffset(this.splitterDiv);
			var div1Height = basePosition[1];
			var div2NewTop = basePosition[1]+ this.options.splitterWidth;	
			var div2Height = dimensions.height-(div1Height+this.options.splitterWidth);
			this._setNewSize(div1Height, div2Height, div2NewTop, basePosition[1]);
		},
		_setNewSize:function(firstDivHeight, secondDivHeight, secondDivTop, splitterTop) {
			Element.setStyle(this.secondDiv.id,{height:secondDivHeight,top:secondDivTop});
			Element.setStyle(this.firstDiv.id,{height:firstDivHeight});
			Element.setStyle(this.splitterDiv.id, {top:splitterTop});
			Element.setStyle(this.splitterCursorDiv.id, {top:0});
			this.options.onPanelsResize(0, firstDivHeight, secondDivTop, secondDivHeight);	
		},
		_getOnImage:function() {
			if (this.options.closeOnLeftUp)
			{
				return this.options.imageDir+'v_grabber.gif';
			}
			else
			{
				return this.options.imageDir+'v_grabber.gif';	
			}
		},
		_getOutImage:function() {
			if (this.options.closeOnLeftUp)
			{
				return this.options.imageDir+'v_grabber.gif';
			}
			else
			{
				return this.options.imageDir+'v_grabber.gif';		
			}
		},
		_getSnap: function() {
			var dimensions = Element.getDimensions(this.container.id);
			var basePosition = Position.page(this.container);
			var xAxis = 0;
			var top= basePosition[1];
			var bottom = basePosition[1]+dimensions.height-this.options.splitterWidth;
			return function(x,y) {
		      return[
		        xAxis, 
		        (y < top) ? (top) : ((y > bottom) ? (bottom) : y)
		        ];
		    };
		}
	});

	Appcelerator.UI.registerUIComponent('layout','split',
	{
		setPath:function(dir)
		{
			this.dir = dir;
		},
	
		/**
		 * The attributes supported by the layouts. This metadata is 
		 * important so that your layout can automatically be type checked, documented, 
		 * so the IDE can auto-sense the widgets metadata for autocomplete, etc.
		 */
		getAttributes: function()
		{
			var T = Appcelerator.Types;
			return [
				{
					name: 'mode', 
					optional: true, 
					description: 'orientation of split layout (horizontal or vertical)', 
					defaultValue: 'vertical',
				 	type: T.enumeration('vertical', 'horizontal')
				}
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
			var imagePath = this.dir + '/images/';

			var opts = 
			{
				imageDir:imagePath,
				withImage:true,
				closeOnLeftUp:true,
				left:0,
				top:0,
				width:'100%', 
				height:'100%', 
				panel1Prop: options['panel1Prop'] || 0.5, 
				panel2Prop: options['panel2Prop'] || 0.5,
				splitterWidth: parseInt(options['size'] || 10),
				splitterBg: options['background'],
				splitterBgHover: options['backgroundHover'],
				handleOnHover: options['handleOnHover']=='true',
				livedragging: options['livedragging']=='true'
			};
			Object.extend(opts, options || {});
			
			new Insertion.Before(element,"<div id='splitter_"+element.id+"' style='position:relative'></div>");
			
			var parent = $('splitter_'+element.id);
			parent.appendChild(element);

			switch (options.mode)
			{
				case 'horizontal':
				{
					new UI.HorizontalSplitter(element.id,opts);
					break;
				}
				case 'vertical':
				{
					new UI.VerticalSplitter(element.id,opts);
					break;
				}
			}
		}
	});
});



