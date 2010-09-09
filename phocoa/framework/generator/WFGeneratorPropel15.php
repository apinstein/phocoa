<?php

class WFModelBuilderPropel15 extends WFObject implements WFModelBuilder
{
    protected $builtEntities = array(); // prevent infinite loops!

    function setup()
    {
        Propel::init(PROPEL_CONF);
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
        if (!class_exists($peerClassName))
        {
            throw( new WFException("Entity {$name} is not a Propel object.") );
        }
        $databaseName = eval("return {$peerClassName}::DATABASE_NAME;");        // autolaod will load the Peer file...
        $dbMap = Propel::getDatabaseMap($databaseName);
        $tableMapTableName = eval("return {$peerClassName}::TABLE_NAME;");
        $tableMap = $dbMap->getTable($tableMapTableName);
        return $tableMap;
    }

    /**
     * Pass in a WFModelEntity object with a name filled out.
     *
     * @param object WFModelEntity An WFModelEntity with a name.
     * @throws object WFModelEntity
     */
    function buildEntityModel($entity)
    {
        if (!($entity instanceof WFModelEntity)) throw( new WFException("WFModelEntity required.") );
        $name = $entity->valueForKey('name');

        if (isset($this->builtEntities[$name])) return $this->builtEntities[$name];

        // build a WFModelEntity structure from the Propel metadata....
        $tableMap = $this->getEntityMetadata($name); 

        // set up properties
        foreach ($tableMap->getColumns() as $column) {
            $property = new WFModelEntityProperty;
            $propertyName = $column->getPhpName();
            $propertyName[0] = strtolower($propertyName[0]);
            $property->setValueForKey($propertyName, 'name');
            $property->setValueForKey($column->getDefaultValue(), 'defaultValue');
            // BOOLEAN|TINYINT|SMALLINT|INTEGER|BIGINT|DOUBLE|FLOAT|REAL|DECIMAL|CHAR|{VARCHAR}|LONGVARCHAR|DATE|TIME|TIMESTAMP|BLOB|CLOB
            switch (strtoupper($column->getType())) {
                case 'TINYINT':
                case 'SMALLINT':
                case 'INTEGER':
                case 'BIGINT':
                case 'DOUBLE':
                case 'NUMERIC':
                case 'FLOAT':
                case 'REAL':
                case 'DECIMAL':
                    $type = WFModelEntityProperty::TYPE_NUMBER;
                    break;
                case 'TIMESTAMP':
                case 'DATETIME':
                case 'DATE':
                    $type = WFModelEntityProperty::TYPE_DATETIME;
                    break;
                case 'TEXT':
                case 'LONGVARCHAR':
                    $type = WFModelEntityProperty::TYPE_TEXT;
                    break;
                case 'BOOLEAN':
                    $type = WFModelEntityProperty::TYPE_BOOLEAN;
                    break;
                case 'CHAR':
                case 'VARCHAR':
                case 'STRING':
                    $type = WFModelEntityProperty::TYPE_STRING;
                    break;
                default: 
                    print "WARNING: Unknown property type for column " . $property->valueForKey('name') . ": " . $column->getType() . "\n";
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
        if (!$entity->valueForKey('descriptiveColumnName'))
        {
            $entity->setValueForKey($entity->valueForKey('primaryKeyProperty'), 'descriptiveColumnName');
        }

        // set up relationships
        $tableMap->getRelations();  // populate databaseMap with related columns
        foreach ($tableMap->getColumns() as $column) {
            if (!$column->isForeignKey()) continue;

            //print "Processing {$tableMap->getPhpName()}.{$column->getPhpName()}\n";

            // get related entity
            $relatedEntityName = $column->getRelatedTable()->getPhpName();
            $relatedEntityTableMap = $this->getEntityMetadata($relatedEntityName);
            $relatedEntity = WFModel::sharedModel()->getEntity($relatedEntityTableMap->getPhpName());
            if (!$relatedEntity)
            {
                //print "Building related WFModel entity {$relatedEntityTableMap->getPhpName()}\n";
                $relatedEntity = WFModel::sharedModel()->buildEntity($relatedEntityTableMap->getPhpName());
            }

            // configure relationship
            $relName = $relatedEntity->valueForKey('name');
            if (!$entity->getRelationship($relName))
            {
                // create relationship from this table to the other one
                $rel = new WFModelEntityRelationship;
                $rel->setToOne(true);   // if we are the fk column, it must be to-one (unless it's many-to-many)
                $rel->setValueForKey($relName, 'name'); // singular
                $rel->setValueForKey($column->isNotNull(), 'required');
                $entity->addRelationship($rel);
            }

            // create relationship in the other direction
            $invRelName = $tableMap->getPhpName();    // make plural as needed -
            if (!$relatedEntity->getRelationship($invRelName))
            {
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
                $invRel->setValueForKey($invRelName, 'name');
                $relatedEntity->addRelationship($invRel);
            }
        }
    
        $this->builtEntities[$entity->valueForKey('name')] = $entity;
    }
}
