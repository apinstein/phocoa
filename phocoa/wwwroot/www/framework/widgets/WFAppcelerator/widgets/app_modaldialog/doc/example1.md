Title: Simple Example

This is a simple example that uses the `<app:modaldialog>`.
	
	<style>
	<!--
	.my_modal_container
	{
		border:2px solid #999;
		width:50%;
	}
	.my_modal_header
	{
		padding:5px;
		color:#fff;
		background-color:#0f3c71;
	}
	.my_modal_header img
	{
		position:relative;
		top:3px;
	}
	.my_modal_body
	{
		background-color:#fff;
		color:#666;
		font-size:16px;
		height:50px;
		padding:10px;
	}
	-->
	</style>
	<a on="click then l:modaldialog1.test">Click here for free Macallan and 2.5% more happiness</a>
	<div style="margin-top:10px">
		<img src="macallan.gif"/>
	</div>
	<app:modaldialog on="l:modaldialog1.test then execute">
		<html:table width="30%" align="center" class="my_modal_container">
			<html:tr>
				<html:td class="my_modal_header" align="left" valign="middle">
					<html:img src="cross.png"></html:img> Request Denied
				</html:td>
			</html:tr>
			<html:tr>
				<html:td class="my_modal_body" valign="middle" align="center">
					I don't think so pal...  
					<html:a on="click then l:appcelerator.modaldialog.hide">Close Me</html:a>							
				</html:td>
			</html:tr>				
		</html:table>
	</app:modaldialog>