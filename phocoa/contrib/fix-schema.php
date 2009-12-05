<?php

/**
 * The fix-schema script is meant to automate the "correction" of the generated schema.xml file.
 *
 * Unfortunately propel's reverse task doesn't get everything right, and sometimes schema.xml requires hand-editing. 
 * However, this makes it painful to make db changes b/c you'd have to add the new DB changes to schema.xml manually since
 * the reverse task would blow away your hand-edits.
 *
 * Instead of the above nightmare, just encode all of your hand-edits into this file, and whenever you "reverse" your db, run fix-schema afterwards.
 */
require_once getenv('PHOCOA_PROJECT_CONF');

$schemaFile = APP_ROOT . '/propel-build/schema.xml';
$xml = simplexml_load_file($schemaFile);

// add WFObject as the BasePeer class
$databaseEl = $xml->xpath('//database');
$databaseEl[0]['basePeer'] = 'BasePeer ';

// prune unused tables
$unusedTables = array(
    '//table[@name="mp_version"]',
);
foreach ($unusedTables as $xpath) {
    removeNode($xml, $xpath, 'all');
}

// prunce all defaultValue="now()" attributes to keep propel from f'n up our times
$sqlDefaultNowDefaultTimestamps = $xml->xpath('//table/column[@defaultValue="now()"]');
foreach ($sqlDefaultNowDefaultTimestamps as $nodeToFix) {
    unset($nodeToFix[0]['defaultValue']);
}

// write out munged XML tree
file_put_contents($schemaFile, $xml->asXML());

function removeNode($xml, $path, $multi='one')
{
    $result = $xml->xpath($path);

    # for wrong $path
    if (!isset($result[0])) return false;

    switch ($multi) {
        case 'all':
            $errlevel = error_reporting(E_ALL & ~E_WARNING);
            foreach ($result as $r) unset ($r[0]);
            error_reporting($errlevel);
            return true;

        case 'child':
            unset($result[0][0]);
            return true;

        case 'one':
            if (count($result[0]->children())==0 && count($result)==1) {
                unset($result[0][0]);
                return true;
            }

        default:
            return false;             
    }
}  
