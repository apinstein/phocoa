<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

class module_yuimenu extends WFModule
{
    protected $menuItems;

    function sharedInstancesDidLoad()
    {
        $this->menuItems = new YUIMenuExampleMenus;
    }

    function defaultPage()
    {
        return 'example';
    }
}

class YUIMenuExampleMenus extends WFObject
{
    protected $menuItemsNestedArray = array('Search Engines' => array('YAHOO' => 'http://yahoo.com',
                                                                 'Google' => 'http://google.com',
                                                                 'Ask' => 'http://ask.com'
                                                                 ),
                                        'PHP Sites' => array('PHP Official Site' => 'http://php.net',
                                                             'Planet PHP' => ' http://planetphp.com',
                                                             'Frameworks' => array('Cake' => 'http://cakephp.org',
                                                                                   'Symfony' => 'http://symfony-project.org',
                                                                                   'PHOCOA' => 'http://phocoa.com'
                                                                                    )

                                                            )
                                        );
    function menuItemsWithMenuPath()
    {
        return array(
            new YUIMenuExample_ClassWithMenuPath('Search Engines/YAHOO', 'YAHOO', 'http://yahoo.com'),
            new YUIMenuExample_ClassWithMenuPath('Search Engines/Google', 'Google', 'http://google.com'),
            new YUIMenuExample_ClassWithMenuPath('Search Engines/Ask', 'Ask', 'http://Ask.com'),
            new YUIMenuExample_ClassWithMenuPath('PHP Sites/PHP Official Site', 'PHP Official Site', 'http://php.net'),
            new YUIMenuExample_ClassWithMenuPath('PHP Sites/Frameworks/Cake', 'Cake', 'http://cakephp.org'),
            new YUIMenuExample_ClassWithMenuPath('PHP Sites/Frameworks/Symfony', 'Symfony', 'http://symfony-project.org'),
            new YUIMenuExample_ClassWithMenuPath('PHP Sites/Frameworks/PHOCOA', 'PHOCOA', 'http://phocoa.com'),
        );
    }

    function menuItems()
    {
        return array(
                   WFMenuItemBasic::WFMenuItemBasic()
                        ->setLabel('Search Engines')
                        ->addChild( WFMenuItemBasic::WFMenuItemBasic()->setLabel('YAHOO')->setLink('http://yahoo.com') )
                        ->addChild( WFMenuItemBasic::WFMenuItemBasic()->setLabel('Google')->setLink('http://google.com') )
                        ->addChild( WFMenuItemBasic::WFMenuItemBasic()->setLabel('Ask')->setLink('http://ask.com') ),
                   WFMenuItemBasic::WFMenuItemBasic()
                        ->setLabel('PHP Sites')
                        ->addChild( WFMenuItemBasic::WFMenuItemBasic()->setLabel('PHP Official Site')->setLink('http://php.net') )
                        ->addChild( WFMenuItemBasic::WFMenuItemBasic()->setLabel('Frameworks')
                                        ->addChild( WFMenuItemBasic::WFMenuItemBasic()->setLabel('Cake')->setLink('http://cakephp.org') )
                                        ->addChild( WFMenuItemBasic::WFMenuItemBasic()->setLabel('Symfony')->setLink('http://symfony-project.org') )
                                        ->addChild( WFMenuItemBasic::WFMenuItemBasic()->setLabel('PHOCOA')->setLink('http://phocoa.com') )
                                  )
                    );
    }
}

class YUIMenuExample_ClassWithMenuPath extends WFMenuItemBasic implements WFMenuTreeBuilding
{
    public $menuPath;

    function __construct($menuPath, $label, $link)
    {
        parent::__construct();
        $this->menuPath = $menuPath;
        $this->setLabel($label);
        $this->setLink($link);
    }
    function menuPath()
    {
        return $this->menuPath;
    }
}

?>
