----------------------------------------------------------
Date: {$smarty.now|date_format:'%A, %B %e, %Y %H:%M:%S'}
Code: {$exception->getcode()}
Message: {$exception->getMessage()}
Stack Trace:
{$exception->getTraceAsString()}
----------------------------------------------------------
