<?php
/**
 * vim: set autoindent expandtab tabstop=4 shiftwidth=4 :
 */

/**
 * YAML Fixtures Data loader utility.
 *
 * The YAML file format supports a variety of ways to create objects for maximal flexibility.
 *
 * You can create objects of any kind (as long as they are KVC-Compliant) and set values of those objects.
 * You can even create related objects, either inline, or by referencing "tagged" objects that were created previously.
 * You can also use actual PHP code to execute arbitrary instructions in the fixture process. This is useful for using const's, looking up data in databases, or randomizing data.
 *
 * <code>
 * Basic Structure
 *  <className>:
 *      - New anonymous object
 *      <tag>: new Named object
 *
 * Example
 *  MyClass:
 *      - prop1: val1
 *        prop2: val2
 *      - prop1: val3
 *        prop2: val4
 *  MyOtherClass:
 *      inst1:
 *          prop1: val1
 *          prop2: val2
 *          MyRelationshipName:
 *              prop1: a
 *              prop2: <?php MyRelatedClass::SOME_CONSTANT ?>
 *  MyToOneRelatedClass:
 *      prop1: val1
 *      myOtherClass: inst1
 *  MyToManyRelatedClass:
 *      - prop1: val1
 *        myOtherClass: inst1
 *      - prop1: val2
 *        myOtherClass: inst1
 * </code>
 *
 * Usage:
 *
 * $rootObjects = WFFixture::WFFixture()->loadFile('myData.yaml');
 * $rootObjects = WFFixture::WFFixture()->loadFile('myData.yaml', 'save');  // call "save()" method on all created objects (at root level)
 * @todo Refactor with Factory pattern instead of just methods on the class for various source structures.
 */
class WFFixture extends WFObject
{
    protected $objById = array();

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Fluent constructor.
     *
     * @return object WFFixture
     */
    public static function WFFixture()
    {
        return new WFFixture();
    }

    /**
     * Function to load data structures via fixtures. Fixtures are YAML files that specify name-value pairs for objects.
     *
     * @param array An array of paths to YAML fixture files.
     * @param string The method to call on all top-level objects declared in the YAML file. Defaults to NULL (no call). Useful for calling "save" method to persist fixtures to a DB.
     * @param array An array of arguments to pass into the save method. Useful for Dependency Injection of db connection for instance.
     * @return array An array containing all created objects, as an associative array. NOTE that if you load multiple files with this call and you have the same "name" for different objects,
     *               they will stomp each other and you aren't guaranteed which one you'll get.
     * @throws object WFException
     */
    public function loadFiles($files, $saveMethod = NULL, $saveMethodArgs = array())
    {
        $allCreatedObjects = array();

        // load the fixtures data & process
        foreach ($files as $file)
        {
            $pathParts = pathinfo($file);
            switch (strtolower($pathParts['extension'])) {
                case 'yaml':
                    $newObjs = $this->processObjectList(WFYaml::load($file));
                    $allCreatedObjects = array_merge($newObjs, $allCreatedObjects);
                    break;
                default:
                    throw( new WFException("No fixture support for files of type {$pathParts['extension']}.") );
            }
            if ($saveMethod)
            {
                foreach ($newObjs as $o)
                {
                    try {
                        call_user_func_array(array($o, $saveMethod), $saveMethodArgs);
                    } catch (Exception $e) {
                        throw (new WFException("Error saving object: " . $o . "\n" . $e->getMessage()) );
                    }
                }
            }
        }

        return $allCreatedObjects;
    }

    /**
     * Function to load data structures via fixtures. Fixtures are YAML files that specify name-value pairs for objects.
     *
     * @param string The yaml file path.
     * @param string The method to call on all top-level objects declared in the YAML file. Defaults to NULL (no call). Useful for calling "save" method to persist fixtures to a DB.
     * @return array An array containing all created objects, as an associative array.
     * @throws object WFException
     */
    public function loadFile($file, $saveMethod = NULL)
    {
        return WFFixture::loadFiles(array($file), $saveMethod);
    }

    public function loadFromYaml($yamlString, $saveMethod = NULL)
    {
        // load the fixtures data & process
        $allCreatedObjects = $this->processObjectList(WFYaml::loadString($yamlString));
        if ($saveMethod)
        {
            foreach ($allCreatedObjects as $o)
            {
                try {
                    $o->$saveMethod();
                } catch (Exception $e) {
                    throw (new WFException("Error saving object: " . $o . "\n" . $e->getMessage()) );
                }
            }
        }

        return $allCreatedObjects;
    }

    /**
     * Creates objects from the passed list. The list should be a hash where the keys are class names and the values are arrays of sets of property values.
     *
     * @param array
     * @return array
     * @throws WFException
     */
    protected function processObjectList($list)
    {
        $allCreatedObjects = array();
        foreach ($list as $class_name => $instances)
        {
            if (!is_array($instances))
            {
                throw(new WFException("Class definition for {$class_name} doesn't have any instances."));
            }
            foreach ($instances as $instanceId => $props)
            {
                try {
                    $o = $this->makeObj($class_name, $props);
                } catch (Exception $e) {
                    throw( new WFException("Error processing class {$class_name}[{$instanceId}]: " . $e->getMessage()) );
                }
                // store all "named" objects for future reference
                if (gettype($instanceId) != 'integer')
                {
                    if (isset($this->objById[$class_name][$instanceId])) throw( new Exception("There already exists a {$class_name} for id {$instanceId}.") );
                    $this->objById[$class_name][$instanceId] = $o;
                }
                // store all created objects; save them as named objects if possible
                if (gettype($instanceId) == 'integer')
                {
                    $allCreatedObjects[] = $o;
                }
                else
                {
                    if (isset($allCreatedObjects[$instanceId])) throw( new WFException("Can't have two objects with the same instance ID in the same object list.") );
                    $allCreatedObjects[$instanceId] = $o;
                }
            }
        }

        return $allCreatedObjects;
    }

    /**
     * Create an object of Class with the passed name/value pairs.
     *
     * @param string The class of object to create.
     * @return object The instance created.
     * @throws object WFException
     */
    protected function makeObj($class, $props)
    {
        if (!is_array($props))
        {
            throw(new WFException("Class definition for {$class} doesn't have any properties. Properties must be defined in an array."));
        }
        $model = WFModel::sharedModel();

        $o = new $class;
        if ($o instanceof BaseObject)
        {
            // model object; could have relationships, see if we need to build it...
            if (!$model->getEntity($class))
            {
                $model->buildModel('Propel', NULL, array($class));
            }
            foreach ($props as $k => $v)
            {
                // is this an attribute or a relationship?
                $entity = $model->getEntity($class);
                if ($entity->getProperty($k))
                {
                    // it's a basic property
                    if (!is_array($v))
                    {
                        // is $v a php eval?
                        $matches = array();
                        if (preg_match('/<\?php (.*)\?'.'>/', $v, $matches))    // goofy syntax there to prevent syntax coloring problems in rest of file due to close php tag
                        {
                            $v = eval( "return {$matches[1]};" );
                        }
                        $o->setValueForKey($v, $k);
                    }
                    else
                    {
                        // "value" for this key is an object.
                        if (count(array_keys($v)) != 1) throw( new WFException("Fixtures can pass only scalars or objects. Arrays of object are not supported.") );
                        $subClass = key($v);
                        $subClassProps = $v[$subClass];
                        $subObj = $this->makeObj($subClass, $subClassProps);
                        $o->setValueForKey($subObj, $k);
                    }
                }
                else
                {
                    // not a property, it is a relationship...
                    // see if we can get that relationship
                    if (!$model->getEntity($k))
                    {
                        // try to build the entity; can't get all relationships unless we build the related entity
                        try {
                            $model->buildModel('Propel', NULL, array($k));
                        } catch (Exception $e) {
                            // maybe it's an instance variable or just a KVC property?
                            try {
                                // is $v a php eval?
                                $matches = array();
                                if (preg_match('/<\?php (.*)\?'.'>/', $v, $matches))
                                {
                                    $v = eval( "return {$matches[1]};" );
                                }
                                $o->setValueForKey($v, $k);
                                continue;
                            } catch (Exception $e) {
                                throw( new WFException("{$k} is not a known Propel entity, nor is it a KVC method:" . $e->getMessage()) );
                            }
                        }
                    }
                    $rel = $entity->getRelationship($k);
                    if (!$rel)
                    {
                        // maybe it's an instance variable or just a KVC property?
                        try {
                            // is $v a php eval?
                            $matches = array();
                            if (preg_match('/<\?php (.*)\?'.'>/', $v, $matches))
                            {
                                $v = eval( "return {$matches[1]};" );
                            }
                            $o->setValueForKey($v, $k);
                            continue;
                        } catch (Exception $e) {
                            throw( new WFException("{$k} is not a manifested entity relationship or a KVC-compliant property of {$class}.") );
                        }
                    }
                    if ($rel->valueForKey('toOne'))
                    {
                        // check for "empty" object
                        if ($v === NULL)
                        {
                            $v = array();
                        }
                        if (is_array($v))
                        {
                            // treat the obj as an "inline object"
                            $subObj = $this->makeObj($k, $v);
                        }
                        else
                        {
                            // we expect an object here. this could be either a named object or result of an eval
                            // is $v a php eval?
                            $matches = array();
                            if (preg_match('/<\?php (.*)\?>/', $v, $matches))
                            {
                                $subObj = eval( "return {$matches[1]};" );
                                if (!is_object($subObj)) throw( new WFException("Result of eval for relationship {$v} is not an object: " . $matches[1]) );
                            }
                            else // we expect a label to a YAML object elsewhere
                            {
                                if (!isset($this->objById[$k][$v])) throw( new WFException("No {$k} with label {$v}.") );
                                $subObj = $this->objById[$k][$v];
                            }
                        }
                        $o->setValueForKey($subObj, $k);
                    }
                    else
                    {
                        foreach ($v as $index => $subClassProps) {
                            try {
                                // we expect an object here. this could be either a named object or result of an "inline" object via YAML
                                // if subClassProps is a string, it's the former.
                                if (is_string($subClassProps)) // we expect a label to a YAML object elsewhere
                                {
                                    if (!isset($this->objById[$k][$subClassProps])) throw( new WFException("Failed to find a named object of class {$k} with label {$subClassProps}.") );
                                    $subObj = $this->objById[$k][$subClassProps];
                                }
                                else
                                {
                                    $subObj = $this->makeObj($k, $v[$index]);
                                }
                            } catch (Exception $e) {
                                throw( new WFException("Error processing {$index} class={$k}: " . $e->getMessage()) );
                            }
                            $toManyAdderF = "add" . $k;
                            $o->$toManyAdderF($subObj);
                            if (gettype($index) != 'integer')
                            {
                                if (isset($this->objById[$k][$index])) throw( new Exception("There already exists a {$k} for id {$index}.") );
                                $this->objById[$k][$index] = $o;
                            }
                        }
                    }
                }
            }
        }
        else
        {
            // simple object; has to contain key-value pairs.
            foreach ($props as $k => $v)
            {
                $o->setValueForKey($v, $k);
            }
        }
        return $o;
    }
}
