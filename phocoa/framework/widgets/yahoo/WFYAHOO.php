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
 * A YAHOO base class for our framework.
 * 
 * This abstract class provides some base features for all Yahoo! YUI classes such as core js/css includes, etc.
 *
 * NOTE: Core js/css include capabilities have been moved to the WFView base class since YUI is now "bundled" with PHOCOA and other PHOCOA widgets rely on YUI.
 *
 * @todo Also need to decide about phocoa.js and prototype.js; should these be included in the skin, or by the widgets that need them (I think this is tricky b/c then those widgets can't be added via AJAX since these base js files won't exist), or by modules that know they are using them?
 */
abstract class WFYAHOO extends WFWidget
{
    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);

        // all WFYAHOO subclasses need this.
        $this->importJS("{$this->yuiPath}/yahoo/yahoo.js");
    }

    function canPushValueBinding() { return false; }
}

?>
