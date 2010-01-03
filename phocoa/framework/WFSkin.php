<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * Skin System.
 *
 * A collection of classes and infrastructure for supporting skins of any layout, with multiple "themes" per skin.
 *
 * The system ships with a simple skin, "default", which is a simple wrapper. You can create your own skins.
 *
 * @package framework-base
 * @subpackage Skin
 * @copyright Alan Pinstein 2005
 * @author Alan Pinstein <apinstein@mac.com>
 * @version $Id: skin.php,v 1.37 2005/03/23 20:31:01 alanpinstein Exp $
 */

/**
 * Skin Manifest abstract interface. Each skin will need to have a concrete subclass to provide the system with needed information about itself.
 *
 * For a full explanation on the Skin infrastructure, including how to set up Skin Types, Skins, and Themes, see {@link WFSkin}.
 *
 * @see WFSkin
 *
 */
abstract class WFSkinManifestDelegate extends WFObject
{
    /**
     * Get the DEFAULT theme of for skin.
     *
     * This method is REQUIRED.
     *
     * @return string - The DEFAULT theme for this skin.
     */
    abstract function defaultTheme();

    /**
     * Get a list of themes for the skin.
     *
     * This is an optional method.
     *
     * @return array A list of all themes supported for this skin.
     */
    function themes() { return array(); }

    /**
     * Load the theme information for this skin.
     *
     * The theme information is a simple associative array of variables used by the skin. Good uses for this include
     * colorscheme information.
     *
     * This is an optional method.
     *
     * @param string $theme Theme information to retrieve.
     * @return assoc_array Various name/value pairs to be used in the templates for the skin.
     */
    function loadTheme($theme) { return array(); }
}

/**
 * Delegate interface for the skin object.
 *
 * The skin delegate provides the skin system with a way of extending the skin's capabilities. Essentially, the WFSkin object is a framework for building "themed" pages.
 * The parts of each skin that are provided by the skin infrastructure can be easily customized using the skin delegate system.
 *
 * The main web application mechanism always uses the skin delegate provided by {@link WFApplicationDelegate}. However, an application may have multiple skin delegates
 * for multiple skinned usages. For instance, maybe you have a need to send skinned email, but the skins for email have a different setup than the normal web site.
 * In this case, you could create a skin object and provide a specialized skin delegate to handle the skinnable email function.
 * 
 * You may also find that you need more template types besides "normal" and "raw". WFSkinDelegate allows you to manifest additional templateTypes(). Each skin
 * you create for that WFSkinDelegate will of course need to implement all of the templateTypes() that the skinDelegate supports.
 */
class WFSkinDelegate
{
    /**
     * Retreive the "named" content from the skin delegate.
     *
     * The "named content" mechanism is the way by which individual applications using this framework can add additional content sections
     * for use in their skins. By default, only a HEAD and BODY section exist within the skin.
     * Individual applications can use the named content mechanism to supply skin-specific information such as NAVIGATION LINKS, COPYRIGHT DISCLAIMERS, etc.
     *
     * @param string Name of the content to retrieve.
     * @param assoc_array Optional parameter list. Name/value pairs to pass on to content generator.
     * @return mixed The named content as provided byt he skin delegate.
     */
    function namedContent($name, $params = NULL) {}

    /**
     * Get a list of all named content for the skin delegate.
     *
     * @return array A list of all named content items in the catalog for this skin delegate.
     */
    function namedContentList() {}

    /**
     * A delegate method to allow the delegate object to load default values for certain skin properties such as:
     * skin, skinTheme, metaDescription, metaKeywords, title etc.
     * Example:
     *
     * $skin->setSkin('exampleskin2');
     * $skin->setTheme('red');
     * $skin->setMetaDescription('This is the default description.');
     * $skin->addMetaKeywords(array('more', 'keywords'));
     * $skin->setTitle('Page Title');
     *
     * @param object WFSkin The skin object to load defaults for.
     */
    function loadDefaults($skin) {}

    /**
     *  Get a list of the additional template types (besides "normal" and "raw") that are supported by this skin.
     *
     *  @return array An array of strings; each is a template type that is supported by this skin.
     *                For each skin, there must be a template_<templateType>.tpl file for each manifested template type.
     */
    function templateTypes() { return array(); }

    /**
     * Callback method which is called just before the skin is rendered.
     *
     * This allows your skin delegate to calculate anything it might need to pass on to the skin for use in rendering.
     *
     * @param object WFSkin The skin object that will be rendered.
     */
    function willRender($skin) {}
}

/**
 * Main skin class for managing skin wrappers around the content.
 *
 * The Web Application's WFRequestController object always uses exactly one skin instance to render the page.
 * However, your application may choose to create other skin instances to use the infrastructure for things like HTML email, etc.
 *
 * The Skin mechanism is broken down into three layers. Each layer provides the ability to swap behaviors/looks at runtime.
 * For each request, one of each layer must be specified.
 *
 * 1. Skin Type -- i.e., which SkinDelegate is used. The Skin Delegate provides the skin with its catalog of behaviors, i.e., menus, footers, etc.
 *                 Each skin type is unique, and skins must be written specifically for each Skin Type.
 *                 Most web sites have just one skin type, that handles the elements appropriate for the skins of that application.
 *                 However, sometimes there is a need for a single site to have multiple skins. For instance, the public site may have different
 *                 navigational needs than the back-end admin interface.
 *                 A {@link WFSkinDelegate} is implemented for each skin type an tells the system what data is available for the skin type. 
 *                 Skin Types, however, do NOT provide any layout or style.
 *                 By default, each Skin Type implements only the "normal" template type. Your Skin Type may need additional layouts. For instance, printer-friendly
 *                 or minimal layouts used for popups. Your skin can support additiona template types via {@link WFSkinDelegate::templateTypes() templateTypes()}.
 *                 Every Skin for a Skin Type must have a template file for all template types to ensure proper operation.
 * 2. Skin -- A skin provides basic layout for a given Skin Type. Skins are specific for Skin Types, since they necessarily know about the 
 *            data types offered by a particular skin type, via its Skin Delegate.
 *            Each Skin resides in its own directory inside the Skin Type directory that it belongs to.
 *            Each Skin thus provides a template file that implements a layout. Skins may also have multiple Skin Themes.
 *            Each Skin has a {@link WFSkinManifestDelegate SkinManifestDelegate} which tells the system which themes are available, and which theme to use by default.
 * 3. Skin Themes -- It may be desirable for a skin to have multiple colorschemes or other minor "thematic" differences. Each skin must have at least one theme.
 *                   Infrastructure is provided so that the SkinManifestDelegate can easily supply different data to the skin based on the theme. This allows easy creation
 *                   of colorschemes or other thematic differences.
 *
 * <pre>
 * Skin Directory Structure:
 *
 * The skins have a specified, hierarchical directory structure, based in the "skins" directory.
 * skins/ - Contains only directories; each directory represents a Skin Type.
 * skins/&lt;skinType&gt;/ - For each skin type, pick a unique name and create a directory.
 * skins/&lt;skinType&gt;/&lt;skinType&gt;_SkinDelegate.php - A php file containing exactly one class, named &lt;skinType&gt;_SkinDelegate, that is the {@link WFSkinDelegate} for the Skin Type.
 * skins/&lt;skinType&gt;/&lt;skinName&gt;/ - Also in the skinType directory are other directories, one for each skin that can be used for the Skin Type.
 * skins/&lt;skinType&gt;/&lt;skinName&gt;/&lt;skinName&gt;_SkinManifestDelegate.php - The {@link WFSkinManifestDelegate} for the skin &lt;skinName&gt;.
 * skins/&lt;skinType&gt;/&lt;skinName&gt;/* - Other files in here are the various tpl and css files used for this skin.
 * skins/&lt;skinType&gt;/&lt;skinName&gt;/www/ - Web root of the skin. Nothing actually goes in this folder but other folders.
 * skins/&lt;skinType&gt;/&lt;skinName&gt;/www/shared/* - Files that need to be accesible to the WWW and are shared by multiple themes of this skin go here.
 * skins/&lt;skinType&gt;/&lt;skinName&gt;/www/&lt;themeName&gt;/* - Files that need to be accessible to the WWW and are specific to a theme go here.  Each theme has its own folder to contain "themed" versions of resources. Typically every theme has the same set of resources, but of course customized for that theme.
 *
 * To use WWW visible items in your pages, simply use {$skinDir}/myImage.jpg and {$skinDirShared}/mySharedImage.jpg in your templates. The skin system automatically assigns these vars.
 * skinDir maps to skins/&lt;skinType&gt;/&lt;skinName&gt;/www/&lt;themeName&gt;/
 * skinDirShared maps to skins/&lt;skinType&gt;/&lt;skinName&gt;/www/shared/
 * </pre>
 *
 * @see WFSkinDelegate, WFSkinManifestDelegate
 */
class WFSkin extends WFObject
{
    /**
     * The "normal" templateType: template_normal.tpl
     */
    const SKIN_WRAPPER_TYPE_NORMAL  = 'normal';
    /**
     * The "raw" templateType: the exact page contents will be displayed; equivalent to using no skin.
     */
    const SKIN_WRAPPER_TYPE_RAW     = 'raw';

    /**
     * @var string The skin delegate name to use for this instance.
     */
    protected $delegateName;
    /**
     * @var string The skin to use for this instance.
     */
    protected $skinName;
    /**
     * @var string The theme of the skin to use.
     */
    protected $skinThemeName;
    /**
     * @var object The {@link WFSkinDelegate delegate object} for this skin.
     */
    protected $delegate;
    /**
     * @var object The SkinManifestDelegate for the current skin.
     */
    protected $skinManifestDelegate;
    /**
     * @var string The body content for the skin. This is the only "predefined" content element of the skin infrastructure.
     */
    protected $body;
    /**
     * @var string The TITLE of the skin. This will be used automatically as the HTML title.
     */
    protected $title;
    /**
     * @var array A list of META KEYWORDS for HTML skins.
     */
    protected $metaKeywords;
    /**
     * @var string The META DESCRIPTION for HTML skins.
     */
    protected $metaDescription;
    /**
     * @var integer Skin wrapper type. One of {@link WFSkin::SKIN_WRAPPER_TYPE_NORMAL}, {@link WFSkin::SKIN_WRAPPER_TYPE_RAW}, or a custom type.
     */
    protected $templateType;
    /**
     * @var string The absolute filesystem path to a tpl file that is automatically added to the "head" element of all skins of this skin type.
     */
    protected $headTemplate;
    /**
     * @var array An array of strings of things that needed to be added to the <head> section.
     */
    protected $headStrings;

    function __construct()
    {
        // determine which skin to use
        $wa = WFWebApplication::sharedWebApplication();
        $this->skinName = 'default';

        $this->delegate = NULL;
        $this->skinManifestDelegate = NULL;
        $this->body = NULL;
        $this->templateType = WFSkin::SKIN_WRAPPER_TYPE_NORMAL;

        $this->title = NULL;
        $this->metaKeywords = array();
        $this->metaDescription = NULL;
        $this->headStrings = array();
        $this->headTemplate = WFWebApplication::appDirPath(WFWebApplication::DIR_SMARTY) . '/head.tpl';
    }

    /**
     * Set the which template file of the current skin will be used to render the skin.
     *
     * - (default) {@link WFSkin::SKIN_WRAPPER_TYPE_NORMAL}, which maps to "template.tpl".
     * - {@link WFSkin::SKIN_WRAPPER_TYPE_RAW}, which will output the body contents only. This is logically equivalent to using no skin.
     * - Any other string you pass will use the file "template_<template_type>.tpl in the skin directory.
     *
     * Potential uses for this include:
     *
     * - Using {@link WFSkin::SKIN_WRAPPER_TYPE_RAW} to return HTML snippets that will be used in AJAX callback
     * - Using a custom "minimal" file that is used for popup windows where there is not enough real estate for the full skin.
     * - Using a custom "mobile" file that would be used for mobile devices.
     *
     * Any custom templates must be manifested by the skin delegate {@link WFSkinDelegate::tempateTypes() templateTypes()} method.
     *
     * @param string The name of the template to use. One of {@link WFSkin::SKIN_WRAPPER_TYPE_NORMAL}, {@link WFSkin::SKIN_WRAPPER_TYPE_RAW}, or a custom string.
     * @throws object WFException if the template of the given name does not exist for this skin
     */
    function setTemplateType($templateType)
    {
        $allowedTemplates = $this->templateTypes();
        if (!in_array($templateType, $allowedTemplates)) throw( new WFException("Template type: '{$templateType}' does not exist for skin '" . $this->skinName() . "'.") );
        $this->templateType = $templateType;
    }

    /**
     *  Get a list of all template types available for this skin.
     *
     *  This list will include WFSkin::SKIN_WRAPPER_TYPE_NORMAL, WFSkin::SKIN_WRAPPER_TYPE_RAW, and any custom template types manifested by the skin type delegate (skin manifest delegate).
     *
     *  @return array An array of strings with the names of all valid template types.
     */
    function templateTypes()
    {
        $allowedTemplates = array(WFSkin::SKIN_WRAPPER_TYPE_NORMAL, WFSkin::SKIN_WRAPPER_TYPE_RAW);
        // call skin type delegate to get list of template types -- delegate implements application-specific logic.
        if (is_object($this->delegate) && method_exists($this->delegate, 'templateTypes')) {
            $allowedTemplates = array_merge($allowedTemplates, $this->delegate->templateTypes());
        }
        return $allowedTemplates;
    }

    /**
     *  Set the skin's delegate by passing the NAME of the skin delegate.
     *
     *  This function will look for the skin delegate in the appropriate place, instantiate it, and set it up for this skin instance.
     *
     *  NOTE: Calling this function may overwrite any existing skin settings, since the loadDefaults() function may overwrite title, meta tags, etc.
     *  For best results, always call setDelegateName() BEFORE making adjustments to the WFSkin object.
     *
     *  @param string The NAME of the Skin Type.
     *  @throws object Exception if the skin delegate does not exist, or it does not contain the skin delegate class.
     */
    function setDelegateName($skinDelegateName)
    {
        $this->delegateName = $skinDelegateName;
        // change name to our convention
        $skinDelegateFileClassName = $skinDelegateName . '_SkinDelegate';
        // load skin class -- in lieu of require_once, do this
        if (!class_exists($skinDelegateFileClassName))
        {
            $skinsDir = WFWebApplication::appDirPath(WFWebApplication::DIR_SKINS);
            $skinDelegatePath = $skinsDir . '/' . $skinDelegateName . '/' . $skinDelegateFileClassName . '.php';
            if (!file_exists($skinDelegatePath)) throw( new Exception("Skin Delegate {$skinDelegateName} file {$skinDelegatePath} does not exist.") );
            require($skinDelegatePath);
        }
        if (!class_exists($skinDelegateFileClassName)) throw( new Exception("Skin Delegate class {$skinDelegateFileClassName} does not exist.") );
        $this->setDelegate(new $skinDelegateFileClassName());
    }

    /**
     *  Get the name of the Skin Type for the current instance.
     *
     *  @return string The name of the current skin type.
     */
    function delegateName()
    {
        return $this->delegateName;
    }

    /**
     *  Get the delegate instance.
     *
     *  @return object WFSkinDelegate A WFSkinDelegate instance.
     */
    function delegate()
    {
        return $this->delegate;
    }

    /**
     * Assign a skin delegate for this instance.
     * @param object The skin delegate.
     */
    function setDelegate($skinDelegate)
    {
        $this->delegate = $skinDelegate;
        $this->loadDefaults();
    }

    /**
     * Set the skin to the given name. Will automatically load the skin and its default theme.
     * @param string The name of the skin to use.
     */
    function setSkin($skinName)
    {
        $this->skinName = $skinName;
        $this->loadSkin();
    }

    /**
     *  Set the theme to use.
     *
     *  @param string The name of the theme of the skin to use.
     */
    function setTheme($skinThemeName)
    {
        $this->skinThemeName = $skinThemeName;
    }

    function getskinThemeName()
    {
        return $this->skinThemeName;
    }

    /**
     *  Get the current skin name
     *
     *  @return string The name of the current skin.
     */
    function skinName()
    {
        return $this->skinName;
    }

    /**
     * Load the current skin.
     * @internal
     */
    function loadSkin()
    {
        // load the current skin
        $skinsDir = WFWebApplication::appDirPath(WFWebApplication::DIR_SKINS);
        $skinManifestDelegateFileClassName = $this->skinName . '_SkinManifestDelegate';
        
        // in lieu of require_once
        if (!class_exists($skinManifestDelegateFileClassName))
        {
            $skinManifestDelegatePath = $skinsDir . '/' . $this->delegateName . '/' . $this->skinName . '/' . $skinManifestDelegateFileClassName . '.php';
            if (!file_exists($skinManifestDelegatePath)) throw( new Exception("Skin manifest delegate file does not exist: $skinManifestDelegatePath.") );
            require($skinManifestDelegatePath);
        }

        // instantiate the skin manifest delegate
        if (!class_exists($skinManifestDelegateFileClassName)) throw( new Exception("Skin manifest delegate class does not exist: {$skinManifestDelegateFileClassName}."));
        $this->skinManifestDelegate = new $skinManifestDelegateFileClassName();

        // make sure a theme is selected
        if (empty($this->skinThemeName)) $this->skinThemeName = $this->skinManifestDelegate->defaultTheme();
    }

    /**
     *  Add a string that needs to go in the page's head section.
     *
     *  @param string The string to go in the head section.
     */
    function addHeadString($string)
    {
        // de-duplicate
        $this->headStrings[$string] = $string;
    }

    /**
     * Set the content for the skin to wrap. Typically this is HTML but could be anything.
     * @param string The content of the skin.
     */
    function setBody($html)
    {
        $this->body = $html;
    }

    /**
     * Set the title of the page. This is the HTML title if you are building an HTML skin.
     * @param string The title of the page.
     */
    function setTitle($title)
    {
        $this->title = htmlentities($title);
    }

    /**
     * Set the template file to be added to the "head" element of every page. Defaults to the built-in template file that sets up various PHOCOA things.
     *
     * If you want to include the default head content, use {$skinPhocoaHeadTpl} in your custom head template file.
     *
     * Typically this function would be called from your SkinDelegate's loadDefaults() function.
     *
     * @param string Absolute path to new head template file.
     */
    function setHeadTemplate($path)
    {
        $this->headTemplate = $path;
    }

    /**
     * Add meta keywords to the skin.
     * @param array A list of keywords to add.
     */
    function addMetaKeywords($keywords)
    {
        $this->metaKeywords = array_merge($keywords, $this->metaKeywords);
    }

    /**
     * Set the META DESCRIPTION of the page.
     * @param string The description of the page.
     */
    function setMetaDescription($description)
    {
        $this->metaDescription = $description;
    }

	/**
	 * @deprecated
     * @see getSkinThemeAssetsDir()
	 */
	function getSkinDir()
	{
		return $this->getSkinThemeAssetsDir();
	}

	/**
     * @deprecated
     * @see getSkinSharedAssetsDir
	 */
	function getSkinDirShared()
	{
        return $this->getSkinSharedAssetsDir();
	}

	/**
	 * @return string www-accessible path to the skin type assets dir (<skintype>/www)
	 */
	function getSkinTypeAssetsDir()
	{
		return WWW_ROOT . '/skins/' . $this->delegateName . '/www';
	}

	/**
	 * @return string www-accessible path to the skin shared assets dir (<skintype>/<skin>/www/<shared>)
	 */
    function getSkinSharedAssetsDir()
    {
		return WWW_ROOT . '/skins/' . $this->delegateName . '/' . $this->skinName . '/shared';
    }

	/**
	 * @return string www-accessible path to the skin theme assets dir (<skintype>/<skin>/www/<theme>)
	 */
    function getSkinThemeAssetsDir()
    {
		return WWW_ROOT . '/skins/' . $this->delegateName . '/' . $this->skinName . '/' . $this->skinThemeName;
    }

    /**
     * Render the skin.
     * @param boolean TRUE to display the results to the output buffer, FALSE to return them in a variable. DEFAULT: TRUE.
     * @return string The rendered view. NULL if displaying.
     * @todo convert the DIR_SMARTY calls to use a new WFWebApplication::getResource($path) infrastructure that will allow for userland overloads of these templates
     */
    function render($display = true)
    {
        $this->loadSkin();

        $skinTemplateDir = WFWebApplication::appDirPath(WFWebApplication::DIR_SKINS) . '/' . $this->delegateName . '/' . $this->skinName;

        $smarty = new WFSmarty();
        $smarty->assign('skin', $this);

        // add variables to smarty
        $themeVars = $this->skinManifestDelegate->loadTheme($this->skinThemeName);
        $smarty->assign('skinThemeVars', $themeVars);
        $smarty->assign('skinTitle', $this->title);
        $smarty->assign('skinMetaKeywords', join(',', $this->metaKeywords));
        $smarty->assign('skinMetaDescription', $this->metaDescription);
        $smarty->assign('skinBody', $this->body);
        $smarty->assign('skinHeadStrings', join("\n", array_values($this->headStrings)));
        $smarty->assign('phocoaDebug', WFWebApplication::sharedWebApplication()->debug());

        // set up shared directory URLs
        // deprecated
        $smarty->assign('skinDir', $this->getSkinDir() );
        $smarty->assign('skinDirShared', $this->getSkinDirShared() );
        // new names
        $smarty->assign('skinTypeAssetsDir', $this->getSkinTypeAssetsDir() );
        $smarty->assign('skinSharedAssetsDir', $this->getSkinSharedAssetsDir() );
        $smarty->assign('skinThemeAssetsDir', $this->getSkinThemeAssetsDir() );

        // build the <head> section
        $smarty->assign('skinPhocoaHeadTpl', WFWebApplication::appDirPath(WFWebApplication::DIR_SMARTY) . '/head.tpl');
        $smarty->assign('skinHead', $smarty->fetch($this->headTemplate));

        // set the template
        if ($this->templateType == WFSkin::SKIN_WRAPPER_TYPE_RAW)
        {
            $smarty->setTemplate(WFWebApplication::appDirPath(WFWebApplication::DIR_SMARTY) . '/template_raw.tpl');
        }
        else
        {
            $smarty->setTemplate($skinTemplateDir . '/template_' . $this->templateType . '.tpl');
        }

        // pre-render callback
        $this->willRender();

        // render smarty
        if ($display) {
            $smarty->render();
        } else {
            return $smarty->render(false);
        }
    }

    /**
     * Get a list of all installed skin types.
     * @static
     * @return array Skin Types are installed.
     */
    function installedSkinTypes()
    {
        $skinTypes = array();

        $skinsDir = WFWebApplication::appDirPath(WFWebApplication::DIR_SKINS);
        $dh = opendir($skinsDir);
        if ($dh) {
            while ( ($file = readdir($dh)) !== false ) {
                if (is_dir($skinsDir . '/' . $file) and !in_array($file, array('.','..'))) {
                    array_push($skinTypes, $file);
                }
            }
            closedir($dh);
        }

        return $skinTypes;
    }

    /**
     * Get a list of all installed skins for the current Skin Type.
     *
     * @return array Skins that are installed.
     */
    function installedSkins()
    {
        $skins = array();

        $skinsDir = WFWebApplication::appDirPath(WFWebApplication::DIR_SKINS);
        $skinDirPath = $skinsDir . '/' . $this->delegateName;
        $dh = opendir($skinDirPath);
        if ($dh) {
            while ( ($file = readdir($dh)) !== false ) {
                if (is_dir($skinDirPath . '/' . $file) and !in_array($file, array('.','..'))) {
                    array_push($skins, $file);
                }
            }
            closedir($dh);
        }

        return $skins;
    }

    /**
     * Allow the skin delegate to load the default values for this skin.
     * @see WFSkinDelegate::loadDefaults
     */
    function loadDefaults()
    {
        // call skin delegate to get skin to use -- delegate implements application-specific logic.
        if (is_object($this->delegate) && method_exists($this->delegate, 'loadDefaults')) {
            $this->delegate->loadDefaults($this);
        }
    }

    /**
     * Get the catalog (ie list) of named content for this skin from its delegate.
     * If the skin delegate supports additional content for the skin, the catalog of content is provided here. Mostly this is for documentation purposes.
     * @see WFSkinDelegate::namedContentList
     * @return array Array of strings; each entry is a name of a content driver for this skin delegate.
     */
    function namedContentList()
    {
        if (is_object($this->delegate) && method_exists($this->delegate, 'namedContentList')) {
            return $this->delegate->namedContentList();
        }
        return array();
    }

    /**
     * Get the named content from the delegate.
     * @see WFSkinDelegate::namedContent
     * @param string The name of the content to retrieve.
     * @param assoc_array A list of additional parameters.
     * @return mixed The content for the named content for this skin instance. Provided by the delegate.
     */
    function namedContent($name, $options = NULL)
    {
        if (is_object($this->delegate) && method_exists($this->delegate, 'namedContent')) {
            return $this->delegate->namedContent($name, $options);
        }
        return NULL;
    }

    /**
     *  Pre-render callback.
     *
     *  Calls the skin delegate's willRender() method if it exists.
     *  This method is called just before the template for the skin is rendered.
     *  @todo Do I need to pass in the WFSmarty instance here so that you can actually change the TPL file from this callback? If so, also update the willRender() delegate docs/prototype
     */
    function willRender()
    {
        if (is_object($this->delegate) && method_exists($this->delegate, 'willRender')) {
            $this->delegate->willRender($this);
        }
    }
}
?>
