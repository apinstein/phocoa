<div style="border: 2px solid red;">
    <ul>
    {section name=error loop=$errorList}
        <li>{$errorList[error]->errorMessage()}</li>
    {/section}
    </ul>
</div>
