{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
<h1>WFForm Features</h1>
<p>This page contains tests for various WFForm features. PHOCOA offers the following features for HTML Forms:</p>
<ul>
    <li>Multiple Button Support
        <blockquote>Forms can have multiple buttons, each routing automatically to distinct action methods.</blockquote>
    </li>
    <li>AJAX Form Submission
        <blockquote>Degrades gracefully if Javascript not enabled</blockquote>
    </li>
    <li>Normalized default button behavior
        <blockquote>PHOCOA allows you to choose the 'default' button that is used to submit a form that is submitted by a user's pressing enter in a text field. If your form has only one button, PHOCOA automatically ensures that your submit action method is called. If your form has more than one submit button, it is recommended that you set defaultSubmitID on the WFForm to guarantee which form button will be triggered. Since all browsers differ slightly in how they handle submit buttons, this normalization is very important to cross-platform consistency. This feature works with or without Javascript enabled on the client.</blockquote>
    </li>
    <li>Duplicate Submission Prevention
        <blockquote>Useful for transaction-oriented forms.</blockquote>
    </li>
    <li>Post-submit button label swap
        <blockquote>Convenient way to tell users to be patient.</blockquote>
    </li>
</ul>

<h2>Default Button Tests</h2>
<p>When submitted, this form will show the button pressed to the right of the buttons.</p>
<p>The "Second Button" is set as the default button, and should appear when pressing ENTER in the text field.</p>
<p>The other buttons, when clicked, should also result in the correct button name being shown.</p>
<p>The First Button has both a postSubmitLabel and a duplicateSubmitMessage enabled.</p>
<p>The Second Button has a postSubmitLabel.</p>

{WFForm id="normalForm"}
    {WFView id="normalInput"}<br />
    <br />
    {WFView id=normalButton1}
    {WFView id=normalButton2}
    {WFView id=normalButton3}
    {$normalButtonPressed}
{/WFForm}

<h2>AJAX Default Button Tests</h2>
<p>This is the same as the above test, but with a form where AJAX is used for all form submissions.</p>
{WFForm id="ajaxForm"}
    {WFView id="ajaxInput"}<br />
    <br />
    {WFView id=ajaxButton1}
    {WFView id=ajaxButton2}
    {WFView id=ajaxButton3}
    <span id="ajaxButtonPressed">{$ajaxButtonPressed}</span>
{/WFForm}
