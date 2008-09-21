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
 * A colorpicker widget. Works in conjunction with a text field.
 *
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * {@link WFYAHOO_widget_ColorPicker::$textFieldId textFieldId}
 * 
 * <b>Optional:</b><br>
 * {@link WFYAHOO_widget_ColorPicker::$animate animate}
 * {@link WFYAHOO_widget_ColorPicker::$showcontrols showcontrols}
 * {@link WFYAHOO_widget_ColorPicker::$showhexcontrols showhexcontrols}
 * {@link WFYAHOO_widget_ColorPicker::$showhexsummary showhexsummary}
 * {@link WFYAHOO_widget_ColorPicker::$showhsvcontrols showhsvcontrols}
 * {@link WFYAHOO_widget_ColorPicker::$showrgbcontrols showrgbcontrols}
 * {@link WFYAHOO_widget_ColorPicker::$showwebsafe showwebsafe}
 */
class WFYAHOO_widget_ColorPicker extends WFYAHOO
{
    protected $textFieldId;
    /**
     * @var boolean TRUE to use animation in the color picker. Default: true
     */
    protected $animate;
    /**
     * @var boolean TRUE to show the entire set of controls. Default: true
     */
    protected $showcontrols;
    /**
     * @var boolean TRUE to show the hex controls. Default: true
     */
    protected $showhexcontrols;
    /**
     * @var boolean TRUE to show the hex summary. Default: true
     */
    protected $showhexsummary;
    /**
     * @var boolean TRUE to show the HSV controls. Default: false
     */
    protected $showhsvcontrols;
    /**
     * @var boolean TRUE to show the RGB controls. Default: true
     */
    protected $showrgbcontrols;
    /**
     * @var boolean TRUE to show the WEB-SAFE color swatch. Default: true
     */
    protected $showwebsafe;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);

        $this->textFieldId = NULL;
        $this->animate = true;
        $this->showcontrols = NULL;
        $this->showhexcontrols = NULL;
        $this->showhexsummary = NULL;
        $this->showhsvcontrols = NULL;
        $this->showrgbcontrols = NULL;
        $this->showwebsafe = NULL;
        if ($this->animate)
        {
            $this->yuiloader()->yuiRequire('animation');
        }
        $this->yuiloader()->yuiRequire('container,colorpicker');
    }

    function render($blockContent = NULL)
    {
        if (!$this->textFieldId) throw( new WFException("WFYAHOO_widget_ColorPicker requires a text field to work with at present.") );

        if ($this->hidden)
        {
            return NULL;
        }
        else
        {
            // render the tab's content block
            $html = parent::render($blockContent);
            $html .= '
            <div id="' . $this->id . '" class="yui-picker-panel" style="display: none">
                <div class="hd">Please choose a color:</div>
                <div class="bd">
                    <form name="' . $this->id . '-yui-picker-form" id="' . $this->id . '-yui-picker-form" method="post" action="assets/post.php">
                    <div class="yui-picker" id="' . $this->id . '-yui-picker"></div>
                    </form>
                </div>
                <div class="ft"></div>
            </div>
';

            if ($this->textFieldId)
            {
                $html .= '<input type="button" value="Pick Color" id="' . $this->id . '_trigger" />';
            }
            return $html;
        }
    }

    function initJS($blockContent)
    {
        $html = "
        PHOCOA.widgets.{$this->id}.init = function() {
            PHOCOA.widgets.{$this->id}.originalValue = null;

            PHOCOA.widgets.{$this->id}.copyRGBToTextField = function(rgb) {
                \$('{$this->textFieldId}').value = '#' + YAHOO.util.Color.rgb2hex(rgb[0], rgb[1], rgb[2]);
            }

            PHOCOA.widgets.{$this->id}.handleOK = function() {
                //PHOCOA.widgets.{$this->id}.copyRGBToTextField(PHOCOA.widgets.colorPicker.picker._configs.rgb.getValue());
                PHOCOA.widgets.{$this->id}.dialog.hide();
            };
            PHOCOA.widgets.{$this->id}.handleCancel = function() {
                \$('{$this->textFieldId}').value = PHOCOA.widgets.{$this->id}.originalValue;
                PHOCOA.widgets.{$this->id}.dialog.hide();
            };
            // Instantiate the Dialog
            PHOCOA.widgets.{$this->id}.dialog = new YAHOO.widget.Dialog('{$this->id}', {
                width : '500px',
                fixedcenter : true,
                visible : false, 
                constraintoviewport : true,
                buttons : [ { text:'Save', handler:PHOCOA.widgets.{$this->id}.handleOK, isDefault:true },
                            { text:'Cancel', handler:PHOCOA.widgets.{$this->id}.handleCancel } ]
             });
            // Once the Dialog renders, we want to create our Color Picker instance.
            PHOCOA.widgets.{$this->id}.dialog.showEvent.subscribe(function() {
                PHOCOA.widgets.{$this->id}.originalValue = \$F('{$this->textFieldId}');
            });
            PHOCOA.widgets.{$this->id}.dialog.renderEvent.subscribe(function() {
                $('{$this->id}').style.display = 'block';
                if (!PHOCOA.widgets.{$this->id}.picker) { //make sure that we haven't already created our Color Picker
                    YAHOO.log('Instantiating the color picker', 'info', 'example');
                    PHOCOA.widgets.{$this->id}.picker = new YAHOO.widget.ColorPicker('{$this->id}-yui-picker', {
                        container: PHOCOA.widgets.{$this->id}.dialog,
                        images: {
                            PICKER_THUMB: '" . $this->yuiloader()->base() . "/colorpicker/assets/picker_thumb.png',
                            HUE_THUMB: '" . $this->yuiloader()->base() . "/colorpicker/assets/hue_thumb.png'
                        },
                        " . (!is_null($this->showcontrols) ? "showcontrols: " . var_export($this->showcontrols, true) . ',' : NULL) . "
                        " . (!is_null($this->showhexcontrols) ? "showhexcontrols: " . var_export($this->showhexcontrols, true) . ',' : NULL) . "
                        " . (!is_null($this->showhexsummary) ? "showhexsummary: " . var_export($this->showhexsummary, true) . ',' : NULL) . "
                        " . (!is_null($this->showhsvcontrols) ? "showhsvcontrols: " . var_export($this->showhsvcontrols, true) . ',' : NULL) . "
                        " . (!is_null($this->showrgbcontrols) ? "showrgbcontrols: " . var_export($this->showrgbcontrols, true) . ',' : NULL) . "
                        " . (!is_null($this->showwebsafe) ? "showwebsafe: " . var_export($this->showwebsafe, true) . ',' : NULL) . "
                        end: null
                    });

                    // listen to rgbChange to be notified about new values
                    PHOCOA.widgets.{$this->id}.picker.on('rgbChange', function(o) {
                        PHOCOA.widgets.{$this->id}.copyRGBToTextField(o.newValue);
                    });
                }
            }); 

            PHOCOA.widgets.colorPicker.dialog.render();

            PHOCOA.widgets.{$this->id}.showPicker = function() {
                PHOCOA.widgets.{$this->id}.dialog.show();

                // set initial value
                var hexRE = /#[0-9A-Fa-f]{6}/;
                var hexColor = \$F('{$this->textFieldId}');
                var reRes = hexColor.match(hexRE);
                if ( reRes != -1)
                {
                    PHOCOA.widgets.{$this->id}.picker.setValue(YAHOO.util.Color.hex2rgb(reRes[0].substr(1)), true);
                }
            };

            YAHOO.util.Event.on('{$this->id}_trigger', 'click', PHOCOA.widgets.{$this->id}.showPicker, PHOCOA.widgets.{$this->id}.dialog, true);

        };
        ";

        return $html;
    }

}
