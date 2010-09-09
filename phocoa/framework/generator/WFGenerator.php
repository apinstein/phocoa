<?php

interface WFModelBuilder
{
    // get a WFModelEntity for the given entity name
    function buildEntityModel($entity);
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

    function __toString()
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
        if (isset($this->entities[$name])) return $this->entities[$name];
        return NULL;
    }

    public function buildEntity($entityName)
    {
        if (isset($this->entities[$entityName]))
        {
            throw( new WFException("Entity {$entityName} is already built. Use WFModel::getEntity().") );
            return $this->getEntity($entityName);
        }

        // create entity
        $entity = new WFModelEntity;
        $entity->setValueForKey($entityName, 'name');
        $this->addEntity($entity);
        $this->builder->buildEntityModel($entity);

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
                                            $invEntity = new WFModelEntity;
                                            $invEntity->setValueForKey($entityName, 'name');
                                            $this->builder->buildEntityModel($invEntity);
                                            $this->addEntity($invEntity);
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
        $this->properties[$property->valueForKey('name')] = $property;
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
        if (isset($this->relationships[$rel->valueForKey('name')])) throw( new WFException("Relationship " . $rel->valueForKey('name') . " already exists for entity " . $this->valueForKey('name')) );
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
    protected $name = NULL;     // a call to get{$name} on the entity should fetch the related object(s)
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

        // look up Peer column constant name from the PHP name; call ObjPeer::translateFieldName($name, $fromType, $toType)
        $translateF = array($entity->valueForKey('name') . 'Peer', 'translateFieldName');
        $peerColName = call_user_func($translateF, ucfirst($entity->valueForKey('descriptiveColumnName')), BasePeer::TYPE_PHPNAME, BasePeer::TYPE_FIELDNAME);
        $this->smarty->assign('descriptiveColumnConstantName', strtoupper($peerColName));

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
                    'clear' => array(
                        'class' => 'WFSubmit',
                        'properties' => array(
                            'label' => 'Clear'
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
        $editYaml[$editFormId]['children']['saveNew'] = array(
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
        $editYaml[$editFormId]['children']['deleteObj'] = array(
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
                    'deleteObj' => array(
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
