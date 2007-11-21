<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package UI
 * @subpackage Widgets
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/**
 * A YAHOO Panel widget for our framework.
 * 
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * 
 * <b>Optional:</b><br>
 */
class WFYAHOO_widget_Dialog extends WFYAHOO_widget_Panel
{
    /**
     * @var string The method used to submit the form: one of "async", "form", or "none".
     */
    protected $postmethod;
    /**
     * @var array An array of button instances to add to the bottom of the dialog box.
     */
    protected $buttons;
    /**
     * @var string The name of a Javascript function to call as the "success" callback for the form submission. Note that "success" means only that a HTTP 200 response was received, and does not indicate "success" of any particular logic.
     */
    protected $callbackSuccess;
    /**
     * @var string The name of a Javascript function to call as the "failure" callback for the form submission. Note that "failure" is a *communication* failure, not a "logical" failure in your application.
     */
    protected $callbackFailure;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->containerClass = 'Dialog';
        $this->postmethod = 'async';
        $this->buttons = array();
        $this->callbackSuccess = NULL;
        $this->callbackFailure = NULL;

        $this->yuiloader()->yuiRequire("connection");
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            'postmethod' => array('async', 'form', 'none'),
            ));
    }

    function setPostmethod($m)
    {
        $this->postmethod = $m;
    }

    function addButton($text, $handler, $isDefault = false)
    {
        $newButton['text'] = $text;
        $newButton['handler'] = $handler;
        $newButton['isDefault'] = $isDefault;
        $this->buttons[] = $newButton;
    }

    function setCallbackSuccess($jsFunc)
    {
        $this->callbackSuccess = $jsFunc;
    }
    function setCallbackFailure($jsFunc)
    {
        $this->callbackFailure = $jsFunc;
    }

    function render($blockContent = NULL)
    {
        if ($this->hidden)
        {
            return NULL;
        }
        else
        {
            // set up basic HTML
            $html = parent::render($blockContent);
            $buttonsJS = '';
            if ($this->buttons)
            {
                $buttonsJS = '[';
                foreach ($this->buttons as $button) {
                    $buttonsJS .= ' { text:"' . $button['text'] . '", handler:' . $button['handler'] . ', isDefault:' . ($button['isDefault'] ? 'true' : 'false') . ' }, ';
                }
                $buttonsJS .= ']';
            }
            return $html;
        }
    }

    function initJS($blockContent)
    {
        $script .= parent::initJS($blockContent);
        $script .= "
PHOCOA.namespace('widgets.{$this->id}.Dialog');
PHOCOA.widgets.{$this->id}.Dialog.queueProps = function(o) {
    PHOCOA.widgets.{$this->id}.Panel.queueProps(o); // queue parent props
    // alert('id={$this->id}: queue Dialog props');
    // queue Dialog props here
    " . ($buttonsJS ? "o.cfg.queueProperty(\"buttons\", {$buttonsJS});" : NULL) . "
}
PHOCOA.widgets.{$this->id}.Dialog.init = function() {
    PHOCOA.widgets.{$this->id}.Panel.init();  // init parent
    var dialog = PHOCOA.runtime.getObject('{$this->id}');
    dialog.cfg.setProperty('postmethod', '{$this->postmethod}');
    " . ($this->callbackSuccess ? "dialog.callback.success = {$this->callbackSuccess};" : NULL) . "
    " . ($this->callbackFailure ? "dialog.callback.failure = {$this->callbackFailure};" : NULL) . "
}
" .
( (get_class($this) == 'WFYAHOO_widget_Dialog') ? "PHOCOA.widgets.{$this->id}.init = function() { PHOCOA.widgets.{$this->id}.Dialog.init(); };" : NULL );
        return $script;
    }

    function canPushValueBinding() { return false; }
}

?>
