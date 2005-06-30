<p>This is a statically declared form. That is, all form elements are statically declared in the template file. See staticPage.tpl.</p>

{WFForm id="form"}
    First name: {WFTextField id="name"}<br />
    Creation DTS: {WFTextField id="creationDTS"}<br />
    Select One: {WFSelect id="selectOne"}<br />
    Select Multiple: {WFSelect id="selectMultiple"}<br />
    Text Area: {WFTextArea id="textarea"}<br />
    {WFSubmit id="action1"} {WFSubmit id="action2"}<br />
{/WFForm}

<hr />
{WFModuleView id="emailView"}
