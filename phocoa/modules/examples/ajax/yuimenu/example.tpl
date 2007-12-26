{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
<h1>YUI Menu Examples</h1>
<p>YUI Menu provides Horizontal (MenuBar), Vertical (Menu), and Context (ContextMenu) menu systems via semantic HTML markup, CSS, and Javascript.</p>
<p>PHOCOA makes it easy to build Menu and MenuBars (ContextMenu coming soon) for your application. All you have to do is provide the data.</p>
<p>PHOCOA supports multiple ways of defining the menu structure. Click the links to see the same menu implemented with three different techniques:
    <ul>
        <li><strong><a href="#menuItems">Array of objects implementing WFMenuItem interface</a></strong></li>
        <li><strong><a href="#arrayMenuPath">Array of objects implementing WFMenuItem interface, but with menu structure specified via a menuPath field rather than as children</a></strong></li>
        <li><strong><a href="#nestedArrayMenu">Nested Associative Array</a></strong></li>
    </ul>
</p>

<h2>Building a YUI Menu from an array of objects implementing WFMenuItem</h2>
{WFView id="menuItems"}
<br />
<p>This technique is ideal when your class structure is already arranged hierarchically in the form of your menu structure. All you need to to is implement the WFMenuItem interface and pass an array of the root menu items build your menu.</p>
<p>$menu->setMenuItems($myArray) takes a structure like:
<pre>
array(
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
</pre>
</p>


<h2>Building a YUI Menu from an array of objects implementing WFMenuTreeBuilding and WFMenuItem (without children)</h2>
{WFView id="arrayMenuPath"}
<br />
<p>This technique is ideal when your class structure is flattened, but a single field contains the "path" of where that item should be in the menu. To implement this technique, implement the WFMenuItem interface and the WFMenuTreeBuilding interface.</p>
<p>$menu->setMenuItemsMenuPath($myArray) takes a structure like:
<pre>
array(
    new YUIMenuExample_ClassWithMenuPath('Search Engines/YAHOO', 'YAHOO', 'http://yahoo.com'),
    new YUIMenuExample_ClassWithMenuPath('Search Engines/Google', 'Google', 'http://google.com'),
    new YUIMenuExample_ClassWithMenuPath('Search Engines/Ask', 'Ask', 'http://Ask.com'),
    new YUIMenuExample_ClassWithMenuPath('PHP Sites/PHP Official Site', 'PHP Sites', 'http://php.net'),
    new YUIMenuExample_ClassWithMenuPath('PHP Sites/Planet PHP', 'Planet PHP', 'http://planetphp.net'),
    new YUIMenuExample_ClassWithMenuPath('PHP Sites/Frameworks/Cake', 'Cake', 'http://cakephp.org'),
    new YUIMenuExample_ClassWithMenuPath('PHP Sites/Frameworks/Symfony', 'Symfony', 'http://symfony-project.org'),
    new YUIMenuExample_ClassWithMenuPath('PHP Sites/Frameworks/PHOCOA', 'PHOCOA', 'http://phocoa.com')
    )
</pre>
</p>
<p>Where YUIMenuExample_ClassWithMenuPath implements WFMenuTree and WFMenuTreeBuilding, and converts it into the requisite tree structure, including the creation of any needed interim nodes.</p>
<p>This is handy when you have a flat structure that contains a field containing path-like data (i.e., a/b/c/d). The algorithm for doing the conversion is a little complex, so PHOCOA includes this as a core capability.</p>

<h2>Building a YUI Menu from a Nested Associative Array</h2>
{WFView id="nestedArrayMenu"}
<br />
<p>This technique is ideal for simple cases where you need only build a menu and don't want to have to implement any class structure at all.</p>
<p>$menu->setMenuItemsNestedArray($myArray) takes a structure like:
<pre>
array('Search Engines' => array('YAHOO' => 'http://yahoo.com',
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
</pre>

Where each "key" is the menu item name, and each "value" is either an array of sub-menu items, or the URL for that menu item.
</p>

