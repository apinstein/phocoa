<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package framework-base
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/** 
 * Unfortunately the built-in DateTime object doesn't have a __toString method which is horribly inconvenient. We add this ourselves.
 */
class WFDateTime extends DateTime
{
    public function __toString()
    {
        return $this->format('r');
    }

    public static function create($time = "now")
    {
        return new WFDateTime($time);
    }

    public function modify($modify)
    {
        parent::modify($modify);
        return $this;
    }
}
