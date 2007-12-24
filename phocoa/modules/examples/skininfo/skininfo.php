<?php

require_once('framework/WFModule.php');
require_once('framework/WFSkin.php');

class skininfo extends WFModule
{
    protected $skinName;

    function defaultPage()
    {
        return 'skinTypes';
    }

    function previewSkin_SetupSkin($skin)
    {
        $skin->setTitle("Skin Preview:: {$this->skinName} / {$this->skinThemeName}");
    }
    function previewSkin_ParameterList()
    {
        return array('skinTypeName', 'skinName', 'skinThemeName', 'skinTemplateType');
    }
    function previewSkin_PageDidLoad($page, $parameters)
    {
        $skin = $this->invocation->rootSkin();
        try {
            $skin->setDelegateName($parameters['skinTypeName']);
            $skin->setSkin($parameters['skinName'], 'skinName');

            $this->skinName = $parameters['skinName'];

            if (empty($parameters['skinThemeName']))
            {
                $this->skinThemeName = $skin->valueForKey('skinManifestDelegate')->defaultTheme();
            }
            else
            {
                $this->skinThemeName = $parameters['skinThemeName'];
            }

            if (!empty($parameters['skinTemplateType']))
            {
                $skin->setTemplateType($parameters['skinTemplateType']);
            }
            
            $skin->setTheme($this->skinThemeName, 'skinThemeName');
        } catch (Exception $e) {
            throw new WFRequestController_NotFoundException($e->getMessage());
        }

        $page->assign('skinTypeName', $parameters['skinTypeName']);
        $page->assign('skinName', $parameters['skinName']);
        $page->assign('themeName', $parameters['skinThemeName']);
        $page->assign('skinTemplateType', $parameters['skinTemplateType']);

        $page->assign('skinThemes', $skin->valueForKey('skinManifestDelegate')->themes());
        $page->assign('skinTemplates', $skin->templateTypes());
        $page->assign('skinDefaultTheme', $skin->valueForKey('skinManifestDelegate')->defaultTheme());
        $page->assign('skinThemeName', $skin->valueForKey('skinThemeName'));
        $page->assign('skinThemeVars', print_r($skin->valueForKey('skinManifestDelegate')->loadTheme($skin->valueForKey('skinThemeName')), true));
    }

    function skinTypes_ParameterList()
    {
        return array('skinTypeName');
    }
    function skinTypes_PageDidLoad($page, $parameters)
    {
        $skinTypes = WFSkin::installedSkinTypes();
        $page->assign('skinTypes', $skinTypes);
        $skin = $this->invocation->rootSkin();
        try {
            if (!empty($parameters['skinTypeName']))
            {
                $skin->setDelegateName($parameters['skinTypeName']);
            }
        } catch (Exception $e) {
            throw new WFRequestController_NotFoundException($e->getMessage());
        }
        $page->assign('currentSkinType', $skin->delegateName());

        // show the info for the current skin delegate
        $contentInfo = array();
        foreach ($skin->namedContentList() as $contentName) {
            $contentInfo[$contentName] = print_r($skin->namedContent($contentName), true);
        }

        // get skin list for current skin type
        $skins = $skin->installedSkins();
        $page->assign('skins', $skins);
        
        $page->assign('namedContentInfo', $contentInfo);
        $page->assign('skinDelegateClassName', get_class($skin->valueForKey('delegate')));
    }
    function skinTypes_SetupSkin($skin)
    {
        $skin->setTitle("Installed Skin Types");
    }
}

?>
