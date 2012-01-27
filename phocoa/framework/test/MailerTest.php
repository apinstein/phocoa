<?php

require_once(getenv('PHOCOA_PROJECT_CONF'));

class MailerTest extends PHPUnit_Framework_TestCase
{

    public function testSendCallsHtmlify()
    {
        $mailer = $this->getMock('Mail_Mailer', array('htmlify'));
        $mailer
            ->expects($this->once())
            ->method('htmlify')
            ->withAnyParameters()
            ;
        $mailer->setToEmail('foo@phocoa.com');
        $mailer->setFromEmail('bar@phocoa.com');
        $mailer->setSubject('test');
        $mailer->send();
    }

    /**
     * @dataProvider variousUrlDataProvider
     */
    public function testHtmlifyDetectsUrlsWithStrangeCharactersAndVariousSurroundingWhitespace($input, $htmlified)
    {
        $mailer = new Mail_Mailer();
        $mailer->setHtmlifyPlainTextOnlyMessages(true);
        $mailer->setMessageTEXT($input);
        $mailer->htmlify();
        $this->assertEquals($input,     $mailer->getMessageTEXT());
        $this->assertEquals($htmlified, $mailer->getMessageHTML());
    }
    public function variousUrlDataProvider()
    {
        return array(
            array(
                    'Message without any links',
               '<pre>Message without any links</pre>',
            ),
            array(
                    'Message mentioning the http protocol',
               '<pre>Message mentioning the http protocol</pre>',
            ),
            array(
                    'Message mentioning the https protocol',
               '<pre>Message mentioning the https protocol</pre>',
            ),
            array(
                    'This is an empty http:// link.',
               '<pre>This is an empty http:// link.</pre>',
            ),
            array(
                    'http://www.linky.com',
               '<pre><a href="http://www.linky.com">http://www.linky.com</a></pre>',
            ),
            array(
                    'https://www.secure-linky.com',
               '<pre><a href="https://www.secure-linky.com">https://www.secure-linky.com</a></pre>',
            ),
            array(
                    'Very basic message http://www.link.com with a link.',
               '<pre>Very basic message <a href="http://www.link.com">http://www.link.com</a> with a link.</pre>',
            ),
            array(
                    'Very basic message https://www.secure-link.com with a link.',
               '<pre>Very basic message <a href="https://www.secure-link.com">https://www.secure-link.com</a> with a link.</pre>',
            ),
            array(
                    "Link with\nhttp://www.link.com\nsurrounding line breaks.",
               "<pre>Link with\n<a href=\"http://www.link.com\">http://www.link.com</a>\nsurrounding line breaks.</pre>",
            ),
            array(
                    "Link with\thttp://www.link.com\tsurrounding tabs.",
               "<pre>Link with\t<a href=\"http://www.link.com\">http://www.link.com</a>\tsurrounding tabs.</pre>",
            ),
            array(
                    "Link with \n\thttp://www.link.com\n \tvarious surrounding whitespace.",
               "<pre>Link with \n\t<a href=\"http://www.link.com\">http://www.link.com</a>\n \tvarious surrounding whitespace.</pre>",
            ),
            array(
                    "http://weird-link.com/foo/?id=7&message=message%20with+spaces",
               '<pre><a href="http://weird-link.com/foo/?id=7&message=message%20with+spaces">http://weird-link.com/foo/?id=7&message=message%20with+spaces</a></pre>',
            ),
            array(
                    'text http://www.foo.com with http://www.bar.com multiple links',
               '<pre>text <a href="http://www.foo.com">http://www.foo.com</a> with <a href="http://www.bar.com">http://www.bar.com</a> multiple links</pre>',
            ),
            array(
                    'Click here http://www.spam.com/free-spam?get_it_now for spammy spam!',
               '<pre>Click here <a href="http://www.spam.com/free-spam?get_it_now">http://www.spam.com/free-spam?get_it_now</a> for spammy spam!</pre>',
            ),
            array(
                    "Link with\rhttp://www.link.com\rsurrounding slash-r.",
               "<pre>Link with\r<a href=\"http://www.link.com\">http://www.link.com</a>\rsurrounding slash-r.</pre>",
            ),
            array(
                    "Link with\r\nhttp://www.link.com\r\nsurrounding slash-r slash-n.",
               "<pre>Link with\r\n<a href=\"http://www.link.com\">http://www.link.com</a>\r\nsurrounding slash-r slash-n.</pre>",
            ),
        );
    }

    public function testHtmlifyDoesNotReplaceUrlsWithLinksIfOptionIsTurnedOff()
    {
        $input = "Simple http://www.link.com link";
        $mailer = new Mail_Mailer(); // Option is turned off by default
        $mailer->setMessageTEXT($input);
        $mailer->htmlify();
        $this->assertEquals('',     $mailer->getMessageHTML());
        $this->assertEquals($input, $mailer->getMessageTEXT());
    }

    public function testHtmlifyDoesNotReplaceUrlsWithLinksInMessagesWithAnExistingHtmlPart()
    {
        $inputText = 'Simple http://www.link.com link';
        $inputHtml = 'A different <a href="http://www.link.com">link</a> html message body.';

        $mailer = new Mail_Mailer(); // Option is turned off by default
        $mailer->setHtmlifyPlainTextOnlyMessages(true);
        $mailer->setMessageHTML($inputHtml);
        $mailer->setMessageTEXT($inputText);
        $mailer->htmlify();
        $this->assertEquals($inputText, $mailer->getMessageTEXT());
        $this->assertEquals($inputHtml, $mailer->getMessageHTML());
    }

}
