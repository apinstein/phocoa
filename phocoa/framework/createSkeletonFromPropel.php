<?php

require_once($_ENV['PHOCOA_PROJECT_CONF']);
require_once 'Console/Getopt.php';
require_once('propel/engine/database/model/NameFactory.php');
require_once('propel/engine/database/model/NameGenerator.php');

// set up defaults

// pull env
if ($phocoaConfFile = getenv('PHOCOA_PROJECT_CONF'))
{
    $__config['phocoaConfFile'] = $phocoaConfFile;
}

$shortopts = "";
$longopts = array('phocoaConfFile=', 'tableName=', 'columnName=', 'propelOutputDir=', 'propelConfFile=', 'databaseName=', 'pageName=', 'pageType=');

$cgo = new Console_Getopt;
$args = $cgo->readPHPArgv();
if (PEAR::isError($args)) { 
    fwrite(STDERR,$args->getMessage()."\n"); 
    exit(1);
} 
$options = $cgo->getopt($args, $shortopts, $longopts);
// Check the options are valid 
if (PEAR::isError($options)) { 
    fwrite(STDERR,$options->getMessage()."\n"); 
    exit(2); 
} 
foreach ($options[0] as $optInfo) {
    $optName = $optInfo[0];
    $optValue = $optInfo[1];
    //print "Checking $optName :: $optValue\n";
    switch ($optName) {
        case '--help':
            print "Usage:
            --phocoaConfFile=/path/to/webapp.conf
            --tableName=table_name The exact name of the table in the database you want to build code for.
            --columnName=column_name The KVC name of the column in the database you should use to search on, and that will be displayed anytime the object needs to be \"named\"
            ";
            break;
        case '--phocoaConfFile':
            $__config['phocoaConfFile'] = $optValue;
            break;
        case '--tableName':
            $__config['tableName'] = $optValue;
            break;
        case '--columnName':
            $__config['columnName'] = $optValue;
            break;
        case '--propelConfFile':
            $__config['propelConfFile'] = $optValue;
            break;
        case '--propelOutputDir':
            $__config['propelOutputDir'] = $optValue;
            break;
        case '--databaseName':
            $__config['propelDatabaseName'] = $optValue;
            break;
        case '--pageType':
            $__config['pageType'] = $optValue;
            break;
        case '--pageName':
            $__config['phocoaPageName'] = $optValue;
            break;
    }
}
// use defaults
// assert params
if (empty($__config['tableName'])) throw( new Exception("tableName must be specified. This is the database name of the table that you want to build code for.") );
if (empty($__config['columnName'])) throw( new Exception("columnName must be specified. This is the database name of column that is a good column to show as a single field description of the row. Example: name") );
if (empty($__config['phocoaConfFile'])) throw( new Exception("phocoaConfFile must be specified.") );
if (empty($__config['propelConfFile'])) throw( new Exception("propelConfFile must be specified.") );
if (empty($__config['propelOutputDir'])) throw( new Exception("propelOutputDir must be specified. This is the directory that propel generates all classes in, without the database name. Same as the build file for propel.") );
if (empty($__config['propelDatabaseName'])) throw( new Exception("databaseName must be specified.") );
if (empty($__config['pageType'])) throw( new Exception("pageType must be specified.") );
if (!isset($__config['phocoaPageName']))
{
    $__config['phocoaPageName'] = $__config['pageType'];
}

// include the web app to bootstrap phocoa
require_once $__config['phocoaConfFile'];
require_once 'framework/WFWebApplication.php';
require_once 'framework/util/PHPArrayDumper.php';

// set up ini path to include the propel dir
ini_set('include_path', ini_get('include_path') . ":{$__config['propelOutputDir']}");
// load propel conf
require_once($__config['propelConfFile']);
// load desired object file - we need to load the main, peer, and base files so that Propel::init() sees that the table is loaded.
$propelClassesDir = $__config['propelOutputDir'] . "/{$__config['propelDatabaseName']}";
$propelObjectName = NameFactory::generateName(NameFactory::PHP_GENERATOR, array($__config['tableName'], NameGenerator::CONV_METHOD_PHPNAME));
require_once( $propelClassesDir . '/' . $propelObjectName . '.php');
require_once( $propelClassesDir . '/om/Base' . $propelObjectName . '.php');
require_once( $propelClassesDir . '/om/Base' . $propelObjectName . 'Peer.php');
Propel::init($__config['propelConfFile']);
// load databaseMap into inst var
$propelDBMap = Propel::getDatabaseMap($__config['propelDatabaseName']);

$sdp = new SkeletonDumperPropel($propelDBMap, $__config['tableName'], $__config['columnName']);
$moduleCode = array();
$pageNames = explode(',', $__config['phocoaPageName']);
$pageTypes = explode(',', $__config['pageType']);
if (!(count($pageTypes) == count($pageNames))) throw( new Exception("Must have 1 phocoaPageName for each pageType entry.") );
for ( $i = 0; $i < count($pageTypes); $i++) {
    $pageType = $pageTypes[$i];
    $pageName = $pageNames[$i];
    if (!in_array($pageType, SkeletonDumperPropel::pageTypes())) throw( new Exception("pageType must be one of: " . join(',', SkeletonDumperPropel::pageTypes())) );
    print "Setting up page: {$pageType}\n";
    switch ($pageType) {
        case 'edit':
            $moduleCode[] = $sdp->updateEditPage($pageName);
            break;
        case 'search':
            $moduleCode[] = $sdp->updateSearchPage($pageName);
            break;
        case 'detail':
            $moduleCode[] = $sdp->updateDetailPage($pageName);
            break;
    }
}
$moduleName = basename(getcwd());
$file = "<?php
class module_{$moduleName} extends WFModule
{
    function defaultPage() { return 'search'; }

    ";
foreach ($moduleCode as $code) {
    $file .= "\n$code\n";
}
    $file .= "
}
?>";
file_put_contents('./suggested_code.php', $file);
print "Suggested module code in suggested_code.php\n";

class SkeletonDumperPropel
{
    protected $dbMap;
    protected $tableName;
    protected $singlePrimaryKey;
    
    function __construct($dbMap, $tableName, $mainColumnName)
    {
        $this->tableName = $tableName;
        $colNameAsPHPName = NameFactory::generateName(NameFactory::PHP_GENERATOR, array($mainColumnName, NameGenerator::CONV_METHOD_PHPNAME));
        $this->mainColumnName = strtolower(substr($colNameAsPHPName, 0, 1)) . substr($colNameAsPHPName, 1);
        $this->mainColumnPropelConstName = strtoupper($mainColumnName);
        $this->className = NameFactory::generateName(NameFactory::PHP_GENERATOR, array($tableName, NameGenerator::CONV_METHOD_PHPNAME));
        $this->sharedInstanceID = $this->className;
        $this->dbMap = $dbMap;
        $this->singlePrimaryKey = NULL;

        // figure out modulePath
        // walk up PWD until we hit "modules" and use that.
        $dir = getcwd();
        $parts = explode('/', $dir);
        $this->modulePath = NULL;
        foreach ($parts as $part) {
            if ($this->modulePath === NULL)
            {
                if ($part == 'modules')
                {
                    $this->modulePath = WWW_ROOT;
                    continue;
                }
            }
            else
            {
                $this->modulePath .= '/' . $part;
            }
        }
    }

    function pageTypes()
    {
        return array('detail', 'edit', 'search');
    }

    function updateSharedSetup($pageType)
    {
        // deal with shared instances
        // figure out primary key
        $primaryKeys = array();
        foreach ($this->dbMap->getTable($this->tableName)->getColumns() as $col) {
            if ($col->isPrimaryKey())
            {
                $primaryKeys[] = strtolower(substr($col->getPhpName(), 0, 1)) . substr($col->getPhpName(), 1);
            }
        }
        if (count($primaryKeys) == 1)
        {
            $this->singlePrimaryKey = $primaryKeys[0];
        }
        else
        {
            print "WARNING: More than one primary key found. You'll need to set up the classIdentifiers in code:\n\$this->{$this->sharedInstanceID}->setClassIdentifiers(" . PHPArrayDumper::arrayToPHPSource($primaryKeys) . ");\n";
        }
        if (file_exists('shared.instances'))
        {
            include('shared.instances');
            $sharedInstances = $__instances;
        }
        if (!isset($sharedInstances[$this->sharedInstanceID]))
        {
            print "Adding shared instance: {$this->sharedInstanceID}\n";
            $sharedInstances[$this->sharedInstanceID] = 'WFArrayController';
        }
        if ($pageType == 'search' and !isset($sharedInstances['paginator']))
        {
            print "Adding shared instance: 'paginator'\n";
            $sharedInstances['paginator'] = 'WFPaginator';
        }
        print "Saving updated shared.instances\n";
        PHPArrayDumper::arrayToPHPFileWithArray($sharedInstances, '__instances', 'shared.instances');
        // deal with shared config
        if (file_exists('shared.config'))
        {
            include('shared.config');
            $sharedConfig = $__config;
        }
        if (!isset($sharedConfig[$this->sharedInstanceID]))
        {
            print "Adding config for shared instance: {$this->sharedInstanceID}\n";
            $sharedConfig[$this->sharedInstanceID] = array(
                                                    'properties' => array(
                                                                        'class' => $this->className,
                                                                        'classIdentifiers' => $this->singlePrimaryKey,
                                                                        'selectOnInsert' => true,
                                                                        'automaticallyPreparesContent' => false,
                                                                        )
                                                    );
        }
        if ($pageType == 'search' and !isset($sharedConfig['paginator']))
        {
            print "Adding config for shared instance 'paginator'\n";
            $sharedConfig['paginator'] = array(
                                                    'properties' => array(
                                                                        'modeForm' => 'search',
                                                                        'pageSize' => 25,
                                                                        'itemPhraseSingular' => $this->className,
                                                                        'itemPhrasePlural' => "{$this->className}s",
                                                                        )
                                                    );
        }
        print "Saving updated shared.config\n";
        PHPArrayDumper::arrayToPHPFileWithArray($sharedConfig, '__config', 'shared.config');
    }

    function updateSearchPage($pageName)
    {
        $this->updateSharedSetup('search');

        $tplFile = $pageName . '.tpl';
        $instanceFile = $pageName . '.instances';
        if (file_exists($instanceFile))
        {
            include($instanceFile);
            $pageInstances = $__instances;
        }
        else
        {
            // eventually call createPage script
            $pageInstances = array();
        }
        $configFile = $pageName . '.config';
        if (file_exists($configFile))
        {
            include($configFile);
            $pageConfig = $__config;
        }
        else
        {
            // eventually call createPage script
            $pageConfig = array();
        }
        
        // set up the form
        $formID = 'search' . $this->className . 'Form';
        // finish form
        if (!isset($pageInstances[$formID]))
        {
            $pageInstances[$formID] = array('class' => 'WFForm', 'children' => array(
                                                                                    'search' => array('class' => 'WFSubmit'),
                                                                                    'paginatorState' => array('class' => 'WFPaginatorState'),
                                                                                    'query' => array('class' => 'WFTextField'),
                                                                                    )
                                            );
            // config for form widgets
            $pageConfig['search'] = array('properties' => array('label' => 'Search'));
            $pageConfig['paginatorState'] = array('properties' => array('paginator' => '#module#paginator'));
        }
        // output TPL file for search
        $tpl = "{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
<h2>{$this->className}</h2>

{WFForm id=\"{$formID}\"}
    {WFPaginatorState id=\"paginatorState\"}
    {WFTextField id=\"query\"} {WFSubmit id=\"search\"} <a href=\"{WFURL action=\"edit\"}\">Add a new {$this->className}.</a>
{/WFForm}

<p>{WFPaginatorPageInfo id=\"paginatorPageInfo\"} {WFPaginatorNavigation id=\"paginatorNavigation\"}</p>
<table border=\"1\" cellspacing=\"0\" cellpadding=\"5\">
{section name=items loop=\$itemCount}
    {if \$smarty.section.items.first}
    <tr>
        <th>{$this->mainColumnName}</th>
        <th></th>
    </tr>
    {/if}
    <tr>
        <td>{WFDynamic id=\"{$this->mainColumnName}\"}</td>
        <td>{WFDynamic id=\"editLink\"} {WFDynamic id=\"deleteLink\"}</td>
    </tr>
{sectionelse}
    <tr><td>No items found.</td></tr>
{/section}
</table>

<script language=\"JavaScript1.2\">
<!--
document.forms.{$formID}.query.focus();
-->
</script>
        ";

        // add pagination widget instances and config
        if (!isset($pageInstances['paginatorNavigation']))
        {
            $pageInstances['paginatorNavigation'] = array('class' => 'WFPaginatorNavigation');
            $pageConfig['paginatorNavigation'] = array('properties' => array('paginator' => '#module#paginator'));
        }
        if (!isset($pageInstances['paginatorPageInfo']))
        {
            $pageInstances['paginatorPageInfo'] = array('class' => 'WFPaginatorPageInfo');
            $pageConfig['paginatorPageInfo'] = array('properties' => array('paginator' => '#module#paginator'));
        }

        // add table display widget instances and config
        if (!isset($pageInstances[$this->mainColumnName]))
        {
            $pageInstances[$this->mainColumnName] = array('class' => 'WFDynamic', 'children' => array( "{$this->mainColumnName}Prototype" => array('class' => 'WFLink') ));
            $pageConfig[$this->mainColumnName] = array('properties' => array('arrayController' => "#module#{$this->sharedInstanceID}"));
            $pageConfig["{$this->mainColumnName}Prototype"] = array(
                                                                        'bindings' => array(
                                                                                        'value' => array(
                                                                                                        'instanceID' => $this->sharedInstanceID,
                                                                                                        'controllerKey' => '#current#',
                                                                                                        'modelKeyPath' => $this->singlePrimaryKey,
                                                                                                        'options' => array('ValuePattern' => $this->modulePath . '/detail/%1%')
                                                                                                        ),
                                                                                        'label' => array(
                                                                                                        'instanceID' => $this->sharedInstanceID,
                                                                                                        'controllerKey' => '#current#',
                                                                                                        'modelKeyPath' => $this->mainColumnName
                                                                                                        )
                                                                                        )
                                                                    );
        }
        if (!isset($pageInstances['editLink']))
        {
            $pageInstances['editLink'] = array('class' => 'WFDynamic', 'children' => array( "editLinkPrototype" => array('class' => 'WFLink') ));
            $pageConfig['editLink'] = array('properties' => array('arrayController' => "#module#{$this->sharedInstanceID}"));
            $pageConfig['editLinkPrototype'] = array(
                                                                        'properties' => array('label' => 'Edit'),
                                                                        'bindings' => array(
                                                                                        'value' => array(
                                                                                                        'instanceID' => $this->sharedInstanceID,
                                                                                                        'controllerKey' => '#current#',
                                                                                                        'modelKeyPath' => $this->singlePrimaryKey,
                                                                                                        'options' => array('ValuePattern' => $this->modulePath . '/edit/%1%')
                                                                                                        )
                                                                                        )
                                                                    );
        }
        if (!isset($pageInstances['deleteLink']))
        {
            $pageInstances['deleteLink'] = array('class' => 'WFDynamic', 'children' => array( "deleteLinkPrototype" => array('class' => 'WFLink') ));
            $pageConfig['deleteLink'] = array('properties' => array('arrayController' => "#module#{$this->sharedInstanceID}"));
            $pageConfig['deleteLinkPrototype'] = array(
                                                                        'properties' => array('label' => 'Delete'),
                                                                        'bindings' => array(
                                                                                        'value' => array(
                                                                                                        'instanceID' => $this->sharedInstanceID,
                                                                                                        'controllerKey' => '#current#',
                                                                                                        'modelKeyPath' => $this->singlePrimaryKey,
                                                                                                        'options' => array('ValuePattern' => $this->modulePath . '/confirmDelete/%1%')
                                                                                                        )
                                                                                        )
                                                                    );
        }

        // thru here
        print "Saving updated {$pageName}.instances\n";
        PHPArrayDumper::arrayToPHPFileWithArray($pageInstances, '__instances', $instanceFile);
        print "Saving updated {$pageName}.config\n";
        PHPArrayDumper::arrayToPHPFileWithArray($pageConfig, '__config', $configFile);
        if (!file_exists($tplFile))
        {
            print "Creating {$pageName}.tpl file.\n";
            file_put_contents($tplFile, $tpl);
        }
        else
        {
            print "\nTemplate file already exists, so it will not be overwritten. Here is the template code for the table:\n$tpl\n";
        }

        // suggested module code
        return "
    function {$pageName}_ParameterList()
    {
        return array('paginatorState');
    }
    function {$pageName}_PageDidLoad(\$page, \$params)
    {
        \$this->paginator->readPaginatorStateFromParams(\$params);
        if (!\$page->hasSubmittedForm())
        {
            \$this->{$pageName}_doSearch(\$page);
        }
    }
    function {$pageName}_search_Action(\$page)
    {   
        \$this->{$pageName}_doSearch(\$page);
        
        // re-build dynamic widgets
        \$page->outlet('{$this->mainColumnName}')->createWidgets();
        \$page->outlet('editLink')->createWidgets();
        \$page->outlet('deleteLink')->createWidgets();
    }   
    function {$pageName}_doSearch(\$page)
    {
        \$query = \$page->outlet('query')->value();
        \$c = new Criteria();
        if (!empty(\$query))
        {   
            \$querySubStr = '%' . str_replace(' ', '%', trim(\$query)) . '%';

            \$c->add({$this->className}Peer::{$this->mainColumnPropelConstName}, \$querySubStr, Criteria::ILIKE);
        }

        \$this->paginator->setDataDelegate(new WFPagedPropelQuery(\$c, '{$this->className}Peer'));
        \$this->{$this->sharedInstanceID}->setContent(\$this->paginator->currentItems());

        \$page->assign('itemCount', \$this->{$this->sharedInstanceID}->arrangedObjectCount());
    }
        ";
    }

    function updateDetailPage($pageName)
    {
        $this->updateSharedSetup('detail');

        $tplFile = $pageName . '.tpl';
        $instanceFile = $pageName . '.instances';
        if (file_exists($instanceFile))
        {
            include($instanceFile);
            $pageInstances = $__instances;
        }
        else
        {
            // eventually call createPage script
            $pageInstances = array();
        }
        $configFile = $pageName . '.config';
        if (file_exists($configFile))
        {
            include($configFile);
            $pageConfig = $__config;
        }
        else
        {
            // eventually call createPage script
            $pageConfig = array();
        }
        
        // deal with all of the columns
        $tpl = "{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}\n" . '<table border="1" cellpadding="3" cellspacing="0">' . "\n";
        foreach ($this->dbMap->getTable($this->tableName)->getColumns() as $col) {
            $widgetID = strtolower(substr($col->getPhpName(), 0, 1)) . substr($col->getPhpName(), 1);
            if (!isset($pageInstances[$widgetID]))
            {
                print "Adding instance: $widgetID\n";
                $pageInstances[$widgetID] = array('class' => 'WFLabel', 'children' => array());
            }
            if (!isset($pageConfig[$widgetID]))
            {
                print "Adding config for instance: $widgetID\n";
                $pageConfig[$widgetID] = array(
                                                'bindings' => array(
                                                                'value' => array(
                                                                                'instanceID' => $this->sharedInstanceID,
                                                                                'controllerKey' => 'selection',
                                                                                'modelKeyPath' => $widgetID
                                                                                )
                                                                )
                                            );
            }
            $tpl .= "    <tr>\n        <td valign=\"top\">" . ucwords(strtolower(str_replace('_', ' ', $col->getColumnName()))) . "</td>\n        <td valign=\"top\">{WFLabel id=\"$widgetID\"}</td>\n    </tr>\n";
        }
        $tpl .= "</table>\n";
        print "Saving updated {$pageName}.instances\n";
        PHPArrayDumper::arrayToPHPFileWithArray($pageInstances, '__instances', $instanceFile);
        print "Saving updated {$pageName}.config\n";
        PHPArrayDumper::arrayToPHPFileWithArray($pageConfig, '__config', $configFile);
        if (!file_exists($tplFile))
        {
            print "Creating {$pageName}.tpl file.\n";
            file_put_contents($tplFile, $tpl);
        }
        else
        {
            print "\nTemplate file already exists, so it will not be overwritten. Here is the template code for the table:\n$tpl\n";
        }

        // suggested module code
        return "
    function {$pageName}_ParameterList()
    {
        return array('id');
    }
    function {$pageName}_PageDidLoad(\$page, \$params)
    {
        \$this->{$this->sharedInstanceID}->setContent(array({$this->className}Peer::retrieveByPK(\$params['id'])));
    }
        ";
    }

    function updateEditPage($pageName)
    {
        $this->updateSharedSetup('edit');

        $tplFile = $pageName . '.tpl';
        $instanceFile = $pageName . '.instances';
        if (file_exists($instanceFile))
        {
            include($instanceFile);
            $pageInstances = $__instances;
        }
        else
        {
            // eventually call createPage script?
            $pageInstances = array();
        }
        $configFile = $pageName . '.config';
        if (file_exists($configFile))
        {
            include($configFile);
            $pageConfig = $__config;
        }
        else
        {
            // eventually call createPage script?
            $pageConfig = array();
        }
        
        // set up the form
        $formID = $this->className . 'Form';
        // finish form
        if (!isset($pageInstances[$formID]))
        {
            $pageInstances[$formID] = array('class' => 'WFForm', 'children' => array());
        }
        // deal with all of the columns
        $tpl = "{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}\n{WFLabel id=\"statusMessage\"}\n{WFShowErrors}\n{WFForm id=\"{$formID}\"}\n" . '<table border="1" cellpadding="3" cellspacing="0">' . "\n";
        foreach ($this->dbMap->getTable($this->tableName)->getColumns() as $col) {
            $displayInLayout = true;
            $widgetID = strtolower(substr($col->getPhpName(), 0, 1)) . substr($col->getPhpName(), 1);
            // handle instance
            print "Adding instance: $widgetID\n";
            if ($col->isPrimaryKey() and $this->singlePrimaryKey)
            {
                $class = 'WFHidden';
                $displayInLayout = false;
            }
            else
            {
                switch ($col->getCreoleType()) {
                    case CreoleTypes::TEXT:
                        $class = 'WFTextArea';
                        break;
                    default:
                        $class = 'WFTextField';
                }
            }
            if (!isset($pageInstances[$formID]['children'][$widgetID]))
            {
                $pageInstances[$formID]['children'][$widgetID] = array('class' => $class, 'children' => array());
            }
            if (!isset($pageConfig[$widgetID]))
            {
                print "Adding config for instance: $widgetID\n";
                $pageConfig[$widgetID] = array(
                                                'bindings' => array(
                                                                'value' => array(
                                                                                'instanceID' => $this->sharedInstanceID,
                                                                                'controllerKey' => 'selection',
                                                                                'modelKeyPath' => $widgetID
                                                                                )
                                                                )
                                            );
            }
            if ($displayInLayout)
            {
                $tpl .= "    <tr>\n        <td valign=\"top\">" . ucwords(strtolower(str_replace('_', ' ', $col->getColumnName()))) . "</td>\n        <td valign=\"top\">{{$class} id=\"$widgetID\"}{WFShowErrors id=\"{$widgetID}\"}</td>\n    </tr>\n";
            }
            else
            {
                $tpl .= "    {{$class} id=\"{$widgetID}\"}\n";
            }

        }
        // status message
        if (!isset($pageInstances['statusMessage']))
        {
            $pageInstances['statusMessage'] = array('class' => 'WFMessageBox', 'children' => array());
        }
        // submit buttons
        if (!isset($pageInstances[$formID]['children']['new']))
        {
            $pageInstances[$formID]['children']['new'] =  array('class' => 'WFSubmit', 'children' => array());
            $pageConfig['new'] = array(
                                        'properties' => array(
                                                            'label' => 'Create ' . $this->className,
                                                            ),
                                        'bindings' => array(
                                                            'hidden' => array(
                                                                             'instanceID' => $this->sharedInstanceID,
                                                                             'controllerKey' => 'selection',
                                                                             'modelKeyPath' => 'isNew',
                                                                             'options' => array(
                                                                                                'valueTransformer' => 'WFNegateBoolean',
                                                                                            )
                                                                            )
                                                            )
                                        );
        }
        if (!isset($pageInstances[$formID]['children']['save']))
        {
            $pageInstances[$formID]['children']['save'] =  array('class' => 'WFSubmit', 'children' => array());
            $pageConfig['save'] = array(
                                        'properties' => array(
                                                            'label' => 'Save'
                                                            ),
                                        'bindings' => array(
                                                            'hidden' => array(
                                                                             'instanceID' => $this->sharedInstanceID,
                                                                             'controllerKey' => 'selection',
                                                                             'modelKeyPath' => 'isNew',
                                                                            )
                                                            )
                                        );
        }
        if (!isset($pageInstances[$formID]['children']['delete']))
        {
            $pageInstances[$formID]['children']['delete'] =  array('class' => 'WFSubmit', 'children' => array());
            $pageConfig['delete'] = array(
                                        'properties' => array(
                                                            'label' => 'Delete'
                                                            ),
                                        'bindings' => array(
                                                            'hidden' => array(
                                                                             'instanceID' => $this->sharedInstanceID,
                                                                             'controllerKey' => 'selection',
                                                                             'modelKeyPath' => 'isNew',
                                                                            )
                                                            )
                                        );
        }
        $tpl .= "    <tr><td valign=\"top\" colspan=\"2\">{WFSubmit id=\"new\"}{WFSubmit id=\"save\"}{WFSubmit id=\"delete\"}</td></tr>\n";
        $tpl .= "</table>\n{/WFForm}\n";

        print "Saving updated {$pageName}.instances\n";
        PHPArrayDumper::arrayToPHPFileWithArray($pageInstances, '__instances', $instanceFile);
        print "Saving updated {$pageName}.config\n";
        PHPArrayDumper::arrayToPHPFileWithArray($pageConfig, '__config', $configFile);
        if (!file_exists($tplFile))
        {
            print "Creating {$pageName}.tpl file.\n";
            file_put_contents($tplFile, $tpl);
        }
        else
        {
            print "\nTemplate file already exists, so it will not be overwritten. Here is the template code for the table:\n$tpl\n";
        }

        // suggested module code
        $deleteSuccessPageName = 'deleteSuccess';
        $confirmDeletePageName = 'confirmDelete';
        $editCode = "
    // this function should throw an exception if the user is not permitted to edit (add/edit/delete) in the current context
    function verifyEditingPermission()
    {
        // example
        // \$authInfo = WFAuthorizationManager::sharedAuthorizationManager()->authorizationInfo();
        // if (\$authInfo->userid() != \$this->{$this->sharedInstanceID}->selection()->getUserId()) throw( new Exception(\"You don't have permission to edit {$this->className}.\") );
    }
    function {$pageName}_ParameterList()
    {
        return array('{$this->singlePrimaryKey}');
    }
    function {$pageName}_PageDidLoad(\$page, \$params)
    {
        if (\$this->{$this->sharedInstanceID}->selection() === NULL)
        {
            if (\$params['{$this->singlePrimaryKey}'])
            {
                \$this->{$this->sharedInstanceID}->setContent(array({$this->className}Peer::retrieveByPK(\$params['{$this->singlePrimaryKey}'])));
                \$this->verifyEditingPermission();
            }
            else
            {
                // prepare content for new
                \$this->{$this->sharedInstanceID}->setContent(array(new {$this->className}()));
            }
        }
    }
    function save{$this->className}(\$page)
    {
        try {
            \$this->{$this->sharedInstanceID}->selection()->save();
            \$page->outlet('statusMessage')->setValue(\"{$this->className} saved successfully.\");
        } catch (Exception \$e) {
            \$page->addError( new WFError(\$e->getMessage()) );
        }
    }
    function {$pageName}_new_Action(\$page)
    {
        \$this->save{$this->className}(\$page);
    }
    function {$pageName}_save_Action(\$page)
    {
        \$this->save{$this->className}(\$page);
    }
    function {$pageName}_delete_Action(\$page)
    {
        \$this->verifyEditingPermission();
        \$this->setupResponsePage('{$confirmDeletePageName}');
    }

    function {$confirmDeletePageName}_ParameterList()
    {
        return array('{$this->singlePrimaryKey}');
    }
    function {$confirmDeletePageName}_PageDidLoad(\$page, \$params)
    {
        // if we're a redirected action, then the {$this->className} object is already loaded. If there is no object loaded, try to load it from the object ID passed in the params.
        if (\$this->{$this->sharedInstanceID}->selection() === NULL)
        {
            \$objectToDelete = {$this->className}Peer::retrieveByPK(\$params['{$this->singlePrimaryKey}']);
            if (!\$objectToDelete) throw( new Exception(\"Could not load {$this->className} object to delete.\") );
            \$this->{$this->sharedInstanceID}->setContent(array(\$objectToDelete));
        }
        if (\$this->{$this->sharedInstanceID}->selection() === NULL) throw( new Exception(\"Could not load {$this->className} object to delete.\") );
    }
    function {$confirmDeletePageName}_cancel_Action(\$page)
    {
        \$this->setupResponsePage('{$pageName}');
    }
    function {$confirmDeletePageName}_delete_Action(\$page)
    {
        \$this->verifyEditingPermission();
        \$myObj = \$this->{$this->sharedInstanceID}->selection();
        \$myObj->delete();
        \$this->{$this->sharedInstanceID}->removeObject(\$myObj);
        \$this->setupResponsePage('{$deleteSuccessPageName}');
    }

        \n";
        // write out confirmDelete page
        if (!file_exists($confirmDeletePageName . '.tpl'))
        {
            // set up instances/config
            $tplFile = $confirmDeletePageName . '.tpl';
            $instanceFile = $confirmDeletePageName . '.instances';
            if (file_exists($instanceFile))
            {
                include($instanceFile);
                $pageInstances = $__instances;
            }
            else
            {
                // eventually call createPage script?
                $pageInstances = array();
            }
            $configFile = $confirmDeletePageName . '.config';
            if (file_exists($configFile))
            {
                include($configFile);
                $pageConfig = $__config;
            }
            else
            {
                // eventually call createPage script?
                $pageConfig = array();
            }
            $formID = $this->className . 'ConfirmDeleteForm';
            $pageInstances[$formID] = array('class' => 'WFForm', 'children' => array());
            $pageInstances[$formID]['children'][$this->singlePrimaryKey] = array('class' => 'WFHidden', 'children' => array());
            $pageConfig[$this->singlePrimaryKey] = array(
                                                        'bindings' => array(
                                                                        'value' => array(
                                                                                        'instanceID' => $this->sharedInstanceID,
                                                                                        'controllerKey' => 'selection',
                                                                                        'modelKeyPath' => $this->singlePrimaryKey
                                                                                        )
                                                                        )
                                                        );
            $pageInstances[$formID]['children']['cancel'] = array('class' => 'WFSubmit', 'children' => array());
            $pageConfig['cancel'] = array(
                                        'properties' => array(
                                                            'label' => 'Cancel'
                                                            )
                                        );
            $pageInstances[$formID]['children']['delete'] = array('class' => 'WFSubmit', 'children' => array());
            $pageConfig['delete'] = array(
                                        'properties' => array(
                                                            'label' => 'Delete'
                                                            )
                                        );
            $pageInstances['confirmMessage'] = array('class' => 'WFMessageBox', 'children' => array());
            $pageConfig['confirmMessage'] = array(
                                                        'bindings' => array(
                                                                        'value' => array(
                                                                                        'instanceID' => $this->sharedInstanceID,
                                                                                        'controllerKey' => 'selection',
                                                                                        'modelKeyPath' => $this->mainColumnName,
                                                                                        'options' => array(
                                                                                                            'ValuePattern' => 'Are you sure you want to delete ' . $this->className . ' "%1%"?'
                                                                                                            )
                                                                                        )
                                                                        )
                                                        );
            // save setup to disk
            print "Saving updated {$confirmDeletePageName}.instances\n";
            PHPArrayDumper::arrayToPHPFileWithArray($pageInstances, '__instances', $instanceFile);
            print "Saving updated {$confirmDeletePageName}.config\n";
            PHPArrayDumper::arrayToPHPFileWithArray($pageConfig, '__config', $configFile);
            // set up tpl
            print "Creating {$confirmDeletePageName}.tpl file.\n";
            file_put_contents($confirmDeletePageName . '.tpl', "{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
{WFMessageBox id=\"confirmMessage\"}
{WFForm id=\"{$this->className}ConfirmDeleteForm\"}
    {WFHidden id=\"{$this->singlePrimaryKey}\"}
    {WFSubmit id=\"cancel\"}{WFSubmit id=\"delete\"}
{/WFForm}
            ");
        }
        else
        {
            print "\nTemplate file '$confirmDeletePageName' already exists, so it will not be overwritten.";
        }

        // write out deleteSuccess page
        if (!file_exists($deleteSuccessPageName . '.tpl'))
        {
            // set up instances/config
            $tplFile = $deleteSuccessPageName . '.tpl';
            $instanceFile = $deleteSuccessPageName . '.instances';
            if (file_exists($instanceFile))
            {
                include($instanceFile);
                $pageInstances = $__instances;
            }
            else
            {
                // eventually call createPage script?
                $pageInstances = array();
            }
            $configFile = $deleteSuccessPageName . '.config';
            if (file_exists($configFile))
            {
                include($configFile);
                $pageConfig = $__config;
            }
            else
            {
                // eventually call createPage script?
                $pageConfig = array();
            }
            $pageInstances['statusMessage'] = array('class' => 'WFMessageBox', 'children' => array());
            $pageConfig['statusMessage'] = array(
                                                    'properties' => array(
                                                                    'value' => $this->className . ' successfully deleted.'
                                                                    )
                                                );
            // save setup to disk
            print "Saving updated {$deleteSuccessPageName}.instances\n";
            PHPArrayDumper::arrayToPHPFileWithArray($pageInstances, '__instances', $instanceFile);
            print "Saving updated {$deleteSuccessPageName}.config\n";
            PHPArrayDumper::arrayToPHPFileWithArray($pageConfig, '__config', $configFile);
            print "Creating {$deleteSuccessPageName}.tpl file.\n";
            file_put_contents($deleteSuccessPageName . '.tpl', "{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}\n{WFMessageBox id=\"statusMessage\"}");
        }
        else
        {
            print "\nTemplate file '$deleteSuccessPageName' already exists, so it will not be overwritten.";
        }
        return $editCode;
    }
}

?>
