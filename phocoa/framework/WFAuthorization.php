<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package framework-base
 * @subpackage Authorization
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/**
  * Informal delegate protocol for your web application to handle authentication.
  *
  * The WFAuthorizationManager will call your delegate methods to attempt logins.
  */
class WFAuthorizationDelegate extends WFObject
{
    /**
      * Provide the login authentication.
      *
      * Your WFAuthorizationDelegate can provide its own login capability. Maybe your app will authenticate against LDAP, a Database, etc.
      *
      * @param string The username to use for the authentication.
      * @param string The password to use for the authentication.
      * @return object WFAuthorizationInfo Return an WFAuthorizationInfo with any additional security profile. This of course can be a subclass. Return NULL if login failed.
      */
    function login($username, $password) {}

    /**
      * Provide the login authentication based on the "RememberMe" function which will store some kind of hash of the password.
      *
      * Your WFAuthorizationDelegate can provide its own login capability. Maybe your app will authenticate against LDAP, a Database, etc.
      *
      * @param string The username to use for the authentication.
      * @param string The password to use for the authentication.
      * @return object WFAuthorizationInfo Return an WFAuthorizationInfo with any additional security profile. This of course can be a subclass. Return NULL if login failed.
      */
    function loginWithHash($username, $passwordHash) {}
}

/**
  * The WFAuthorizationInfo object stores all access control information for the logged-in user.
  *
  * The base class provides the ability to tell if someone is logged in, if they logged in recently, and their userid. For many applications, this is all that's needed.
  *
  * For applications requiring more complicated access control, they should subclass WFAuthorizationInfo and provide further access control information and methods to query it.
  *
  * NOTE: The WFAuthorizationInfo class is stored in the SESSION at the time of login. The WFAuthorizationInfo is immutable once stored in the session; whatever rights are given 
  * to the user at login remain with him until he logs in again (this includes REMEMBER-ME login).
  * The WFAuthorizationInfo MUST be easily serializable! No circular references, etc... subclasses be careful!
  *
  * NOTE: If you are using a subclass of WFAuthorizationInfo, please note that the authorizationInfo managed by WFAuthorizationManager will only be of your subclass' type if
  * someone is logged in. Until then, it's always WFAuthorizationInfo. So, always test isLoggedIn() before accessing authorizationInfo as your subclass.
  */
class WFAuthorizationInfo extends WFObject
{
    /**
      * @var string The userid of the logged in user.
      */
    protected $userid;
    /**
     * @var boolean TRUE is the user is a super-user. FALSE otherwise.
     */
    protected $isSuperUser;

    /**
      * @const Flag for no user logged in.
      */
    const NO_USER = -1;

    function __construct()
    {
        $this->userid = WFAuthorizationInfo::NO_USER;
        $this->isSuperUser = false;
    }

    /**
     *  Is the current user a superuser?
     *
     *  @return boolean TRUE if superuser, false otherwise.
     */
    function isSuperUser()
    {
        return $this->isSuperUser;
    }
    
    /**
     *  Set the superuser status.
     *
     *  @param boolean TRUE if the user is a superuser, false otherwise.
     */
    function setIsSuperUser($isSuperUser)
    {
        $this->isSuperUser = $isSuperUser;
    }
    
    /**
      * Set the user id of the authorized user.
      * @param string The user id.
      */
    function setUserid($uid)
    {
        $this->userid = $uid;
    }

    /**
      * Is there a user logged in?
      *
      * @return boolean TRUE if a user is logged in, false otherwise.
      */
    final function isLoggedIn()
    {
        return ($_SESSION[WFAuthorizationManager::SESSION_NAMESPACE][WFAuthorizationManager::SESSION_KEY_LOGGED_IN] === true);
    }

    /**
      * What is the userid of the currently logged in user?
      *
      * @return string The userid of the currently logged in user, or WFAuthorizationInfo::NO_USER if no one is logged in.
      */
    final function userid()
    {
        return $this->userid;
    }

    /**
      * Has the user authenticated recently?
      *
      * Some sites may wish to keep a user logged in forever, even with "remember me", but then restrict access to extremely sensitive data by requiring that a user is in a "recent" session. That is, they have recently authenticated with username/password and have not been "idle" in that session for more than a short period of time.
      *
      * @return boolean TRUE if a user has authenticated recently and not been idle for more than WFAuthorizationManager::RECENT_LOGIN_SECS seconds.
      */
    final function isRecentLogin()
    {
        return ( (time() - $_SESSION[WFAuthorizationManager::SESSION_NAMESPACE][WFAuthorizationManager::SESSION_KEY_RECENT_LOGIN_TIME]) < WFAuthorizationManager::RECENT_LOGIN_SECS );
    }
}

/**
  * A specialized exception class for authorization exceptions. Used by the Module subsystem to handle access control.
  */
class WFAuthorizationException extends Exception
{
    /**
      * @const Set the exception's CODE to this if access is denied based on the user's WFAuthorizationInfo.
      */
    const DENY = 1;
    /**
      * @const Set the exception's CODE to this if access is denied because no one is logged in. System will bounced to login and return.
      */
    const TRY_LOGIN = 2;
}

/**
  * The WFAuthorizationManager helps the application manage user authentication, login, and access control.
  *
  * By default, a web application has no login capabilities and thus all users are unprivileged.
  *
  * WFAuthorizationManager works in conjuction with the bundled "login" module. The following is the public interface of the login module:
  * - promptLogin($continueURL)
  * - doLogout()
  * - notAuthorized()
  * If you want to extend / override the default login module, you can set the invocation path of the login module with {@link WFAuthorizationManager::setLoginModule()}. NOT YET IMPLEMENTED.
  * ??????????? THINK ABOUT THE ABOVE... NOT YET WELL THOUGHT OUT....
  *
  * @todo Do we need to encapsulate the "login" module in a method of WFAuthorizationManager so that applications can override the login module with their own?
  * @todo Remember-me logins not yet implemented. See loginWithHash()
  * @todo Login page needs to have redirect-url support (default and passed-in)
  * @todo verify login with garbled image (OPTIONAL)
  */
class WFAuthorizationManager extends WFObject
{
    const SESSION_NAMESPACE = 'WFAuthorizationManager';

    const SESSION_KEY_VERSION = 'version';
    const SESSION_KEY_LOGGED_IN = 'isLoggedIn';
    const SESSION_KEY_AUTHORIZATION_INFO = 'authorizationInfo';
    const SESSION_KEY_RECENT_LOGIN_TIME = 'recentLoginTime';

    const VERSION = 1.0;
    const RECENT_LOGIN_SECS = 900; // 15 minutes

    const ALLOW = 1;
    const DENY = 2;

    /**
      * @var object WFAuthorizationInfo The authorization info for the current session.
      */
    protected $authorizationInfo;
    /**
      * @var object WFAuthorizationDelegate The delegate object for handling authorization-related things.
      */
    protected $authorizationDelegate;

    function __construct()
    {
        parent::__construct();

        $this->authorizationInfo = new WFAuthorizationInfo;
        $this->authorizationDelegate = NULL;

        // is session authorization info initialized?
        if (empty($_SESSION[WFAuthorizationManager::SESSION_NAMESPACE][WFAuthorizationManager::SESSION_KEY_VERSION]) or $_SESSION[WFAuthorizationManager::SESSION_NAMESPACE][WFAuthorizationManager::SESSION_KEY_VERSION] < WFAuthorizationManager::VERSION)
        {
            // SESSION authorization info doesn't exist; initialize to least-privileged state

            // try to load from remember me

            // initialize
            $this->init();
        }
        else
        {
            // SESSION authorization does exist; restore from session
            $this->authorizationInfo = $_SESSION[WFAuthorizationManager::SESSION_NAMESPACE][WFAuthorizationManager::SESSION_KEY_AUTHORIZATION_INFO];

            // update recent-auth time if it's within the window
            if ( (time() - $_SESSION[WFAuthorizationManager::SESSION_NAMESPACE][WFAuthorizationManager::SESSION_KEY_RECENT_LOGIN_TIME]) < WFAuthorizationManager::RECENT_LOGIN_SECS )
            {
                $_SESSION[WFAuthorizationManager::SESSION_NAMESPACE][WFAuthorizationManager::SESSION_KEY_RECENT_LOGIN_TIME] = time();
            }
        }
    }

    /**
      * Initialize the auth manager to the default state.
      */
    function init()
    {
        $_SESSION[WFAuthorizationManager::SESSION_NAMESPACE][WFAuthorizationManager::SESSION_KEY_VERSION] = WFAuthorizationManager::VERSION;
        $_SESSION[WFAuthorizationManager::SESSION_NAMESPACE][WFAuthorizationManager::SESSION_KEY_LOGGED_IN] = false;
        $this->authorizationInfo = $_SESSION[WFAuthorizationManager::SESSION_NAMESPACE][WFAuthorizationManager::SESSION_KEY_AUTHORIZATION_INFO] = new WFAuthorizationInfo();
        $_SESSION[WFAuthorizationManager::SESSION_NAMESPACE][WFAuthorizationManager::SESSION_KEY_RECENT_LOGIN_TIME] = 0;
    }

    /**
     * Get a reference to the shared WFAuthorizationManager object.
     * @static
     * @return object The WFAuthorizationManager object.
     */
    function sharedAuthorizationManager()
    {
        static $singleton = NULL;
        if (!$singleton) {
            $singleton = new WFAuthorizationManager();
        }
        return $singleton;
    }

    /**
      * Get the current auth info.
      *
      * @return object WFAuthorizationInfo The active WFAuthorizationInfo info.
      */
    function authorizationInfo()
    {
        return $this->authorizationInfo;
    }

    /**
      * Set the WFAuthorizationDelegate to use.
      *
      * The WFWebApplication will usually do this for you.
      *
      * @param object An object that implements WFAuthorizationDelegate.
      */
    function setDelegate($d)
    {
        $this->authorizationDelegate = $d;
    }

    /**
      * Logout the current session.
      */
    function logout()
    {
        $this->init();
    }

    /**
      * Attempt to authorize the user with the given name/password.
      *
      * This will call the delegate's login function to authenticate and get the authorizationInfo.
      *
      * @return boolean TRUE if login was successful, FALSE otherwise.
      */
    function login($username, $password)
    {
        if (!$this->authorizationDelegate) throw( new Exception("WFAuthorizationDelegate required to attempt login.") );
        if (!method_exists($this->authorizationDelegate, 'login')) throw( new Exception("WFAuthorizationDelegate is missing login() function.") );

        // get delegate to attempt authorization
        $result = $this->authorizationDelegate->login($username, $password);
        if ($result instanceof WFAuthorizationInfo)
        {
            // means login succeeded!
            $this->authorizationInfo = $result;

            $_SESSION[WFAuthorizationManager::SESSION_NAMESPACE][WFAuthorizationManager::SESSION_KEY_LOGGED_IN] = true;
            $_SESSION[WFAuthorizationManager::SESSION_NAMESPACE][WFAuthorizationManager::SESSION_KEY_AUTHORIZATION_INFO] = $this->authorizationInfo;
            $_SESSION[WFAuthorizationManager::SESSION_NAMESPACE][WFAuthorizationManager::SESSION_KEY_RECENT_LOGIN_TIME] = time();

            return true;
        }
        else
        {
            // means login failed - kill all current login info!
            $this->init();

            return false;
        }
    }
}

?>
