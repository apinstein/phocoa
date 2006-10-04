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
 * A Paginator sort select widget.
 *
 * Creates a select element with one choice for each possible sort option.
 *
 * The WFPaginatorSortSelect supports only single-key sorting. Multi-key userland sorting must be done with another method.
 *
 * When the user selects from the menu, the page will refresh using the new sort order.
 *
 * Note that when a sort option is selected, the paginator will reset to the first page since the current page is meaningless once the sort changes.
 *
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * - {@link WFWidget::$value value} The value is the sort key that this item represents (setup without the +/-).
 * - {@link WFPaginatorPageInfo::$paginator Paginator}
 * 
 * <b>Optional:</b><br>
 * None.
 *
 * @todo Make sure that the pagination links work properly when this widget is in a composited view. Not sure how the params will work from __construct etc... 
 */
class WFPaginatorSortSelect extends WFWidget
{
    /**
     * @var object WFPaginator The paginator object that we will draw navigation for.
     */
    protected $paginator;
    protected $loadOnselect;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->paginator = NULL;
        $this->loadOnSelect = true;
    }

    function setPaginator($p)
    {
        if (!($p instanceof WFPaginator)) throw( new Exception("Argument must be a WFPaginator.") );
        $this->paginator = $p;
    }

    function restoreState()
    {
        parent::restoreState();

    }

    function render($blockContent = NULL)
    {
        if (!$this->paginator) throw( new Exception("No paginator assigned.") );
        if ($this->paginator->itemCount() == 0) return NULL;

        $html = '';

        // determine selection
        $sortSelection = NULL;
        $sortKeys = $this->paginator->sortKeys();
        if (count($sortKeys) == 1)
        {
            $sortSelection = $sortKeys[0];
        }

        $sortOptions = $this->paginator->sortOptions();

        // add onSelect handler
        $libraryJS = '
            <script language="JavaScript">
            <!--
            function __phocoaWFPaginatorSortSelect_' . $this->id . '(select)
            {
                var index;
                var initialSelection = \'' . $this->value . '\';

                for(index=0; index<select.options.length; index++)
                    if(select.options[index].selected)
                    {
                        if(select.options[index].value != initialSelection)
                        {
                            ';
        if ($this->paginator->mode() == WFPaginator::MODE_URL)
        {
            $libraryJS .= '
                            newURL = select.options[index].value; 
                            window.location.href = newURL;
            ';
        }
        else
        {
            $libraryJS .= '
                            jsCode = select.options[index].value; 
                            eval(jsCode);
            ';
        }
        $libraryJS .= '
                        }
                        break;
                    }
            }
            -->
            </script>
            ';
        $selectJS = NULL;
        if ($this->loadOnSelect)
        {
            $selectJS = " onChange=\"__phocoaWFPaginatorSortSelect_{$this->id}(this);\" ";
            $html .= $libraryJS;
        }
        $html .= "<select {$selectJS} id=\"{$this->id}\">\n";
        foreach ($sortOptions as $opt => $label) {
            $selectedAttribute = NULL;
            if ($opt == $sortSelection)
            {
                $selectedAttribute = ' selected';
            }
            if ($this->loadOnSelect)
            {
                if ($this->paginator->mode() == WFPaginator::MODE_URL)
                {
                    $selectValue = $this->paginator->urlForPaginatorState($this->page, $this->paginator->paginatorState(0, NULL, array($opt)));
                    $selectValue = str_replace('+', '%2B', $selectValue);
                    $html .= '<option value="' . $selectValue . '"' . $selectedAttribute . '>' . $label . '</option>';
                }
                else
                {
                    throw( new Exception('untested... please test!'));
                    $html .= '<option value="' . $this->paginator->jsForState($this->paginator->paginatorState(0, NULL, array($opt))) . '"' . $selectedAttribute . '>' . $label . '</option>';
                }
            }
            else
            {
                $html .= '<option value="' . $opt . '"' . $selectedAttribute . '>' . $label . '</option>';
            }
        }
        $html .= "</select>\n";
        return $html;
    }

    function canPushValueBinding() { return false; }
}

?>
