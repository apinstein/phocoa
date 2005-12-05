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
 * A TextField widget for our framework.
 */
class WFTextArea extends WFWidget
{
    protected $cols;
    protected $rows;
    /**
     * @var boolean TRUE if the widget is enabled, false otherwise.
     */
    protected $enabled;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->cols = 40;
        $this->rows = 10;
        $this->enabled = true;
    }

    function restoreState()
    {
        //  must call super
        parent::restoreState();

        if (isset($_REQUEST[$this->name]))
        {
            $this->setValue($_REQUEST[$this->name]);
        }
    }

    function render($blockContent = NULL)
    {
        return '<textarea name="' . $this->name() . '"' .
            ($this->cols ? ' cols="' . $this->cols . '" ' : '') .
            ($this->rows ? ' rows="' . $this->rows . '" ' : '') .
            ($this->valueForKey('enabled') ? '' : ' disabled readonly ') .
            '>' . $this->value . '</textarea>';
    }

    function setupExposedBindings()
    {
        $myBindings = parent::setupExposedBindings();
        $myBindings[] = new WFBindingSetup('rows', 'The # of rows in the text area (in HTML).');
        $myBindings[] = new WFBindingSetup('columns', 'The # of columns in the text area (in HTML).');
        return $myBindings;
    }

    function canPushValueBinding() { return 'true'; }
}

?>
