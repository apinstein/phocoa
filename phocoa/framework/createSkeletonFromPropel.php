<?php

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
$longopts = array('phocoaConfFile=', 'tableName=', 'propelOutputDir=', 'propelConfFile=', 'databaseName=', 'pageName=', 'pageType=');

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
        case '--phocoaConfFile':
            $__config['phocoaConfFile'] = $optValue;
            break;
        case '--tableName':
            $__config['tableName'] = $optValue;
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
if (empty($__config['tableName'])) throw( new Exception("tableName must be specified.") );
if (empty($__config['phocoaConfFile'])) throw( new Exception("phocoaConfFile must be specified.") );
if (empty($__config['propelConfFile'])) throw( new Exception("propelConfFile must be specified.") );
if (empty($__config['propelOutputDir'])) throw( new Exception("propelOutputDir must be specified. This is the directory that propel generates all classes in, without the database name. Same as the build file for propel.") );
if (empty($__config['propelDatabaseName'])) throw( new Exception("databaseName must be specified.") );
if (empty($__config['pageType'])) throw( new Exception("pageType must be specified.") );
if (!isset($__config['phocoaPageName']))
{
    $__config['phocoaPageName'] = $__config['pageType'];
}
if (!in_array($__config['pageType'], SkeletonDumperPropel::pageTypes())) throw( new Exception("pageType must be one of: " . join(',', SkeletonDumperPropel::pageTypes())) );

// include the web app to bootstrap phocoa
require_once $__config['phocoaConfFile'];
require_once 'framework/WFWebApplication.php';
require_once 'framework/util/PHPArrayDumper.php';

// set up ini path to include the propel dir
ini_set('include_path', ini_get('include_path') . ":{$__config['propelOutputDir']}");
// load propel conf
require_once($__config['propelConfFile']);
// load desired object file
$propelClassesDir = $__config['propelOutputDir'] . "/{$__config['propelDatabaseName']}";
$propelObjectName = NameFactory::generateName(NameFactory::PHP_GENERATOR, array($__config['tableName'], NameGenerator::CONV_METHOD_PHPNAME));
require_once( $propelClassesDir . '/' . $propelObjectName . '.php');
// start up propel; this will load the database map including info for all included files
Propel::init($__config['propelConfFile']);
// load databaseMap into inst var
$propelDBMap = Propel::getDatabaseMap($__config['propelDatabaseName']);

$sdp = new SkeletonDumperPropel($propelDBMap, $__config['tableName']);
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
        $this->className = NameFactory::generateName(NameFactory::PHP_GENERATOR, array($tableName, NameGenerator::CONV_METHOD_PHPNAME));
        $this->sharedInstanceID = $this->className;
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
            print "\nTemplate file already exists, so it will not be overwritten. Here is the template code for the table:\n$tpl\n";
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
        $tpl = "{WFLabel id=\"statusMessage\"}\n{WFShowErrors}\n{WFForm id=\"{$formID}\"}\n" . '<table border="1" cellpadding="3" cellspacing="0">' . "\n";
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
        print "Suggested module code:
    // this function should throw an exception if the user is not permitted to edit (add/edit/delete) in the current context
    function verifyEditingPermissions()
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
        if (\$params['{$this->singlePrimaryKey}'])
        {
            \$this->{$this->sharedInstanceID}->setContent(array({$this->className}Peer::retrieveByPK(\$params['{$this->singlePrimaryKey}'])));
            \$this->verifyEditingPermission();
        }
        else
        {
            // will prepare content automatically, for new instance
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

    function {$confirmDeletePageName}_PageDidLoad(\$page, \$params)
    {
        if (\$page->hasSubmittedForm())
        {
            // load selection from form hidden field
            \$this->{$this->sharedInstanceID}->setContent(array({$this->className}Peer::retrieveByPK(\$page->outlet('{$this->singlePrimaryKey}')->value())));
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
                                                                                        'modelKeyPath' => $this->singlePrimaryKey,
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
{WFForm id=\"CommercialOpportunityConfirmDeleteForm\"}
    {WFHidden id=\"opportunityId\"}
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
    }
}

?>
