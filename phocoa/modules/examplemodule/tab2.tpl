    {section loop=$itemCount name=fields}
        {WFSelectionCheckbox id="selectedFields"} {$smarty.section.fields.iteration}. {WFDynamic id="lotsOFields"}<br />
    {/section}
