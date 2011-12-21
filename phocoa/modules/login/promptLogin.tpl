{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
{WFShowErrors}
<p>{$loginMessage}</p>
{WFForm id="loginForm"}
    {WFHidden id="continueURL"}
    <table border="0">
        <tr>
            <td>{$usernameLabel}:</td>
            <td>{WFTextField id="username"}</td>
        </tr>
        <tr>
            <td>Password:</td>
            <td>{WFPassword id="password"} {WFLink id="forgottenPasswordLink"}</td>
        </tr>
        {WFViewHiddenHelper id="rememberMe"}
        <tr>
            <td>Remember me?</td>
            <td>{WFCheckbox id="rememberMe"}</td>
        </tr>
        {/WFViewHiddenHelper}
        <tr>
            <td colspan="2" align="center">
                {WFSubmit id="login"}
                {WFViewHiddenHelper id="signUpLink"}
                    <span>or {WFLink id="signUpLink}</span>
                {/WFViewHiddenHelper}
            </td>
        </tr>
    </table>
{/WFForm}
<script>
document.observe('dom:loaded', function() {ldelim} $('username').activate(); {rdelim});
</script>
