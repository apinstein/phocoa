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
 * A Label widget for our framework.
 */
class WFLabel extends WFWidget
{
    /**
     * @var integer Number of chars after which the string should be ellipsised. Default UNLIMITED.
     */
	protected $ellipsisAfterChars;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->value = NULL;
        $this->ellipsisAfterChars = NULL;
    }

    function render($blockContent = NULL)
    {
        if ($this->hidden)
        {
            return NULL;
        }
        else
        {
        	if ( $this->ellipsisAfterChars == NULL )
        	{
	            return $this->value;
			}
			else
			{
				if ( strlen( $this->value ) >= $this->ellipsisAfterChars )
				{
					return substr($this->value, 0, $this->ellipsisAfterChars) . '...';
				}
				else
				{
					return $this->value;
				}
			}
        }
    }


    function canPushValueBinding() { return false; }
}

?>
