<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package framework-base
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/**
 *  WFIncluding helps PHOCOA improve performance by providing autoload infrastructure.
 */
class WFIncluding
{
    /**
     *  PHOCOA autload callback.
     *
     *  This function will load any PHOCOA classes, interfaces, etc.
     *
     *  NOTE: autoload() will be called automatically for any new classes, interfaces, etc that are not yet in existence.
     *
     *  @param string The className that needs to be loaded.
     *  @return boolean TRUE if we handled the loading, false otherwise.
     */
    static public function autoload($className)
    {
        // I am guessing that using a hashmap will be faster than a big switch statement... no tests yet, but... in any case I'll do it this way first.
        // other option is to add a bunch of paths to include_path, but that seems like a bad idea... YES that's a VERY BAD IDEA. Searching paths is much more expensive 
        // than including files directly because it hits the filesystem a lot to find the files.
        static $autoloadClassmapCache = NULL;
        if ($autoloadClassmapCache == NULL)
        {
             $autoloadClassmapCache = array(
                'Smarty' => 'smarty/Smarty.class.php',
                'Spyc' => 'libs/spyc.php5',
                'Services_JSON' => 'libs/JSON.php',

                'Mail_Mailer' => 'framework/Mailer.php',

                'WFModel' => 'framework/generator/WFGenerator.php',
                'WFYaml' => 'framework/util/WFYaml.php',
                'WFJSON' => 'framework/util/WFJSON.php',
                'WFMenuTree' => 'framework/WFMenuItem.php',
                'WFMenuTreeBuilding' => 'framework/WFMenuItem.php',
                'WFMenuItem' => 'framework/WFMenuItem.php',
                'WFMenuItemBasic' => 'framework/WFMenuItem.php',
                'WFObject' => 'framework/WFObject.php',
                'WFKeyValueCoding' => 'framework/WFKeyValueCoding.php',
                'WFKeyValueValidators' => 'framework/util/WFKeyValueValidators.php',
                'WFRequestController' => 'framework/WFRequestController.php',
                'WFSkin' => 'framework/WFSkin.php',
                'WFModule' => 'framework/WFModule.php',
                'WFModuleInvocation' => 'framework/WFModule.php',

                // working
                'WFAction' => 'framework/WFRPC.php',
                'WFEvent' => 'framework/WFRPC.php',
                'WFClickEvent' => 'framework/WFRPC.php',
                'WFJSAction' => 'framework/WFRPC.php',
                'WFRPC' => 'framework/WFRPC.php',
                'WFActionResponse' => 'framework/WFRPC.php',
                'WFActionResponsePlain' => 'framework/WFRPC.php',
                'WFActionResponseJSON' => 'framework/WFRPC.php',
                'WFActionResponseXML' => 'framework/WFRPC.php',
                'WFActionResponseJavascript' => 'framework/WFRPC.php',

                'WFPage' => 'framework/WFPage.php',
                'WFPageRendering' => 'framework/WFPageRendering.php',
                'WFView' => 'framework/widgets/WFView.php',
                'WFException' => 'framework/WFException.php',
                'WFBinding' => 'framework/WFBinding.php',
                'WFBindingSetup' => 'framework/WFBinding.php',
                'WFSmarty' => 'framework/WFSmarty.php',
                'WFAuthorizationDelegate' => 'framework/WFAuthorization.php',
                'WFAuthorizationInfo' => 'framework/WFAuthorization.php',
                'WFAuthorizationException' => 'framework/WFAuthorization.php',
                'WFAuthorizationManager' => 'framework/WFAuthorization.php',
                'WFBooleanFormatter' => 'framework/widgets/WFFormatter.php',
                'WFDateTimeFormatter' => 'framework/widgets/WFFormatter.php',
                'WFUNIXDateFormatter' => 'framework/widgets/WFFormatter.php',
                'WFSQLDateFormatter' => 'framework/widgets/WFFormatter.php',
                'WFNumberFormatter' => 'framework/widgets/WFFormatter.php',
                'WFPaginator' => 'framework/WFPagination.php',
                'WFPagedArray' => 'framework/WFPagination.php',
                'WFPagedPropelQuery' => 'framework/WFPagination.php',
                'WFPagedCreoleQuery' => 'framework/WFPagination.php',
                'WFPagedData' => 'framework/WFPagination.php',
                'WFDieselSearch' => 'framework/WFDieselpoint.php',
                'WFDieselSearchHelper' => 'framework/WFDieselpoint.php',
                'WFDieselKeyword' => 'framework/widgets/WFDieselKeyword.php',
                'WFDieselNav' => 'framework/widgets/WFDieselNav.php',
                'WFDieselFacet' => 'framework/widgets/WFDieselFacet.php',
                'WFWidget' => 'framework/widgets/WFWidget.php',
                'WFDynarchMenu' => 'framework/widgets/WFDynarchMenu.php',
                'WFDynamic' => 'framework/widgets/WFDynamic.php',
                'WFSelectionCheckbox' => 'framework/widgets/WFSelectionCheckbox.php',
                'WFImage' => 'framework/widgets/WFImage.php',
                'WFForm' => 'framework/widgets/WFForm.php',
                'WFAutoForm' => 'framework/widgets/WFAutoForm.php',
                'WFLabel' => 'framework/widgets/WFLabel.php',
                'WFLink' => 'framework/widgets/WFLink.php',
                'WFMessageBox' => 'framework/widgets/WFMessageBox.php',
                'WFPassword' => 'framework/widgets/WFPassword.php',
                'WFTextField' => 'framework/widgets/WFTextField.php',
                'WFTextArea' => 'framework/widgets/WFTextArea.php',
                'WFHTMLArea' => 'framework/widgets/WFHTMLArea.php',
                'WFSubmit' => 'framework/widgets/WFSubmit.php',
                'WFSelect' => 'framework/widgets/WFSelect.php',
                'WFJumpSelect' => 'framework/widgets/WFJumpSelect.php',
                'WFTimeSelect' => 'framework/widgets/WFTimeSelect.php',
                'WFHidden' => 'framework/widgets/WFHidden.php',
                'WFCheckbox' => 'framework/widgets/WFCheckbox.php',
                'WFCheckboxGroup' => 'framework/widgets/WFCheckboxGroup.php',
                'WFRadio' => 'framework/widgets/WFRadio.php',
                'WFRadioGroup' => 'framework/widgets/WFRadioGroup.php',
                'WFUpload' => 'framework/widgets/WFUpload.php',
                'WFBulkUpload' => 'framework/widgets/WFBulkUpload.php',
                'WFBulkUploadFile' => 'framework/widgets/WFBulkUpload.php',
                'WFPaginatorNavigation' => 'framework/widgets/WFPaginatorNavigation.php',
                'WFPaginatorSortLink' => 'framework/widgets/WFPaginatorSortLink.php',
                'WFPaginatorSortSelect' => 'framework/widgets/WFPaginatorSortSelect.php',
                'WFPaginatorState' => 'framework/widgets/WFPaginatorState.php',
                'WFModuleView' => 'framework/widgets/WFModuleView.php',
                'WFTabView' => 'framework/widgets/WFTabView.php',
                'WFTableView' => 'framework/widgets/WFTableView.php',
                'WFAppcelerator' => 'framework/widgets/WFAppcelerator.php',
                'WFYAHOO' => 'framework/widgets/yahoo/WFYAHOO.php',
                'WFYAHOO_yuiloader' => 'framework/widgets/yahoo/WFYAHOO.php',
                'WFYAHOO_widget_TreeView' => 'framework/widgets/yahoo/WFYAHOO_widget_TreeView.php',
                'WFYAHOO_widget_TreeViewNode' => 'framework/widgets/yahoo/WFYAHOO_widget_TreeView.php',
                'WFYAHOO_widget_Module' => 'framework/widgets/yahoo/WFYAHOO_widget_Module.php',
                'WFYAHOO_widget_Overlay' => 'framework/widgets/yahoo/WFYAHOO_widget_Overlay.php',
                'WFYAHOO_widget_Panel' => 'framework/widgets/yahoo/WFYAHOO_widget_Panel.php',
                'WFYAHOO_widget_Dialog' => 'framework/widgets/yahoo/WFYAHOO_widget_Dialog.php',
                'WFYAHOO_widget_PhocoaDialog' => 'framework/widgets/yahoo/WFYAHOO_widget_PhocoaDialog.php',
                'WFYAHOO_widget_Logger' => 'framework/widgets/yahoo/WFYAHOO_widget_Logger.php',
                'WFYAHOO_widget_Menu' => 'framework/widgets/yahoo/WFYAHOO_widget_Menu.php',
                'WFYAHOO_widget_AutoComplete' => 'framework/widgets/yahoo/WFYAHOO_widget_AutoComplete.php',
                'WFYAHOO_widget_Tab' => 'framework/widgets/yahoo/WFYAHOO_widget_TabView.php',
                'WFYAHOO_widget_TabView' => 'framework/widgets/yahoo/WFYAHOO_widget_TabView.php',
                'WFPaginatorPageInfo' => 'framework/widgets/WFPaginatorPageInfo.php',
                'WFValueTransformer' => 'framework/ValueTransformers/WFValueTransformer.php',
                'WFNegateBooleanTransformer' => 'framework/ValueTransformers/WFNegateBooleanTransformer.php',
                'WFIsEmptyTransformer' => 'framework/ValueTransformers/WFIsEmptyTransformer.php',
                'WFIsNotEmptyTransformer' => 'framework/ValueTransformers/WFIsNotEmptyTransformer.php',
                'WFUrlencodeTransformer' => 'framework/ValueTransformers/WFUrlencodeTransformer.php',
                'WFObjectController' => 'framework/WFObjectController.php',
                'WFArrayController' => 'framework/WFArrayController.php',
                'WFError' => 'framework/WFError.php',
                'WFExceptionReporting' => 'framework/WFExceptionReporting.php',
                'WFUnixDateFormatter' => 'framework/widgets/WFFormatter.php',
                'WFSQLDateFormatter' => 'framework/widgets/WFFormatter.php',
                'WFNumberFormatter' => 'framework/widgets/WFFormatter.php',
                'FCKEditor' => FRAMEWORK_DIR . '/wwwroot/www/framework/FCKEditor/fckeditor.php',
            );
        }

        if (isset($autoloadClassmapCache[$className]))
        {
            // including absolute paths is much faster than relative paths to the include_path dirs because one doesn't have to walk the include path.
            // so, if it's a framework/ dir, then include it absolutely! Otherwise, let require figure it out.
            if (substr($autoloadClassmapCache[$className], 0, 10) == 'framework/')
            {
                $requirePath = FRAMEWORK_DIR . '/' . $autoloadClassmapCache[$className];
            }
            else
            {
                $requirePath = $autoloadClassmapCache[$className];
            }
            require($requirePath);
            return true;
        }

        return false;
    }
}

?>
