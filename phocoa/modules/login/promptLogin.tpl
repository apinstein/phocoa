{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
{if $showLogin}
    {if $showLoginMessage}
    <p>You must log in to access the requested page.</p>
    {/if}

    {WFShowErrors}
    {WFForm id="loginForm"}
    {WFHidden id="continueURL"}
        <table border="0">
            <tr>
                <td>Username:</td>
                <td>{WFTextField id="username"}</td>
            </tr>
            <tr>
                <td>Password:</td>
                <td>{WFPassword id="password"}</td>
            </tr>
            <tr>
                <td colspan="2" align="center">{WFSubmit id="login"}</td>
            </tr>
        </table>
    {/WFForm}
{else}
    <p><a href="{WFURL page="doLogout"}">Logout</a></p>
{/if}
