<?php

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

class helloworld extends WFModule
{
    /**
      * Tell system which page to show if none specified.
      */
    function defaultPage() { return 'helloWorld'; }

    function helloWorld_submit_Action($page)
    {
        $this->email->send();
        $this->setupResponsePage('emailSuccess');
    }

    function helloWorld_SetupSkin($skin)
    {
        $skin->setTitle("Hello, World, from PHOCOA!");
        $skin->addMetaKeywords(array('keyword 1', 'keyword 2'));
        $skin->setMetaDescription('Hello world example module in PHOCOA.');
    }
}

?>
