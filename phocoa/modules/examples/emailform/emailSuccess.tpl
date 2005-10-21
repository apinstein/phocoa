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
<h3>.tpl file</h3>
<pre>
{$smarty.capture.emailTPL|escape:'html'}
</pre>
    
<h3>.instances file</h3>
<pre>
$__instances = array(
	'timestamp' => array('class' => 'WFLabel', 'children' => array()),
	'message' => array('class' => 'WFLabel', 'children' => array()),
	'subject' => array('class' => 'WFLabel', 'children' => array()),
	'email' => array('class' => 'WFLabel', 'children' => array()),
);
</pre>
    
<h3>.config file</h3>
<pre>
$__config = array(
	'timestamp' => array(
		'properties' => array(
			'formatter' => '#module#dateSentFormatter',
		),
		'bindings' => array(
			'value' => array(
				'instanceID' => 'email',
				'controllerKey' => '',
				'modelKeyPath' => 'sendTimestamp',
			),
		),
	),
	'message' => array(
		'bindings' => array(
			'value' => array(
				'instanceID' => 'email',
				'controllerKey' => '',
				'modelKeyPath' => 'message',
			),
		),
	),
	'subject' => array(
		'bindings' => array(
			'value' => array(
				'instanceID' => 'email',
				'controllerKey' => '',
				'modelKeyPath' => 'subject',
			),
		),
	),
	'email' => array(
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


<p>The module code and shared instances are the same as the previous screen, just repeated for convenience. Both pages are part of the same module.</p>
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
