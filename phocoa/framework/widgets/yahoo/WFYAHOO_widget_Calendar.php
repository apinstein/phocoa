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
 * A calendar widget. Works in conjunction with a text field.
 * Only supports m/d/Y formatting currently.
 *
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * 
 * <b>Optional:</b><br>
 * {@link WFYAHOO_widget_Calendar::$title title}
 *
 * @todo deal with layout/css/alignment; locale issues?; formatter?
 * @TODO: support different date formats.
 */
class WFYAHOO_widget_Calendar extends WFYAHOO
{
    /**
     * @var string The title of the date picker dialog.
     */
    protected $title;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);

        $this->yuiloader()->yuiRequire('button,container,calendar');
        $this->title = "Select Date";
    }

    function restoreState()
    {
        //  must call super
        parent::restoreState();

        // look for the things in the form I need to restore state...
        if (isset($_REQUEST[$this->id]))
        {
            $value = new WFDateTime();
            $value->setTime(0, 0, 0);
            $date = explode('/', $_REQUEST[$this->id]);
            $value->setDate($date[2], $date[0], $date[1]);
            $this->value = $value;
        }
    }

    function render($blockContent = NULL)
    {
        if ($this->hidden)
        {
            return NULL;
        }
        else
        {
            if( !($this->value instanceof WFDateTime)) throw new WFException("Value must be of type WFDateTime");
            $formattedValue = $this->value->format('m/d/Y');

            // render the tab's content block
            $html = parent::render($blockContent);
            $html .= '
        <input type="text" id="' . $this->id . '" name="' . $this->id . '" value="' . $formattedValue . '" /><img id="' . $this->id . '-show" src="' . $this->getWidgetWWWDir() . '/calbtn.gif" width="18" height="18" alt="Calendar" style="margin: 0 0 1px 3px" valign="bottom" />
       <div id="' . $this->id . '-container" style="visibility: hidden">
          <div class="hd">' . $this->title . '</div>
          <div class="bd">
             <div id="' . $this->id . '-cal"></div>
          </div>
       </div>
';

            return $html;
        }
    }

    function initJS($blockContent)
    {
        $html = '
        PHOCOA.widgets.' . $this->id . '.init = function() {
            var dialog, calendar;

            calendar = new YAHOO.widget.Calendar("' . $this->id . '-cal", {
                iframe:false,          // Turn iframe off, since container has iframe support.
                hide_blank_weeks:true  // Enable, to demonstrate how we handle changing height, using changeContent
            });

            function okHandler() {

                if (calendar.getSelectedDates().length > 0) {
                    var selDate = calendar.getSelectedDates()[0];

                    // Pretty Date Output, using Calendar\'s Locale values: Friday, 8 February 2008
                    var dStr = selDate.getDate();
                    var mStr = selDate.getMonth() + 1;
                    var yStr = selDate.getFullYear();

                    YAHOO.util.Dom.get("' . $this->id . '").value = mStr + "/" + dStr + "/" + yStr;
                } else {
                    YAHOO.util.Dom.get("' . $this->id . '").value = "";
                }
                this.hide();
            }
            
            function cancelHandler() {
                this.hide();
            }

            dialog = new YAHOO.widget.Dialog("' . $this->id . '-container", {
                context:["' . $this->id . '-show", "tl", "bl"],
                buttons:[ {text:"Select", isDefault:true, handler: okHandler}, 
                          {text:"Cancel", handler: cancelHandler}],
                width:"16em",  // Sam Skin dialog needs to have a width defined (7*2em + 2*1em = 16em).
                draggable:false,
                close:true
            });
            calendar.render();
            dialog.render();

            // Using dialog.hide() instead of visible:false is a workaround for an IE6/7 container known issue with border-collapse:collapse.
            dialog.hide();

            calendar.renderEvent.subscribe(function() {
                // Tell Dialog it\'s contents have changed, Currently used by container for IE6/Safari2 to sync underlay size
                dialog.fireEvent("changeContent");
            });

            function updateCal() {
                var txtDate1 = document.getElementById("' . $this->id . '");

                if (txtDate1.value != "") {
                    calendar.select(txtDate1.value);
                    var selectedDates = calendar.getSelectedDates();
                    if (selectedDates.length > 0) {
                        var firstDate = selectedDates[0];
                        calendar.cfg.setProperty("pagedate", (firstDate.getMonth()+1) + "/" + firstDate.getFullYear());
                        calendar.render();
                    } else {
                        alert("Cannot select a date before 1/1/2006 or after 12/31/2008");
                    }
                    
                }
            };

            dialog.beforeShowEvent.subscribe( updateCal );

            YAHOO.util.Event.on("' . $this->id . '-show", "click", dialog.show, dialog, true);
        };

        ';

        return $html;
    }

    function canPushValueBinding() { return true; }
}
