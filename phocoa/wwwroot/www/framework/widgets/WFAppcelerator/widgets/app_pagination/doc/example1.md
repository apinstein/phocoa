Title: Simple Example

This is a simple example that uses the `<app:pagination>`.
	
	<app:pagination id="example_pagination" request="l:paginationexample.request" response="l:paginationexample.response"
		resultsString="#{start}-#{end}" totalsString="of #{total} scores" nextText="next"
		prevText="prev" showTotals="true" startProperty="start" endProperty="end" totalProperty="total">
	</app:pagination>
	<div class="scorecontainer">
		<app:iterator on="l:paginationexample.response then execute" property="rows" >
			<html:div class="scoreentry">
				<html:table width="100%">
					<html:tr class="scoretitle"><html:td>#{date} </html:td></html:tr>
					<html:tr><html:td class="home">#{home} #{homescore}</html:td></html:tr>
					<html:tr><html:td class="away">#{away} #{awayscore}</html:td></html:tr>
				</html:table>
				<html:div class="scoretime">#{timeleft}</html:div>
			</html:div>
		</app:iterator>
	</div>
	<app:script>
		var start = 1;
		var end = 4;
		var total = 12;
		var rows = new Array();
		for (var i=start-1;i&lt;=end-1;i++)
		{
			rows.push (PaginationExample.rows[i]);
		}
		var result = {'end':end,'start':start,'total':total,'rows':rows};
		$MQ('l:paginationexample.response',result);
	</app:script>
	<app:script on="l:paginationexample.request then execute">
		var start = 0;
		var end = 0;
		if (this.data.dir == 'next')
		{
			start = this.data.end + 1;
			end = this.data.end + 4;
		}
		else
		{
			start = this.data.start - 4;
			end = this.data.end - 4;
		}
		var dir = this.data.dir;
		var total = this.data.total;
		var rows = new Array();
		for (var i=start-1;i&lt;=end-1;i++)
		{
			rows.push (PaginationExample.rows[i]);
		}
		var result = {'end':end,'start':start,'total':total,'rows':rows};
		$MQ('l:paginationexample.response',result);
	</app:script>
	<script>
		var PaginationExample = Class.create();
		PaginationExample.rows = new Array();
		function addScore(array,home,away,homescore,awayscore,date,timeleft)
		{
			array.push({'home':home,'away':away,'homescore':homescore,'awayscore':awayscore,'date':date,'timeleft':timeleft})
		}
		addScore(PaginationExample.rows,"(16) Hawaii","Nevada","","","Fri 11/16","");
		addScore(PaginationExample.rows,"(2) Oregon","Arizona",24,34,"Thu 11/15","Final");
		addScore(PaginationExample.rows,"(1) LSU","Mississippi","","","Sat 11/17","");
		addScore(PaginationExample.rows,"(3) Kansas","Iowa St","","","Sat 11/17","");
		addScore(PaginationExample.rows,"(4) Oklahoma","Texas Tech","","","Sat 11/17","");
		addScore(PaginationExample.rows,"(5) Missouri","Kansas St","","","Sat 11/17","");
		addScore(PaginationExample.rows,"(6) West Virgina","(22) Cincinatti","","","Sat 11/17","");
		addScore(PaginationExample.rows,"(7) Ohio St","(21) Michigan","","","Sat 11/17","");
		addScore(PaginationExample.rows,"(23) Kentucky","(9) Georgia","","","Sat 11/17","");
		addScore(PaginationExample.rows,"Miami (FL)","(10) Virginia Tech","","","Sat 11/17","");
		addScore(PaginationExample.rows,"Florida Atlantic","(12) Florida","","","Sat 11/17","");
		addScore(PaginationExample.rows,"(17) Boston College","(15) Clemson","","","Sat 11/17","");
	</script>

	<style>
	.scoretitle
	{
		color:#696969;
		background-color:#FFFFCC;
		font-weight:bold;
	}
	.home
	{
		font-weight:bold;
		font-size:10px;
	}
	.scoretime
	{
		color:#F00;
		font-size:10px;
		position:absolute;
		top:5px;
		right:14px;
	}
	.away
	{
		font-size:10px;
	}
	.scoreentry
	{
		border-left:1px solid #ccc;
		border-top:1px solid #ccc;
		border-right:1px solid #ccc;
		border-bottom:1px solid #ccc;
		padding:3px 3px;
		margin-bottom:5px;
		width:150px;
		position:relative;
	}
	.scorecontainer
	{
		padding-top:10px;
		padding-bottom:10px;
	}
	</style>