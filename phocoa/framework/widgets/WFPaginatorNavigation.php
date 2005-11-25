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
 * A Paginator Navigation widget for our framework.
 *
 * Creates a clickable navigation element for the paged data. Something along the lines of:
 *
 * Previous 10 items [ Jump to Page 1 2 3 4 5 ] Next 10 Items
 *
 * <b>Required:</b><br>
 * - {@link WFPaginatorNavigation::$paginator Paginator}
 * 
 * <b>Optional:</b><br>
 * - {@link WFPaginatorNavigation::$maxJumpPagesToShow}
 *
 * @todo Make sure that the pagination links work properly when this widget is in a composited view. Not sure how the params will work from __construct etc... 
 */
class WFPaginatorNavigation extends WFWidget
{
    /**
     * @var object WFPaginator The paginator object that we will draw navigation for.
     */
    protected $paginator;
    /**
     * @var integer The maximum number of direct-page-links to show in the "Jump To" section. Default is 10. Set to 0 to disable "Jump To" section.
     */
    protected $maxJumpPagesToShow;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->paginator = NULL;
        $this->maxJumpPagesToShow = 10;
    }

    /**
     *  Get the phrase for the links in format "N item(s)"
     *
     *  @param integer Count of items.
     *  @return string
     */
    private function itemsPhrase($count)
    {
        if ($count == 1)
        {
            return $this->paginator->itemPhrase($count);
        }
        else
        {
            return "$count " . $this->paginator->itemPhrase($count);
        }
    }

    function render($blockContent = NULL)
    {
        if (!$this->paginator) throw( new Exception("No paginator assigned.") );
        if ($this->paginator->itemCount() == 0) return NULL;
        if ($this->paginator->pageSize() == WFPaginator::PAGINATOR_PAGESIZE_ALL) return NULL;

        $output = '';

        if ($this->paginator->mode() == WFPaginator::MODE_URL)
        {
            if ($this->paginator->prevPage())
            {
                $output .= "<a href=\"" . $this->paginator->urlForPaginatorState($this->page, $this->paginator->paginatorState($this->paginator->prevPage())) . "\">&lt;&lt; Previous " . $this->itemsPhrase($this->paginator->pageSize()) . "</a>";
            }
            if ($this->paginator->pageCount() > 1 and $this->maxJumpPagesToShow != 0)
            {
                $firstJumpPage = max(1, $this->paginator->currentPage() - (floor($this->maxJumpPagesToShow / 2)));
                $lastJumpPage = min($firstJumpPage + $this->maxJumpPagesToShow, $this->paginator->pageCount());
                $output .= " [ Jump to: ";
                if ($firstJumpPage != 1)
                {
                    $output .= "<a href=\"" . $this->paginator->urlForPaginatorState($this->page, $this->paginator->paginatorState(1)) . "\">First</a> ...";
                }
                for ($p = $firstJumpPage; $p <= $lastJumpPage; $p++)
                {
                    if ($p == $this->paginator->currentPage())
                    {
                        $output .= " $p ";
                    }
                    else
                    {
                        $output .= " <a href=\"" . $this->paginator->urlForPaginatorState($this->page, $this->paginator->paginatorState($p)) . "\">$p</a>";
                    }
                }
                if ($lastJumpPage != $this->paginator->pageCount())
                {
                    $output .= "... <a href=\"" . $this->paginator->urlForPaginatorState($this->page, $this->paginator->paginatorState($this->paginator->pageCount())) . "\">Last</a>";
                }
                $output .= " ] ";
            }
            else if ($this->paginator->prevPage() and $this->paginator->nextPage())
            {
                $output .= " | ";
            }
            if ($this->paginator->nextPage())
            {
                $output .= "<a href=\"" . $this->paginator->urlForPaginatorState($this->page, $this->paginator->paginatorState($this->paginator->nextPage())) . "\">Next " . $this->itemsPhrase( min($this->paginator->pageSize(), ($this->paginator->itemCount() - $this->paginator->endItem())) ) . " &gt;&gt;</a>";
            }
        }
        else
        {
            // JS to edit form, then click submit button
            if ($this->paginator->prevPage())
            {
                $output .= "<a href=\"#\" onClick=\"" . $this->paginator->jsForState($this->paginator->paginatorState($this->paginator->prevPage())) . "\">&lt;&lt; Previous " . $this->itemsPhrase($this->paginator->pageSize()) . "</a>";
            }
            if ($this->paginator->pageCount() > 1 and $this->maxJumpPagesToShow != 0)
            {
                $firstJumpPage = max(1, $this->paginator->currentPage() - (floor($this->maxJumpPagesToShow / 2)));
                $lastJumpPage = min($firstJumpPage + $this->maxJumpPagesToShow, $this->paginator->pageCount());
                $output .= " [ Jump to: ";
                if ($firstJumpPage != 1)
                {
                    $output .= "<a href=\"#\" onClick=\"" . $this->paginator->jsForState($this->paginator->paginatorState(1)) . "\">First</a> ...";
                }
                for ($p = $firstJumpPage; $p <= $lastJumpPage; $p++)
                {
                    if ($p == $this->paginator->currentPage())
                    {
                        $output .= " $p ";
                    }
                    else
                    {
                        $output .= " <a href=\"#\" onClick=\"" . $this->paginator->jsForState($this->paginator->paginatorState($p)) . "\">$p</a>";
                    }
                }
                if ($lastJumpPage != $this->paginator->pageCount())
                {
                    $output .= "... <a href=\"#\" onClick=\"" . $this->paginator->jsForState($this->paginator->paginatorState($this->paginator->pageCount())) . "\">Last</a>";
                }
                $output .= " ] ";
            }
            else if ($this->paginator->prevPage() and $this->paginator->nextPage())
            {
                $output .= " | ";
            }
            if ($this->paginator->nextPage())
            {
                $output .= "<a href=\"#\" onClick=\"" . $this->paginator->jsForState($this->paginator->paginatorState($this->paginator->nextPage())) . "\">Next " . $this->itemsPhrase( min($this->paginator->pageSize(), ($this->paginator->itemCount() - $this->paginator->endItem())) ) . " &gt;&gt;</a>";
            }
        }

        return $output;
    }

    function canPushValueBinding() { return false; }
}

?>
