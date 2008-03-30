<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

class module_appcelerator extends WFModule
{
    function defaultPage()
    {
        return 'example';
    }
    function allPages()
    {
        return array('example');
    }
}

class module_appcelerator_example
{
    function setupSkin($page, $params, $skin)
    {
        $skin->setTitle('Appcelerator Demo');
    }

    // Appcelerator Services Below
    function send($page, $params)
    {
        // code to send message here...
        // Send response to appcelerator
        sleep(2);   // give animation time to display for demo
        return WFActionResponseAppcelerator::WFActionResponseAppcelerator('response', array('message' => 'Mail request received. NOTE: This demo does not actually send the email.'));
    }
}

class email extends WFObject
{
    protected $to;
    protected $from;
    protected $subject;
    protected $body;
}
?>
