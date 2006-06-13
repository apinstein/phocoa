<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package framework-base
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/**
 *  WFIncluding helps PHOCOA improve performance by providing autoload infrastructure and more efficient include_once and require_once routines.
 *
 *  WFIncluding contains static methods that re-implement require_once() and include_once() because the versions in PHP perform very poorly.
 *
 *  PHP's versions assume that the include_path could have changed since the first xxx_once() call, which means they re-search the include_path for the file anyway.
 *
 *  Our version keeps track of the include_path that existed when xxx_once() was called, eliminating this problem.
 */
class WFIncluding
{
    static protected $alreadyIncludedFiles = array();

    /**
     *  Our version of include_once. Since include_once is a language construct, we cannot use that name for our function name.
     *
     *  NOTE: one thing that this function fails to do that include_once does is deal with paths relative to the current script file.
     *  For instance, include_once("FileA.php") from FileB.php where they're both in the same dir normally works. It will fail here
     *  since we don't know where FileB.php is when we're called. So, to use includeOnce, you must supply a path that is absolute,
     *  or relative to one of the include_path pieces, otherwise it will fail.
     *
     *  @return mixed The return value from include_once.
     *  @param string The file to include. Can be absolute or relative to one of the paths in include_path.
     */
    static public function includeOnce($file)
    {
        // handle absolute paths - just let PHP do it.
        $firstChr = substr($file, 0, 1);
        if ($firstChr == '/' or $firstChr == '\\')
        {
            return include_once($testPath);
        }

        // handle includes relative to include_path
        $include_path = ini_get('include_path');
        $searchPaths = explode(':', $include_path);

        if (isset(WFIncluding::$alreadyIncludedFiles[$include_path][$file]))
        {
            //print "Already included '$file'\n";
            return NULL;
        }

        foreach ($searchPaths as $path) {
            $testPath = "{$path}/{$file}";
            if (file_exists($testPath))
            {
                //print "Including '$testPath'\n";
                $retVal = include_once($testPath);
                WFIncluding::$alreadyIncludedFiles[$include_path][$file] = true;
                return $retVal;
            }
        }
        //print "Couldn't include file '$file' because it wasn't found in: {$include_path}\n";
        return NULL;
    }

    /**
     *  Our version of require_once. Since require_once is a language construct, we cannot use that name for our function name.
     *
     *  NOTE: one thing that this function fails to do that include_once does is deal with paths relative to the current script file.
     *  For instance, include_once("FileA.php") from FileB.php where they're both in the same dir normally works. It will fail here
     *  since we don't know where FileB.php is when we're called. So, to use includeOnce, you must supply a path that is absolute,
     *  or relative to one of the include_path pieces, otherwise it will fail.
     *
     *  @param string The file to require. Can be absolute or relative to one of the paths in include_path.
     *  @return mixed The return value from include_once.
     */
    static public function requireOnce($file)
    {
        // handle absolute paths - just let PHP do it.
        $firstChr = substr($file, 0, 1);
        if ($firstChr == '/' or $firstChr == '\\')
        {
            return require_once($file);
        }

        // handle includes relative to include_path
        $include_path = ini_get('include_path');
        $searchPaths = explode(':', $include_path);

        if (isset(WFIncluding::$alreadyIncludedFiles[$include_path][$file]))
        {
            //print "Already included '$file'\n";
            return NULL;
        }

        foreach ($searchPaths as $path) {
            $testPath = "{$path}/{$file}";
            if (file_exists($testPath))
            {
                //print "Including '$testPath'\n";
                $retVal = require_once($testPath);
                WFIncluding::$alreadyIncludedFiles[$include_path][$file] = true;
                return $retVal;
            }
        }
        // if we get to here, one last-ditch effort to try to include it. This won't work, but it will at least fail the way require_once fails.
        return require_once($file);
    }

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

                'Mail_Mailer' => 'framework/Mailer.php',
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
                'WFPage' => 'framework/WFPage.php',
                'WFPageRendering' => 'framework/WFPageRendering.php',
                'WFView' => 'framework/widgets/WFView.php',
                'WFException' => 'framework/WFException.php',
                'WFBinding' => 'framework/WFBinding.php',
                'WFSmarty' => 'framework/WFSmarty.php',
                'WFAuthorizationDelegate' => 'framework/WFAuthorization.php',
                'WFAuthorizationInfo' => 'framework/WFAuthorization.php',
                'WFAuthorizationException' => 'framework/WFAuthorization.php',
                'WFAuthorizationManager' => 'framework/WFAuthorization.php',
                'WFBooleanFormatter' => 'framework/widgets/WFFormatter.php',
                'WFUNIXDateFormatter' => 'framework/widgets/WFFormatter.php',
                'WFSQLDateFormatter' => 'framework/widgets/WFFormatter.php',
                'WFNumberFormatter' => 'framework/widgets/WFFormatter.php',
                'WFPaginator' => 'framework/WFPagination.php',
                'WFPagedArray' => 'framework/WFPagination.php',
                'WFPagedPropelQuery' => 'framework/WFPagination.php',
                'WFPagedCreoleQuery' => 'framework/WFPagination.php',
                'WFPagedData' => 'framework/WFPagination.php',
                'WFDieselSearch' => 'framework/WFDieselpoint.php',
                'WFWidget' => 'framework/widgets/WFWidget.php',
                'WFDieselFacet' => 'framework/widgets/WFDieselFacet.php',
                'WFDynarchMenu' => 'framework/widgets/WFDynarchMenu.php',
                'WFDynamic' => 'framework/widgets/WFDynamic.php',
                'WFSelectionCheckbox' => 'framework/widgets/WFSelectionCheckbox.php',
                'WFImage' => 'framework/widgets/WFImage.php',
                'WFForm' => 'framework/widgets/WFForm.php',
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
                'WFPaginatorNavigation' => 'framework/widgets/WFPaginatorNavigation.php',
                'WFPaginatorSortLink' => 'framework/widgets/WFPaginatorSortLink.php',
                'WFPaginatorState' => 'framework/widgets/WFPaginatorState.php',
                'WFModuleView' => 'framework/widgets/WFModuleView.php',
                'WFTabView' => 'framework/widgets/WFTabView.php',
                'WFPaginatorPageInfo' => 'framework/widgets/WFPaginatorPageInfo.php',
                'WFValueTransformer' => 'framework/ValueTransformers/WFValueTransformer.php',
                'WFNegateBooleanTransformer' => 'framework/ValueTransformers/WFNegateBooleanTransformer.php',
                'WFIsEmptyTransformer' => 'framework/ValueTransformers/WFIsEmptyTransformer.php',
                'WFIsNotEmptyTransformer' => 'framework/ValueTransformers/WFIsNotEmptyTransformer.php',
                'WFObjectController' => 'framework/WFObjectController.php',
                'WFArrayController' => 'framework/WFArrayController.php',
                'WFError' => 'framework/WFError.php',
                'WFExceptionReporting' => 'framework/WFExceptionReporting.php',
                'WFUnixDateFormatter' => 'framework/widgets/WFFormatter.php',
                'WFSQLDateFormatter' => 'framework/widgets/WFFormatter.php',
                'WFNumberFormatter' => 'framework/widgets/WFFormatter.php',
                'FCKEditor' => APP_ROOT . '/wwwroot/www/framework/FCKEditor/fckeditor.php',
            );
        }

        if (isset($autoloadClassmapCache[$className]))
        {
            // including absolute paths is much faster than relative paths to the include_path dirs because one doesn't have to walk the include path.
            // so, if it's a framework/ dir, then include it absolutely! Otherwise, let requireOnce figure it out.
            if (substr($autoloadClassmapCache[$className], 0, 10) == 'framework/')
            {
                WFIncluding::requireOnce(FRAMEWORK_DIR . '/' . $autoloadClassmapCache[$className]);
            }
            else
            {
                WFIncluding::requireOnce($autoloadClassmapCache[$className]);
            }
            return true;
        }

        return false;
    }
}

?>
