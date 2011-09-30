<?php

require_once(getenv('PHOCOA_PROJECT_CONF'));

if ($argc != 2) die("Usage: scaffold.php 'entity1 entity2 ...'\n");

$adapter = 'Propel';
if (defined('ORM_MODEL_ADAPTER'))
{
    $adapter = ORM_MODEL_ADAPTER;
}
$builder = 'WFModelCodeGenPropel';
$configFile = APP_ROOT . '/propel-build/phocoa-generator-config.yaml';
if (!file_exists($configFile))
{
    $configFile = NULL;
}

$delim = ' ';
if (strchr($argv[1], ','))
{
    $delim = ',';
}
$entities = array_map("trim", explode($delim, $argv[1]));

$model = WFModel::sharedModel();
$model->buildModel($adapter, $configFile, $entities);
foreach ($model->entities() as $entity) {
    $codeGen = new $builder;
    try {
        $codeGen->generateModuleForEntity($entity);
    } catch (Exception $e) {
        print "Error generating scaffold for entity '{$entity}': " . $e->getMessage() . "\n";
    }
}
