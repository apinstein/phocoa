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
 * A combined date-time widget. Works in conjunction with a text field.
 *
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * 
 * <b>Optional:</b><br>
 *
 */
class WFYAHOO_widget_DateTimePicker extends WFYAHOO
{
    const SELECT_TIMEZONE_LABEL = 'Select Timezone';

    /**
     * @var object DateTimeZone Bindable timezone value.
     */
    protected $timezone;

    // SETTINGS
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
    protected $requireTimezone;
    protected $timezones = array();

    // INTERNAL
    protected $datePicker;
    protected $timePicker;
    protected $timezonePicker;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);

        $this->value = NULL;
        $this->timezone = NULL;

        $this->startTime = '00:00:00';
        $this->endTime = '23:59:59';
        $this->incrementsPerHour = 12;
        $this->formatString = 'g:i A';
        $this->requireTimezone = false;

        $this->datePicker = new WFYAHOO_widget_Calendar("{$id}_datepart", $page);
        $this->timePicker = new WFYAHOO_widget_AutoComplete("{$id}_timepart", $page);
        $this->timezonePicker = new WFSelect("{$id}_timezone", $page);

        // configure things
        $this->timePicker->setWidth('70px');
        $this->datePicker->setWidth('100px');
        $this->timezonePicker->setWidth('150px');
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            'incrementsPerHour' => array('1', '2', '4', '12', '60'),
            'startTime',
            'endTime',
            'formatString',
            ));
    }

    function setupExposedBindings()
    {
        $myBindings = parent::setupExposedBindings();

        $newValBinding = new WFBindingSetup('timezones', 'The array of timezone choices in WFSelect::setOptions() format.');
        $newValBinding->setReadOnly(true);
        $myBindings[] = $newValBinding;

        return $myBindings;
    }

    /**
      * Set the format string used to generate the labels.
      * @param string Suitable format string for {@link date()}. Remember this is just used to show times!!!
      */
    function setFormatString($str)
    {
        $this->formatString = $str;
    }

    /**
      * Set the last time to be shown in the select list.
      * @param string 24h format such as 13:30:00.
      */
    function setEndTime($end)
    {
        $this->endTime = $end;
    }

    /**
      * Set the first time to be shown in the select list.
      * @param string 24h format such as 13:30:00.
      */
    function setStartTime($start)
    {
        $this->startTime = $start;
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
    }

    /**
      * This function generates the "options" list for the base WFSelect to use.
      */
    function generateTimePickerOptions()
    {
        $firstTime = strtotime($this->startTime);
        if ($firstTime === false) throw new Exception("startTime is invalid: " . var_export($this->startTime, true));
        $lastTime = strtotime($this->endTime);
        if ($lastTime === false) throw new Exception("lastTime is invalid: " . var_export($this->lastTime, true));
        $incrementMinutes = 60 / $this->incrementsPerHour;
        $incrementSeconds = 60 * $incrementMinutes;

        $options = array();
        $nextOption = $firstTime;
        do {
            $options[] = date($this->formatString, $nextOption);
            $nextOption += $incrementSeconds;
        } while ($nextOption <= $lastTime);
        $this->timePicker->setDatasourceJSArray($options, false);
    }
    
    function restoreState()
    {
        //  must call super
        parent::restoreState();

        try {
            $this->datePicker->restoreState();
            $this->timePicker->restoreState();
            $this->timezonePicker->restoreState();

            if ($this->datePicker->hasErrors() or $this->timePicker->hasErrors() or $this->timezonePicker->hasErrors()) return;
        } catch (Exception $e) {
            die('should not happen. someone is mis-behaving.');
        }

        $datePartEntered = ($this->datePicker->value() !== NULL);
        $timePartEntered = (trim ($this->timePicker->value()) != '');

        if (!$datePartEntered and !$timePartEntered)
        {
            $this->value = NULL;
            return;
        }

        // parse & validate date
        $datePart = date_parse($this->datePicker->value());
        if ($datePart === false)
        {
            $this->addError(new WFError("Invalid date."));
        }
        else if ($datePart['error_count'])
        {
            foreach ($datePart['errors'] as $err) {
                $this->addError(new WFError("The date you entered is invalid")); 
            }
        }

        // parse & validate time
        if (!$timePartEntered)
        {
             $this->addError(new WFError("Please enter a time.")); 
        }
        else 
        {
            $timePart = date_parse($this->timePicker->value());
            if ($timePart === false)
            {
                $this->addError(new WFError("Invalid time."));
            }
            else if ($timePart['error_count'])
            {
                foreach ($timePart['errors'] as $err) {                
                    $this->addError(new WFError("The time you entered is invalid")); 
                }
            }
        }

        try {
            // calculate timezone
            $tz = $this->timezonePicker->value();
            if ($tz == NULL or $tz === self::SELECT_TIMEZONE_LABEL)
            {
                if ($this->requireTimezone)
                {
                    $this->addError(new WFError("Please select a timezone."));
                }
                else
                {
                    $tz = date_default_timezone_get();
                }
            }
            else
            {
                $tz = new DateTimeZone($tz);
            }

            // assemble datetime+timezone
            if (count($this->errors()) === 0)
            {
                try {
                    $this->setValue(new WFDateTime("{$datePart['year']}-{$datePart['month']}-{$datePart['day']} {$timePart['hour']}:{$timePart['minute']}:{$timePart['second']}", $tz));
                } catch (Exception $e) {
                    $this->addError(new WFError("Couldn't create datetime: " . $e->getMessage()));
                }
            }
        } catch (Exception $e) {
            $this->addError(new WFError("The timezone you entered is invalid"));
        }
    }

    function setValue($v)
    {
        if ($v !== NULL and !($v instanceof DateTime)) throw new Exception("WFYAHOO_widget_DateTimePicker value must be a DateTime object.");

        $this->value = $v;
        if ($v)
        {
            $this->timezone = $v->getTimezone();
        }
    }

    function setTimezone($tz)
    {
        if (is_string($tz)) $tz = new DateTimeZone($tz);
        if (!($tz instanceof DateTimeZone)) throw new Exception("setTimezone() requires a DateTimeZone as first argument.");
        $this->timezone = $tz;
        if ($this->value)
        {
            $this->value->setTimezone($tz);
        }
        return $this;
    }
    function getTimezone()
    {
        return $this->timezone;
    }

    function render($blockContent = NULL)
    {
        if ($this->hidden) return NULL;

        // sanity check
        if (!$this->requireTimezone && count($this->timezones) > 0) throw new Exception("requireTimezone must be set to true if timezones is populated.");
        if ($this->requireTimezone && count($this->timezones) === 0) throw new Exception("timezones must be populated if requireTimezone is set to true.");
        
        // prep data
        if ($this->value instanceof DateTime)
        {
            $this->datePicker->setValue($this->value);
            $this->timePicker->setValue($this->value->format('h:i A'));
        }
        if ($this->getTimezone())
        {
            $this->timezonePicker->setValue($this->getTimezone()->getName());
        }

        // configure timezone UI
        $this->generateTimePickerOptions();
        if ($this->requireTimezone)
        {
            $this->timezonePicker->setOptions(array_merge(array(self::SELECT_TIMEZONE_LABEL => NULL), $this->timezones));
        }
        else
        {
            $this->timezonePicker->setHidden(true);
        }

        // render
        return '<div style="overflow: visible;">' . 
                   '<div style="float: left;"> ' . $this->datePicker->render($blockContent) . '</div>' .
                   '<div style="float: left;"> ' . $this->timePicker->render($blockContent) . '</div>' .
                   '<div style="float: left;"> ' . $this->timezonePicker->render($blockContent) . '</div>' .
               '</div>';
    }

    function initJS($blockContent)
    {
        $html = '
        ';

        return $html;
    }
    
    function canPushValueBinding() { return true; }
    
}
