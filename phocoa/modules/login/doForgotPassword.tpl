{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
{if $ok}
    <p>{$okMessage}</p>
{else}
    <p>The password for {$usernameLabel} "{$username}" could not be reset due to the following error:</p>
    <p>{$errMessage}</p>
{/if}
