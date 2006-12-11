<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package UI
 * @subpackage Widgets
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/**
 * A Select widget for our framework that will "jump" to a new URL when the user selects an item.
 *
 * This is implemented via JavaScript. You should account for non-JS browsers by having a button and appropriate action handler.
 *
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * - {@link WFWidget::$value value} or {@link WFSelect::$values values}, depending on {@link WFSelect::$multiple multiple}.
 * 
 * <b>Optional:</b><br>
 * - {@link WFJumpSelect::$baseURL baseURL}
 * - {@link WFSelect::$multiple multiple}
 * - {@link WFSelect::$contentValues contentValues}
 * - {@link WFSelect::$contentLabels contentLabels}
 * - {@link WFSelect::setOptions() options}
 * - {@link WFSelect::$visibleItems visibleItems}
 * - {@link WFSelect::$enabled enabled}
 */
class WFJumpSelect extends WFSelect
{
    /**
     * @var string The base URL that should be used before appending the "value" for the redirect. If this is left blank, supply the ENTIRE URL as the value.
     */
    protected $baseURL;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->baseURL = NULL;
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            'baseURL',
            ));
    }
    function setupExposedBindings()
    {
        $myBindings = parent::setupExposedBindings();
        $newValBinding = new WFBindingSetup('baseURL', 'The href value for the link.', array(WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_NAME => WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_VALUE));
        $newValBinding->setReadOnly(true);
        $newValBinding->setBindingType(WFBindingSetup::WFBINDINGTYPE_MULTIPLE_PATTERN);
        $myBindings[] = $newValBinding;

        return $myBindings;
    }

    // WFJumpSelect does not push the value back... 
    function canPushValueBinding()
    {
        return false;
    }
    /**
      * Set the base URL for the WFJumpSelect.
      *
      * @param string The base URL for the WFJumpSelect. If NULL, use the "value". If not NULL, append the "value" to baseURL.
      */
    function setBaseURL($url)
    {
        $this->baseURL = $url;
    }

    /**
      * Get the base URL for the WFJumpSelect.
      *
      * @return string The base URL for the WFJumpSelect.
      */
    function baseURL()
    {
        return $this->baseURL;
    }

    /**
      * Do an HTTP 302 redirect to the selected item. This is a helper function for the Action handler for non-JS clients.
      *
      * <code>
      * $page->outlet('myWFJumpSelect')->doJump();
      * </code>
      */
    function doJump()
    {
        header('Location: ' . $this->baseURL . $this->value);
        exit;
    }

    function render($blockContent = NULL)
    {
        // get control
        $output = parent::render($blockContent);

        // add onSelect handler
        $selectJS = " onChange=\"__phocoaWFJumpSelectGoto(this);\" ";
        $libraryJS = '
            <script language="JavaScript">
            <!--
            function __phocoaWFJumpSelectGoto(select)
            {
                var index;
                var initialSelection = \'' . $this->value . '\';

                for(index=0; index<select.options.length; index++)
                    if(select.options[index].selected)
                    {
                        if(select.options[index].value != initialSelection)
                        {
                            newURL = "' . $this->baseURL . '" + select.options[index].value; 
                            window.location.href = newURL;
                        }
                        break;
                    }
            }
            -->
            </script>
            ';

        $output = str_replace('<select ', $libraryJS . '<select ' . $selectJS, $output);

        return $output;
    }
}

?>
