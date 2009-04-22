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
 * A YAHOO Carousel widget for our framework.
 *
 * WFYAHOO_widget_Carousel is a BLOCK widget that takes in a &lt;ul&gt; and turns it into a carousel.
 * 
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * none
 * 
 * <b>Optional:</b><br>
 * isCircular
 * autoPlay
 */
class WFYAHOO_widget_Carousel extends WFYAHOO
{
    /**
     * @var integer First visible item. 0-based. DEFAULT: 0.
     */
    protected $firstVisible;
    /**
     * @var integer Number of items visible at once. DEFAULT: 3.
     */
    protected $numVisible;
    /**
     * @var integer Number of items to scroll at a time. DEFAULT: 1.
     */
    protected $scrollIncrement;
    /**
     * @var integer Index of the selected item. DEFAULT: 0.
     */
    protected $selectedItem;
    /**
     * @var integer Percentage of the item to be revealed on either side of the main item. DEFAULT: 0.
     */
    protected $revealAmount;
    /**
     * @var boolean Is the carousel circular? DEFAULT: false.
     */
    protected $isCircular;
    /**
     * @var boolean Is the carousel vertical? DEFAULT: false.
     */
    protected $isVertical;
    /**
     * @var string JSON of the animation to use. DEFAULT: { speed: 0, effect: null }
     */
    protected $navigation;
    /**
     * @var string JSON of the navigation controls to use. DEFAULT: SAM skin setup.
     */
    protected $animation;
    /**
     * @var boolean Number of milliseconds before automatically moving to next item. DEFAULT: 0 (autoplay off).
     */
    protected $autoPlay;
    /**
     * @var integer Number of items in the carousel. If the passed number is less than then # of elements, truncates carousel. If more, will call {@link WFYAHOO_widget_Carousel::loadItemsHandler loadItemsHandler} javascript function.
     */
    protected $numItems;
    /**
     * @var string The name of a javascript function which will be called to load additional items.
     */
    protected $loadItemsHandler;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);

        $this->firstVisible = null;
        $this->numVisible = null;
        $this->scrollIncrement = null;
        $this->selectedItem = null;
        $this->revealAmount = null;
        $this->isCircular = null;
        $this->isVertical = null;
        $this->navigation = null;
        $this->animation = null;
        $this->autoPlay = null;
        $this->numItems = null;
        $this->loadItemsHandler = null;

        $this->yuiloader()->yuiRequire("carousel");
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            ));
    }

    function render($blockContent = NULL)
    {
        if ($this->hidden)
        {
            return NULL;
        }
        else
        {
            if ($this->animation !== NULL)
            {
                $this->yuiloader()->yuiRequire('animation');
            }
            $html = parent::render($blockContent);
            $html .= "<div id=\"{$this->id}\" style=\"display: none;\">{$blockContent}</div>";
            return $html;
        }
    }

    function initJS($blockContent)
    {
        $js = "
PHOCOA.widgets.{$this->id}.init = function() {
    var carousel = new YAHOO.widget.Carousel('{$this->id}');
    ";
        foreach (array('firstVisible', 'numVisible', 'scrollIncrement', 'selectedItem', 'revealAmount', 'isCircular', 'isVertical', 'navigation', 'animation', 'autoPlay', 'numItems', 'loadItemsHandler') as $prop) {
            if ($this->$prop === NULL) continue;
            $val = $this->$prop;
            if (is_bool($val))
            {
                $val = ($val ? 'true' : 'false');
            }
            $js .= "\n    carousel.set('{$prop}', {$val});";
        }
        $js .= "
    carousel.render();
    carousel.show();
    PHOCOA.runtime.addObject(carousel, '{$this->id}');
    $('{$this->id}').show();
    // IE doesn't show first item for some reason, so hack fix here. Also, IE seems to think first item is index 1
    if (Prototype.Browser.IE)
    {
        carousel.scrollTo(1);
    }
    ";
        if ($this->autoPlay != 0)
        {
            $js .= "\n    if (!Prototype.Browser.IE) carousel.startAutoPlay();";
        }
        $js .= "
};
";
        return $js;
    }

    function canPushValueBinding() { return false; }
}
