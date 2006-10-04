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
 * A Paginator sort link widget. 
 *
 * Creates a link along the lines of:
 *
 * Price (+/-)
 *
 * Where +/- is a graphic indicating ascending or descending sort. The text will be BOLD if the data is currently sorted by this key.
 *
 * The WFPaginatorSortLink supports only single-key sorting. Multi-key userland sorting must be done with another method.
 *
 * When the user click on the sort link, the page will refresh using the new sort order. If the user click on the same link that is the current sort, the sort
 * will now be in the reverse direction.
 *
 * Note that when a sort link is clicked, the paginator will reset to the first page since the current page is meaningless once the sort changes.
 *
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * - {@link WFWidget::$value value} The value is the {@link WFPaginator::setSortOptions() sort key} that this item represents (setup without the +/-).
 * - {@link WFPaginatorPageInfo::$paginator Paginator}
 * 
 * <b>Optional:</b><br>
 * None.
 *
 * @todo Make sure that the pagination links work properly when this widget is in a composited view. Not sure how the params will work from __construct etc... 
 */
class WFPaginatorSortLink extends WFWidget
{
    /**
     * @var object WFPaginator The paginator object that we will draw navigation for.
     */
    protected $paginator;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->paginator = NULL;
    }

    function setPaginator($p)
    {
        if (!($p instanceof WFPaginator)) throw( new Exception("Argument must be a WFPaginator.") );
        $this->paginator = $p;
    }

    function render($blockContent = NULL)
    {
        if (!$this->paginator) throw( new Exception("No paginator assigned.") );

        $imgSrc = $this->getWidgetWWWDir();
        $sortIndicator = NULL;
        $linkKey = "+{$this->value}";
        $sortKeys = $this->paginator->sortKeys();
        // if the paginator is currently using our sort key, or the reverse of our sort key, then we need to show a "toggle sort dir" link. Otherwise, just show the toggle ascending link.
        if (in_array("+{$this->value}", $sortKeys))
        {
            $sortIndicator = "+";
            $sortIndicator = "<img src=\"{$imgSrc}/arrow-up.gif\" width=\"11\" height=\"6\" border=\"0\" />";
            $linkKey = "-{$this->value}";
        }
        else if (in_array("-{$this->value}", $sortKeys))
        {
            $sortIndicator = "-";
            $sortIndicator = "<img src=\"{$imgSrc}/arrow-dn.gif\" width=\"11\" height=\"6\" border=\"0\" />";
            $linkKey = "+{$this->value}";
        }

        $sortOptions = $this->paginator->sortOptions();

        if ($this->paginator->mode() == WFPaginator::MODE_URL)
        {
            $linkURL = $this->paginator->urlForPaginatorState($this->page, $this->paginator->paginatorState(NULL, NULL, array($linkKey)));
            $output = "<a href=\"{$linkURL}\">{$sortOptions[$linkKey]}</a> <a href=\"{$linkURL}\">{$sortIndicator}</a>";
            if ($sortIndicator)
            {
                return "<b>$output</b>";
            }
            return $output;
        }
        else
        {
            $linkURL = $this->paginator->jsForState($this->paginator->paginatorState(NULL, NULL, array($linkKey)));
            $output = "<a href=\"#\" onClick=\"{$linkURL}\">{$sortOptions[$linkKey]}</a> <a href=\"#\" onClick=\"{$linkURL}\">{$sortIndicator}</a>";
            if ($sortIndicator)
            {
                return "<b>$output</b>";
            }
            return $output;
        }
    }

    function canPushValueBinding() { return false; }
}

?>
