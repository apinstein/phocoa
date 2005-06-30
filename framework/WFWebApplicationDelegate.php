<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package WebApplication
 * @subpackage Delegate
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/** 
 * Delegate methods for WFWebApplication.
 *
 * This file is used only as a documentation stub.
 * Most of the delegate methods for WFWebApplication are for application-wide settings. HOWEVER, these settings should generally be
 * settings for the application logic itself, NOT deployment configuration that changes between deployment locations (ie DEV, STAGING, PRODUCTION).
 * Deployment configuration settings are in the {@link webapp.conf .conf files}.
 *
 */
class WFWebApplicationDelegate
{

    /**
     * Retrieve the default module for the application.
     *
     * @return string The default module for this web application. You may specify either a module name (examplemodule) or a path to a module (path/to/examplemodule).
     */
    function defaultModule() {}

    /**
     * Retreive the default skin delegate to use for the application.
     *
     * @see WFSkin, WFSkinDelegate
     *
     * @return string The name of the SkinDelegate to use.
     */
    function defaultSkinDelegate() {}

    /**
      * A callback function that your application can use to set up application config.
      *
      * This function is called just after the WFWebApplication is constructed.
      *
      * An example of something to do during initialize() is to set up the {@link WFAuthorizationDelegate}.
      */
    function initialize() {}
}

?>
