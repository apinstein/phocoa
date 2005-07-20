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
require_once('framework/widgets/WFWidget.php');

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
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * - <b>value:</b> The image's URL. The baseDir attribute will be pre-pended.
 * 
 * <b>Optional:</b><br>
 * - <b>baseDir:</b> The URL prefix used before ALL images. IE: /images/products/previews/small/.<br>
 * - <b>width:</b> The PIXEL width of the image.<br>
 * - <b>height:</b> The PIXEL height of the image.<br>
 * - <b>border:</b> The CSS border text. Example: "1 px solid black"<br>
 * - <b>align:</b> The HTML align attribute. Example: left, right, top, bottom, middle.<br>
 */
class WFImage extends WFWidget
{
    /**
      * @var string The base dir for the image path stored in value.
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
      * @var string The HTML align string. Default "".
      */
    protected $align;

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
    }

    function setupExposedBindings()
    {
        $myBindings = parent::setupExposedBindings();
        $myBindings[] = new WFBindingSetup('value', 'The path to the image. Will be concatenated on baseDir.');
        $myBindings[] = new WFBindingSetup('baseDir', 'The base path to the image. Blank by default.');
        $myBindings[] = new WFBindingSetup('width', 'The width in pixels of the image, or blank.');
        $myBindings[] = new WFBindingSetup('height', 'The height in pixels of the image, or blank.');
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

        return '<img src="' . $this->baseDir . $this->value . '"' .
            ($this->align ? " align=\"{$this->align}\"" : '') .
            ' style="' .
            ($this->width ? "width: {$this->width}; " : '') .
            ($this->height ? "height: {$this->height}; " : '') .
            $this->border .
            '" />';
    }

    function canPushValueBinding() { return false; }
}

?>
