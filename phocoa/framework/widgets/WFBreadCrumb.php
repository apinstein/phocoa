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
 * A breadcrumb widget for our framework.
 * 
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * - {@link WFWidget::$value value} The current "object" used to generate the breadcrumb.
 * - {@link WFBreadCrumb::$breadcrumbSetup breadcrumbSetup}
 * 
 * <b>Optional:</b><br>
 * none.
 */
class WFBreadCrumb extends WFWidget
{
    /**
     * @var object WFBreadCrumbSetup The setup used to generate the breadcrumb.
     */
    protected $breadcrumbSetup;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->breadcrumbSetup = NULL;
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            'breadcrumbSetup'
            ));
    }
    function setupExposedBindings()
    {
        $myBindings = parent::setupExposedBindings();
        $newValBinding = new WFBindingSetup('breadcrumbSetup', 'A WFBreadCrumbSetup object.');
        $newValBinding->setReadOnly(true);
        $myBindings[] = $newValBinding;
        return $myBindings;
    }

    function render($blockContent = NULL)
    {
        if ($this->hidden)
        {
            return NULL;
        }
        else
        {
            return $this->breadcrumbSetup->renderBreadCrumb($this->value);
        }
    }

    function canPushValueBinding() { return false; }
}

/**
 * A Breadcrumb system.
 *
 * Supports WFDecorator(s).
 *
 * @package UI
 * @subpackage Breadcrumb
 */
class WFBreadCrumbNode extends WFObject
{
    const LINK_URL_TEMPLATE = '%LINK%';

    protected $class;
    protected $parentClass;
    protected $linkTextKeyPath;
    protected $linkURLTemplate;
    protected $linkURLKeyPath;
    protected $parentKeyPath;
    /**
     * @var array An array of WFDecorator objects that will be used to wrap the content object(s).
     */
    protected $decorators;

    function __construct()
    {
        $this->class = NULL;
        $this->parentClass = NULL;
        $this->linkTextKeyPath = NULL;
        $this->linkURLTemplate = NULL;
        $this->linkURLKeyPath = NULL;
        $this->parentKeyPath = NULL;
        $this->decorators = NULL;
    }

    /**
     * A static constructor for use with fluent interface.
     *
     * @return object WFBreadCrumbNode
     */
    public static function BreadCrumbNode()
    {
        return new WFBreadCrumbNode;
    }

    function getClass()
    {
        return $this->class;
    }
    function getParentClass()
    {
        return $this->parentClass;
    }
    function getLinkURL($o)
    {
        return str_replace(WFBreadCrumbNode::LINK_URL_TEMPLATE, $o->valueForKeyPath($this->linkURLKeyPath), $this->linkURLTemplate);
    }

    /**
     * Set a {@link object WFDecorator} object to be used with this controller.
     *
     * @param string The name of the decorator class.
     */
    function setDecorator($decoratorClassName)
    {
        $this->decorators = array($decoratorClassName);
        return $this;
    }

    /**
     * Set multiple {@link object WFDecorator} objects to be used with this controller.
     *
     * @param string The name(s) of the decorator class(es) to be used to decorate objects managed by this controller. Names should be separated by commas; LAST one wins.
     */
    function setDecorators($decoratorList)
    {
        if (is_string($decoratorList))
        {
            $decoratorList = array_map('trim', explode(',', $decoratorList));
        }
        $this->decorators = $decoratorList;
        return $this;
    }

    /**
     * Decorate the passed object with the decorator(s) for this controller.
     */
    public function decorateObject($o)
    {
        if (!$this->decorators) return $o;
        foreach ($this->decorators as $d) {
            $o = new $d($o);
        }
        return $o;
    }

    public function undecorateObject($o)
    {
        while ($o instanceof WFDecorator) {
            $o = $o->decoratedObject();
        }
        return $o;
    }

    /**
     * Set the class which represents this node.
     *
     * @param string A class name.
     * @return object WFBreadCrumbNode
     */
    public function setClass($class)
    {
        $this->class = $class;
        return $this;
    }

    /**
     * Set the class which represents the "parent" of this node in the object graph.
     *
     * @param string A class name.
     * @param string The keypath to call on the object to get the "parent" object. Defaults to the name of the parent class.
     * @return object WFBreadCrumbNode
     */
    public function setParentClass($class, $parentKeyPath = NULL)
    {
        $this->parentClass = $class;
        $this->parentKeyPath = ($parentKeyPath === NULL ? $class : $parentKeyPath);
        return $this;
    }

    /**
     * Set the information used to create the link which wrap the node.
     *
     * @param string A keypath on the object to get the link text.
     * @param string A template string to create the link URL.
     * @param string A keypath on the object to get the value that should be subsitutued for {@link WFBreadCrumbNode::LINK_URL_TEMPLATE}
     * @return object WFBreadCrumbNode
     */
    public function setLinkURL($linkTextKeyPath, $linkURLTemplate, $linkURLKeyPath)
    {
        $this->linkTextKeyPath = $linkTextKeyPath;
        $this->linkURLTemplate = $linkURLTemplate;
        $this->linkURLKeyPath = $linkURLKeyPath;
        return $this;
    }
}

/**
 * A Breadcrumb system.
 *
 * WFBreadCrumbSetup manages the object graph which makes up the breadcrumb architecture.
 *
 * You can add as many nodes as you like to the breadcrumb setup; you can even create complex branched trees.
 * However, while a single class can have multiple classes pointing to it, of course a single class can only point to one class (the parent).
 *
 * The resulting object graph is used to calculate a breadcrumb from any given object that appear in the graph.
 *
 * @package UI
 * @subpackage Breadcrumb
 */
class WFBreadCrumbSetup extends WFObject
{
    /**
     * @var array A hash of parentClassName => childClassName.
     */
    protected $graph;
    /**
     * @var array A hash of className => object WFBreadCrumbNode to quickly map a given object to the node that handles it.
     */
    protected $nodes;

    protected $rootLabel;
    protected $rootURL;

    protected $separator;

    function __construct()
    {
        $this->graph = array();
        $this->nodes = array();
        $this->rootURL = NULL;
        $this->rootLabel = NULL;
        $this->separator = '&gt;';
    }

    /**
     * Static constructor for fluent interfaces.
     *
     * @return object WFBreadCrumbSetup
     */
    public static function BreadCrumbSetup()
    {
        return new WFBreadCrumbSetup;
    }

    /**
     * Set the "root" node of the object graph. This is used whenever the parent is NULL.
     *
     * @param string The label to display at the "root" of the breadcrumb.
     * @param string The link for the root. OPTIONAL, default NULL.
     * @return object WFBreadCrumbSetup
     */
    public function setRoot($label, $url = NULL)
    {
        $this->rootLabel = $label;
        $this->rootURL = $url;
        return $this;
    }

    /**
     * Set the HTML separator code.
     *
     * @param string HTML separator. Default is '&gt;'.
     * @return object WFBreadCrumbSetup
     */
    public function setSeparator($sep)
    {
        $this->separator = $sep;
        return $this;
    }

    /**
     * Add a WFBreadCrumbNode to the object graph.
     *
     * @param object WFBreadCrumbNode
     * @return object WFBreadCrumbSetup
     */
    public function addBreadCrumbNode($bcNode)
    {
        if (!($bcNode instanceof WFBreadCrumbNode)) throw( new WFException("Must pass a WFBreadCrumbNode.") );
        
        // only one parent per child
        $childClass = $bcNode->getClass();
        $parentClass = $bcNode->getParentClass();
        if (isset($this->graph[$childClass])) throw( new WFException("Class {$childClass} already maps to parent class {$parentClass}.") );

        // add node to graph
        $this->nodes[$childClass] = $bcNode;
        $this->graph[$childClass] = $parentClass;

        return $this;
    }

    /**
     * Calculate the breadcrumb info.
     *
     * @param object WFObject The instance to create the breadcrumb for.
     * @return array A hash of title => url for all items in the breadcrumb.
     */
    public function getBreadCrumb($object)
    {
        // unroll decorated objects
        while ($object instanceof WFDecorator) {
            $object = $object->decoratedObject();
        }

        $class = get_class($object);
        // must use array_key_exists b/c the value could be null
        if (!array_key_exists($class, $this->graph)) throw( new WFException("This breadcrumb setup doesn't know how to handle objects of class: {$class}.") );

        $node = $this->nodes[$class];

        // build breadcrumb
        $breadcrumb = array();  // will be linkTitle => linkURL
        while (true) {
            $decoratedObject = $node->decorateObject($object);
            $title = $decoratedObject->valueForKeyPath($node->valueForKey('linkTextKeyPath'));
            if (count($breadcrumb) > 0)
            {
                $url = $node->getLinkURL($decoratedObject);
            }
            else
            {
                $url = NULL;
            }
            $breadcrumb[$title] = $url;

            if ($node->valueForKeyPath('parentKeyPath') === NULL)
            {
                break;
            }
            $object = $object->valueForKeyPath($node->valueForKeyPath('parentKeyPath'));
            $node = $this->nodes[$node->getParentClass()];
        }
        // add root
        if ($this->rootLabel)
        {
            $breadcrumb[$this->rootLabel] = $this->rootURL;
        }
        return array_reverse($breadcrumb);
    }

    /**
     * Render the bread crumb into HTML.
     *
     * @param object WFObject The instance to create the breadcrumb for.
     * @return string The HTML for the breadcrumb.
     */
    public function renderBreadCrumb($object)
    {
        $html = NULL;
        foreach ($this->getBreadCrumb($object) as $linkText => $linkURL) {
            if ($html !== NULL)
            {
                $html .= ' ' . $this->separator . ' ';
            }
            if ($linkURL !== NULL)
            {
                $html .= '<a href="' . $linkURL . '">' . $linkText . '</a> ';
            }
            else
            {
                $html .= $linkText;
            }
        }
        return $html;
    }
}
