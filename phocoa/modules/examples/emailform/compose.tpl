<p>Simple email module showcases many PHOCOA features. Code below.</p>

<ul>
    <li>Form creation.</li>
    <li>Action handlers.</li>
    <li>Validation - Notice how there are no calls in code to validate. This is done automatically via Key-Value Validation concept.</li>
    <li>Error management - Try entering invalid data. Notice how the system autmatically tracks errors per-field and per-form.</li>
    <li>Bindings - notice how there is no code to move the data from the form to the ExampleEmail object. This is all done via Bindings which are configured graphically (or in a text file).</li>
    <li>Adjusting page title for each page. This is part of the skin infrastructure.</li>
</ul>

{WFShowErrors}

<table border="0">
{WFForm id="form"}
    <tr><td valign="top">To Email:</td><td>{WFTextField id="email"}<br /> {WFShowErrors id="email"}</td></tr>
    <tr><td valign="top">Subject:</td><td>{WFTextField id="subject"}<br /> {WFShowErrors id="subject"}</td></tr>
    <tr><td valign="top">Message:</td><td>{WFTextArea id="message"}<br /></td></tr>
    {WFSubmit id="submit"}
{/WFForm}
</table>

<hr>

<p>Shared instances are objects that are shared among two or more pages in a module. The shared instances mechanism allows multi-page processes to easily share the same instances. For this module, we have 2 shared instances, a WFUNIXDateFormatter, and our ExampleEmail object. PHOCOA automatically instantiates shared objects as members of your module subclass.</p>
<h3>shared.instances file</h3>
<pre>
$__instances = array(
	'dateSentFormatter' => 'WFUNIXDateFormatter',
	'email' => 'ExampleEmail',
);
</pre>
<h3>shared.config file</h3>
<pre>
$__config = array(
	'dateSentFormatter' => array(
		'properties' => array(
			'formatString' => 'F j, Y, g:i a',
		),
	),
);
</pre>

{capture name="emailTPL"}
{literal}
<table border="0">
{WFForm id="form"}
    <tr><td valign="top">To Email:</td><td>{WFTextField id="email"}<br /> {WFShowErrors id="email"}</td></tr>
    <tr><td valign="top">Subject:</td><td>{WFTextField id="subject"}<br /> {WFShowErrors id="subject"}</td></tr>
    <tr><td valign="top">Message:</td><td>{WFTextArea id="message"}<br /></td></tr>
    {WFSubmit id="submit"}
{/WFForm}
</table>
{/literal}
{/capture}
<h3>.tpl file</h3>
<pre>
{$smarty.capture.emailTPL|escape:'html'}
</pre>
    
<h3>.instances file</h3>
<pre>
$__instances = array(
	'form' => array('class' => 'WFForm', 'children' => array(
        'subject' => array('class' => 'WFTextField', 'children' => array()),
        'message' => array('class' => 'WFTextArea', 'children' => array()),
        'submit' => array('class' => 'WFSubmit', 'children' => array()),
        'email' => array('class' => 'WFTextField', 'children' => array()),
        )
    ),
);
</pre>
    
<h3>.config file</h3>
<pre>
$__config = array(
	'subject' => array(
		'properties' => array(
			'size' => '50',
		),
		'bindings' => array(
			'value' => array(
				'instanceID' => 'email',
				'controllerKey' => '',
				'modelKeyPath' => 'subject',
			),
		),
	),
	'message' => array(
		'properties' => array(
			'rows' => '10',
			'cols' => '50',
		),
		'bindings' => array(
			'value' => array(
				'instanceID' => 'email',
				'controllerKey' => '',
				'modelKeyPath' => 'message',
			),
		),
	),
	'submit' => array(
		'properties' => array(
			'label' => 'Send Email',
		),
	),
	'email' => array(
		'properties' => array(
			'size' => '50',
		),
		'bindings' => array(
			'value' => array(
				'instanceID' => 'email',
				'controllerKey' => '',
				'modelKeyPath' => 'toEmail',
			),
		),
	),
);
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
