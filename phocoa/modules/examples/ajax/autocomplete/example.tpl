{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
<h1>YUI AutoComplete</h1>

{*
{WFView id="logger"}
*}

<script type="text/javascript">
{literal}
// Custom formatter function
PHOCOA.namespace('example.autocomplete');
PHOCOA.example.autocomplete.formatter = function(aResultItem, sQuery) {
       var sKey = aResultItem[0]; // the entire result key
       var sKeyQuery = sKey.substr(0, sQuery.length); // the query itself
       var sKeyRemainder = sKey.substr(sQuery.length); // the rest of the result

       // some other piece of data defined by schema
       var attribute1 = aResultItem[1]; 

       var aMarkup = ["<div id='ysearchresult'>",
          "<span style='font-weight:bold'>",
          sKeyQuery,
          "</span>",
          sKeyRemainder,
          ": ",
          attribute1,
          "</div>"];
      return (aMarkup.join(""));
};
// Install Custom formatter function on both state widgets
PHOCOA.namespace('widgets.state.yuiDelegate');
PHOCOA.widgets.state.yuiDelegate.widgetDidLoad = function(ac) {
    ac.formatResult = PHOCOA.example.autocomplete.formatter;
};
PHOCOA.namespace('widgets.stateAjax.yuiDelegate');
PHOCOA.widgets.stateAjax.yuiDelegate.widgetDidLoad = function(ac) {
    ac.formatResult = PHOCOA.example.autocomplete.formatter;
};
{/literal}
</script>

<p>Examples of ComboBox/Autocomplete AJAX widget built with YUI AutoComplete widget. PHOCOA procides support for both static data and AJAX callbacks to deliver the autocomplete results.</p>

<p>Note that all of these examples are created without any coding, other than defining the arrays of data. The rest is all done through PHOCOA Builder configuration.</p>
 
<h2>AutoComplete with Static Data</h2>
<h3>Simplest case</h3>
<p>What is your favorite color of the rainbow? {WFView id="color"}</p>

<h3>Adding custom formatters and other options</h3>
<p>All of the YUI options for AutoComplete are supported.</p>
<p>Select a state example, with a custom format function.</p>
<p>Pick a US state: {WFView id="state"}</p>

<h2>AutoComplete with Dynamic Data (via AJAX)</h2>
<p>This is the same as the state example above, but using AJAX to look for autocomplete matches.</p>
<p>Pick a US state: {WFView id="stateAjax"}</p>
