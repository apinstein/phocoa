{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
{WFShowErrors}
{WFForm id="resetPasswordForm"}
    <p>Enter your {$usernameLabel} and press "Go" to recover your password:</p>
    {WFView id="username"} {WFView id="reset"}
{/WFForm}
