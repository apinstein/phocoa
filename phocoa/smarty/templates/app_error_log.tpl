----------------------------------------------------------
Date: {$smarty.now|date_format:'%A, %B %e, %Y %H:%M:%S'}
{foreach name=errorDisplay from=$standardErrorData item=error key=errorIndex}
{$errorIndex+1}. {$error.title}
Message: {$error.message}
Code: {$error.code}
Location:
{$error.traceAsString}

{/foreach}
----------------------------------------------------------
