Title: Simple Example

This is a simple example that uses the `<app:search>`.
	
Here is a real simple example
	<app:search request="l:search.request" response="l:search.response" selected="l:search.selected" key="search" property="result">
	</app:search>
	<app:script on="l:search.request then execute">
		$MQ('l:search.response', {result: ['item 1','item 2','item 3']});
	</app:script>
	

Here's another example with a complex search result.
	<app:search request="l:search2.request" response="l:search2.response" selected="l:search2.selected" key="search" property="result">
		<html:div>
			#{name}
		</html:div>
		<html:div>
			#{city}, #{job}
		</html:div>
	</app:search>
	<app:script on="l:search2.request then execute">
		$MQ('l:search2.response', {result: [{id: 1, name: 'John', city: 'Atlanta', job: 'Engineer'}, {id: 2, name: 'Bob', city: 'New York', job: 'Mayor'}, {id: 3, name: 'Bill', city: 'Seattle', job: 'Plumber'}]});
	</app:script>
	<div style="border:1px solid #ccc;background-color:#f6f6f6;padding:10px;margin-top:10px;display:none" on="l:search2.selected then effect[appear] or l:search2.request then effect[fade]">
		You've selected an item with id <span on="l:search2.selected then value[value]"></span>.
	</div>