   {if $skinTitle}<title>{$skinTitle}</title>{/if}
   {if $skinMetaDescription}<meta name="description" content="{$skinMetaDescription}" />{/if}
   {if $skinMetaKeywords}<meta name="keywords" content="{$skinMetaKeywords}" />{/if} 
     
    <meta http-equiv="Content-Type" content="text/html; charset={$skinCharset}" />
    <link rel="stylesheet" type="text/css" href="{php}echo WFWebApplication::webDirPath(WFWebApplication::WWW_DIR_FRAMEWORK);{/php}/phocoa.css" />
    {if $phocoaDebug}
    <script type="text/javascript" src="{php}echo WFWebApplication::webDirPath(WFWebApplication::WWW_DIR_FRAMEWORK);{/php}/js/prototype.js" ></script>
    <script type="text/javascript" src="{php}echo WFWebApplication::webDirPath(WFWebApplication::WWW_DIR_FRAMEWORK);{/php}/js/phocoa.js" ></script>
    <script type="text/javascript" src="{php}echo WFView::yuiPath();{/php}yuiloader/yuiloader-debug.js" ></script>
    {else}
    <script type="text/javascript" src="{php}echo WFWebApplication::webDirPath(WFWebApplication::WWW_DIR_FRAMEWORK);{/php}/js/prototype-min.js" ></script>
    <script type="text/javascript" src="{php}echo WFWebApplication::webDirPath(WFWebApplication::WWW_DIR_FRAMEWORK);{/php}/js/phocoa-min.js" ></script>
    <script type="text/javascript" src="{php}echo WFView::yuiPath();{/php}yuiloader/yuiloader-min.js" ></script>
    {/if}
    {php} print WFYAHOO_yuiloader::sharedYuiLoader()->getSetupJS(); {/php}
    {$skinHeadStrings}
