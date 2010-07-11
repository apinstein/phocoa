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
 * - (none)
 * 
 * <b>Optional:</b><br>
 * - deferModuleViewLoading - boolean - True to defer loading of the WFModuleView content until the module is shown, false to load immediately. Default true.
 * - cacheModuleView - boolean - True to locally cache the result of the WFModuleView, false to re-load it every time the module is shown. Default false.
 * - inline - boolean TRUE to revert this "Panel" to act like a "Module" and render the content INLINE where it's placed. Very useful for embedding other containers inline.
 */
class WFYAHOO_widget_PhocoaDialog extends WFYAHOO_widget_Panel
{
    protected $moduleView;
    protected $deferModuleViewLoading;
    protected $cacheModuleView;
    protected $inline;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->containerClass = 'PhocoaDialog';
        $this->moduleView = NULL;
        $this->deferModuleViewLoading = false;
        $this->cacheModuleView = false;
        $this->inline = false;

        $this->yuiloader()->addModule('phocoaDialog',
                                        'js',
                                        NULL,
                                        WFWebApplication::webDirPath(WFWebApplication::WWW_DIR_FRAMEWORK) . '/js/yahoo-phocoa-min.js',
                                        array('connection', 'container', 'dragdrop', 'selector'),
                                        NULL,
                                        NULL,
                                        NULL
                                    );
        $this->yuiloader()->yuiRequire("phocoaDialog");
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            'deferModuleViewLoading' => array('true', 'false'),
            'cacheModuleView' => array('true', 'false')
            ));
    }

    public function setCacheModuleView($b)
    {
        $this->cacheModuleView = $b;
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

    function setInline($b)
    {
        $this->inline = (bool) $b;
        if ($this->inline)
        {
            $this->setValuesForKeys(array(
                                            'modal'     => false,
                                            'underlay'  => 'none',
                                            'close'     => false,
                                   ));
            $this->setRenderTo("'{$this->getInlinePlaceholderId()}'");
        }
        return $this;
    }
    protected function getInlinePlaceholderId()
    {
        return "{$this->id}_inlinePlaceholder";
    }

    function render($blockContent = NULL)
    {
        if ($this->hidden)
        {
            return NULL;
        }
        else
        {
            $html = '';

            // set "body" from WFModuleView if there is one
            if ($this->moduleView and !$this->deferModuleViewLoading)
            {
                $this->setBody($this->moduleView->render());
            }

            if ($this->inline)
            {
                $inlinePlaceholderId = $this->getInlinePlaceholderId();
                $html = "<div id=\"{$inlinePlaceholderId}\"></div>";
            }

            $html .= parent::render($blockContent);
            return $html;
        }
    }

    function initJS($blockContent)
    {
        $script = "";
        $script .= parent::initJS($blockContent);
        $script .= "
PHOCOA.namespace('widgets.{$this->id}.PhocoaDialog');
PHOCOA.widgets.{$this->id}.PhocoaDialog.queueProps = function(o) {
    PHOCOA.widgets.{$this->id}.Panel.queueProps(o); // queue parent props
    // alert('id={$this->id}: queue PhocoaDialog props');
    // queue PhocoaDialog props here
};
PHOCOA.widgets.{$this->id}.PhocoaDialog.init = function() {
    PHOCOA.widgets.{$this->id}.Panel.init();   // init parent
    var phocoaDialog = PHOCOA.runtime.getObject('{$this->id}');
    phocoaDialog.cfg.setProperty('deferModuleViewLoading', " . ($this->deferModuleViewLoading ? 'true' : 'false') . ");
    phocoaDialog.cfg.setProperty('cacheModuleView', " . ($this->cacheModuleView ? 'true' : 'false') . ");
    phocoaDialog.cfg.setProperty('moduleViewInvocationPath', " . ($this->moduleView ? "'" . WWW_ROOT . '/' . $this->moduleView->invocationPath() . "'" : 'null') . ");
    " . ($this->inline ? "phocoaDialog.element.setStyle({position:'static'});" : null) . "
};
" .
( (get_class($this) == 'WFYAHOO_widget_PhocoaDialog') ? "PHOCOA.widgets.{$this->id}.init = function() { PHOCOA.widgets.{$this->id}.PhocoaDialog.init(); };" : NULL );
        return $script;
    }
    function canPushValueBinding() { return false; }
}

?>
