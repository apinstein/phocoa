<?php

class login extends WFModule
{
    /**
      * Tell system which page to show if none specified.
      */
    function defaultPage() { return 'promptLogin'; }

    function doLogout_PageDidLoad($page, $params)
    {
        $ac = WFAuthorizationManager::sharedAuthorizationManager();
        $ac->logout();
        if ($ac->shouldShowLogoutConfirmation())
        {
            $this->setupResponsePage('showLogoutSuccess');
        }
        else
        {
            throw( new WFRedirectRequestException($ac->defaultLogoutContinueURL()) );
        }
    }
    function showLogoutSuccess_PageDidLoad($page, $params)
    {
        $ac = WFAuthorizationManager::sharedAuthorizationManager();
        $page->assign('continueURL', $ac->defaultLogoutContinueURL());
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
                $continueURL = $ac->defaultLogoutContinueURL();
            }
            throw( new WFRedirectRequestException($continueURL) );
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
                $continueURL = base64_decode($page->outlet('continueURL')->value());
            }
            else
            {
                $continueURL = $ac->defaultLoginContinueURL();
            }
            throw( new WFRedirectRequestException($continueURL) );
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
        $page->assign('username', $params['username']);
        $page->assign('usernameLabel', $ac->usernameLabel());
        try {
            $okMessage = "The password for " . $ac->usernameLabel() . " {$params['username']} been reset. Your new password information has been emailed to the email address on file for your account.";
            $newMessage = $ac->resetPassword($params['username']);
            if ($newMessage)
            {
                $okMessage = $newMessage;
            }
            $page->assign('okMessage', $okMessage);
            $page->assign('ok', true);
        } catch (WFRedirectRequestException $e) {
            throw($e);
        } catch (WFException $e) {
            $page->assign('ok', false);
            $page->assign('errMessage', $e->getMessage());
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
