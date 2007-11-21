{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
<h2>Examples of all Widgets: Table of Contents</h2>

<p>All examples use the same data model for consistency. There is a <b>Person</b> object and a <b>City</b> object. Person has many attributes, one of which, city, is a link to a city object.</p>

<p>Code is included with each example.</p>

<h3>Widget Examples</h3>
<p>The examples below demonstrate how to use each widget, and how to bind the widget's data to a model object. For these examples, there are two shared instances, <b>person</b> and <b>cities</b>. Person is a WFObjectController object with its content set to a single person instance. Cities is a WFObjectController with its content set to a list of all cities. This is a typical setup for a PHOCOA web page.</p> 
<p>Each example below is a different page of the same module. The module's shared setup source is <a href="{WFURL page="sharedSetup"}">here</a>.</p>
<ul>
    <li><a href="{WFURL page="form"}">WFForm</a></li>
    <li><a href="{WFURL page="textField"}">WFTextField</a></li>
    <li><a href="{WFURL page="textArea"}">WFTextArea</a></li>
    <li><a href="{WFURL page="htmlArea"}">WFHTMLArea</a></li>
    <li><a href="{WFURL page="select"}">WFSelect</a></li>
    <li><a href="{WFURL page="jumpSelect"}">WFJumpSelect</a></li>
    <li><a href="{WFURL page="radios"}">WFRadioGroup with WFDynamic WFRadio's</a></li>
    <li><a href="{WFURL page="simpleForm"}">Putting it all together: A simple form with multiple widgets</a></li>
</ul>

<h3>AJAX Integration</h3>
<p>PHOCOA provides both low-level and high-level AJAX support. The low-level support is a simple RPC infrastructure for AJAX with specialized wiring to easily route Javascript RPC calls to PHP objects. The high-level support allows DOM events to easily be wired to php objects.</p>
<p>Some PHOCOA widgets also make use of the AJAX integration directly, making it very easy to add AJAX functionality to your app by adding only PHP functions.</p>
<ul>
    <li><a href="{WFURL module="/examples/ajax/general"}">General Ajax Examples</a></li>
    <li><a href="{WFURL module="/examples/ajax/tree"}">YUI TreeView</a></li>
    <li><a href="{WFURL module="/examples/ajax/autocomplete"}">YUI Autocomplete</a></li>
    <li><a href="{WFURL module="/examples/ajax/container"}">YUI Container Family: Module, Overlay, Panel, Dialog, and PhocoaDialog</a></li>
</ul>

<h3>Advanced Features</h3>
<p>This area includes additional UI kit capabilities that are more involved than simple widgets.</p>
<ul>
    <li><a href="{WFURL module="/examples/concepts/pagination"}">Pagination</a></li>
    <li><a href="{WFURL module="/examples/concepts/formatters"}">Formatters</a></li>
    <li><a href="{WFURL module="/examples/concepts/dynamicHiding"}">Dynamic Hiding</a></li>
    <li><a href="{WFURL module="/examples/concepts/bindings"}">Bindings - Binding Options and Multi-Value Bindings</a></li>
</ul>
