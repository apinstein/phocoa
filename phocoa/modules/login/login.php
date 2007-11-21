<?php

class login extends WFModule
{
    /**
      * Tell system which page to show if none specified.
      */
    function defaultPage() { return 'promptLogin'; }

    function gotoURL($url)
    {
        // for now, always use internal redirects; in future may want to pass this as param along with continueURL
        if (WFRequestController::isAjax() or 1)
        {
            throw( new WFRequestController_InternalRedirectException($url) );
        }
        else
        {   
            throw( new WFRequestController_RedirectException($url) );
        }
    }

    function doLogout_ParameterList()
    {
        return array('continueURL');    // will be WFWebApplication::serializeURL() encoded
    }
    function doLogout_PageDidLoad($page, $params)
    {
        $ac = WFAuthorizationManager::sharedAuthorizationManager();

        // calculate continueURL
        $continueURL = NULL;
        if (empty($params['continueURL']))
        {
            $continueURL = $ac->defaultLogoutContinueURL();   // need to get this before we log out as the delegate might want access to the credentials to figure this out
        }
        else
        {
            $continueURL = WFWebApplication::unserializeURL($params['continueURL']);
        }

        $ac->logout();
        if ($ac->shouldShowLogoutConfirmation())
        {
            $page->assign('continueURL', $continueURL);
            $page->setTemplateFile('showLogoutSuccess.tpl');
        }
        else
        {
            if (!$continueURL) throw( new WFException("No continueURL found... defaultLogoutContinueURL cannot be empty if shouldShowLogoutConfirmation is false.") );
            $this->gotoURL($continueURL);
        }
    }

    function promptLogin_ParameterList()
    {
        return array('continueURL');
    }
    function promptLogin_PageDidLoad($page, $params)
    {
        $ac = WFAuthorizationManager::sharedAuthorizationManager();
        $authinfo = $ac->authorizationInfo();

        // calculate continueURL
        $continueURL = NULL;
        if (!empty($params['continueURL']))
        {
            $continueURL = $params['continueURL'];
        }
        $page->outlet('continueURL')->setValue($params['continueURL']);

        // if already logged in, bounce to home
        if ($authinfo->isLoggedIn())
        {
            if (!$continueURL)
            {
                $continueURL = $ac->defaultLoginContinueURL();
            }
            else
            {
                $continueURL = WFWebApplication::unserializeURL($continueURL);
            }
            $this->gotoURL($continueURL);
        }
        
        // continue to normal promptLogin setup
        $page->assign('loginMessage', $ac->loginMessage());
        $page->assign('usernameLabel', $ac->usernameLabel());
        $page->outlet('rememberMe')->setHidden( !$ac->shouldEnableRememberMe() );
        $page->outlet('forgottenPasswordLink')->setHidden( !$ac->shouldEnableForgottenPasswordReset() );
        $page->outlet('forgottenPasswordLink')->setValue( WFRequestController::WFURL('login', 'doForgotPassword') . '/' . $page->outlet('username')->value());

        if (!$page->hasSubmittedForm())
        {
            $page->outlet('rememberMe')->setChecked( $ac->shouldRememberMeByDefault() );
        }
    }

    function promptLogin_doLogin_Action($page)
    {
        $ac = WFAuthorizationManager::sharedAuthorizationManager();
        $ok = $ac->login($page->outlet('username')->value(), $page->outlet('password')->value());
        if ($ok)
        {
            // login was successful
            // remember me stuff
            // ...

            // continue to next page
            if ($page->outlet('continueURL')->value())
            {
                $continueURL = WFWebApplication::unserializeURL($page->outlet('continueURL')->value());
            }
            else
            {
                $continueURL = $ac->defaultLoginContinueURL();
            }
            $this->gotoURL($continueURL);
        }
        else
        {
            // login failed
            $failMsg = $ac->loginFailedMessage($page->outlet('username')->value());
            if (!is_array($failMsg))
            {
                $failMsg = array($failMsg);
            }
            foreach ($failMsg as $msg) {
                $page->addError(new WFError($msg) );
            }
        }
    }

    function promptLogin_SetupSkin($skin)
    {
        $skin->setTitle("Please log in.");
    }

    function doForgotPassword_ParameterList()
    {
        return array('username');
    }
    function doForgotPassword_PageDidLoad($page, $params)
    {
        $ac = WFAuthorizationManager::sharedAuthorizationManager();
        $page->outlet('username')->setValue($params['username']);
        $page->assign('usernameLabel', $ac->usernameLabel());
    }

    function doForgotPassword_reset_Action($page)
    {
        $ac = WFAuthorizationManager::sharedAuthorizationManager();
        try {
            $username = $page->outlet('username')->value();
            $okMessage = "The password for " . $ac->usernameLabel() . " {$username} been reset. Your new password information has been emailed to the email address on file for your account.";
            $newMessage = $ac->resetPassword($username);
            if ($newMessage)
            {
                $okMessage = $newMessage;
            }
            $page->assign('okMessage', $okMessage);
            $page->setTemplateFile('forgotPasswordSuccess.tpl');
        } catch (WFException $e) {
            $page->addError(new WFError($e->getMessage()));
        }
    }

    function showLoginSuccess_SetupSkin($skin)
    {
        $skin->setTitle("Login Successful.");
    }

    // simple debug function to see who's logged in and give an option to log out.
    function showLogin_PageDidLoad($page, $params)
    {
        $ac = WFAuthorizationManager::sharedAuthorizationManager();
        $authinfo = $ac->authorizationInfo();
        if ($authinfo->isLoggedIn())
        {
            $page->outlet('userinfo')->setValue('User is logged in (' . $authinfo->userid() . ')');
            $page->assign('showLogout', true);
        }
        else
        {
            $page->outlet('userinfo')->setValue('No user logged in.');
            $page->assign('showLogout', false);
        }
    }

    function notAuthorized_SetupSkin($skin)
    {
        $skin->setTitle('Not authorized.');
    }
}

?>
