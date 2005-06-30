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
 * Includes
 */
require_once('framework/widgets/WFDynamic.php');

/**
 * A {@link WFDynamic} subclass that has specialized behavior for creating a bunch of checkboxes that represent the selected items managed by a {@link WFArrayController}.
 * @todo Refactor to coalesce createWidgets and createSelectionWidgets
 */
class WFSelectionCheckbox extends WFDynamic
{
    /**
     * @var boolean TRUE if the pre-render check has already run, false otherwise. The pre-render check simply updates the checked state of all managed checkboxes
     *              based on whether or not the object for each checkbox is selected in the arraycontroller.
     */
    private $preRenderCheckHasRun;

    function __construct($id, $page)
    {
        parent::__construct($id, $page);

        $this->preRenderCheckHasRun = false;
    }


    /**
     *  Create the dynamic widgets.
     *
     *  This will be called AFTER the _PageDidLoad method... which is what we need to wait for before creating our widgets. WFPage makes this call.
     *
     *  @throws A variety of exceptions that can occur from createWidgets or createSelectionWidgets.
     */
    function createWidgets()
    {
        // check inputs
        $this->createSelectionWidgets($this->arrayController, $this->page->outlet($this->parentFormID), $this->id);
    }

    /**
     * A special version of {@link createDynamicWidgets} that is used for managing SELECTION of a list of objects.
     *
     * @param object A WFArrayController instance. The array of objects to manage selection for.
     * @param object The WFForm that this widget belongs to.
     * @param string The base name of the widget.
     * @param string A keyPath of the managed object class of the arrayController that will return the identifier {@link WFArrayController} for the object.
     */
    function createSelectionWidgets($arrayController, $parentForm, $widgetBaseName)
    {
        // add checkboxes for selection
        $options = array(
            'groupMode' => array( 'custom' => array('iterate' => false, 'value' => true) ),
            'checkedValue' => array( 'custom' => array('iterate' => true, 'keyPath' => '#identifier#') ),
            'uncheckedValue' => array( 'custom' => array('iterate' => false, 'value' => '') )
        ); 
        $this->createDynamicWidgets($arrayController, $parentForm, 'WFCheckbox', $widgetBaseName, false, $options);
    }

    /**
     *  Restore the state of all managed checkboxes.
     *
     *  For WFSelectionCheckbox, that means updating the selection status of the managed arrayController to reflect the checked status of the managed checkboxes.
     */
    function restoreState()
    {
        $checkedIDs = array();
        foreach ($this->createdWidgets as $cb) {
            $cb->restoreState();
            if ($cb->checked())
            {
                $checkedIDs[] = $this->arrayController->identifierValuesForHash($cb->value());
            }
        }
        $this->arrayController->setSelectionIdentifiers($checkedIDs);
    }

    /**
     *  Just before rendering, we want to update the checked status of all managed checkboxes to reflect the selection status of the arrayController.
     *
     *  @param string Blockcontent.
     *  @return string Rendered HTML.
     */
    function render($blockContent = NULL)
    {
        if (!$this->preRenderCheckHasRun)
        {
            $this->preRenderCheckHasRun = true;
            $this->updateSelectionCheckboxes();
        }
        return parent::render($blockContent);
    }

    /**
     *  Update the selected status of all checkboxes managed by this instance to reflect the current selection of the managed arrayController.
     */
    function updateSelectionCheckboxes()
    {
        foreach ($this->createdWidgets as $widget) {
            $widget->setChecked($this->arrayController->hashIsSelected($widget->checkedValue()));
        }
    }
}

?>
