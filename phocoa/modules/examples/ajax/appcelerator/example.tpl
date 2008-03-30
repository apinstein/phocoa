{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
<h1>Appcelerator</h1>
<p>We are very excited to be working with the amazing people over at <a href="http://appcelerator.com" target="_blank">Appcelerator</a>. Appcelerator is an amazing UI toolkit for writing RIA's (rich internet applications) without using proprietary technologies like Flex or Silverlight. Appcelerator is completely standards-compliant, using only Javascript to implement it's magic. You can learn more at the <a href="http://appcelerator.org" target="_blank">Appcelerator Developer Network</a>.</p>

<p>What's perhaps most amazing about it is how easy it makes writing RIA's. The event-driven programming model works seamlessly with standard HTML coding practices and talks to your server using SOA (service-oriented architecture). It's fast, lightweight, and even fun!</p>

<p>PHOCOA now ships with Appcelerator included, so you can start writing Appcelerator applications with PHOCOA without any additional installs.</p>

<p>We have done a nice integration between Appcelerator and PHOCOA -- Appcelerator ties into the same RPC mechanism that PHOCOA uses for its own AJAX infrastructure. This means you can write services for Appcelerator in the same way you implement AJAX functionality for a normal PHOCOA page. You don't even need to write separate SOA services (ie SOAP, REST, etc.).  This means that you can use PHOCOA bindings still with Appcelerator data, combining PHOCOA's powerful controller layer with a rich interface.</p>

<table border="0" cellpadding="3" cellspacing="0">
<tr>
    <th colspan="2">Example Email Form</th>
</tr>
<tr>
    <td>To:</td>
    <td><input decorator="required" validator="email" id="to" name="to" fieldset="myFields" type="text"></td>
</tr>
<tr>
    <td>From:</td>
    <td><input decorator="required" validator="email" id="from" name="from" fieldset="myFields" type="text"></td>
</tr>
<tr>
    <td>Subject:</td><td><input decorator="required" validator="required" id="subject" name="subject" fieldset="myFields" type="text"></td>
</tr>
<tr>
    <td>Body:</td><td><textarea decorator="required" validator="required" fieldset="myFields" id="body" name="body"></textarea></td>
</tr>
<tr>
    <td colspan="2"><input activators="to,from,subject,body" fieldset="myFields" type="button" value="send" on="click then r:send" /></td>
</tr>
</table>

<div style="position: relative; height: 200px; width: 200px;">
    <div on="r:send then effect[grow] or r:response then hide" style="display: none; position: absolute; ">Sending message...</div>
    <div on="r:send then hide or r:response then value[message] and show" style="display: none"></div>
</div>

{WFView id="appcelerator"}
