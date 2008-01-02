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
    protected $builtEntities = array(); // prevent infinite loops!

    function setup()
    {
        Propel::init(PROPEL_CONF);
        require_once('propel/engine/database/model/NameFactory.php');
    }

    /**
     * Get the propel metadata for the given entity.
     *
     * @param string The name of the entity, as it's PHP class name.
     * @return object TableMap The Propel TableMap for this entity.
     */
    function getEntityMetadata($name)
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
        if (isset($this->builtEntities[$name])) return $this->builtEntities[$name];

        // build a WFModelEntity structure from the Propel metadata....
        $tableMap = $this->getEntityMetadata($name); 

        // set up entity
        $entity = new WFModelEntity;
        $entity->setValueForKey($tableMap->getPhpName(), 'name');

        // set up properties
        foreach ($tableMap->getColumns() as $column) {
            $property = new WFModelEntityProperty;
            $property->setValueForKey($column->getPhpName(), 'name');
            $property->setValueForKey($column->getDefaultValue(), 'defaultValue');
            switch ($column->getType()) {
                case 'INTEGER':
                case 'int':
                    $type = WFModelEntityProperty::TYPE_NUMBER;
                    break;
                case 'TIMESTAMP':
                case 'datetime':
                    $type = WFModelEntityProperty::TYPE_DATETIME;
                    break;
                case 'text':
                    $type = WFModelEntityProperty::TYPE_TEXT;
                    break;
                case 'boolean':
                    $type = WFModelEntityProperty::TYPE_BOOLEAN;
                    break;
                case 'VARCHAR':
                case 'string':
                    $type = WFModelEntityProperty::TYPE_STRING;
                    break;
                default: 
                    print "WARNING: Unknown property type for column: " . $property->valueForKey('name') . ":" . $column->getType() . "\n";
                    $type = WFModelEntityProperty::TYPE_STRING;
                    break;
            }
            if (!$entity->valueForKey('descriptiveColumnName') && $type === WFModelEntityProperty::TYPE_STRING)
            {
                $entity->setValueForKey($property->valueForKey('name'), 'descriptiveColumnName');
            }
            if (!$entity->valueForKey('primaryKeyProperty') && $column->isPrimaryKey())
            {
                $entity->setValueForKey($property->valueForKey('name'), 'primaryKeyProperty');
            }
            $property->setValueForKey($type, 'type');
            $entity->addProperty($property);
        }

        // set up relationships
        foreach ($tableMap->getColumns() as $column) {
            if (!$column->isForeignKey()) continue;

            // create relationship from this table to the other one
            $rel = new WFModelEntityRelationship;
            // get related entity
            $relatedEntityName = NameFactory::generateName(NameFactory::PHP_GENERATOR, array($column->getRelatedTableName(), NameGenerator::CONV_METHOD_PHPNAME));
            $relatedEntityTableMap = $this->getEntityMetadata($relatedEntityName);
            $relatedEntity = WFModel::sharedModel()->buildEntity($relatedEntityTableMap->getPhpName());
            // configure relationship
            $rel->setToOne(true);   // if we are the fk column, it must be to-one (unless it's many-to-many)
            $rel->setValueForKey($relatedEntity->valueForKey('name'), 'name'); // singular
            $rel->setValueForKey($column->isNotNull(), 'required');
            $entity->addRelationship($rel);

            // create relationship in the other direction
            $invRel = new WFModelEntityRelationship;
            // configure relationship
            $inverseRelationshipIsToOne = false;
            // is this an "extension" table? TRUE if the relationship is on our PK; this makes the INVERSE relationship have an EXT relationship to this table
            if ($column->isPrimaryKey())
            {
                $inverseRelationshipIsToOne = true;
                $invRel->setValueForKey(true, 'isExtension');
            }
            $invRel->setToOne($inverseRelationshipIsToOne);
            $invRel->setValueForKey($tableMap->getPhpName() . ($inverseRelationshipIsToOne ? NULL : 's'), 'name');    // make plural as needed
            $relatedEntity->addRelationship($invRel);
        }
    
        $this->builtEntities[$entity->valueForKey('name')] = $entity;
        return $entity;
    }
}

// PHOCOA WFModel Class Structure -- internal representation of the object model. Decoupled from the implementations.
class WFModel extends WFObject
{
    protected $builder = NULL;

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

    function toString()
    {
        $str = NULL;
        foreach ($this->entities as $entity) {
            $str .= "\n" . $entity->valueForKey('name');
            foreach ($entity->getProperties() as $property) {
                $str .= "\n - " . $property->valueForKey('name') . " (" . $property->valueForKey('type') . ")";
            }
            foreach ($entity->getRelationships() as $rel) {
                $str .= "\n > " . $rel->valueForKey('name') . " (" . ($rel->valueForKey('toOne') ? 'to-one' : 'to-many') . ($rel->valueForKey('isExtension') ? ' [EXT]' : NULL) . ", " . ($rel->valueForKey('required') ? 'required' : 'optional') . ")";
            }
        }
        $str .= "\n\n";
        return $str;
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

    public function buildEntity($entityName)
    {
        if (isset($this->entities[$entityName]))
        {
            return $this->getEntity($entityName);
        }
        $entity = $this->builder->buildEntityModel($entityName);
        $this->addEntity($entity);
        return $entity;
    }

    public function buildModel($adapter, $configFile, $buildEntities)
    {
        // bootstrap
        $builderClass = 'WFModelBuilder' . $adapter;
        $this->builder = new $builderClass;
        $this->builder->setup();
        foreach ($buildEntities as $entity) {
            $this->buildEntity($entity);
        }

        if (file_exists($configFile))
        {
            // READ CONFIG - read a config YAML file and "override" settings in various entities, such as descriptiveColumnName, or cardinality:
            $config = WFYaml::load($configFile);
            // Blog:
            //   descriptiveColumnName: name
            //   relationships:
            //     BlogPreferences:
            //       minCount: 0
            //       maxCount: NULL
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
                                    if ($key === 'inverseRelationship')
                                    {
                                        list($entityName, $relName) = explode('.', $value);
                                        if (!$entityName or !$relName) throw( new WFException("inverseRelationship format must be <entityName>.<relationshipName>") );
                                        if (!$this->getEntity($entityName))
                                        {
                                            $this->addEntity($this->builder->buildEntityModel($entityName));
                                        }
                                        $rel->setInverseRelationship($this->getEntity($entityName)->getRelationship('relName'));
                                    }
                                    else
                                    {
                                        $rel->setValueForKey($value, $key);
                                    }
                                }
                            }
                            break;
                        case 'properties':
                            foreach ($config as $propertyName => $propertyConfig) {
                                $property = $entity->getProperty($propertyName);
                                if (!$property)
                                {
                                    print "WARNING: Property: {$propertyName} of Entity {$entityName} not loaded...\n";
                                    continue;
                                }
                                foreach ($propertyConfig as $key => $value) {
                                    switch ($key) {
                                        case 'type':
                                            $value = eval("return {$value};");
                                            break;
                                    }
                                    $property->setValueForKey($value, $key);
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
    protected $primaryKeyProperty = NULL;
    protected $descriptiveColumnName = NULL;
    protected $properties = array();
    protected $relationships = array();

    public function addProperty($property)
    {
        if (!($property instanceof WFModelEntityProperty)) throw( new WFException("addProperty parameter must be a WFModelEntityProperty.") );
        $this->properties[] = $property;
        return $this;
    }
    public function getProperty($name)
    {
        if (isset($this->properties[$name])) return $this->properties[$name];
        return NULL;
    }
    public function getProperties()
    {
        return $this->properties;
    }

    public function addRelationship($rel)
    {
        if (!$rel->valueForKey('name')) throw( new WFException("Relationships must have a name before being added.") );
        if (!($rel instanceof WFModelEntityRelationship)) throw( new WFException("addRelationship parameter must be a WFModelEntityRelationship.") );
        if (isset($this->relationships[$rel->valueForKey('name')])) throw( new WFException("Relationship " . $rel->valueForKey('name') . " already exists.") );
        $this->relationships[$rel->valueForKey('name')] = $rel;
        return $this;
    }
    public function getRelationship($name)
    {
        if (isset($this->relationships[$name])) return $this->relationships[$name];
        return NULL;
    }
    public function getRelationships()
    {
        return $this->relationships;
    }
}
class WFModelEntityProperty extends WFObject
{
    protected $name = NULL;
    protected $type = WFModelEntityProperty::TYPE_STRING;
    protected $defaultValue = NULL;

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
    protected $name = NULL;     // a call to get$name on the entity should fetch the related object(s)
    protected $isExtension = false; // extensions are to-one relationships that use the same id field in both tables and the related table stores "extended" properties. The extended table is basically a "grouping" of properties for the primary entity.
    protected $toOne = true;    // TRUE = to-one, FALSE = to-many
    protected $required = false;    // orthogonal to minCount; minCount is only enforced if there is a relationship. Required disallows lack of related object(s).
    protected $minCount = 1;
    protected $maxCount = 1;
    protected $inverseRelationship = NULL;

    function setToOne($isToOne)
    {
        if (!is_bool($isToOne)) throw( new WFException("boolean expected.") );
        $this->toOne = $isToOne;
        $this->minCount = $this->maxCount = ($this->toOne ? 1 : NULL);
    }
    function setMinCount($num)
    {
        if ($this->toOne) throw( new WFException("Can't set minCount on to-one relationships.") );
        $this->minCount = $num;
    }
    function setMaxCount($num)
    {
        if ($this->toOne) throw( new WFException("Can't set maxCount on to-one relationships.") );
        $this->maxCount = $num;
    }
    function setInverseRelationship($r)
    {
        if (!($r instanceof WFModelEntityRelationship)) throw( new WFException("Relationship must be a WFModelEntityRelationship.") );
        $this->inverseRelationship = $r;
    }
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
                    'classIdentifiers' => $entity->valueForKey('primaryKeyProperty'),
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
        $this->smarty->assign('sharedEntityPrimaryKeyProperty', $entity->valueForKey('primaryKeyProperty'));
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
                                'modelKeyPath' => $entity->valueForKey('primaryKeyProperty'),
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
                                'modelKeyPath' => $entity->valueForKey('primaryKeyProperty'),
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
                                'modelKeyPath' => $entity->valueForKey('primaryKeyProperty'),
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
        foreach ($entity->getProperties() as $property) {
            $widgetID = $property->valueForKey('name');
            $widgets[$widgetID] = $property;

            if ($property->valueForKey('name') === $entity->valueForKey('primaryKeyProperty'))
            {
                $class = 'WFHidden';
            }
            else
            {
                switch ($property->valueForKey('type')) {
                    case WFModelEntityProperty::TYPE_TEXT;
                        $class = 'WFTextArea';
                        break;
                    case WFModelEntityProperty::TYPE_NUMBER;
                    case WFModelEntityProperty::TYPE_STRING;
                    case WFModelEntityProperty::TYPE_DATETIME;
                    case WFModelEntityProperty::TYPE_TIME;
                    case WFModelEntityProperty::TYPE_DATE;
                        $class = 'WFTextField';
                        break;
                    case WFModelEntityProperty::TYPE_BOOLEAN;
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
        $pkId = $entity->valueForKey('primaryKeyProperty');
        $confirmDeleteYaml[$confirmDeleteFormId] = array(
                'class' => 'WFForm',
                'children' => array(
                    $pkId => array(
                        'class' => 'WFHidden',
                        'bindings' => array(
                            'value' => array(
                                'instanceID' => $sharedEntityId,
                                'controllerKey' => 'selection',
                                'modelKeyPath' => $entity->valueForKey('primaryKeyProperty'),
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
        foreach ($entity->getProperties() as $property) {
            $widgetID = $property->valueForKey('name');
            $widgets[$widgetID] = $property;
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
