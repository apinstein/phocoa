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
 * A Password widget for our framework.
 *
 * In cases where you're letting a user *set* a password, you will want a 2nd "confirm password" field. To accomplish this,
 * add a child WFPassword widget and the main widget will automatically verify that they match. This allows you to use normal
 * phocoa widget placement to decide where to insert the "confirm password" widget.
 */
class WFPassword extends WFWidget
{
    /**
     *  @var integer The max length of the text field
     */
    protected $maxLength;
    /**
     *  @var integer The size of the text field
     */
    protected $size;
    /**
     *  @var boolean Whether or not to "preserve" the input in the UI if the form is re-displayed. TRUE is useful for sign-up forms to prevent people from having to re-enter the PW if there's nothing wrong with that data.
     */
    protected $preserveInput;
    /**
     * @var string The ID of a collaborator WFPassword widget to be used to "confirm" the passwords entered match. DEFAULT: NULL
     */
    private $confirmPasswordId;

    // errors
    const ERR_PASSWORDS_DO_NOT_MATCH = 1;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->maxLength = NULL;
        $this->size = NULL;
        $this->enabled = true;
        $this->preserveInput = false;
        $this->confirmPasswordId = NULL;
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            'maxLength',
            'size',
            'preserveInput'   => array(true, false),
            ));
    }

    function addChild(WFView $view)
    {
        if (!($view instanceof WFPassword)) throw new WFException("Only WFPassword child views are accepted.");
        if ($this->confirmPasswordId !== NULL) throw new WFException("WFPassword accepts only one child.");

        $this->confirmPasswordId = $view->id();
        return parent::addChild($view);
    }

    function restoreState()
    {
        //  must call super
        parent::restoreState();

        // look for the things in the form I need to restore state...
        if (isset($_REQUEST[$this->name]))
        {
            $this->value = $_REQUEST[$this->name];

            if ($this->value && $this->confirmPasswordId)
            {
                $confirmPasswordWidget = $this->page->outlet($this->confirmPasswordId);
                $confirmPasswordWidget->restoreState();
                $confirmValue = $confirmPasswordWidget->value();
                if ($confirmValue !== $this->value)
                {
                    $this->addError(new WFError("Passwords entered do not match.", self::ERR_PASSWORDS_DO_NOT_MATCH));
                }
            }
        }
    }

    function render($blockContent = NULL)
    {
        if ($this->hidden) return NULL;

        return '<input type="password" name="' . $this->valueForKey('name') . '" value="' . ($this->preserveInput ? $this->value : NULL) . '"' .
            ($this->valueForKey('size') ? ' size="' . $this->valueForKey('size') . '" ' : '') .
            ($this->valueForKey('maxLength') ? ' maxLength="' . $this->valueForKey('maxLength') . '" ' : '') .
            ($this->valueForKey('enabled') ? '' : ' disabled readonly ') .
            ($this->class ? ' class="' . $this->class . '"' : '') .
            '/>';
    }

    /********************* BINDINGS SETUP ************************/
    function setupExposedBindings()
    {
        $myBindings = parent::setupExposedBindings();
        $myBindings[] = new WFBindingSetup('maxLength', 'The maxLength of the password field (in HTML).');
        $myBindings[] = new WFBindingSetup('size', 'The size of the password field (in HTML).');
        return $myBindings;
    }

    function canPushValueBinding() { return true; }
}
