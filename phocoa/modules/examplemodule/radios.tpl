{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
<p>This page demonstrates the use of radio buttons in conjunction with WFDynamic.</p>

{WFForm id="radioForm"}
    {WFView id="radios"}<br />
    {WFSubmit id="submit"}
{/WFForm}

{$selectedValue}
