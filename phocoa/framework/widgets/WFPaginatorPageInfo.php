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
require_once('framework/widgets/WFView.php');

/**
 * A Paginator Page info widget.
 *
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * - {@link WFPaginatorPageInfo::$paginator Paginator}
 * 
 * <b>Optional:</b><br>
 * None.
 */
class WFPaginatorPageInfo extends WFView
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

    function render($blockContent = NULL)
    {
        if (!$this->paginator) throw( new Exception("No paginator assigned.") );
        return 'Showing ' . $this->paginator->itemPhrase($this->paginator->itemCount()) . ' ' . $this->paginator->startItem() . ' - ' . $this->paginator->endItem() .' of ' . $this->paginator->itemCount() . '.';
    }

    function canPushValueBinding() { return false; }
}

?>
