<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package framework-base
 * @subpackage Exception
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/** 
 * A helper class to easily throw named exceptions.
 *
 * The "name" field has been added for consistency with the way Cocoa handles exceptions. This allows components to easily define "exception names" in the conventional format of:
 *
 * <code>
 * define('MyProblemException', 'MyProblemException');
 * </code>
 *
 * Using named exceptions makes it easier to keep track of exceptions across than with exception codes.
 *
 * @todo Need to have all PHOCOA exceptions that are thrown switched to WFException so code can distinguish b/w PHOCOA and external exceptions.
 */
class WFException extends Exception {}
