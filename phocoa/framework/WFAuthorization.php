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
      * @param boolean TRUE if the password is in "token" form; ie the TOKEN that the application generates for rememberMeToken(). If TRUE, $username will be null.
      * @return object WFAuthorizationInfo Return an WFAuthorizationInfo with any additional security profile. This of course can be a subclass. Return NULL if login failed.
      */
    function login($username, $password, $passIsToken) {}

    /**
     *  Provide the invocationPath for handling login.
     *
     *  By default, this will be "login/promptLogin". Applications can override this behavior by writing their own login modules, or even simply "wrapping" the built-in one.
     *
     *  @return string The invocationPath to the login. Remember the page handling login *should* accept a first parameter of "continueURL" (the url will be encoded with {@link WFWebApplication::serializeURL()})
     */
    function loginInvocationPath() {}

    /**
     *  The URL to continue to if the user logs in but there is no "continue to url" set.
     *
     *  If NULL, no redirect will be performed, and just a message saying "Login successful" will be seen.
     *
     *  @return string A URL to redirect to (will be done via {@link WFRedirectRequestException}). DEFAULT: NULL.
     */
    function defaultLoginContinueURL() {}

    /**
     *  The URL to continue to if the user logs out.
     *
     *  If NULL, no redirect will be performed, and just a message saying "Logout successful" will be seen.
     *
     *  @return string A URL to redirect to (will be done via {@link WFRedirectRequestException}). DEFAULT: NULL.
     */
    function defaultLogoutContinueURL() {}

    /**
     *  Should there be an interstitial "You have logged out successfully, click here to continue", or should logout immediately redirect to {@link WFAuthorizationDelegate::defaultLogoutContinueURL() defaultLogoutContinueURL()}?
     *
     *  @return boolean TRUE to show a logout interstitial. DEFAULT: true.
     */
    function shouldShowLogoutConfirmation() {}

    /**
     *  Should the login interface have a "remember me" checkbox?
     *
     *  @return boolean TRUE to enable "remember me" functionality. DEFAULT: false.
     */
    function shouldEnableRememberMe() {}

    /**
     *  The label for the sign-up link.
     *
     *  @return string The label for the sign-up link. Default: Sign Up
     */
    function signUpLabel() {}

    /**
     *  The URL for the sign-up link.
     *
     *  If NULL, the sign-up link will not be shown.
     *
     *  @return string The url for the sign-up link. Default: NULL
     */
    function signUpUrl() {}

    /**
     *  Delegate should return the "token" to persist via a long-term cookie. This exact token will be passed back in when trying to do a rememberMe login.
     *
     *  SECURITY RECOMMENDATION:
     *  We recommend that you return a rememberMeLookupId:token combination as per https://paragonie.com/blog/2015/04/secure-authentication-php-with-long-term-persistence
     *
     *  @param object WFAuthorizationInfo The current logged in data.
     *  @return string The token to persist on the client.
     */
    function rememberMeToken($authInfo) {}

    /**
     *  Delegate should implement and return this method if they want to override the default rememberme settings.
     *
     *  @return array OPTIONS hash. See WFAuthorizationManager::REMEMBER_ME_OPT_*.
     */
    function rememberMeOptions() {}

    /**
     *  If "remember me" is enabled with {@link WFAuthorizationDelegate::shouldEnableRememberMe() shouldEnableRememberMe}, should "remember me"
     *  be checked by default?
     *
     *  @return boolean TRUE if the "remember me" checkbox should be checked by default. DEFAULT: false.
     */
    function shouldRememberMeByDefault() {}

    /**
     *  The login help message that should be displayed above the login box.
     *
     *  @return string The login message to display above the login box. DEFAULT: "You must log in to access the requested page."
     */
    function loginMessage() {}

    /**
     *  The label to use for the "username" field.
     *
     *  @return string The label for the username field. DEFAULT: "Username".
     */
    function usernameLabel() {}

    /**
     *  The message to display to a use on unsuccessful login.
     *
     *  @param string The username that the attempted login was for.
     *  @return mixed string: The message to display on failed login. array of strings; Multiple messages to display (as list items). DEFAULT: string:"Login username or password is not valid."
     */
    function loginFailedMessage($username) {}

    /**
     *  Should a "forgot your password" link be shown?
     *
     *  @return boolean TRUE to enable forgotten password reset feature.
     */
    function shouldEnableForgottenPasswordReset() {}

    /**
     *  Reset the password for the given user.
     *
     *  @param string The username that the attempted login was for.
     *  @return string The message to show the user on successful password reset. DEFAULT: "The password for <usernameLabel> <username> been reset. Your new password information has been emailed to the email address on file for your account."
     *  @throws object WFException If the password cannot be reset, throw an error with the message to be displayed as the string.<br>
     *          object WFRedirectRequestException If your reset password system is more complicated than can be handled by PHOCOA, feel free to redirect to another page to handle this.
     */
    function resetPassword($username) {}

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
    /**
      * @const Set the exception's CODE to this if access is denied because no one is logged in recently; System will force a re-authorization.
      */
    const TRY_PROMPT = 3;
}

/**
  * The WFAuthorizationManager helps the application manage user authentication, login, and access control.
  *
  * By default, a web application has no login capabilities and thus all users are unprivileged.
  *
  * WFAuthorizationManager works in conjuction with the bundled "login" module. The following is the public interface of the login module (via invocationPath redirects)
  * - promptLogin/<continueURL:base64>
  * - doLogout
  * - notAuthorized
  *
  * You can reliably link to the above listed invocationPaths from your application.
  *
  * @todo captcha option
  * @todo Decouple the default WFAuthorizationInfo class from the manager; let applications define this so that if there's no one logged in at least they get back the correct instance type.
  * @todo Make VERSION accessible externally (maybe through Delegate interface?) so that applications can have phocoa invalidate/re-login automatically when session structures change.
  */
class WFAuthorizationManager extends WFObject
{
    const SESSION_NAMESPACE = 'WFAuthorizationManager';

    const SESSION_KEY_VERSION = 'version';
    const SESSION_KEY_LOGGED_IN = 'isLoggedIn';
    const SESSION_KEY_AUTHORIZATION_INFO = 'authorizationInfo';
    const SESSION_KEY_RECENT_LOGIN_TIME = 'recentLoginTime';

    const VERSION = 1.1;
    const RECENT_LOGIN_SECS = 900; // 15 minutes

    const ALLOW  = 1;   // allow given current credentials
    const DENY   = 2;   // deny given current credentials
    const PROMPT = 3;   // force re-authorization

    /**
      * @var object WFAuthorizationInfo The authorization info for the current session.
      */
    protected $authorizationInfo;
    /**
      * @var object WFAuthorizationDelegate The delegate object for handling authorization-related things.
      */
    protected $authorizationDelegate;
    /**
     * @var string The class name to use as the {@link WFAuthorizationManager::$authorizationInfo}. Defaults to {@link WFAuthorizationInfo}.
     */
    protected $authorizationInfoClass;

    function __construct()
    {
        parent::__construct();

        $this->authorizationInfoClass = WFWebApplication::sharedWebApplication()->authorizationInfoClass();
        $this->authorizationInfo = NULL;
        $this->authorizationDelegate = NULL;


        // is session authorization info initialized?
        if (empty($_SESSION[WFAuthorizationManager::SESSION_NAMESPACE][WFAuthorizationManager::SESSION_KEY_VERSION]) or $_SESSION[WFAuthorizationManager::SESSION_NAMESPACE][WFAuthorizationManager::SESSION_KEY_VERSION] < WFAuthorizationManager::VERSION)
        {
            // SESSION authorization info doesn't exist; initialize to least-privileged state

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

    const REMEMBER_ME_OPT_NAME          = 'name';
    const REMEMBER_ME_OPT_DURATION      = 'duration';
    const REMEMBER_ME_OPT_DOMAIN        = 'domain';
    const REMEMBER_ME_OPT_PATH          = 'path';
    /**
     * Set a long-term remember me cookie.
     * @param array OPTIONS hash. See WFAuthorizationManager::REMEMBER_ME_OPT_*.
     */
    function rememberMe()
    {
        $options = $this->rememberMeOptions();
        $userToken = $this->rememberMeToken();
        setcookie($options[self::REMEMBER_ME_OPT_NAME], $userToken, strtotime($options[self::REMEMBER_ME_OPT_DURATION]), $options[self::REMEMBER_ME_OPT_PATH], $options[self::REMEMBER_ME_OPT_DOMAIN]);
    }

    function rememberMeToken()
    {
        if (!$this->authorizationInfo->isLoggedIn()) throw new WFException("Cannot call rememberMeToken if not logged in.");
        if (!$this->authorizationDelegate) throw( new Exception("WFAuthorizationDelegate required for rememberMeToken.") );

        if (!method_exists($this->authorizationDelegate, 'rememberMeToken'))
        {
            throw new Exception("WFAuthorizationDelegate.rememeberMeToken must be implemented when remember me is enabled.");
        }

        $rememberMeToken = $this->authorizationDelegate->rememberMeToken($this->authorizationInfo);

        return $rememberMeToken;
    }

    function rememberMeOptions()
    {
        $options = array(
            self::REMEMBER_ME_OPT_NAME      => 'PHOCOA_REMEMBER_ME',
            self::REMEMBER_ME_OPT_DURATION  => '+10 years',
            self::REMEMBER_ME_OPT_DOMAIN    => NULL,
            self::REMEMBER_ME_OPT_PATH      => '/',
        );

        if ($this->authorizationDelegate && method_exists($this->authorizationDelegate, 'rememberMeOptions'))
        {
            $options = array_merge($options, $this->authorizationDelegate->rememberMeOptions());
        }

        return $options;
    }

    /**
      * Initialize the auth manager to the default state.
      */
    function init()
    {
        $_SESSION[WFAuthorizationManager::SESSION_NAMESPACE][WFAuthorizationManager::SESSION_KEY_VERSION] = WFAuthorizationManager::VERSION;
        $_SESSION[WFAuthorizationManager::SESSION_NAMESPACE][WFAuthorizationManager::SESSION_KEY_LOGGED_IN] = false;
        $this->authorizationInfo = $_SESSION[WFAuthorizationManager::SESSION_NAMESPACE][WFAuthorizationManager::SESSION_KEY_AUTHORIZATION_INFO] = new $this->authorizationInfoClass();
        $_SESSION[WFAuthorizationManager::SESSION_NAMESPACE][WFAuthorizationManager::SESSION_KEY_RECENT_LOGIN_TIME] = 0;
    }

    /**
     * Get a reference to the shared WFAuthorizationManager object.
     * @static
     * @return object The WFAuthorizationManager object.
     */
    public static function sharedAuthorizationManager()
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

        if (!$this->authorizationInfo->isLoggedIn())
        {
            $this->checkRememeberMe();
        }
    }

    private function checkRememeberMe()
    {
        $options = $this->rememberMeOptions();

        // try to load from remember me
        if (isset($_COOKIE[$options[self::REMEMBER_ME_OPT_NAME]]))
        {
            $this->clearRememberMe();   // remember me is once-only

            $rememberMeToken = $_COOKIE[$options[self::REMEMBER_ME_OPT_NAME]];
            $ok = $this->login(NULL, $rememberMeToken, true);
            if ($ok)
            {
                $this->rememberMe();
            }
        }
    }

    /**
      * Get the WFAuthorizationDelegate set for the WFAuthorizationManager.
      *
      * @return object An object that implements WFAuthorizationDelegate.
      */
    function delegate()
    {
        return $this->authorizationDelegate;
    }

    /**
      * Logout the current session.
      */
    function logout()
    {
        $this->init();

        $this->clearRememberMe();
    }

    /**
     * Clear the remember me cookie
     */
    function clearRememberMe()
    {
        $options = $this->rememberMeOptions();
        // clear REMEMBER ME state from CLIENT...
        setcookie($options[self::REMEMBER_ME_OPT_NAME], '', strtotime('-1 year'), $options[self::REMEMBER_ME_OPT_PATH], $options[self::REMEMBER_ME_OPT_DOMAIN]);
        // ...and PHP superglobal
        unset($_COOKIE[$options[self::REMEMBER_ME_OPT_NAME]]);
    }

    /**
      * Attempt to authorize the user with the given name/password.
      *
      * This will call the delegate's login function to authenticate and get the authorizationInfo.
      *
      * @param string The username to use for the authentication.
      * @param string The password to use for the authentication.
      * @param boolean TRUE if the password is in "token" form; ie, not the clear-text password. Useful for remember-me logins or single-sign-on (SSO) setups.
      * @return boolean TRUE if login was successful, FALSE otherwise.
      * @see WFAuthorizationDelegate::login()
      */
    function login($username, $password, $passIsToken = false)
    {
        if (!$this->authorizationDelegate) throw( new Exception("WFAuthorizationDelegate required to attempt login.") );
        if (!method_exists($this->authorizationDelegate, 'login')) throw( new Exception("WFAuthorizationDelegate is missing login() function.") );

        // get delegate to attempt authorization
        $result = $this->authorizationDelegate->login($username, $password, $passIsToken);
        if ($result instanceof WFAuthorizationInfo)
        {
            $this->loginAsAuthorizationInfo($result, !$passIsToken);

            return true;
        }
        else
        {
            // means login failed - kill all current login info!
            $this->init();

            return false;
        }
    }

    /**
     * Change the current session authorization info to the specified WFAuthorizationInfo object.
     *
     * This is useful for SSO or other internal user-switching capabilities.
     *
     * @param object WFAuthorizationInfo The new authorization info to set for the current session.
     */
    function loginAsAuthorizationInfo($authInfo, $authorizeRecentLogin = true)
    {
        if (!$authInfo instanceof WFAuthorizationInfo) throw new WFException("WFAuthorizationInfo or subclass required.");

        $this->authorizationInfo = $authInfo;

        $_SESSION[WFAuthorizationManager::SESSION_NAMESPACE][WFAuthorizationManager::SESSION_KEY_LOGGED_IN] = true;
        $_SESSION[WFAuthorizationManager::SESSION_NAMESPACE][WFAuthorizationManager::SESSION_KEY_AUTHORIZATION_INFO] = $this->authorizationInfo;

        $lastAuthTime = $authorizeRecentLogin ? time() : 0;
        $_SESSION[WFAuthorizationManager::SESSION_NAMESPACE][WFAuthorizationManager::SESSION_KEY_RECENT_LOGIN_TIME] = $lastAuthTime;
    }

    /**
     *  Cause the visitor to be re-directed to the login page.
     *
     *  OPTIONAL: "continueURL" support.
     *
     *  This will issue a 302 redirect and exit the current request execution.
     *
     *  @param string The URL of the page to go to after successful login. Note that this should be a PLAIN URL, but it WILL BE base64-encoded before being passed to the login module.
     *  @param boolean TRUE to force the login screen even if already logged in (used for forcing re-auth of secure areas).
     */
    function doLoginRedirect($continueURL, $reauthorizeEvenIfLoggedIn = false)
    {
        $reauthorizeEvenIfLoggedInSuffix = $reauthorizeEvenIfLoggedIn ? "/1" : NULL;

        $loginInvocationPath = $this->loginInvocationPath();
        if (WFRequestController::sharedRequestController()->isAjax())
        {
            header("HTTP/1.0 401 Login Required");
            print WWW_ROOT . "/{$loginInvocationPath}/" . WFWebApplication::serializeURL($continueURL) . $reauthorizeEvenIfLoggedInSuffix;
        }
        else
        {
            header("Location: " . WWW_ROOT . "/{$loginInvocationPath}/" . WFWebApplication::serializeURL($continueURL) . $reauthorizeEvenIfLoggedInSuffix);
        }
        exit;
    }

    /**
     *  Get the login modulePath to use.
     *
     *  @return string The modulePath for the login module. The module at the given path must implement promptLogin/doLogout/notAuthorized
     */
    function loginInvocationPath()
    {
        if (!$this->authorizationDelegate) throw( new Exception("WFAuthorizationDelegate required for defaultLoginContinueURL.") );

        $loginInvocationPath = 'login/promptLogin';
        if (method_exists($this->authorizationDelegate, 'loginInvocationPath'))
        {
            $dLoginInvocationPath = $this->authorizationDelegate->loginInvocationPath();
            if ($dLoginInvocationPath)
            {
                $loginInvocationPath = $dLoginInvocationPath;
            }
        }

        return $loginInvocationPath;
    }

    /**
     *  The URL to continue to if the user logs in but there is no "continue to url" set.
     *
     *  Will call the login delegate method to get info as well.
     *
     *  @return string A URL to redirect to (will be done via {@link WFRedirectRequestException}). DEFAULT: NULL.
     *  @see WFAuthorizationDelegate::defaultLoginContinueURL()
     */
    function defaultLoginContinueURL()
    {
        if (!$this->authorizationDelegate) throw( new Exception("WFAuthorizationDelegate required for defaultLoginContinueURL.") );

        $continueURL = WFRequestController::WFURL('login', 'showLoginSuccess');
        if (method_exists($this->authorizationDelegate, 'defaultLoginContinueURL'))
        {
            $dContinueURL = $this->authorizationDelegate->defaultLoginContinueURL();
            if ($dContinueURL)
            {
                $continueURL = $dContinueURL;
            }
        }

        return $continueURL;
    }

    /**
     *  The URL to continue to if the user logs out.
     *
     *  Will call the login delegate method.
     *
     *  If NULL, no redirect will be performed, and just a message saying "Logout successful" will be seen.
     *
     *  @return string A URL to redirect to (will be done via {@link WFRedirectRequestException}). DEFAULT: NULL.
     *  @see WFAuthorizationDelegate::defaultLogoutContinueURL()
     */
    function defaultLogoutContinueURL()
    {
        if (!$this->authorizationDelegate) throw( new Exception("WFAuthorizationDelegate required for defaultLogoutContinueURL.") );

        $continueURL = NULL;
        if (method_exists($this->authorizationDelegate, 'defaultLogoutContinueURL'))
        {
            $continueURL = $this->authorizationDelegate->defaultLogoutContinueURL();
        }

        return $continueURL;
    }

    /**
     *  Should there be an interstitial "You have logged out successfully, click here to continue", or should logout immediately redirect to {@link WFAuthorizationDelegate::defaultLogoutContinueURL() defaultLogoutContinueURL()}?
     *
     *  Will call login delegate.
     *
     *  @return boolean TRUE to show a logout interstitial. DEFAULT: true.
     *  @see WFAuthorizationDelegate::defaultLoginContinueURL()
     */
    function shouldShowLogoutConfirmation()
    {
        if (!$this->authorizationDelegate) throw( new Exception("WFAuthorizationDelegate required for shouldShowLogoutConfirmation.") );

        $shouldShowLogoutConfirmation = true;
        if (method_exists($this->authorizationDelegate, 'shouldShowLogoutConfirmation'))
        {
            $shouldShowLogoutConfirmation = $this->authorizationDelegate->shouldShowLogoutConfirmation();
        }

        return $shouldShowLogoutConfirmation;
    }

    /**
     *  Should the login interface have a "remember me" checkbox?
     *
     *  Will call the login delegate method.
     *
     *  @return boolean TRUE to enable "remember me" functionality. DEFAULT: false.
     *  @see WFAuthorizationDelegate::shouldEnableRememberMe()
     */
    function shouldEnableRememberMe()
    {
        if (!$this->authorizationDelegate) throw( new Exception("WFAuthorizationDelegate required for shouldEnableRememberMe.") );

        $shouldEnableRememberMe = false;
        if (method_exists($this->authorizationDelegate, 'shouldEnableRememberMe'))
        {
            $shouldEnableRememberMe = $this->authorizationDelegate->shouldEnableRememberMe();
        }

        return $shouldEnableRememberMe;
    }

    /**
     *  If "remember me" is enabled with {@link WFAuthorizationDelegate::shouldEnableRememberMe() shouldEnableRememberMe}, should "remember me"
     *  be checked by default?
     *
     *  Will call the login delegate method.
     *
     *  @return boolean TRUE if the "remember me" checkbox should be checked by default. DEFAULT: false.
     *  @see WFAuthorizationDelegate::shouldRememberMeByDefault()
     */
    function shouldRememberMeByDefault()
    {
        if (!$this->authorizationDelegate) throw( new Exception("WFAuthorizationDelegate required for shouldRememberMeByDefault.") );

        $shouldRememberMeByDefault = false;
        if (method_exists($this->authorizationDelegate, 'shouldRememberMeByDefault'))
        {
            $shouldRememberMeByDefault = $this->authorizationDelegate->shouldRememberMeByDefault();
        }

        return $shouldRememberMeByDefault;
    }

    /**
     *  The login help message that should be displayed above the login box.
     *
     *  Will call the login delegate method.
     *
     *  @return string The login message to display above the login box. DEFAULT: "You must log in to access the requested page."
     *  @see WFAuthorizationDelegate::loginMessage()
     */
    function loginMessage()
    {
        if (!$this->authorizationDelegate) throw( new Exception("WFAuthorizationDelegate required for loginMessage.") );

        $loginMessage = 'You must log in to access the requested page.';
        if (method_exists($this->authorizationDelegate, 'loginMessage'))
        {
            $loginMessage = $this->authorizationDelegate->loginMessage();
        }

        return $loginMessage;
    }

    /**
     *  The label to use for the "username" field.
     *
     *  Will call the login delegate method.
     *
     *  @return string The label for the username field. DEFAULT: "Username".
     *  @see WFAuthorizationDelegate::usernameLabel()
     */
    function usernameLabel()
    {
        if (!$this->authorizationDelegate) throw( new Exception("WFAuthorizationDelegate required for usernameLabel.") );

        $usernameLabel = 'Username';
        if (method_exists($this->authorizationDelegate, 'usernameLabel'))
        {
            $usernameLabel = $this->authorizationDelegate->usernameLabel();
        }

        return $usernameLabel;
    }

    /**
     *  The label for the sign up link on the login form.
     *
     *  Will call the login delegate method.
     *
     *  @return string The label for the sign up link. DEFAULT: 'Sign Up'.
     *  @see WFAuthorizationDelegate::signUpLabel()
     */
    function signUpLabel()
    {
        if (!$this->authorizationDelegate) throw( new Exception("WFAuthorizationDelegate required for signUpLabel.") );

        if (method_exists($this->authorizationDelegate, 'signUpLabel'))
        {
            return $this->authorizationDelegate->signUpLabel();
        }

        return 'Sign Up';
    }

    /**
     *  The URL for the sign up link on the login form.
     *
     *  Will call the login delegate method.
     *
     *  @return string The URL for the sign up link. DEFAULT: NULL.
     *  @see WFAuthorizationDelegate::signUpUrl()
     */
    function signUpUrl()
    {
        if (!$this->authorizationDelegate) throw( new Exception("WFAuthorizationDelegate required for signUpUrl.") );

        if (method_exists($this->authorizationDelegate, 'signUpUrl'))
        {
            return $this->authorizationDelegate->signUpUrl();
        }

        return NULL;
    }

    /**
     *  The message to display to a use on unsuccessful login.
     *
     *  Will call the login delegate method.
     *
     *  @param string The username that the attempted login was for.
     *  @return mixed string: The message to display on failed login. array of strings; Multiple messages to display (as list items). DEFAULT: string:"Login username or password is not valid."
     *  @see WFAuthorizationDelegate::loginFailedMessage()
     */
    function loginFailedMessage($username)
    {
        if (!$this->authorizationDelegate) throw( new Exception("WFAuthorizationDelegate required for loginFailedMessage.") );

        $loginFailedMessage = 'Login failed for ' . $this->usernameLabel() . ' "' . $username . '". Please check your ' . $this->usernameLabel() . ' and password and try again.';
        if ($this->shouldEnableForgottenPasswordReset())
        {
            $loginFailedMessage .= " If you have forgotten your password, <a href=\"" . WFRequestController::WFURL('login', 'doForgotPassword') . '/' . $username . "\">click here</a>.";
        }
        if (method_exists($this->authorizationDelegate, 'loginFailedMessage'))
        {
            $loginFailedMessage = $this->authorizationDelegate->loginFailedMessage($username);
        }

        return $loginFailedMessage;
    }

    /**
     *  Should a "forgot your password" link be shown?
     *
     *  Will call the login delegate method.
     *
     *  @return boolean TRUE to enable forgotten password reset feature.
     *  @see WFAuthorizationManager::shouldEnableForgottenPasswordReset()
     */
    function shouldEnableForgottenPasswordReset()
    {
        if (!$this->authorizationDelegate) throw( new Exception("WFAuthorizationDelegate required for shouldEnableForgottenPasswordReset.") );

        $shouldEnableForgottenPasswordReset = false;
        if (method_exists($this->authorizationDelegate, 'shouldEnableForgottenPasswordReset'))
        {
            $shouldEnableForgottenPasswordReset = $this->authorizationDelegate->shouldEnableForgottenPasswordReset();
        }

        return $shouldEnableForgottenPasswordReset;
    }

    /**
     *  Reset the password for the given user.
     *
     *  Your delegate method should craft an email or such to that user with the new password info.
     *  If there is a problem (ie user doesn't exist) throw a WFException with an appropriate message to be displyed.
     *  If not, just send your email and that's it. The default implementation will show an appropriate confirmation message.
     *
     *  Alternatively, if you have more complicated reset password logic you want to implement, throw a WFRedirectRequestException.
     *
     *  Will call the login delegate method.
     *
     *  @param string The username that the attempted login was for.
     *  @return string The message to show the user on successful password reset.
     *  @throws object WFException If the password cannot be reset, throw a WFException with the message to be displayed as the string.<br>
     *  @see WFAuthorizationDelegate::resetPassword($username)
     */
    function resetPassword($username)
    {
        if (!$this->authorizationDelegate) throw( new Exception("WFAuthorizationDelegate required for resetPassword.") );
        if (!method_exists($this->authorizationDelegate, 'resetPassword')) throw( new Exception("WFAuthorizationDelegate::resetPassword() must be definied to use the password reset feature.") );
        return $this->authorizationDelegate->resetPassword($username);
    }
}

?>
