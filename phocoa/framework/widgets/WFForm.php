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
  * A wrapper for HTML Form Elements.
  */
class WFForm extends WFWidget
{
    /**
     * @const Use the GET HTTP method for submitting the form.
     */
    const METHOD_GET = 'get';
    /**
     * @const Use the POST HTTP method for submitting the form.
     */
    const METHOD_POST = 'post';

    /**
     * @var string The action attribute of the HTML form. By default, the current URI.
     */
    protected $action;
    /**
     * @var string The submit method to use.
     * @see METHOD_POST, METHOD_GET
     */
    protected $method;

    /**
      * Constructor.
      *
      * Sets up the smarty object for this module.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);

        if ($this->page->module()->invocation()->targetRootModule() and !$this->page->module()->invocation()->isRootInvocation())
        {
            $this->action = WWW_ROOT . '/' . $this->page->module()->invocation()->rootInvocation()->invocationPath();
        }
        else
        {
            $this->action = WWW_ROOT . '/' . $this->page->module()->invocation()->modulePath() . '/' . $this->page->pageName();
        }

        $this->method = WFForm::METHOD_POST;
    }

    function setAction($action)
    {
        $this->action = $action;
    }
    
    function setMethod($method)
    {
        $this->method = $method;
    }

    function render($blockContent = NULL)
    {
        $encType = '';
        if (strtolower($this->method) == WFForm::METHOD_POST)
        {
            $encType = 'enctype="multipart/form-data"';
        }
        return "\n" . '<form id="' . $this->id . '" action="' . $this->action . '" method="' . $this->method . '" ' . $encType . '>' .
               "\n" . '<input type="hidden" name="__modulePath" value="' . $this->page->module()->invocation()->modulePath() . '/' . $this->page->pageName() . '" />' .
               //"\n" . '<input type="hidden" name="__currentModule" value="' . $this->page->module()->invocation()->modulePath() . '" />' .
               //"\n" . '<input type="hidden" name="__currentPage" value="' . $this->page->pageName() . '" />' .
               "\n" . '<input type="hidden" name="__formName" value="' . $this->id . '" />' .
               "\n" . $blockContent .
               "\n</form>\n" .
               "\n";
    }

    function canPushValueBinding() { return false; }
}

?>
