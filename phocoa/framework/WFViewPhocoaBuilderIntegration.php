<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/** 
 * @package UI
 * @subpackage PHOCOA Builder
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/**
 * WFViewPhocoaBuilderIntegration interface (informal protocol) for widgets to adopt if they want to be supported in PHOCOA builder.
 */
interface WFViewPhocoaBuilderIntegration
{
    /**
     *  Get a list of the properties for the object that can be configured in PHOCOA builder.
     *
     *  Subclasses should call the parent and add the local entries to the array or array_merge the results before returning.
     *
     *  @return array A 2-d associative array ('property' => array('choice1','choice2'), 'proeprty2', 'property3')
     */
    public static function exposedProperties();

    /**
      * Set up all exposed bindings for this widget.
      *
      * The default implementation sets up bindings available for all WFWidgets. Subclasses must call super method.
      *
      * NOTE: This function is actually part of WFWidget, but repeated here for documentation purposes.
      *
      * @return array An array of {@link WFBindingSetup} objects.
      */
    public static function setupExposedBindings()
}
?>
