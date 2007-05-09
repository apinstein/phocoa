<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package WebApplication
 * @subpackage Session
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */
if (php_sapi_name() !== 'cli')
{
    session_start();
}

// This class is for shit right now; for now just let people access session manually.
///**
// * The WFMainSession class is the base session handler.
// *
// * The Main session contains the session data shared for the entire application.
// */
//class WFMainSession extends WFObject
//{
//    const REMEMBER_ME_COOKIE_NAME = 'remember_me_info';
//    const MAJOR_VERSION = '1';
//
//    // session keys used
//    const KEY_VERSION = 'version';
//
//    protected $delegate;
//    
//    function __construct()
//    {
//        $this->delegate = NULL;
//    }
//
//    /**
//     * Get a reference to the shared WFSession object.
//     * @static
//     * @return object The WFSession object.
//     */
//    function sharedSession()
//    {
//        static $singleton = NULL;
//        if (!$singleton) {
//            $singleton = new WFSession();
//        }
//        return $singleton;
//    }
//
//    /**
//      * Set the {@link WFSessionDelegate} for the session.
//      * @param object The object implementing WFSessionDelegate to be used as the session delegate.
//      */
//    function setDelegate($delegate)
//    {
//        $this->delegate = $delegate;
//    }
//
//    /**
//      * Load the session info.
//      */
//    function loadSession()
//    {
//        // set cookie domain, if a non-default one is specified.
//        if ($this->cookieDomain()) {
//            ini_set("session.cookie_domain", $this->cookie_domain());
//        }
//        session_start();
//
//        if (empty($_SESSION[WFSession::KEY_VERSION]) or $_SESSION[WFSession::KEY_VERSION] < $this->version()) {
//            $this->initializePHPSession();
//
//            // try to "remember" user
//            if (isset($_COOKIE[WFSession::REMEMBER_ME_COOKIE_NAME])) {
//                // let delegate restore state, if available
//                if (is_object($this->delegate) && method_exists($this->delegate, 'restoreRememberedState')) {
//                    $this->delegate->restoreRememberedState(unserialize($_COOKIE[WFSession:REMEMBER_ME_COOKIE_NAME]));
//                }
//            }
//        }
//    }
//
//    function setSessionData($name, $value)
//    {
//    }
//
//    function sessionData($name)
//    {
//    }
//
//    /**
//      * Initialize the PHP session to the default values.
//      */
//    function initializePHPSession()
//    {
//        $_SESSION[WFSession::KEY_VERSION] = $this->version();
//    }
//
//    function cookieDomain()
//    {
//        if (is_object($this->delegate) && method_exists($this->delegate, 'cookieDomain')) {
//            return $this->delegate->cookieDomain();
//        }
//        return NULL;
//    }
//
//    /**
//      * Get the version of this session.
//      * The version is a combo of the framework version and the application's specific version.
//      * @return float The version in MAJOR.MINOR format.
//      */
//    function version()
//    {
//        $minorVersion = 1;  // default minor version
//        if (is_object($this->delegate) && method_exists($this->delegate, 'version')) {
//            $minorVersion = $this->delegate->version();
//        }
//        return WFSession::MAJOR_VERSION . '.' . $minorVersion;
//    }
//
//    /**
//      * Set the remember me information for the current user.
//      * @param assoc_array A set of name/value pairs (NO OBJECTS!) to store in the remember me info for the user.
//      * @param int The lifetime of the cookie, in seconds, from now. OPTIONAL; DEFAULT = 5 years.
//      */
//    function enableRememberedState($info, $lifetime = 155520000)
//    {
//        $rememberMeStr = serialize($info);
//        $expiry = time() + $lifetime;
//        setcookie(WFSession::REMEMBER_ME_COOKIE_NAME, $rememberMeStr, $expiry, WWW_ROOT, $this->cookieDomain());
//    }
//
//    function disableRememberedState()
//    {
//        setcookie(WFSession::REMEMBER_ME_COOKIE_NAME, '', time() - 3600, WWW_ROOT, $this->cookieDomain());
//    }
//}
//
//class WFSessionDelegate
//{
//    /**
//      * Set the cookie domain to be used by the session infrastructure.
//      * Optional delegate method.
//      * @return string The cookie domain to use for the web application.
//      */
//    function cookieDomain() {}
//
//    /**
//      * Get the version of your application's session information.
//      * If the version of the current session is older than the version you return, the session will be reset.
//      * @return string The cookie domain to use for the web application.
//      */
//    function version() {}
//
//    /**
//      * If the visitor's session was restored, this delegate method will be called with the saved info.
//      * Use this opportunity to restore the state based on the passed info.
//      * @param assoc_array The name/value pairs saved.
//      */
//    function restoreRememberedState($info) {}
//}
