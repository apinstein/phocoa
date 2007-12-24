<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

class module_regex extends WFModule
{
    function defaultPage()
    {
        return 'tester';
    }
}

class module_regex_tester
{
    function regexRun($page, $params)
    {
        $showResult = "No matches.";
        $matches = array();
        switch ($page->outlet('regexMatchType')->value()) {
            case 'preg_match_all':
                if (preg_match_all('/' . $page->outlet('regexExpression')->value() . '/', $page->outlet('regexTarget')->value(), $matches))
                {
                    $showResult = "<pre>";
                    //$showResult .= print_r($matches, true);
                    for ($j = 0; $j < count($matches[0]); $j++) {
                        $showResult .= "\n\nMatched: {$matches[0][$j]}";
                        for ($i = 0; $i < count($matches); $i++) {
                            $showResult .= "\n{$i}: {$matches[$i][$j]}";
                        }
                    }
                    $showResult .= "</pre>";
                }
                break;
            case 'preg_match':
                if (preg_match('/' . $page->outlet('regexExpression')->value() . '/', $page->outlet('regexTarget')->value(), $matches))
                {
                    $showResult = "<pre>";
                    for ($i = 0; $i < count($matches); $i++) {
                        if ($i == 0)
                        {
                            $showResult .= "\nMatched: {$matches[0]}\n\n";
                        }
                        else
                        {
                            $showResult .= "\n{$i}: {$matches[$i]}";
                        }
                    }
                    $showResult .= "</pre>";
                }
                break;
        }
        if (WFRequestController::sharedRequestController()->isAjax())
        {
            return WFActionResponsePhocoaUIUpdater::WFActionResponsePhocoaUIUpdater()
                        ->addUpdateHTML('regexResult', $showResult);
        }
        else
        {
            print $showResult;
            exit;
        }
    }
    function setupSkin($page, $params, $skin)
    {
        $skin->addMetaKeywords(array('php regular expression tester', 'php regex tester', 'php regular expressions', 'php regex debug', 'php regex'));
        $skin->setMetaDescription('PHP Regular Expression Tester: PHOCOA AJAX Demonstration');
        $skin->setTitle('PHP Regular Expression Tester: PHOCOA AJAX Demonstration');
    }
}
?>
