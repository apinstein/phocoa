<?php
/*
 * Smarty plugin
 *
-------------------------------------------------------------
 * File:     function.json.php
 * Type:     function
 * Name:     json
 * Version:  1.0
 * Date:     12/18/2010
 * Purpose:  Turns a variable into JSON.
 * Install:  Drop into the plugin directory.
 * Author:   Alan Pinstein <apinstein@mac.com>
 *
 * IMPORTANT!!!!
 * Smarty2 is weird in that when you call {$myArray|json} it actually tries to call the json modified on *each* item in the array.
 * Instead, you will want to call {$myArray|@json}.
 *
-------------------------------------------------------------
 */
function smarty_modifier_json($in)
{
    return WFJSON::encode($in);
}
