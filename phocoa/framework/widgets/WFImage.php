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
 * - {@link WFImage::$filesystemPath filesystemPath}
 * - {@link WFImage::$filesystemBasePath filesystemBasePath}
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
      * @var string The base dir for the image path stored in value. Example: /images/products/previews/small/. Note that the value will be concatenated directly to this string with no '/' or other characters added. Thus be sure to include a trailing slash if your baseDir is truly a directory. This property should have probably been named basePath. Now that PHOCOA supports ValuePattern binding construction, baseDir is not as relevant as it used to be, except in the situation where you want to supply your baseDir programmatically.
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
     * @var boolean If true, fit the image to the box specified by {@link WFImage::$width width} and {@link WFImage::$height height}.
     *              The image will be scaled proportionally.
     *              If only one of width or height is specified, the image will be scaled to fit that dimension only.
     *              Requires that the filesystemPath property exist.
     *              If any error conditions occur, will throw an exception.
     */
    protected $fitToBox;
    /**
     * @var string The filesystem path to the image.
     */
    protected $filesystemPath;
    /**
     * @var string OPTIONAL "base" path for the filesystemPath attribute. The two properties will be concatenated to create the full path.
     */
    protected $filesystemBasePath;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->baseDir = NULL;
        $this->width = NULL;
        $this->height = NULL;
        $this->border = 'border: 0;';
        $this->align = NULL;
        $this->link = NULL;
        $this->linkTarget = NULL;
        $this->fitToBox = false;
        $this->filesystemPath = NULL;
        $this->filesystemBasePath = NULL;
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            'baseDir',
            'filesystemPath',
            'filesystemBasePath',
            'width',
            'height',
            'border',
            'align' => array('left', 'center', 'right', 'middle', 'baseline', 'top'),
            //'text-align' => array('left', 'center', 'right'),
            //'vertical-align' => array('baseline', 'sub', 'super', 'top', 'text-top', 'middle', 'bottom', 'text-bottom'),
            'alt',
            'link',
            'linkTarget',
            'fitToBox' => array('true', 'false')
            ));
    }

    function setupExposedBindings()
    {
        $myBindings = parent::setupExposedBindings();
        $newValBinding = new WFBindingSetup('value', 'The path to the image. Will be concatenated on baseDir.', array(WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_NAME => WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_VALUE));
        $newValBinding->setReadOnly(true);
        $newValBinding->setBindingType(WFBindingSetup::WFBINDINGTYPE_MULTIPLE_PATTERN);
        $myBindings[] = $newValBinding;
        $newValBinding = new WFBindingSetup('filesystemPath', 'The path to the image on the filesystem. Will be concatenated on filesystemBasePath.', array(WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_NAME => WFBindingSetup::WFBINDINGSETUP_PATTERN_OPTION_VALUE));
        $newValBinding->setReadOnly(true);
        $newValBinding->setBindingType(WFBindingSetup::WFBINDINGTYPE_MULTIPLE_PATTERN);
        $myBindings[] = $newValBinding;
        $myBindings[] = new WFBindingSetup('baseDir', 'The base path to the image. Blank by default.');
        $myBindings[] = new WFBindingSetup('filesystemBasePath', 'The base path to the image. Blank by default.');
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

    private function doFitToBox(&$pxWidth, &$pxHeight)
    {
        $fsPath = $this->filesystemBasePath . $this->filesystemPath;
        $info = getimagesize($fsPath);
        if ($info === false) throw( new WFException("Can't fitToBox because no image file found at: {$fsPath}") );

        // calculate some useful info; "i" prefix means "original image"
        $iWidth = $info[0];
        $iHeight = $info[1];
        $iRatio = $iWidth / $iHeight;

        if ($this->width and $this->height)
        {
            // fit to box
            
            // scale DOWN to fix box 
            if ($iWidth > $this->width or $iHeight > $this->height)
            {   
                $newRatio = $this->width / $this->height;
                //die("$iWidth x $iHeight; $iRatio / $this->width x $this->height; $newRatio");
                // resize
                if ($iRatio > $newRatio)
                {   
                    $resizeRatio = $this->width / $iWidth;
                    $newWidth = $iWidth * $resizeRatio;
                    $newHeight = $newWidth / $iRatio;
                }
                else
                {
                    $resizeRatio = $this->height / $iHeight;
                    $newHeight = $iHeight * $resizeRatio;
                    $newWidth = $newHeight * $iRatio;
                }
            }
            // DON'T scale up to fit box; only down - maybe make this optional later
            else
            {   
                //die("no need to resize: {$iWidth}x{$iHeight} to fix box: {$this->width}x{$this->height}");
                $newWidth = $iWidth;
                $newHeight = $iHeight;
            }
        }
        else if ($this->width)
        {
            // scale DOWN to fit width
            if ($iWidth > $this->width)
            {
                $resizeRatio = $this->width / $iWidth;
                $newWidth = $iWidth * $resizeRatio;
                $newHeight = $newWidth / $iRatio;
            }
            // DON'T scale up to fit width
            else
            {
            }
        }
        else if ($this->height)
        {
            // scale DOWN to fit height
            if ($iWidth > $this->height)
            {
                $resizeRatio = $this->height / $iHeight;
                $newHeight = $iHeight * $resizeRatio;
                $newWidth = $newHeight * $iRatio;
            }
            // DON'T scale up to fit height
            else
            {
            }
        }
        else
        {
            throw( new WFException("Can't use fitToBox without specifying width and/or height.") );
        }
        $pxWidth = ceil($newWidth);
        $pxHeight = ceil($newHeight);
    }

    function render($blockContent = NULL)
    {
        if ($this->hidden) return NULL;
        if (!$this->value) return NULL; // don't display anything if there is no value.

        $pxWidth = $this->width;
        $pxHeight = $this->height;
        if ($this->fitToBox)
        {
            $this->doFitToBox($pxWidth, $pxHeight);
        }

        // resizing
        // calcualte CSS width/height info - expect pxWidth and pxHeight to be set up by now (either null or an integer)
        $cssHeight = '';
        if ($pxHeight) $cssHeight = $pxHeight . 'px';
        $cssWidth = '';
        if ($pxWidth) $cssWidth = $pxWidth . 'px';
        if ($pxWidth and !$pxHeight) $pxHeight = 'auto';
        else if ($pxHeight and !$pxWidth) $pxWidth = 'auto';

        // pull width/height from filesystem, but only if we don't already have it
        if ($this->filesystemPath and (!$this->width and !$this->height))
        {
            $fsPath = $this->filesystemBasePath . $this->filesystemPath;
            $info = getimagesize($fsPath);
            if ($info === false) throw( new WFException("Can't calculate width/height because No image file found at: {$fsPath}") );
            $this->width = $info[0];
            $this->height = $info[1];
        }

        $imgHTML = '<img id="' . $this->id() . '" src="' . $this->baseDir . $this->value . '"' .
            ($this->alt ? " alt=\"{$this->alt}\"" : '') .
            ($this->align ? " align=\"{$this->align}\"" : '') .
            ($this->class ? " class=\"{$this->class}\"" : '') .
            ' style="' .
            ($cssWidth ? "width: {$cssWidth}; " : '') .
            ($cssHeight ? "height: {$cssHeight}; " : '') .
            $this->border .
            '" />'
            . $this->getListenerJS();
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
