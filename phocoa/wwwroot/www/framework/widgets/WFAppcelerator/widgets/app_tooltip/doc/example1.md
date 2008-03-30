Title: Simple Example

This is a simple example that uses the `<app:tooltip>`.
	
Tooltip (basic)
	<div id="tooltip1">Hover over me and you should see a tooltip message</div>
	<app:tooltip element="tooltip1">
		Just a basic tooltip
	</app:tooltip>
	
Tooltip (background color and foreground color)
	<div id="tooltip2">Hover over me and you should see a tooltip message
	with background color of yellow</div>
	<app:tooltip element="tooltip2" backgroundColor="#ffffcc" textColor="#f00">
		Heres the tip!
	</app:tooltip>

Tooltip (track mouse movements)
	<div id="tooltip3">Hover over me and you should see the tooltip track with your mouse as you move it</div>
	<app:tooltip element="tooltip3" mouseFollow="true">
	Heres the tip!
	</app:tooltip>

Tooltip (opacity)
	<div id="tooltip4">Hover over me and you should see the tooltip with opacity</div>
	<app:tooltip element="tooltip4" opacity=".5">
		Heres the tip! with 50% opacity
	</app:tooltip>
