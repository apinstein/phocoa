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
 * WFSelectionCheckbox is used to create numerous checkboxes representing a selection of items in an array.
 *
 * A {@link WFDynamic} subclass that has specialized behavior for creating a bunch of checkboxes that represent the selected items managed by a {@link WFArrayController}.
 *
 * WFSelectionCheckbox is great for adding a column of checkboxes representing a "selection" of objects to perform an action on, for instance multiple deletes or bulk updates.
 * Another common use is for creating a list of checkboxes in a "search" situation where the checked items represent items whose condition you want to match (maybe generating an IN clause).
 *
 * <b>PHOCOA Builder Setup:</b>
 * 
 * Required:<br>
 * - See {@link WFDynamic} for required parameters.
 *
 * Optional:<br>
 * - {@link WFSelectionCheckbox::$labelKeyPath labelKeyPath}
 *
 * @todo Refactor to coalesce createWidgets and createSelectionWidgets
 * @todo Should WFSelectionCheckbox use WFCheckboxGroup internally for consistency?
 */
class WFSelectionCheckbox extends WFDynamic
{
    /**
     * @var boolean TRUE if the pre-render check has already run, false otherwise. The pre-render check simply updates the checked state of all managed checkboxes
     *              based on whether or not the object for each checkbox is selected in the arraycontroller.
     */
    private $preRenderCheckHasRun;
    /**
     * @var string The keyPath to use as the label for each checkbox.
     *             By default, this is NULL, and no labels will be output. Supply a valid keyPath for the class managed by your arrayController and the labels will be dynamically generated.
     *             This option is often used in conjunction with {@link WFDynamic::$oneShotMode oneShotMode}.
     */
    protected $labelKeyPath;

    function __construct($id, $page)
    {
        parent::__construct($id, $page);

        $this->preRenderCheckHasRun = false;
        $this->labelKeyPath = NULL;
    }


    /**
     *  Create the dynamic widgets.
     *
     *  This will be called AFTER the _PageDidLoad method... which is what we need to wait for before creating our widgets. WFPage makes this call.
     *
     *  Module code may need to call this function again, particularly if the content of they arrayController is changed by the current action.
     *
     *  @throws A variety of exceptions that can occur from createWidgets or createSelectionWidgets.
     */
    function createWidgets()
    {
        // check inputs
        if (!$this->arrayController instanceof WFArrayController) throw( new Exception("arrayController must be a WFArrayController instance."));
        
        // add checkboxes for selection
        $options = array(
            'groupMode' => array( 'custom' => array('iterate' => false, 'value' => true) ),
            'checkedValue' => array( 'custom' => array('iterate' => true, 'keyPath' => '#identifier#') ),
            'uncheckedValue' => array( 'custom' => array('iterate' => false, 'value' => '') )
        ); 
        if ($this->labelKeyPath)
        {
            $options['label'] = array( 'custom' => array('iterate' => true, 'keyPath' => $this->labelKeyPath) );
        }
        $this->setWidgetConfig($options);
        $this->setWidgetClass('WFCheckbox');
        $this->useUniqueNames = false;

        parent::createWidgets();
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
