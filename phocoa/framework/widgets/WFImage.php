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
 * An Image widget for our framework.
 *
 * This widget allows you to easily display an image on your page.
 *
 * The PATH for the image is created from concatenating the {@link setBaseDir base dir} and the {@link setValue image path (value)}.
 * There are separate properties and bindings available for each because this adds flexibility in displaying images that
 * are based on dynamic data. Plus, no image will be rendered if value is blank, even if baseDir is non-blank.
 *
 * If only one of width or height is supplied, the image will be proportionally resized with CSS.
 * 
 * The "value" for WFImage is the image URL to include. The {@link WFImage::$baseDir baseDir} attribute will be pre-pended.
 *
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * - {@link WFWidget::$value value}
 * 
 * <b>Optional:</b><br>
 * - {@link WFImage::$baseDir baseDir}
 * - {@link WFImage::$width width}
 * - {@link WFImage::$height height}
 * - {@link WFImage::$alt alt}
 * - {@link WFImage::$border border}
 * - {@link WFImage::$align align}
 * - {@link WFImage::$link link}
 * - {@link WFImage::$linkTarget linkTarget}
 * - {@link WFWidget::$class class}
 */
class WFImage extends WFWidget
{
    /**
      * @var string The base dir for the image path stored in value. Example: /images/products/previews/small/
      */
    protected $baseDir;
    /**
      * @var integer The width in pixels of the image.
      */
    protected $width;
    /**
      * @var integer The height in pixels of the image.
      */
    protected $height;
    /**
      * @var integer The border to use (css-style: 1px solid blue). Default 0.
      */
    protected $border;
    /**
      * @var string The HTML align string. Default "". One of left, right, top, bottom, middle, baseline, etc.
      */
    protected $align;
    /**
      * @var string The HTML alt string. Default "".
      */
    protected $alt;
    /**
      * @var string The url of the link to surround the image with.
      */
    protected $link;
    /**
      * @var string The HTML "target" attribute of the link.
      */
    protected $linkTarget;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->baseDir = '';
        $this->width = NULL;
        $this->height = NULL;
        $this->border = 'border: 0;';
        $this->align = NULL;
        $this->link = NULL;
        $this->linkTarget = NULL;
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            'baseDir',
            'width',
            'height',
            'border',
            'align',
            'alt',
            'link',
            'linkTarget'
            ));
    }

    function setupExposedBindings()
    {
        $myBindings = parent::setupExposedBindings();
        $newValBinding = new WFBindingSetup('value', 'The path to the image. Will be concatenated on baseDir.', array(WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_NAME => WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_VALUE));
        $newValBinding->setReadOnly(true);
        $newValBinding->setBindingType(WFBindingSetup::WFBINDINGTYPE_MULTIPLE_PATTERN);
        $myBindings[] = $newValBinding;
        $myBindings[] = new WFBindingSetup('baseDir', 'The base path to the image. Blank by default.');
        $myBindings[] = new WFBindingSetup('width', 'The width in pixels of the image, or blank.');
        $myBindings[] = new WFBindingSetup('height', 'The height in pixels of the image, or blank.');
        $myBindings[] = new WFBindingSetup('alt', 'The alt tag.', array(WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_NAME => WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_VALUE));
        $newValBinding = new WFBindingSetup('link', 'The url that the image should link to.', array(WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_NAME => WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_VALUE));
        $newValBinding->setReadOnly(true);
        $newValBinding->setBindingType(WFBindingSetup::WFBINDINGTYPE_MULTIPLE_PATTERN);
        $myBindings[] = $newValBinding;
        return $myBindings;
    }

    /**
      * The base path for the image path. This is a good place to put http://domain.com/path/to/img/ if you're linking to an external image.
      * @param string The base path to the image path stored in value.
      */
    function setBaseDir($path)
    {
        $this->baseDir = $path;
    }

    function render($blockContent = NULL)
    {
        if ($this->hidden) return NULL;
        if (!$this->value) return NULL; // don't display anything if there is no value.

        $height = '';
        if ($this->height) $height = $this->height . 'px';
        $width = '';
        if ($this->width) $width = $this->width . 'px';
        if ($this->width and !$this->height) $this->height = 'auto';
        else if ($this->height and !$this->width) $this->width = 'auto';

        $imgHTML = '<img src="' . $this->baseDir . $this->value . '"' .
            ($this->alt ? " alt=\"{$this->alt}\"" : '') .
            ($this->align ? " align=\"{$this->align}\"" : '') .
            ($this->class ? " class=\"{$this->class}\"" : '') .
            ' style="' .
            ($this->width ? "width: {$this->width}; " : '') .
            ($this->height ? "height: {$this->height}; " : '') .
            $this->border .
            '" />';
        if ($this->link)
        {
            $target = ($this->linkTarget ? " target=\"{$this->linkTarget}\"" : '');
            return '<a href="' . $this->link . '"' . $target . '>' . $imgHTML . '</a>';
        }
        else
        {
            return $imgHTML;
        }
    }

    function canPushValueBinding() { return false; }
}

?>
