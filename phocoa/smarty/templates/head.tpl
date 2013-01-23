    <title>{$skinTitle}</title>
    <meta http-equiv="Content-Type" content="text/html; charset={$skinCharset}" />
    <meta name="description" content="{$skinMetaDescription}" />
    <meta name="keywords" content="{$skinMetaKeywords}" />
    <link rel="stylesheet" type="text/css" href="{WFWebApplication::webDirPath(WFWebApplication::WWW_DIR_FRAMEWORK)}/phocoa.css" />
    {if $phocoaDebug}
    <script type="text/javascript" src="{WFWebApplication::webDirPath(WFWebApplication::WWW_DIR_FRAMEWORK)}/js/prototype.js" ></script>
    <script type="text/javascript" src="{WFWebApplication::webDirPath(WFWebApplication::WWW_DIR_FRAMEWORK)}/js/phocoa.js" ></script>
    <script type="text/javascript" src="{WFView::yuiPath()}yuiloader/yuiloader-debug.js" ></script>
    {else}
    <script type="text/javascript" src="{WFWebApplication::webDirPath(WFWebApplication::WWW_DIR_FRAMEWORK)}/js/prototype-min.js" ></script>
    <script type="text/javascript" src="{WFWebApplication::webDirPath(WFWebApplication::WWW_DIR_FRAMEWORK)}/js/phocoa-min.js" ></script>
    <script type="text/javascript" src="{WFView::yuiPath()}yuiloader/yuiloader-min.js" ></script>
    {/if}
    {WFYAHOO_yuiloader::sharedYuiLoader()->getSetupJS()}
    {$skinHeadStrings}
