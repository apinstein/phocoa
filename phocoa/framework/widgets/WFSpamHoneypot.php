<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package UI
 * @subpackage Widgets
 * @copyright Copyright (c) 2010 Alan Pinstein. All Rights Reserved.
 * @author Alan Pinstein <apinstein@mac.com>
 */

/**
 * A Spam Honeypot widget for our framework.
 *
 * To use, simply add an instance to your form.
 * @todo Upgrade this to use info from the CSRF protection in the honeypotName so that it isn't easy for bots to hard-code around the honeypot field.
 */
class WFSpamHoneypot extends WFTextField
{
    protected $honeypotName = "__special";

    function restoreState()
    {
        //  must call super
        parent::restoreState();

        if (!empty($_REQUEST[$this->honeypotName]))
        {
            throw new WFRequestController_HTTPException("Server problem.", 400);
        }
    }

    function render($blockContent = NULL)
    {
        return '<input type="text" class="phocoaWFSpamHoneypot_input" name="' . $this->honeypotName . '" />';
    }

    function canPushValueBinding() { return false; }
}
