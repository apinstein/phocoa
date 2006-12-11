<?php

require_once('/Users/alanpinstein/dev/sandbox/phocoadev/phocoadev/conf/webapp.conf');

$classesToExport = array(
    'WFCheckbox',
    'WFCheckboxGroup',
    'WFDieselKeyword',
    'WFDieselNav',
    'WFDynamic',
    'WFDynarchMenu',
    'WFForm',
    'WFHidden',
    'WFHTMLArea',
    'WFImage',
    'WFJumpSelect',
    'WFLabel',
    'WFLink',
    'WFMessageBox',
    'WFModuleView',
    'WFPaginatorNavigation',
    'WFPaginatorPageInfo',
    'WFPaginatorSortLink',
    'WFPaginatorSortSelect',
    'WFPaginatorState',
    'WFPassword',
    'WFRadio',
    'WFRadioGroup',
    'WFSelect',
    'WFSelectionCheckbox',
    'WFSubmit',
    'WFTabContent',
    'WFTabView',
    'WFTextArea',
    'WFTextField',
    'WFTimeSelect',
    'WFUpload',
    'WFYAHOO_widget_Module',
    'WFYAHOO_widget_Panel',
    'WFYAHOO_widget_TreeView',
    'WFYAHOO_widget_TreeViewNode',
);

$plist = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple Computer//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
';

foreach ($classesToExport as $class) {
    $plist .= "<key>$class</key>
    <dict>
    ";

    $plist .= "<key>properties</key>\n<dict>\n";
    if (is_callable(array($class, 'exposedProperties')))
    {
        $exposedProperties = call_user_func( array($class, 'exposedProperties') );
        // fix integer keys into keys... this allows exposedProperties to return ('myProp' => array(1,2,3), 'myProp2', 'myProp3')
        foreach ( array_keys($exposedProperties) as $k ) {
            if (gettype($k) == 'integer')
            {
                $exposedProperties[$exposedProperties[$k]] = NULL;
                unset($exposedProperties[$k]);
            }
        }
        foreach ($exposedProperties as $prop => $values) {
            $plist .= "<key>{$prop}</key>\n";
            if ($values)
            {
                $plist .= "<array>\n";
                foreach ($values as $v) {
                    $plist .= "<string>{$v}</string>\n";
                }
                $plist .= "</array>\n";
            }
            else
            {
                $plist .= "<array/>\n";
            }
        }
    }
    $plist .= "</dict>";

    $plist .= "<key>bindings</key>\n<array>\n";
    if (is_callable(array($class, 'setupExposedBindings')))
    {
        $bindings = call_user_func( array($class, 'setupExposedBindings') );
        foreach ($bindings as $bo) {
            $plist .= "<string>" . $bo->boundProperty() . "</string>\n";
        }
    }
    $plist .= "</array>";

    $plist .= "</dict>";
}

$plist .= '
</dict>
</plist>';

file_put_contents('WFViewPhocoaBuilderIntegration.plist', $plist);
