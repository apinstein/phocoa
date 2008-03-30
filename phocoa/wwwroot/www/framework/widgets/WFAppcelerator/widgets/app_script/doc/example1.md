Title: Simple Example

This is a simple example that uses the `<app:script>`.
	
	<div style="border:1px solid #ccc;background-color:#f6f6f6;padding:10px;margin-top:10px;display:none"
		on="l:show.current.datetime then show and effect[Highlight] or l:reset.script.example then hide">
		<span style="color:#000">
			Current Date Time = <span on="l:show.current.datetime then value[datetime]"></span>
			<a on="click then l:reset.script.example">Reset Example</a>
		</span>
	</div>

	<!-- get current date time and send a message with the value -->
	<app:script on="l:get.current.datetime then execute">
		var date = new Date();
		$MQ('l:show.current.datetime',{'datetime':date});
	</app:script>
	