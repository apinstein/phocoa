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
 * <b>Required:</b><br>
 * - {@link WFPaginatorNavigation::$paginator Paginator}
 * 
 * <b>Optional:</b><br>
 * - {@link WFPaginatorNavigation::$mode Mode}
 * - {@link WFPaginatorNavigation::$maxJumpPagesToShow maxJumpPagesToShow}
 */
class WFPaginatorNavigation extends WFWidget
{
    /**
     * @const Make the paginator use URL mode, which will produce pagination through standard links.
     */
    const MODE_URL = 1;
    /**
     * @const Make the paginator use FORM mode, which will produce pagination through javascript that manipulates a form's data and then submits sthe form.
     */
    const MODE_FORM = 2;

    /**
     * @var integer The mode of the paginator navigation control. Either WFPaginatorNavigation::MODE_URL or WFPaginatorNavigation::MODE_FORM. Default is MODE_URL.
     */
    protected $mode;
    /**
     * @var object WFPaginator The paginator object that we will draw navigation for.
     */
    protected $paginator;
    /**
     * @var string The base URL of the link to use in MODE_URL.
     */
    protected $baseURL;
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
        $this->mode = WFPaginatorNavigation::MODE_URL;
        $this->paginator = NULL;
        $this->maxJumpPagesToShow = 10;

        if ($this->page->module()->invocation()->targetRootModule() and !$this->page->module()->invocation()->isRootInvocation())
        {
            $this->baseURL = WWW_ROOT . '/' . $this->page->module()->invocation()->rootInvocation()->invocationPath();
        }
        else
        {
            $this->baseURL = WWW_ROOT . '/' . $this->page->module()->invocation()->modulePath() . '/' . $this->page->pageName();
        }
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
            return "1 " . $this->paginator->itemPhrase($count);
        }
        else
        {
            return "$count " . $this->paginator->itemPhrase($count);
        }
    }

    function render($blockContent = NULL)
    {
        if (!$this->paginator) throw( new Exception("No paginator assigned.") );

        $output = '';

        if ($this->mode == WFPaginatorNavigation::MODE_URL)
        {
            if ($this->paginator->prevPage())
            {
                $output .= "<a href=\"" . $this->baseURL . "/" . $this->paginator->paginatorState($this->paginator->prevPage()) . "\">&lt;&lt; Previous " . $this->itemsPhrase($this->paginator->pageSize()) . "</a>";
            }
            if ($this->paginator->pageCount() > 1 and $this->maxJumpPagesToShow != 0)
            {
                $firstJumpPage = max(1, $this->paginator->currentPage() - (floor($this->maxJumpPagesToShow / 2)));
                $lastJumpPage = min($firstJumpPage + $this->maxJumpPagesToShow, $this->paginator->pageCount());
                $output .= " [ Jump to: ";
                if ($firstJumpPage != 1)
                {
                    $output .= "<a href=\"{$this->baseURL}/" . $this->paginator->paginatorState(1) . "\">First</a> ...";
                }
                for ($p = $firstJumpPage; $p <= $lastJumpPage; $p++)
                {
                    if ($p == $this->paginator->currentPage())
                    {
                        $output .= " $p ";
                    }
                    else
                    {
                        $output .= " <a href=\"" . $this->baseURL . "/" . $this->paginator->paginatorState($p) . "\">$p</a>";
                    }
                }
                if ($lastJumpPage != $this->paginator->pageCount())
                {
                    $output .= "... <a href=\"{$this->baseURL}/" . $this->paginator->paginatorState($this->paginator->pageCount()) . "\">Last</a>";
                }
                $output .= " ] ";
            }
            else if ($this->paginator->prevPage() and $this->paginator->nextPage())
            {
                $output .= " | ";
            }
            if ($this->paginator->nextPage())
            {
                $output .= "<a href=\"" . $this->baseURL . "/" . $this->paginator->paginatorState($this->paginator->nextPage()) . "\">Next " . $this->itemsPhrase( min($this->paginator->pageSize(), ($this->paginator->itemCount() - $this->paginator->endItem())) ) . " &gt;&gt;</a>";
            }
        }
        else
        {
            throw( new Exception("MODE_FORM is not yet implemented.") );
        }

        return $output;
    }

    function canPushValueBinding() { return false; }
}

?>
