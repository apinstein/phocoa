    <title>{$skinTitle}</title>
    <meta name="description" content="{$skinMetaDescription}" />
    <meta name="keywords" content="{$skinMetaKeywords}" />
    <link rel="stylesheet" type="text/css" href="{php}echo WFWebApplication::webDirPath(WFWebApplication::WWW_DIR_FRAMEWORK);{/php}/phocoa.css" />
    {if $phocoaDebug}
    <script type="text/javascript" src="{php}echo WFWebApplication::webDirPath(WFWebApplication::WWW_DIR_FRAMEWORK);{/php}/js/prototype.js" ></script>
    <script type="text/javascript" src="{php}echo WFWebApplication::webDirPath(WFWebApplication::WWW_DIR_FRAMEWORK);{/php}/js/phocoa.js" ></script>
    <script type="text/javascript" src="{php}echo WFView::yuiPath();{/php}/yuiloader/yuiloader-debug.js" ></script>
    {else}
    <script type="text/javascript" src="{php}echo WFWebApplication::webDirPath(WFWebApplication::WWW_DIR_FRAMEWORK);{/php}/js/prototype-min.js" ></script>
    <script type="text/javascript" src="{php}echo WFWebApplication::webDirPath(WFWebApplication::WWW_DIR_FRAMEWORK);{/php}/js/phocoa-min.js" ></script>
    <script type="text/javascript" src="{php}echo WFView::yuiPath();{/php}/yuiloader/yuiloader-min.js" ></script>{* DO NOT USE -min BECAUSE IT IS BROKEN... dependency loading fails to call the insert() callback in some cases. Wait until we can verify no-problem here. See http://tourbuzz.net/ tour edit TourLinks javascripts NOTE: We put back -min b/c it seems fixed in 2.6.0 (was bad in 2.5.2) leaving this note until we verify with public use.... *}
    {/if}
    {$skinHeadStrings}
