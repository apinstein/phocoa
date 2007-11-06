{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
<h1>AJAX AutoComplete</h1>

<p>Examples of ComboBox/Autocomplete AJAX widget built with YUI AutoComplete widget.</p>

<p>If you're looking to build an ajax autocomplete or javascript autocomplete widget for your web page, PHOCOA and YUI make it easy to do.</p>

<p>Note that all of these examples are created without any coding, other than defining the arrays of data. The rest is all done through PHOCOA Builder configuration.
 
<hr />
<p>Select a state example, with a custom format function.</p>

<script type="text/javascript">
{literal}
// This function returns markup that bolds the original query,
// and also displays to additional pieces of supplemental data.
PHOCOA.namespace('widgets.state.yuiDelegate.widgetDidLoad');
PHOCOA.widgets.state.yuiDelegate.widgetDidLoad = function(ac) {
    ac.formatResult = function(aResultItem, sQuery) {
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
};
{/literal}
</script>
 
<p>Pick a US state: {WFView id="state"}</p>

<hr />
<p>This one shows all choices before even entering, to act as kind of a "pick list". (all choices shown by default is not yet working)</p>
<p>What is your favorite color of the rainbow? {WFView id="color"}</p>

