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
 * A Label widget for our framework.
 * 
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * - {@link WFWidget::$value value} The message to display. Can use ValuePattern.
 * 
 * <b>Optional:</b><br>
 * - {@link WFMessageBox::$mode mode} Built-in types are Info, Warning, Error, and Confirm. You can supply any string, which will be used as the "class" of the div. This allows you to customize the icons for custom messages. Default is "Info".
 */
class WFMessageBox extends WFWidget
{
    const WFMESSAGEBOX_INFO = 'Info';
    const WFMESSAGEBOX_WARNING = 'Warning';
    const WFMESSAGEBOX_ERROR = 'Error';
    const WFMESSAGEBOX_CONFIRM = 'Confirm';

    /**
     * @var string The "mode" of the MessageBox.
     */
	protected $mode;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->mode = WFMessageBox::WFMESSAGEBOX_INFO;
    }

    /**
     *  Set the mode of the message box.
     *
     *  Options are WFMESSAGEBOX_INFO, WFMESSAGEBOX_WARNING, WFMESSAGEBOX_ERROR, or WFMESSAGEBOX_CONFIRM.
     *
     *  @param string The mode to use.
     */
    function setMode($m)
    {
        $this->mode = $m;
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            'mode',
            ));
    }
    function setupExposedBindings()
    {
        $myBindings = parent::setupExposedBindings();
        $newValBinding = new WFBindingSetup('value', 'The selected value for non-multiple select boxes.', array(WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_NAME => WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_VALUE));
        $newValBinding->setReadOnly(true);
        $newValBinding->setBindingType(WFBindingSetup::WFBINDINGTYPE_MULTIPLE_PATTERN);
        $myBindings[] = $newValBinding;
        return $myBindings;
    }

    function render($blockContent = NULL)
    {
        if ($this->hidden or empty($this->value))
        {
            return NULL;
        }
        else
        {
            $imgSrc = $this->getWidgetWWWDir() . '/';
            switch ($this->mode) {
                case WFMessageBox::WFMESSAGEBOX_INFO:
                    $imgSrc .= 'WFMessageBox_Icon_Info.jpg';
                    break;
                case WFMessageBox::WFMESSAGEBOX_CONFIRM:
                    $imgSrc .= 'WFMessageBox_Icon_Confirm.jpg';
                    break;
                case WFMessageBox::WFMESSAGEBOX_ERROR:
                    $imgSrc .= 'WFMessageBox_Icon_Error.jpg';
                    break;
                case WFMessageBox::WFMESSAGEBOX_WARNING:
                    $imgSrc .= 'WFMessageBox_Icon_Warning.jpg';
                    break;
            }
            $modeClass = "phocoaWFMessageBox_{$this->mode}";
            return "
<div id=\"{$this->id}\" style=\"margin: 5px 0; padding: 0;\"><div class=\"phocoaWFMessageBox {$modeClass}\">
<img class=\"phocoaWFMessageBox_icon\" src=\"{$imgSrc}\" />
<div class=\"phocoaWFMessageBox_message\">{$this->value}</div>
</div></div>
            ";
        }
    }


    function canPushValueBinding() { return false; }
}

?>
