<p>The response page uses a formatter on the timestamp field to convert the PHP time to a human-readable format.</p>

<hr>

<p>Successfully sent email!</p>

Date Sent: {WFLabel id="timestamp"}<br />
To: {WFLabel id="email"}<br />
Subject: {WFLabel id="subject"}<br />
Message: {WFLabel id="message"}

<hr>
{capture name="emailTPL"}
{literal}
Date Sent: {WFLabel id="timestamp"}<br />
To: {WFLabel id="email"}<br />
Subject: {WFLabel id="subject"}<br />
Message: {WFLabel id="message"}
{/literal}
{/capture}
<h3>emailSuccess.tpl file</h3>
<pre>
{$smarty.capture.emailTPL|escape:'html'}
</pre>
    
<h3>emailSuccess.yaml file</h3>
<pre>
{php}
echo file_get_contents(FRAMEWORK_DIR . '/modules/examples/emailform/emailSuccess.yaml');
{/php}
</pre>

<p>The module code and shared instances are the same as the previous screen, just repeated for convenience. Both pages are part of the same module.</p>
<h3>shared.yaml file</h3>
<pre>
{php}
echo file_get_contents(FRAMEWORK_DIR . '/modules/examples/emailform/shared.yaml');
{/php}
</pre>

<h3>Module Code</h3>
<pre>
{literal}
class ExampleEmail extends WFObject
{
    protected $toEmail;
    protected $subject;
    protected $message;
    protected $sendTimestamp;

    function send()
    {
        $sent = mail( $this->toEmail, $this->subject, $this->message );
        $this->sendTimestamp = time();
        return $sent;
    }

    function validateToEmail(&$value, &$edited, &$errors)
    {
        $value = trim($value);
        $edited = true;
        if (preg_match("/[A-z0-9._-]+@[A-z0-9-]+\.[A-z0-9-\.]*[A-z]+$/", $value) == 1) return true;

        $errors[] = new WFError("The email you entered is not a properly formatted email address.");
        return false;
    }

    function validateSubject(&$value, &$edited, &$errors)
    {
        $value = trim($value);
        $edited = true;
        if ($value != '') return true;
        
        $errors[] = new WFError("The subject cannot be blank.");
        return false;
    }
}

class emailform extends WFModule
{
    /**
      * Tell system which page to show if none specified.
      */
    function defaultPage() { return 'compose'; }

    function compose_submit_Action($page)
    {
        $this->email->send();
        $this->setupResponsePage('emailSuccess');
    }

    function compose_SetupSkin($skin)
    {
        $skin->setTitle("Compose an email.");
    }

    function emailSuccess_SetupSkin($skin)
    {
        $skin->setTitle("Email sent successfully.");
    }
}
{/literal}
</pre>
