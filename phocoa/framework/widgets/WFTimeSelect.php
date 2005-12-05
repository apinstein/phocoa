<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package UI
 * @subpackage Widgets
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/**
 * A Select widget for our framework.
 *
 * Used to select either a single, or multiple, values.
 */
class WFTimeSelect extends WFSelect
{
    /**
     * @var integer Number of time increments to show per hour. Set to 1 to show each hour; 2 for every half-our, 4 for 15 minutes, up to 60.
     */
    protected $incrementsPerHour;
    /**
     * @var string The Start time in HH:MM:SS 24-hour format.
     */
    protected $startTime;
    /**
     * @var string The End time in HH:MM:SS 24-hour format.
     */
    protected $endTime;
    /**
     * @var string The format string for displaying the times. Should be something that shows hours, minutes, (seconds) (am/pm);
     */
    protected $formatString;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);

        $this->startTime = '00:00:00';
        $this->endTime = '23:59:59';
        $this->incrementsPerHour = 1;
        $this->formatString = 'g:i A';

        $this->generateOptions();
    }

    /**
      * Mark the current options as invalid; done because we've changed one of the inputs startTime, endTime, incrementsPerHour, or formatString.
      * 
      * Presently this just instantly re-generates the options.
      * @todo For efficiency we might want to figure out a way to load it on-demand.
      */
    function invalidateOptions()
    {
        $this->generateOptions();
    }

    /**
      * Set the format string used to generate the labels.
      * @param string Suitable format string for {@link date()}. Remember this is just used to show times!!!
      */
    function setFormatString($str)
    {
        $this->formatString = $str;
        $this->invalidateOptions();
    }

    /**
      * Set the last time to be shown in the select list.
      * @param string 24h format such as 13:30:00.
      */
    function setEndTime($end)
    {
        $this->endTime = $end;
        $this->invalidateOptions();
    }

    /**
      * Set the first time to be shown in the select list.
      * @param string 24h format such as 13:30:00.
      */
    function setStartTime($start)
    {
        $this->startTime = $start;
        $this->invalidateOptions();
    }

    /**
      * Set the number of increments to be shown per hour. For instance, 1 to show each hour, 4 to show every 15 minutes.
      * @param integer One of 1,2,3,4,5,6,30.
      * @throws Exception if a valid increment not passed.
      */
    function setIncrementsPerHour($incs)
    {
        if (!in_array($incs, array(1,2,3,4,5,6,30))) throw( new Exception("Invalid incrementsPerHour. Valid ones are: 1,2,3,4,5,6,30.") );
        $this->incrementsPerHour = $incs;
        $this->invalidateOptions();
    }

    /**
      * This function generates the "options" list for the base WFSelect to use.
      */
    function generateOptions()
    {
        $firstTime = strtotime($this->startTime);
        $lastTime = strtotime($this->endTime);
        $incrementMinutes = 60 / $this->incrementsPerHour;
        $incrementSeconds = 60 * $incrementMinutes;

        $options = array();
        $nextOption = $firstTime;
        do {
            $options[date('H:i:s', $nextOption)] = date($this->formatString, $nextOption);
            $nextOption += $incrementSeconds;
        } while ($nextOption <= $lastTime);
        $this->setOptions($options);
    }

}

?>
