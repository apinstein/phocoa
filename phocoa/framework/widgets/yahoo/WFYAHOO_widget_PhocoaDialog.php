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
 * A PHOCOA-custom YUI container for doing AJAX forms via WFModuleView.
 *
 * The YUI "Dialog" container is not suitable for use with existing forms (having their own buttons) or content that contains multiple forms. It also hides the panel as soon as the form is submitted.
 *
 * This Container widget works like so:
 * - Any forms inside the widget will automatically be submitted via XHR
 * - A "successCheck" callback allows examination of the result returned from the server.
 *  - If your callback determines that the result is an error, simply set "isError" and the container will automatically be updated with the result as HTML.
 *  - If your callback determines that the result is valid, you can set setCloseMessage(msgHTML, secsToAutoclose)
 * - PhocoaDialog also inherently supports the use of a WFModuleView as the source for the block. Simply add a WFModuleView as a child.
 * 
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * 
 * <b>Optional:</b><br>
 */
class WFYAHOO_widget_PhocoaDialog extends WFYAHOO_widget_Panel
{
    protected $moduleView;
    protected $deferModuleViewLoading;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->containerClass = 'PhocoaDialog';
        $this->moduleView = NULL;
        $this->deferModuleViewLoading = false;

        $this->importYahooJS("connection/connection-min.js");
        $this->importJS(WFWebApplication::webDirPath(WFWebApplication::WWW_DIR_FRAMEWORK) . '/js/yahoo-phocoa.js');
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            'deferModuleViewLoading' => array('true', 'false'),
            ));
    }

    public function setDeferModuleViewLoading($b)
    {
        $this->deferModuleViewLoading = $b;
    }

    /**
     *  To implement our automatic WFModuleView support, we need to detect when a child object of type WFModuleView is added.
     *
     *  @param object WFModuleView The object being added.
     */
    function addChild(WFView $view)
    {
        if ($view instanceof WFModuleView)
        {
            if ($this->moduleView) throw( new WFException("moduleView has already been set.") );
            $this->moduleView = $view;
        }
        else
        {
            throw( new WFException("WFYAHOO_widget_PhocoaDialog doesn't allow children other than a single WFModuleView") );
        }
    }

    function render($blockContent = NULL)
    {
        if ($this->hidden)
        {
            return NULL;
        }
        else
        {
            // set "body" from WFModuleView if there is one
            if ($this->moduleView and !$this->deferModuleViewLoading)
            {
                $this->setBody($this->moduleView->render());
            }

            // set up basic HTML
            $html = parent::render($blockContent);
            $script = "
<script type=\"text/javascript\">
//<![CDATA[

YAHOO.namespace('phocoa.widgets.PhocoaDialog');
YAHOO.phocoa.widgets.module.queueProps_PhocoaDialog_{$this->id} = function(o) {
    YAHOO.phocoa.widgets.module.queueProps_Panel_{$this->id}(o);
    // alert('id={$this->id}: queue Dialog props');
    // queue PhocoaDialog props here
}
YAHOO.phocoa.widgets.PhocoaDialog.init_{$this->id} = function() {
    YAHOO.phocoa.widgets.panel.init_{$this->id}();
    var phocoaDialog = PHOCOA.runtime.getObject('{$this->id}');
    phocoaDialog.cfg.setProperty('deferModuleViewLoading', " . ($this->deferModuleViewLoading ? 'true' : 'false') . ");
    phocoaDialog.cfg.setProperty('moduleViewInvocationPath', " . ($this->moduleView ? "'" . WWW_ROOT . '/' . $this->moduleView->invocationPath() . "'" : 'null') . ");
}
" .
( (get_class($this) == 'WFYAHOO_widget_PhocoaDialog') ? "YAHOO.util.Event.addListener(window, 'load', YAHOO.phocoa.widgets.PhocoaDialog.init_{$this->id});" : NULL ) . "
//]]>
</script>";
            // output script
            $html .= "\n{$script}\n";
            return $html;
        }
    }

    function canPushValueBinding() { return false; }
}

?>
