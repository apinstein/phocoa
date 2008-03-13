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
 * A special form field used in conjuction with WFPagination in MODE_FORM.
 *
 * This field works in conjunction with the WFPaginator to allow navigation on sort changes of the paginator in MODE_FORM.
 *
 * This field automatically preserves the sortKeys and pageSize attributes when re-submitting the form de-novo so that the user doens't have to keep re-sorting to his preferred state.
 *
 * Also, this state is smart enough so that if the user submits a form with a button OTHER than {@link WFPagination::setModeForm() the MODE_FORM submission button}, the paginator state
 * is *presevered* so that the action function for that button will have the appropriate data loaded.
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

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            'paginator',
            ));
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
        if (!($this->paginator instanceof WFPaginator)) throw( new WFException("No paginator assigned to WFPaginatorState " . $this->id) );
        if ($this->paginator->mode() == WFPaginator::MODE_FORM)
        {
            //$this->importJS(self::yuiPath() . "/yahoo-dom-event/yahoo-dom-event.js", 'YAHOO');
        }
        $html = parent::render($blockContent);
        // When restoring the value, only put back the SORT KEYS and PAGE SIZE; the page num should be RESET.
        $button = '<input type="hidden" id="' . $this->id . '" name="' . $this->name . '" value="' . $this->paginator->paginatorState() . '" />';
        $js = NULL;
        if ($this->paginator->mode() == WFPaginator::MODE_FORM)
        {
            // js function to set paginator to go to first page when the MODE_FORM submit button is pressed.
            $paginatorResetJSFunctionName = "__WFPaginatorState_gotoFirstPage_{$this->id}";
            // do not go to first page if the submit button was pressed by paginator MODE_FORM
            $paginatorModeFormSubmissionVarName = $this->paginator->jsPaginatorStateModeFormSubmissionVarName();
            $js = $this->jsStartHTML() . '
            var ' . $paginatorModeFormSubmissionVarName . ' = false;
            function ' . $this->paginator->jsPaginatorStateModeFormGoToStateFunctionName() . '(state)
            {
                ' . $this->paginator->jsPaginatorStateModeFormSubmissionVarName() . ' = true;
                document.getElementById("' . $this->id . '").value = state;
                document.getElementById("' . $this->paginator->submitID() . '").click();
            }
            function ' . $paginatorResetJSFunctionName . '()
            {
                if (' . $paginatorModeFormSubmissionVarName . ' == true) return;
                var submitID = \'' . $this->paginator->submitID() . '\';
                document.getElementById("' . $this->paginator->paginatorStateParameterID() . '").value = "' . $this->paginator->paginatorState(WFPaginator::PAGINATOR_FIRST_PAGE) . '";
            }';

            $loader = WFYAHOO_yuiloader::sharedYuiLoader();
            $loader->yuiRequire('event');
            $callback = 'function() { YAHOO.util.Event.addListener("' . $this->paginator->submitID() . '", "click", ' . $paginatorResetJSFunctionName . '); }';
            $js .= $loader->jsLoaderCode($callback);
            $js .= $this->jsEndHTML();
        }
        return $html . $js . $button;
    }

    function canPushValueBinding() { return false; }
}

?>
