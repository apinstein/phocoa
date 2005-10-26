<?php

require_once 'Console/Getopt.php';
require_once '/Users/alanpinstein/dev/sandbox/phocoadev/phocoadev/conf/webapp.conf';
require_once 'framework/WFWebApplication.php';
require_once 'framework/util/PHPArrayDumper.php';

$shortopts = "";
$longopts = array('objectName=', 'outputDir==', 'confFile=', 'databaseName=', 'pageName=', 'pageType=');

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
        case '--objectName':
            $__config['objectName'] = $optValue;
            break;
        case '--confFile':
            $__config['propelConfFile'] = $optValue;
            break;
        case '--outputDir':
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
// assert params
if (!isset($__config['objectName'])) throw( new Exception("objectName must be specified.") );
if (!isset($__config['propelConfFile'])) throw( new Exception("confFile must be specified.") );
if (!isset($__config['propelOutputDir'])) throw( new Exception("outputDir must be specified.") );
if (!isset($__config['propelDatabaseName'])) throw( new Exception("databaseName must be specified.") );
if (!isset($__config['pageType'])) throw( new Exception("pageType must be specified.") );
if (!isset($__config['phocoaPageName'])) throw( new Exception("phocoaPageName must be specified.") );
if (!in_array($__config['pageType'], SkeletonDumperPropel::pageTypes())) throw( new Exception("pageType must be one of: " . join(',', SkeletonDumperPropel::pageTypes())) );

// set up ini path to include the propel dir
ini_set('include_path', ini_get('include_path') . ":{$__config['propelOutputDir']}");
// load propel conf
require_once($__config['propelConfFile']);
// load desired object file
$propelClassesDir = $__config['propelOutputDir'] . "/{$__config['propelDatabaseName']}";
require_once( $propelClassesDir . '/' . $__config['objectName'] . '.php');
// start up propel; this will load the database map including info for all included files
Propel::init($__config['propelConfFile']);
// load databaseMap into inst var
$propelDBMap = Propel::getDatabaseMap($__config['propelDatabaseName']);

$sdp = new SkeletonDumperPropel($propelDBMap, $__config['objectName']);
switch ($__config['pageType']) {
    case 'edit':
        $sdp->updateEditPage($__config['phocoaPageName']);
        break;
    case 'list':
        $sdp->updateListPage($__config['phocoaPageName']);
        break;
    case 'detail':
        $sdp->updateDetailPage($__config['phocoaPageName']);
        break;
}
print "\n";

class SkeletonDumperPropel
{
    protected $dbMap;
    protected $tableName;
    protected $singlePrimaryKey;
    
    function __construct($dbMap, $tableName)
    {
        $this->tableName = $tableName;
        $this->sharedInstanceID = $this->tableName;
        $this->className = ucfirst($this->sharedInstanceID);
        $this->dbMap = $dbMap;
        $this->singlePrimaryKey = NULL;
    }

    function pageTypes()
    {
        return array('detail', 'edit', 'search', 'list');
    }

    function updateSharedSetup()
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
                                                                        )
                                                    );
        }
        print "Saving updated shared.config\n";
        PHPArrayDumper::arrayToPHPFileWithArray($sharedConfig, '__config', 'shared.config');

    }

    function updateDetailPage($pageName)
    {
        $this->updateSharedSetup();

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
        $tpl = '<table border="1" cellpadding="3" cellspacing="0">' . "\n";
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
            print "Template file already exists, so it will not be overwritten. Here is the template code for the table:\n$tpl\n";
        }

        // suggested module code
        print "Suggested module code:
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
        $this->updateSharedSetup();

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
        $formID = $this->tableName . 'Form';
        // finish form
        if (!isset($pageInstances[$formID]))
        {
            $pageInstances[$formID] = array('class' => 'WFForm', 'children' => array());
        }
        // deal with all of the columns
        $tpl = "{WFShowErrors}\n{WFForm id=\"{$formID}\"}\n" . '<table border="1" cellpadding="3" cellspacing="0">' . "\n";
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
            print "Template file already exists, so it will not be overwritten. Here is the template code for the table:\n$tpl\n";
        }

        // suggested module code
        $deleteSuccessPageName = 'deleteSuccess';
        print "Suggested module code:
    function {$pageName}_ParameterList()
    {
        return array('{$this->singlePrimaryKey}');
    }
    function {$pageName}_PageDidLoad(\$page, \$params)
    {
        if (\$params['{$this->singlePrimaryKey}'])
        {
            \$this->{$this->sharedInstanceID}->setContent(array({$this->className}Peer::retrieveByPK(\$params['{$this->singlePrimaryKey}'])));
        }
        else
        {
            // will prepare content automatically, for new instance
        }
    }
    function saveAgent(\$page)
    {
        try {
            \$this->{$this->sharedInstanceID}->selection()->save();
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
        \$myObj = \$this->{$this->sharedInstanceID}->selection();
        \$myObj->delete();
        \$this->{$this->sharedInstanceID}->removeObject(\$myObj);
        \$this->setupResponsePage('$deleteSuccessPageName');
    }
        \n";
        // write out deleteSuccess page.
        if (!file_exists($deleteSuccessPageName . '.tpl'))
        {
            print "Creating {$deleteSuccessPageName}.tpl file.\n";
            file_put_contents($deleteSuccessPageName . '.tpl', $this->className . " successfully deleted.");
        }
        else
        {
            print "Template file '$deleteSuccessPageName' already exists, so it will not be overwritten.";
        }
    }
}

?>
