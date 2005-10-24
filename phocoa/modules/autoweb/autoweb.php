<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

require_once 'framework/WFPagination.php';

class autoweb extends WFModule
{
    // propel setup
    protected $propelDBMap;

    // shared instances
    protected $records;

    function __construct($i)
    {
        parent::__construct($i);
        $this->propelDBMap = NULL;

        $this->readConfig();
    }

    /**
     *  Read the config file which contains info on the Propel database to work with. Initialize things.
     */
    function readConfig()
    {
        // load config file
        include_once('autoweb.config');
        
        // load the proper propel stuff -- this should be moved to a config file
        ini_set('include_path', ini_get('include_path') . ":{$__autowebConfig['propelOutputDir']}");
        require_once($__autowebConfig['propelConfFile']);
        // walk propel directory for a list of contents
        $propelClassesDir = $__autowebConfig['propelOutputDir'] . "/{$__autowebConfig['propelDatabaseName']}";
        foreach (new DirectoryIterator($propelClassesDir) as $item) {
            if ( !(preg_match('/^[^\.].*\.php$/', $item) == 1) ) continue; // skip everything buy PHP classs, skip invisible files
            $propelFile = $propelClassesDir . '/' . $item;
            require_once( $propelFile  );   // include everything
        }
        
        // start up propel; this will load the database map including info for all included files
        Propel::init($__autowebConfig['propelConfFile']);
        // load databaseMap into inst var
        $this->propelDBMap = Propel::getDatabaseMap($__autowebConfig['propelDatabaseName']);
    }

    function defaultPage()
    {
        return 'objects';
    }

    // Uncomment additional functions as needed
    function sharedInstancesDidLoad()
    {
    }

    function browse_ParameterList()
    {
        return array('objectName', 'paginatorState');
    }
    /**
     *  @todo Build a resource plugin that creates the {WFLabel id="XXX"} on the fly so we can use bindings etc.
     *  @todo also eventually we will have a convention for calling certain methods to get metadata about all discoverable properties.
     */
    function browse_PageDidLoad($page, $params)
    {
        $tName = $params['objectName'];
        try {
            $tMap = $this->propelDBMap->getTable($tName);
        } catch (Exception $e) {
            header("Location: " . WFRequestController::WFURL($this->invocation()->modulePath(), 'objects'));
            exit;
        }

        $peerMethod = $tMap->getPhpName() . "Peer";

        // set up paginator; query for data
        // set up sort links
        $cols = array();
        $sortOptions = array();
        $defaultSortKeys = array();
        $dynamicWidgetIDs = array();
        $primaryKeys = array();
        foreach ($tMap->getColumns() as $col) {
            $cols[$col->getColumnName()] = $col->getPhpName();
            $sortOptions['+' . $col->getFullyQualifiedName()] = $col->getPhpName();
            $sortOptions['-' . $col->getFullyQualifiedName()] = $col->getPhpName();
            if ($col->isPrimaryKey())
            {
                $defaultSortKeys[] = '+' . $col->getFullyQualifiedName();
                $primaryKeys[] = $col->getPhpName();
            }
            // create sort widget
            $sw = new WFPaginatorSortLink("sortLink_" . $col->getPhpName(), $page);
            $sw->setPaginator($this->paginator);
            $sw->setValue($col->getFullyQualifiedName());
            $dynamicWidgetIDs[$col->getPhpName()]['sortLink'] = $sw->id();

            // create label widget via WFDynamic
            $lw = new WFDynamic("label_" . $col->getPhpName(), $page);
            $lw->setWidgetClass('WFLabel');
            $lw->setArrayController($this->records);
            $lw->setSimpleBindKeyPath($col->getPhpName());
            $labelConfig = array(
                                'ellipsisAfterChars' => array(
                                                            'custom' => array('iterate' => false, 'value' => 50)
                                                            ),
                                );
            $lw->setWidgetConfig($labelConfig);
            $dynamicWidgetIDs[$col->getPhpName()]['label'] = $lw->id();
        }
        $this->paginator->setSortOptions($sortOptions);
        $this->paginator->setDefaultSortKeys($defaultSortKeys);
        
        $this->paginator->setDataDelegate(new WFPagedPropelQuery(new Criteria(), $peerMethod));
        $this->paginator->setPaginatorState($params['paginatorState']);
        $result = $this->paginator->currentItems();

        // set up controller
        $this->records->setClass($tMap->getPhpName());
        $this->records->setClassIdentifiers($primaryKeys);
        $this->records->setContent($result);

        $page->assign('dynamicWidgetIDs', $dynamicWidgetIDs);
        $page->assign('tableMap', $tMap);
        $page->assign('columns', $cols);
        $page->assign('objects', $result);

        $this->browseTableName = $tMap->getName();
    }
    function browse_SetupSkin($skin)
    {
        $skin->setTitle('Browsing ' . $this->browseTableName);
    }

    function objects_SetupSkin($skin)
    {
        $skin->setTitle('PHOCOA / Propel Object Admin');
    }

    function objects_PageDidLoad($page, $params)
    {
        $propelObjects = array();
        foreach ($this->propelDBMap->getTables() as $tableMap) {
            $propelObjects[] = $tableMap->getName();
        }

        $page->assign('propelObjects', $propelObjects);
    }
}
