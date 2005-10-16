<p>This is a statically declared form. That is, all form elements are statically declared in the template file. See staticPage.tpl.</p>

{WFForm id="form"}
    First name: {WFTextField id="name"}<br />
    Creation DTS: {WFTextField id="creationDTS"}<br />
    Select One: {WFSelect id="selectOne"}<br />
    Select Multiple: {WFSelect id="selectMultiple"}<br />
    Text Area: {WFTextArea id="textarea"}<br />
    {WFCheckbox id="checkbox"}<br />
    <div style="border: 1px solid black; padding: 10px">
    Example of WFCheckboxGroup.<br />
    {WFView id="checkboxOne"}<br />
    {WFView id="checkboxTwo"}<br />
    {WFView id="checkboxThree"}<br />
    </div>
    {WFView id="radioOne"} {WFView id="radioTwo"}<br />
    {WFSubmit id="action1"} {WFSubmit id="action2"}<br />
{/WFForm}

<hr />
{WFModuleView id="emailView"}
