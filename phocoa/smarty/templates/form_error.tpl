<div id="phocoaWFFormError_{$id}" class="phocoaWFFormError" style="margin: 5px 0; padding: 0;">
    {if $errorList}
    <div class="phocoaWFMessageBox phocoaWFMessageBox_Error">
        <ul>
        {section name=error loop=$errorList}
            <li>{$errorList[error]->errorMessage()}</li>
        {/section}
        </ul>
    </div>
    {/if}
</div>
