Hello, World!

{WFShowErrors}

{WFForm id="form"}
    Email: {WFTextField id="email"}<br /> {WFShowErrors id="email"}
    Subject: {WFTextField id="subject"}<br /> {WFShowErrors id="subject"}
    Message: {WFTextArea id="message"}<br />
    {WFSubmit id="submit"}
{/WFForm}
