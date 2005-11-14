<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * A mailer interface for easily constructing and sending text or text/html emails.
 *
 * Basically this is an easy wrapper around Mail and Mail_Mime.
 *
 * PHP Versions 4 and 5
 *
 * @category    Mail
 * @package     Mail_Mailer
 * @author      Alan Pinstein <apinstein@mac.com>                        
 * @copyright   Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @license     http://www.freebsd.org/copyright/freebsd-license.html  BSD License (2 Clause)
 * @version     CVS: $Id:$
 * @link        http://pear.php.net/package/Mail_Mailer
 * @todo        Need to finish packaging and documentation and submit to PEAR!
 */

/**
 * Include PEAR Mail.
 */
require_once('Mail.php');
/**
 * Include PEAR Mail_Mime.
 */
require_once('Mail/mime.php');

/**
 * This class is designed to allow users to easily send both TEXT and HTML email messages.
 *
 * Handles plain-text, html email, and attachments via Mail_Mime.
 *
 * @category    Mail
 * @package     Mail_Mailer
 * @author      Alan Pinstein <apinstein@mac.com>                        
 * @copyright   Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @license     http://www.freebsd.org/copyright/freebsd-license.html  BSD License (2 Clause)
 * @version     Release: @package_version@
 * @link        http://pear.php.net/package/Mail_Mailer
 * @see         Mail, Mail_Mime
 */
class Mail_Mailer
{
    /**
     * @var array An array of options for the pear_mailer_driver.
     */
    protected $pear_mailer_config;
    /**
     * @var string The Mail driver backend.
     */
    protected $pear_mailer_driver;
    /**
     * @var string TO email address.
     */
    protected $to_email;
    /**
     * @var string FROM name.
     */
    protected $from_name;
    /**
     * @var string FROM email address.
     */
    protected $from_email;
    /**
     * @var boolean TRUE to BCC the sender.
     */
    protected $bcc_sender;
    /**
     * @var array A list of all people to BCC.
     */
    protected $bcc_list;
    /**
     * @var array A list of all people to CC.
     */
    protected $cc_list;
    /**
     * @var string The subject of the message.
     */
    protected $subject;
    /**
     * @var string The Reply-To email address.
     */
    protected $reply_to_email;
    /**
     * @var integer Priority of the message, from 1 (high priority) to 5 (low priority). Default is 3 (normal).
     */
    protected $priority;
    /**
     * @var array An array of attachments.
     */
    protected $attachments;   // array of assoc_arrays; 'data' => file, 'type' => mime-type
    /**
     * @var string The HTML message.
     */
    protected $raw_message_html;
    /**
     * @var string The TEXT message.
     */
    protected $raw_message_text;
    
    /**
     * Constructor. Initializes object.
     *
     * See {@link http://pear.php.net/manual/en/package.mail.mail.factory.php PEAR Mail config} for info on the what the parameters do.
     *
     * @param $driver string The PEAR Mail backend to use. One of smtp, sendmail, or mail. DEFAULT is smtp. OPTIONAL.
     * @param $config array A PEAR Mail compatible config array. By default, will use local SMTP connection. OPTIONAL.
     */
    function __construct($driver = 'smtp', $config = NULL)
    {
        $this->to_email             = '';
        $this->to_name              = '';
        $this->from_name            = '';
        $this->from_email           = '';
        $this->bcc_sender           = false;
        $this->cc_list              = array();
        $this->bcc_list             = array();
        $this->subject              = '';
        $this->reply_to_email       = '';
        $this->priority             = 3;    // normal
        $this->attachments          = array();      // array of assoc_arrays with kv pairs: file, type, name, isfile
                                                    // to attach a file, set file = /path/to/file and isfile = true
                                                    // to attach a stream, set file = data and isfile = false
        $this->pear_mailer_driver   = $driver;
        $this->pear_mailer_config   = $config;
        $this->raw_message_html     = '';
        $this->raw_message_text     = '';
    }

    /**
     * Set a flag to BCC the sender.
     * @param $bcc_sender boolean
     */
    function setBCCSender($bcc_sender)
    {
        $this->bcc_sender = $bcc_sender;
    }

    /**
     *  Add an email to the CC list.
     *
     *  @param $cc Email address.
     *  @return 0 on success, a PEAR error if the email is not valid.
     */
    function addCCEmail($cc)
    {
        $cc = trim($cc);
        if ($this->emailOK($cc)) {
            $this->cc_list[] = $cc;
            return 0;
        } else {
            return PEAR::raiseError("Email address '$cc' is not valid.");
        }
    }


    /**
     *  Add an email to the BCC list.
     *
     *  @param $bcc Email address.
     *  @return 0 on success, a PEAR error if the email is not valid.
     */
    function addBCCEmail($bcc)
    {
        $bcc = trim($bcc);
        if ($this->emailOK($bcc)) {
            $this->bcc_list[] = $bcc;
            return 0;
        } else {
            return PEAR::raiseError("Email address '$bcc' is not valid.");
        }
    }

    /**
     * Set the TO NAME for the email.
     * @param $to string NAME of the person being sent to.
     */
    function setToName($to)
    {
        $to = trim($to);
        $this->to_name = $to;
    }

    /**
     * Set the TO address for the email.
     * @param $to string Properly formatted email.
     * @return $err int 0 if no error, PEAR error if email is not valid.
     */
    function setToEmail($to)
    {
        $to = trim($to);
        if ($this->emailOK($to)) {
            $this->to_email = $to;
            return 0;
        } else {
            return PEAR::raiseError("Email address '$to' is not valid.");
        }
    }

    /**
     * Set the FROM NAME for the email.
     * @param $from_name string From name
     */
    function setFromName($from_name)
    {
        $this->from_name = trim($from_name);
    }

    /**
     * Set the FROM address for the email.
     * @param $from_email string Properly formatted email.
     * @return $err int 0 if no error, PEAR error if email is not valid.
     */
    function setFromEmail($from_email)
    {
        $from_email = trim($from_email);
        if ($this->emailOK($from_email)) {
            $this->from_email = $from_email;
            return 0;
        } else {
            return PEAR::raiseError("Email address '$from_email' is not valid.");
        }
    }

    /**
     * Set the REPLY TO address for the email.
     * @param $reply_to_email string Properly formatted email.
     * @return $err int 0 if no error, 1 if error (malformed email)
     */
    function setReplyToEmail($reply_to_email)
    {
        $reply_to_email = trim($reply_to_email);
        if (!$this->emailOK($reply_to_email)) {
            return 1;
        }

        $this->reply_to_email = $reply_to_email;

        return 0;
    }

    /**
     * Set the priority of the message.
     * @param $priority int Priority from 1 to 5, 1 is MOST IMPORTANT.
     * @return $err int 0 if no error, 1 if error (illegal priority value)
     */
    function setPriority($priority)
    {
        if ($priority > 5 or $priority < 1) {
            return 1;
        }

        $this->priority = $priority;

        return 0;
    }

    /**
     * Set the subject line of the message.
     * @param $subj string The subject.
     */
    function setSubject($subj)
    {
        $this->subject = $subj;
    }

    /**
     * Set the HTML for the message.
     * @param $html string HTML to use for HTML portion of message.
     */
    function setMessageHTML($html_msg)
    {
        $this->raw_message_html = $html_msg;
    }

    /**
     * If just a simple message is desired to be sent, the caller can use this function.
     * @param $msg string - the BODY of the email.
     */
    function setMessageTEXT($msg)
    {
        $this->raw_message_text = $msg;
    }

    /**
     * Add an attachment from a stream of data.
     * @param $file string The file data stream
     * @param $filetype string The mime type of the file. defaults to 'application/octet-stream'
     * @param $name string The name of the attachment. defaults to ''.
     */
    function addAttachmentStream($filedata, $filetype = 'application/octet-stream', $name = '')
    {
        array_push($this->attachments, array('file' => $filedata, 'type' => $filetype, 'name' => $name, 'isfile' => false));
    }

    /**
     * Add an attachment from a file.
     * @param $filepath string The path to the file to attach
     * @param $filetype string The mime type of the file. defaults to 'application/octet-stream'
     * @param $name string The name of the attachment. defaults to ''.
     */
    function addAttachment($filepath, $filetype = 'application/octet-stream', $name = '')
    {
        array_push($this->attachments, array('file' => $filepath, 'type' => $filetype, 'name' => $name, 'isfile' => true));
    }

    /**
     * Send the email out. 
     * @return err 0 if no error; otherwise a PEAR_Error object with the error.
     */
    function send()
    {
        // sanity check required pieces
        if (empty($this->to_email)) {
            return PEAR::raiseError('to email cannot be empty.');
        }
        if (empty($this->from_email)) {
            return PEAR::raiseError('from email cannot be empty.');
        }
        
        // set up all headers
        $headers = array();
        $recipients = array();

        // TO field
        if ($this->to_name) {
            $to = "\"{$this->to_name}\" <{$this->to_email}>";
        } else {
            $to = $this->to_email;
        }
        $headers["To"] = $to;
        $recipients[] = $this->to_email;
        
        // CC people
        if (count($this->cc_list)) {
            $headers["Cc"] = join(", ", $this->cc_list);
            $recipients = array_merge($recipients, $this->cc_list);
        }
        // BCC people
        // BCC sender?
        if ($this->bcc_sender) {
            $this->bcc_list[] = $this->from_email;
        }
        if (count($this->bcc_list)) {
            $recipients = array_merge($recipients, $this->bcc_list);
        }

        // FROM
        if ($this->from_name != '') {
            $headers["From"] = "\"{$this->from_name}\" <{$this->from_email}>";
        } else {
            $headers["From"] = "{$this->from_email}";
        }

        // REPLY-TO
        if ($this->reply_to_email != '') {
            $headers["Reply-To"] = "{$this->reply_to_email}";
        }
        
        // PRIORITY
        $headers["X-Priority"] = "{$this->priority}";

        // SUBJECT
        $headers["Subject"] = $this->subject;

        // ENVELOPE ADDRESS - make things bounce to the right place
        $headers["Return-Path"] = $headers["Errors-To"] = "{$this->from_email}";
        $headers["Errors-To"] = "{$this->from_email}";

        // Add an x-mailer; reduces chance of being flagged as SPAM
        $headers["X-Mailer"] = "ShowCase Web Framework Mailer";

        // use PEAR::Mail_Mime to format message
        $mm = new Mail_Mime();

        $mm->setTXTBody($this->raw_message_text);
        if ($this->raw_message_html) {
            $mm->setHTMLBody($this->raw_message_html);
        }

        // add attachments
        foreach ($this->attachments as $attachment_info) {
            $err = $mm->addAttachment($attachment_info['file'], $attachment_info['type'], $attachment_info['name'], $attachment_info['isfile']);
            if (PEAR::isError($err)) {
                return $err;
            }
        }

        $mm_body = $mm->get();
        $mm_headers = $mm->headers($headers);

        if (is_null($this->pear_mailer_config)) {
            $mailer =& Mail::factory($this->pear_mailer_driver);
        } else {
            $mailer =& Mail::factory($this->pear_mailer_driver, $this->pear_mailer_config);
        }
        if (PEAR::isError($mailer)) {
            return $mailer;
        }

        $err = $mailer->send($recipients, $mm_headers, $mm_body);

        if (PEAR::isError($err)) {
            return $err;
        }
        return 0;
    }

    /**
     * Verify an email addresses well-formedness.
     * @static
     * @param $addr string Email address to check.
     * @return $ok boolean True if email is valid, false otherwise.
     */
    function emailOK($addr)
    {
        return (preg_match("/[A-z0-9._-]+@[A-z0-9-]+\.[A-z0-9-\.]*[A-z]+$/", $addr) == 1);
    }
}

?>
