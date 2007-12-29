<?php

require_once($_ENV['PHOCOA_PROJECT_CONF']);

interface WFModelBuilder
{
    // get a WFModelEntity for the given entity name
    function buildEntityModel($name);
}

// PROPEL 1.2/1.3 (check both versions!) model builder
class WFModelBuilderPropel extends WFObject implements WFModelBuilder
{
    function setup()
    {
        Propel::init(PROPEL_CONF);
    }

    /**
     * Load the propel metadata for the given entity.
     *
     * @param string The name of the entity, as it's PHP class name.
     * @return object TableMap The Propel TableMap for this entity.
     */
    function loadEntityMetadata($name)
    {
        // in Propel, the MapBuilder class is only set up for an entity when the Peer file is loaded...
        $peerClassName = $name . 'Peer';
        $databaseName = eval("return {$peerClassName}::DATABASE_NAME;");        // autolaod will load the Peer file...
        $dbMap = Propel::getDatabaseMap($databaseName);
        $tableMapTableName = eval("return {$peerClassName}::TABLE_NAME;");
        $tableMap = $dbMap->getTable($tableMapTableName);
        return $tableMap;
    }
    function buildEntityModel($name)
    {
        // build a WFModelEntity structure from the Propel metadata....
        $tableMap = $this->loadEntityMetadata($name); 

        // set up entity
        $entity = new WFModelEntity;
        $entity->setValueForKey($tableMap->getPhpName(), 'name');

        // set up attributes
        foreach ($tableMap->getColumns() as $column) {
            $attribute = new WFModelEntityAttribute;
            $attribute->setValueForKey($column->getPhpName(), 'name');
            switch ($column->getType()) {
                case 'INTEGER':
                case 'int':
                    $type = WFModelEntityAttribute::TYPE_NUMBER;
                    break;
                case 'TIMESTAMP':
                case 'datetime':
                    $type = WFModelEntityAttribute::TYPE_DATETIME;
                    break;
                case 'text':
                    $type = WFModelEntityAttribute::TYPE_TEXT;
                    break;
                case 'boolean':
                    $type = WFModelEntityAttribute::TYPE_BOOLEAN;
                    break;
                case 'VARCHAR':
                case 'string':
                    $type = WFModelEntityAttribute::TYPE_STRING;
                    break;
                default: 
                    print "WARNING: Unknown attribute type for column: " . $attribute->valueForKey('name') . ":" . $column->getType() . "\n";
                    $type = WFModelEntityAttribute::TYPE_STRING;
                    break;
            }
            if (!$entity->valueForKey('descriptiveColumnName') && $type === WFModelEntityAttribute::TYPE_STRING)
            {
                $entity->setValueForKey($attribute->valueForKey('name'), 'descriptiveColumnName');
            }
            if (!$entity->valueForKey('primaryKeyAttribute') && $column->isPrimaryKey())
            {
                $entity->setValueForKey($attribute->valueForKey('name'), 'primaryKeyAttribute');
            }
            $attribute->setValueForKey($type, 'type');
            $entity->addAttribute($attribute);
        }

        // set up relationships
        foreach ($tableMap->getColumns() as $column) {
            if (!$column->isForeignKey()) continue;
            $rel = new WFModelEntityRelationship;
            // get related entity
            $relatedEntityTableMap = $this->loadEntityMetadata( ucfirst($column->getRelatedTableName()) );
            $rel->setValueForKey($relatedEntityTableMap->getPhpName(), 'name');
            if ($column->isNotNull())
            {
                $rel->setValueForKey(WFModelEntityRelationship::CARDINALITY_ONE_TO_ONE_OR_MORE, 'cardinality');
            }
            else
            {
                $rel->setValueForKey(WFModelEntityRelationship::CARDINALITY_ONE_TO_ZERO_OR_MORE, 'cardinality');
            }
            // is this an "extension" table?

            // add relationship
            $entity->addRelationship($rel);

            // add to builder list
            WFModel::sharedModel()->addEntityToBuild($relatedEntityTableMap->getPhpName());
        }

        return $entity;
    }
}

// PHOCOA WFModel Class Structure -- internal representation of the object model. Decoupled from the implementations.
class WFModel extends WFObject
{
    protected $entitiesToBuild = array();
    protected $entities = array();
    static private $_instance = NULL;

    function sharedModel()
    {
        if (!self::$_instance)
        {
            self::$_instance = new WFModel;
        }
        return self::$_instance;
    }

    public function addEntity($entity)
    {
        $this->entities[$entity->valueForKey('name')] = $entity;
    }

    public function entities()
    {
        return $this->entities;
    }

    public function getEntity($name)
    {
        if (!isset($this->entities[$name])) throw( new WFException("Entity {$name} is not loaded in the model.") );
        return $this->entities[$name];
    }

    public function addEntityToBuild($entityName)
    {
        array_push($this->entitiesToBuild, $entityName);
    }

    public function buildModel($adapter, $configFile, $buildEntities)
    {
        // bootstrap
        $builderClass = 'WFModelBuilder' . $adapter;
        $builder = new $builderClass;
        $builder->setup();
        foreach ($buildEntities as $entity) {
            $this->addEntityToBuild($entity);
        }

        // run builder
        while ( ($entity = array_pop($this->entitiesToBuild)) !== NULL) {
            $this->addEntity($builder->buildEntityModel($entity));
        }


        if (file_exists($configFile))
        {
            // READ CONFIG - read a config YAML file and "override" settings in various entities, such as descriptiveColumnName, or cardinality:
            $config = WFYaml::load($configFile);
            // Blog:
            //   descriptiveColumnName: name
            //   relationships:
            //     BlogPreferences:
            //       cardinality: WFModelEntityRelationship::CARDINALITY_ONE_TO_EXACLTY_ONE
            //       isExtension: true

            // apply config
            foreach ($config as $entityName => $entityConfig) {
                try {
                    $entity = $this->getEntity($entityName);
                } catch (WFException $e) {
                    print "WARNING: Entity {$entityName} not loaded...\n";
                    continue;
                }
                foreach ($entityConfig as $key => $config) {
                    switch ($key) {
                        case 'relationships':
                            foreach ($config as $relationshipName => $relationshipConfig) {
                                $rel = $entity->getRelationship($relationshipName);
                                if (!$rel)
                                {
                                    print "WARNING: Relationship: {$relationshipName} of Entity {$entityName} not loaded...\n";
                                    continue;
                                }
                                foreach ($relationshipConfig as $key => $value) {
                                    switch ($key) {
                                        case 'cardinality':
                                            $value = eval("return {$value};");
                                            break;
                                    }
                                    $rel->setValueForKey($value, $key);
                                }
                            }
                            break;
                        case 'attributes':
                            foreach ($config as $attributeName => $attributeConfig) {
                                $attr = $entity->getAttribute($attributeName);
                                if (!$attr)
                                {
                                    print "WARNING: Attribute: {$attributeName} of Entity {$entityName} not loaded...\n";
                                    continue;
                                }
                                foreach ($attributeConfig as $key => $value) {
                                    switch ($key) {
                                        case 'type':
                                            $value = eval("return {$value};");
                                            break;
                                    }
                                    $attr->setValueForKey($value, $key);
                                }
                            }
                            break;
                        default:
                            $entity->setValueForKey($config, $key);
                            break;
                    }
                }
            }
        }
    }
}

class WFModelEntity extends WFObject
{
    protected $name = NULL;
    protected $primaryKeyAttribute = NULL;
    protected $descriptiveColumnName = NULL;
    protected $attributes = array();
    protected $relationships = array();

    public function addAttribute($attr)
    {
        if (!($attr instanceof WFModelEntityAttribute)) throw( new WFException("addAttribute parameter must be a WFModelEntityAttribute.") );
        $this->attributes[] = $attr;
        return $this;
    }
    public function getAttribute($name)
    {
        if (isset($this->attributes[$name])) return $this->attributes[$name];
        return NULL;
    }
    public function getAttributes()
    {
        return $this->attributes;
    }

    public function addRelationship($rel)
    {
        if (!($rel instanceof WFModelEntityRelationship)) throw( new WFException("addRelationship parameter must be a WFModelEntityRelationship.") );
        $this->relationships[$rel->valueForKey('name')] = $rel;
        return $this;
    }
    public function getRelationship($name)
    {
        if (isset($this->relationships[$name])) return $this->relationships[$name];
        return NULL;
    }
}
class WFModelEntityAttribute extends WFObject
{
    protected $name = NULL;
    protected $type = WFModelEntityAttribute::TYPE_STRING;

    const TYPE_STRING = 'string';
    const TYPE_TEXT = 'text';
    const TYPE_NUMBER = 'number';
    const TYPE_DATETIME = 'datetime';
    const TYPE_TIME = 'time';
    const TYPE_DATE = 'date';
    const TYPE_BOOLEAN = 'boolean';
}
class WFModelEntityRelationship extends WFObject
{
    protected $name = NULL;
    protected $isExtension = false;
    protected $cardinality = WFModelEntityRelationship::CARDINALITY_ONE_TO_ZERO_OR_MORE;

    const CARDINALITY_ONE_TO_EXACLTY_ONE    = '1-1,1';
    const CARDINALITY_ONE_TO_ONE_OR_MORE    = '1-1,*';
    const CARDINALITY_ONE_TO_ZERO_OR_ONE    = '1-0,1';
    const CARDINALITY_ONE_TO_ZERO_OR_MORE   = '1-0,*';
    const CARDINALITY_MANY_TO_ZERO_OR_MORE  = '*-0,*';
    const CARDINALITY_MANY_TO_ONE_OR_MORE   = '*-1,*';
}

// PHOCOA Code-Gen Classes
// Right now, hard-coded for Propel; refactor later to call out to builder classes for things like entity lookup code (any ORM-specific stuff)
class WFModelCodeGenPropel extends WFObject
{
    protected $smarty = NULL;
    protected $modulePath = NULL;

    function __construct()
    {
        $this->smarty = new WFSmarty;
        $this->smarty->left_delimiter = '{{';
        $this->smarty->right_delimiter = '}}';

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
        $this->modulePath .= '/';
    }

    function generateModuleForEntity($entity)
    {
        print "Generating module for entity '" . $entity->valueForKey('name') . "'\n";
        $cwd = getcwd();
        $moduleName = strtolower( $entity->valueForKey('name') );
        $moduleDir = $cwd . '/' . $moduleName;
        if (file_exists($moduleDir))
        {
            print "WARNING: Module $moduleName already exists. Skipping\n";
            return;
        }

        mkdir($moduleDir); // module dir
        $this->modulePath .= $moduleName;

        // setup shared instances
        $sharedYaml[$entity->valueForKey('name')] = array(
                'class' => 'WFArrayController',
                'properties' => array(
                    'class' => $entity->valueForKey('name'),
                    'classIdentifiers' => $entity->valueForKey('primaryKeyAttribute'),
                    'selectOnInsert' => true,
                    'automaticallyPreparesContent' => false
                    )
                );
        $sharedYaml['paginator'] = array(
                'class' => 'WFPaginator',
                'properties' => array(
                    'modeForm' => 'search',
                    'pageSize' => 25,
                    'itemPhraseSingular' => $entity->valueForKey('name'),
                    'itemPhrasePlural' => $entity->valueForKey('name') . 's'
                    )
                );
        file_put_contents($moduleDir . '/shared.yaml', WFYaml::dump($sharedYaml));

        $sharedEntityId = $entity->valueForKey('name');

        // build module code
        $this->smarty->assign('moduleName', $moduleName);
        $this->smarty->assign('entity', $entity);
        $this->smarty->assign('entityName', $entity->valueForKey('name'));
        $this->smarty->assign('sharedEntityId', $sharedEntityId);
        $this->smarty->assign('sharedEntityPrimaryKeyAttribute', $entity->valueForKey('primaryKeyAttribute'));
        $this->smarty->assign('descriptiveColumnName', $entity->valueForKey('descriptiveColumnName'));
        $this->smarty->assign('descriptiveColumnConstantName', strtoupper($entity->valueForKey('descriptiveColumnName')));
        $moduleCode = $this->smarty->fetch(FRAMEWORK_DIR . '/framework/generator/module.tpl');
        file_put_contents($moduleDir . '/' . $moduleName . '.php', $moduleCode);

        // build list page
        // list.yaml
        $listYaml = array();
        $listFormId = 'search' . $entity->valueForKey('name') . 'Form';
        $listYaml[$listFormId] = array(
                'class' => 'WFForm', 'children' => array(
                    'search' => array(
                        'class' => 'WFSubmit',
                        'properties' => array(
                            'label' => 'Search'
                            ),
                        ),
                    'paginatorState' => array(
                        'class' => 'WFPaginatorState',
                        'properties' => array('paginator' => '#module#paginator')
                        ),
                    'query' => array('class' => 'WFTextField'),
                    )
                );
        $listYaml['paginatorNavigation'] = array(
                'class' => 'WFPaginatorNavigation',
                'properties' => array('paginator' => '#module#paginator'),
                );
        $listYaml['paginatorPageInfo'] = array(
                'class' => 'WFPaginatorPageInfo',
                'properties' => array('paginator' => '#module#paginator'),
                );

        $descriptiveColumnName = $entity->valueForKey('descriptiveColumnName');
        $listYaml[$descriptiveColumnName] = array(
                'class' => 'WFDynamic',
                'properties' => array(
                    'arrayController' => "#module#{$sharedEntityId}",
                    ),
                'children' => array(
                    "{$descriptiveColumnName}Prototype" => array(
                        'class' => 'WFLink',
                        'bindings' => array(
                            'value' => array(
                                'instanceID' => $sharedEntityId,
                                'controllerKey' => '#current#',
                                'modelKeyPath' => $entity->valueForKey('primaryKeyAttribute'),
                                'options' => array('ValuePattern' => $this->modulePath . '/detail/%1%')
                                ),
                            'label' => array(
                                'instanceID' => $sharedEntityId,
                                'controllerKey' => '#current#',
                                'modelKeyPath' => $entity->valueForKey('descriptiveColumnName')
                                )
                            )
                        )
                    )
                    );
        $listYaml['editLink'] = array(
                'class' => 'WFDynamic',
                'properties' => array(
                    'arrayController' => "#module#{$sharedEntityId}",
                    ),
                'children' => array(
                    "editLinkPrototype" => array(
                        'class' => 'WFLink',
                        'properties' => array('label' => 'Edit'),
                        'bindings' => array(
                            'value' => array(
                                'instanceID' => $sharedEntityId,
                                'controllerKey' => '#current#',
                                'modelKeyPath' => $entity->valueForKey('primaryKeyAttribute'),
                                'options' => array('ValuePattern' => $this->modulePath . '/edit/%1%')
                                )
                            )
                        ) 
                    )
                );
        $listYaml['deleteLink'] = array(
                'class' => 'WFDynamic',
                'properties' => array(
                    'arrayController' => "#module#{$sharedEntityId}",
                    ),
                'children' => array(
                    "deleteLinkPrototype" => array(
                        'class' => 'WFLink',
                        'properties' => array('label' => 'Delete'),
                        'bindings' => array(
                            'value' => array(
                                'instanceID' => $sharedEntityId,
                                'controllerKey' => '#current#',
                                'modelKeyPath' => $entity->valueForKey('primaryKeyAttribute'),
                                'options' => array('ValuePattern' => $this->modulePath . '/confirmDelete/%1%')
                                )
                            )
                        ) 
                    )
                );
        file_put_contents($moduleDir . '/list.yaml', WFYaml::dump($listYaml));

        // build list.tpl
        $this->smarty->assign('listFormId', $listFormId);
        file_put_contents($moduleDir . '/list.tpl', $this->smarty->fetch(FRAMEWORK_DIR . '/framework/generator/list.tpl'));

        // build edit page
        // build edit.yaml
        $editYaml = array();
        $editFormId = 'edit' . $entity->valueForKey('name') . 'Form';
        $editYaml[$editFormId] = array('class' => 'WFForm', 'children' => array());

        $widgets = array();
        foreach ($entity->getAttributes() as $attr) {
            $widgetID = $attr->valueForKey('name');
            $widgets[$widgetID] = $attr;

            if ($attr->valueForKey('name') === $entity->valueForKey('primaryKeyAttribute'))
            {
                $class = 'WFHidden';
            }
            else
            {
                switch ($attr->valueForKey('type')) {
                    case WFModelEntityAttribute::TYPE_TEXT;
                        $class = 'WFTextArea';
                        break;
                    case WFModelEntityAttribute::TYPE_NUMBER;
                    case WFModelEntityAttribute::TYPE_STRING;
                    case WFModelEntityAttribute::TYPE_DATETIME;
                    case WFModelEntityAttribute::TYPE_TIME;
                    case WFModelEntityAttribute::TYPE_DATE;
                        $class = 'WFTextField';
                        break;
                    case WFModelEntityAttribute::TYPE_BOOLEAN;
                        $class = 'WFCheckbox';
                        break;
                    default:
                        $class = 'WFTextField';
                }
            }
            $editYaml[$editFormId]['children'][$widgetID] = array(
                    'class' => $class,
                    'bindings' => array(
                        'value' => array(
                            'instanceID' => $sharedEntityId,
                            'controllerKey' => 'selection',
                            'modelKeyPath' => $widgetID
                            )
                        )
                    );

        }
        // status message
        $editYaml['statusMessage'] = array('class' => 'WFMessageBox');
        $editYaml[$editFormId]['children']['new'] = array(
                'class' => 'WFSubmit',
                'properties' => array(
                    'label' => 'Create ' . $entity->valueForKey('name'),
                    'action' => 'save'
                    ),
                'bindings' => array(
                    'hidden' => array(
                        'instanceID' => $sharedEntityId,
                        'controllerKey' => 'selection',
                        'modelKeyPath' => 'isNew',
                        'options' => array(
                            'valueTransformer' => 'WFNegateBoolean',
                            )
                        )
                    )
                );
        $editYaml[$editFormId]['children']['save'] = array(
                'class' => 'WFSubmit',
                'properties' => array(
                    'label' => 'Save'
                    ),
                'bindings' => array(
                    'hidden' => array(
                        'instanceID' => $sharedEntityId,
                        'controllerKey' => 'selection',
                        'modelKeyPath' => 'isNew',
                        )
                    )
                );
        $editYaml[$editFormId]['children']['delete'] = array(
                'class' => 'WFSubmit',
                'properties' => array(
                    'label' => 'Delete'
                    ),
                'bindings' => array(
                    'hidden' => array(
                        'instanceID' => $sharedEntityId,
                        'controllerKey' => 'selection',
                        'modelKeyPath' => 'isNew',
                        )
                    )
                );
        file_put_contents($moduleDir . '/edit.yaml', WFYaml::dump($editYaml));

        // build edit.tpl
        $this->smarty->assign('editFormId', $editFormId);
        $this->smarty->assign('widgets', $widgets);
        file_put_contents($moduleDir . '/edit.tpl', $this->smarty->fetch(FRAMEWORK_DIR . '/framework/generator/edit.tpl'));

        // build confirmDelete page
        $confirmDeleteYaml = array();
        $confirmDeleteFormId = 'confirmDelete' . $entity->valueForKey('name')  . 'Form';
        $pkId = $entity->valueForKey('primaryKeyAttribute');
        $confirmDeleteYaml[$confirmDeleteFormId] = array(
                'class' => 'WFForm',
                'children' => array(
                    $pkId => array(
                        'class' => 'WFHidden',
                        'bindings' => array(
                            'value' => array(
                                'instanceID' => $sharedEntityId,
                                'controllerKey' => 'selection',
                                'modelKeyPath' => $entity->valueForKey('primaryKeyAttribute'),
                                )
                            )
                        ),
                    'cancel' => array(
                        'class' => 'WFSubmit',
                        'properties' => array(
                            'label' => 'Cancel'
                            )
                        ),
                    'delete' => array(
                        'class' => 'WFSubmit',
                        'properties' => array(
                            'label' => 'Delete'
                            )
                        )
                    ),
                    );
        $confirmDeleteYaml['confirmMessage'] = array(
                'class' => 'WFMessageBox',
                'bindings' => array(
                    'value' => array(
                        'instanceID' => $sharedEntityId,
                        'controllerKey' => 'selection',
                        'modelKeyPath' => $descriptiveColumnName,
                        'options' => array(
                            'ValuePattern' => 'Are you sure you want to delete ' . $entity->valueForKey('name') . ' "%1%"?'
                            )
                        )
                    )
                );
        file_put_contents($moduleDir . '/confirmDelete.yaml', WFYaml::dump($confirmDeleteYaml));
        
        // confirmDelete.tpl file
        $this->smarty->assign('confirmDeleteFormId', $confirmDeleteFormId);
        file_put_contents($moduleDir . '/confirmDelete.tpl', $this->smarty->fetch(FRAMEWORK_DIR . '/framework/generator/confirmDelete.tpl'));

        // delete success
        $deleteSuccessYaml = array();
        $deleteSuccessYaml['statusMessage'] = array(
                'class' => 'WFMessageBox',
                'properties' => array(
                    'value' => $entity->valueForKey('name') . ' successfully deleted.'
                    )
                );
        file_put_contents($moduleDir . '/deleteSuccess.yaml', WFYaml::dump($deleteSuccessYaml));
        file_put_contents($moduleDir . '/deleteSuccess.tpl', $this->smarty->fetch(FRAMEWORK_DIR . '/framework/generator/deleteSuccess.tpl'));

        // detail page
        $detailYaml = array();
        $widgets = array();
        foreach ($entity->getAttributes() as $attr) {
            $widgetID = $attr->valueForKey('name');
            $widgets[$widgetID] = $attr;
            $detailYaml[$widgetID] = array(
                    'class' => 'WFLabel',
                    'bindings' => array(
                        'value' => array(
                            'instanceID' => $sharedEntityId,
                            'controllerKey' => 'selection',
                            'modelKeyPath' => $widgetID
                            )
                        )
                    );
        }
        file_put_contents($moduleDir . '/detail.yaml', WFYaml::dump($detailYaml));
       
        // build detail.tpl
        $this->smarty->assign('widgets', $widgets);
        file_put_contents($moduleDir . '/detail.tpl', $this->smarty->fetch(FRAMEWORK_DIR . '/framework/generator/detail.tpl'));
    }
}

// FAKE command-line call for right now
$configFile = NULL;
$adapter = 'Propel';
$builder = 'WFModelCodeGenPropel';
if (1)
{
    $entities = array('Blog');
}
else
{
    $entities = array('Client');
}

$model = WFModel::sharedModel();
$model->buildModel($adapter, $configFile, $entities);
foreach ($model->entities() as $entity) {
    $codeGen = new $builder;
    $codeGen->generateModuleForEntity($entity);
}
