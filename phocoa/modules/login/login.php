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
        $this->setupResponsePage('promptLogin');
    }

    function promptLogin_ParameterList()
    {
        return array('continueURL');
    }
    function promptLogin_PageDidLoad($page, $params)
    {
        $ac = WFAuthorizationManager::sharedAuthorizationManager();
        $authinfo = $ac->authorizationInfo();
        $page->assign('showLogin', !$authinfo->isLoggedIn());
        $page->assign('showLoginMessage', !empty($params['continueURL']));
        if (!empty($params['continueURL']))
        {
            $page->outlet('continueURL')->setValue($params['continueURL']);
        }
    }

    function promptLogin_doLogin_Action($page)
    {
        $ac = WFAuthorizationManager::sharedAuthorizationManager();
        $ok = $ac->login($page->outlet('username')->value(), $page->outlet('password')->value());
        if ($ok)
        {
            if ($page->outlet('continueURL')->value())
            {
                header("Location: " . base64_decode($page->outlet('continueURL')->value()));
            }
            else
            {
                header("Location: " . WFRequestController::WFURL('login', 'showLoginSuccess'));
            }
            exit;
        }
        else
        {
            $page->addError(new WFError("Login username or password is not valid.") );
        }
    }

    function promptLogin_SetupSkin($skin)
    {
        $skin->setTitle("Please log in.");
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
