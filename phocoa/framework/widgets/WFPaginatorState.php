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
 * Includes
 */
require_once('framework/widgets/WFWidget.php');

/**
 * A special form field used in conjuction with WFPagination in MODE_FORM.
 *
 * This field works in conjunction with the WFPaginator to allow navigation on sort changes of the paginator in MODE_FORM.
 *
 * This field automatically preserves the sortKeys and pageSize attributes when re-submitting the form de-novo so that the user doens't have to keep re-sorting to his preferred state.
 *
 * <b>Required:</b><br>
 * - {@link WFPaginatorNavigation::$paginator Paginator}
 * 
 * <b>Optional:</b><br>
 * None.
 */
class WFPaginatorState extends WFWidget
{
    /**
     * @var object WFPaginator The paginator object that we will store state for.
     */
    protected $paginator;
    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
    }

    function restoreState()
    {
        //  must call super
        parent::restoreState();

        if (isset($_REQUEST[$this->name]))
        {
            $this->value = $_REQUEST[$this->name];
        }
    }

    function render($blockContent = NULL)
    {
        // When restoring the value, only put back the SORT KEYS and PAGE SIZE; the page num should be RESET.
        return '<input type="hidden" id="' . $this->id . '" name="' . $this->name . '" value="' . $this->paginator->paginatorState(WFPaginator::PAGINATOR_FIRST_PAGE) . '" />';
    }

    function canPushValueBinding() { return false; }
}

?>
