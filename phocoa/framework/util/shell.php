<?php

ini_set("memory_limit", '200M');

$conf = getenv('PHOCOA_PROJECT_CONF');
$tags = basename($conf) . '/../tags';

// bootstrap phocoa for iphp shell
require_once($conf);
require_once('framework/util/iphp.php');
iphp::main(array(
                    'tags' => $tags,
                    'require' => $conf
                   ));
