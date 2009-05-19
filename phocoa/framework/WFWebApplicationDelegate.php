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
 * @todo Do we need sessionDidStartForFirstTime()?
 */
class WFWebApplicationDelegate
{
    /**
     *  Autoload callback.
     *
     *  @param string The class name needing loading.
     *  @return boolean TRUE if the class loading request was handled, FALSE otherwise.
     */
    function autoload($className)

    /**
     * Retrieve the default invocation path for the application.
     *
     * By specifying a complete path to a module/page, you can have mydomain.com/ actually display a page without redirecting.
     *
     * @return string The default invocation path for this web application.
     */
    function defaultInvocationPath() {}

    /**
     * Retrieve the default module for the application.
     *
     * @return string The default module for this web application. You may specify either a module name (examplemodule) or a path to a module (path/to/examplemodule).
     * @deprecated
     * @see WFWebApplicationDelegate::defaultInvocationPath
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
     *  Called just before the session is started.
     *
     *  This gives applications a chance to twiddle php session config before starting the session.
     */
    function sessionWillStart() {}

    /**
     *  Called just after the session is started.
     *
     *  This gives applications a chance to set up session info.
     */
    function sessionDidStart() {}

    /**
      * A callback function that your application can use to set up application config.
      *
      * This function is called from the WFWebApplication constructor... life cycle currently goes:
      *
      * - sessionWillStart
      * - initialize
      * - sessionDidStart
      * 
      * If you need a hook to do something BEFORE the session is initialized, do it from your delegate's constructor. Remember, though, you're constructor is called before autoload() works...
      *
      * An example of something to do during initialize() is to set up the {@link WFAuthorizationDelegate}, and bootstrap your DB connection (ie Propel::init()).
      */
    function initialize() {}

    /**
     *  A callback function so that the application can handle uncaught exceptions (ie log to database, email, etc)
     *
     *  @param object Exception
     *  @return boolean TRUE if the exception was handled and stop further processing, FALSE otherwise.
     */
    function handleUncaughtException($e) {}

    /**
     * A callback function to allow the application to use a custom {@link WFAuthorizationInfo} subclass.
     *
     * @return string The class name to use as the {@link WFAuthorizationManager::$authorizationInfo}. Defaults to {@link WFAuthorizationInfo}.
     */
    function authorizationInfoClass() {}
}
