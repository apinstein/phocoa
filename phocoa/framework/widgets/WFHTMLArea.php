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
 * A HTML WYSIWYG Editor widget for our framework.
 *
 * <b>PHOCOA Builder Setup:</b>
 *
 * Properties:
 * - {@link WFHTMLArea::$width width}
 * - {@link WFHTMLArea::$height height}
 * - {@link WFHTMLArea::$CustomConfigurationsPath CustomConfigurationsPath}
 * - {@link WFHTMLArea::$ToolbarSet ToolbarSet}
 *
 * Bindings:
 * <b>Required:</b><br>
 * - {@link WFWidget::$value value} or {@link WFSelect::$values values}, depending on {@link WFSelect::$multiple multiple}.
 */
class WFHTMLArea extends WFWidget
{
    /**
     * @var string The width of the HTML area. Default 100%.
     */
    protected $width;
    /**
     * @var string The height of the HTML area. Default 400px.
     */
    protected $height;

    /**
     * @var sring The URL to a custom config js file for the FCKEditor.
     */
    protected $CustomConfigurationsPath;
    /**
     * @var string THe toolbar set name to use. Default: NULL (whatever FCK does by default).
     */
    protected $ToolbarSet;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->value = NULL;
        $this->width = '100%';
        $this->height = '400';
        $this->CustomConfigurationsPath = 'Default';
        $this->ToolbarSet = NULL;
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            'width',
            'height',
            ));
    }

    function value()
    {
        return $this->value;
    }

    function setValue($v)
    {
        $this->value = $v;
    }

    function restoreState()
    {
        //  must call super
        parent::restoreState();

        if (isset($_REQUEST[$this->name]))
        {
            $this->setValue($_REQUEST[$this->name]);
        }
    }

    function render($blockContent = NULL)
    {
        $editor = new FCKEditor($this->name());
        $editor->Width = $this->width;
        $editor->Height = $this->height;
        $editor->BasePath = WWW_ROOT . '/www/framework/FCKEditor/';
        $editor->Value = $this->value();
        $editor->ToolbarSet = $this->ToolbarSet;
        if ($this->CustomConfigurationsPath)
        {
            $editor->Config['CustomConfigurationsPath'] = $this->CustomConfigurationsPath;
        }
        return $editor->CreateHtml();
    }

    function setupExposedBindings()
    {
        $myBindings = parent::setupExposedBindings();
        $myBindings[] = new WFBindingSetup('width', 'The width of the text area (as 100% or 250 for pixels).');
        $myBindings[] = new WFBindingSetup('height', 'The height of the text area (as 100% or 250 for pixels).');
        return $myBindings;
    }

    function canPushValueBinding() { return 'true'; }
}

?>
