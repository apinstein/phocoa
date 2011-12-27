<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package UI
 * @subpackage Widgets
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @author Alan Pinstein <apinstein@mac.com>
 */

/**
 * An HTML5 drag-n-drop upload widget.
 *
 * If possible, will use async drag-n-drop upload from: https://github.com/blueimp/jQuery-File-Upload
 *
 * @todo Need to update so that this no longer needs to be a WFForm subclass.
 */
class WFHTML5_Uploader extends WFForm
{
    /**
     * @var array An array of object WFUploadedFile_Basic.
     */
    protected $uploads;

    /**
     * @var mixed A valid php callback object that will be called on each uploaded file. The prototype is:
     *            $page         WFPage
     *            $params       array
     *            $upload       WFUploadedFile
     *            $uplaodError  WFUploadError (or NULL)
     *            string handleUploadedFile($page, $params, $upload, $uploadError)
     *                   +-> throws Exception
     * If the upload is processed successfully, optionally return a STRING to display to the user about the upload. NULL to use the default message.
     * If the upload cannot be processed, throw an Exception; the message will be shown to the user.
     *
     * Note that the handleUploadedFile() callback will be called even if there is an error with the uploaded file (see $error) so that the
     * application can track and report upload failures as it deems appropriate.
     */
    protected $hasUploadCallback;

    /**
     * @var string The base URL for the web site. Defaults to HTTP_HOST
     */
    protected $baseurl;
    /**
     * @var int The maximimum number of concurrent uploads. Defaults to 1. 
     *          NOTE: the underlying control presently supports only *all concurrent* or *all sequential*.
     */
    protected $maxConcurrentUploads;
    /**
     * @var int The maximum file size in bytes to allow. A warning will be displayed for any file over that size and no upload will be attempted on that file. NULL = no limit; defaults to ini's upload_max_filesize setting. 
     */
    protected $maxUploadBytes;
    /**
     * @var boolean Auto-start uploads when files are added. Defaults to false.
     */
    protected $autoupload;
    /**
     * @var string If set, automatically redirects to the provided location when all uploads have completed.
     */
    protected $autoRedirectToUrlOnCompleteAll;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->setHasUploadCallback('handleUploadedFile');

        $this->baseurl = 'http://' . $_SERVER['HTTP_HOST'];
        $this->maxConcurrentUploads = 1;
        $this->maxUploadBytes = WFUploaderUtils::getIniSpecifiedUploadMaxFilesizeAsBytes();
        $this->autoupload = false;
        $this->autoRedirectToUrlOnCompleteAll = NULL;

        $this->uploads = array();
    }

    function setupExposedBindings()
    {
        $myBindings = parent::setupExposedBindings();
        $autoRedirectToUrlOnCompleteAll = new WFBindingSetup('autoRedirectToUrlOnCompleteAll', 'Automatically redirect to the given URL on completion of all uploads.', array(WFBinding::OPTION_VALUE_PATTERN => WFBinding::OPTION_VALUE_PATTERN_DEFAULT_PATTERN));
        $autoRedirectToUrlOnCompleteAll->setBindingType(WFBindingSetup::WFBINDINGTYPE_MULTIPLE_PATTERN);
        $autoRedirectToUrlOnCompleteAll->setReadOnly(true);
        $myBindings[] = $autoRedirectToUrlOnCompleteAll;
        return $myBindings;
    }

    /**
     * Set the callback function to be used to process the uploaded file.
     * 
     * @param mixed String: the method of the current page delegate to call. Array: a php callback.
     * @throws object Exception
     */
    function setHasUploadCallback($callback)
    {
        if (is_string($callback))
        {
            $callback = array($this->page()->delegate(), $callback);
        }
        if (!is_callable($callback)) throw( new WFException('Invalid callback: ' . print_r($callback, true)) );
        $this->hasUploadCallback = $callback;
    }

    function submitAction()
    {
        return WFAction::serverAction()
            ->setTarget("#page#{$this->id}")
            ->setAction('_handleSyncMultipleUploads')
            ;
    }

    private function getInputFileName()
    {
        return "{$this->id}_file";
    }

    function restoreState()
    {
        //  must call super
        parent::restoreState();

        $this->uploads = WFUploaderUtils::restoreState($this->getInputFileName());
    }

    /**
     * Internal callback which will be pinged with the uploaded file sent by the client-side control.
     *
     * This will call out to the configured handleUploadedFile callback.
     *
     * @param array An associative array: 'upload' => WFUploadedFile, 'error' => WFUploadError to process.
     * @return array A hash of data to return to the caller.
     */
    function _handleUploadedFile($uploadedFileInfo)
    {
        extract($uploadedFileInfo);

        $resultMessage = NULL;
        $uploadOK = false;

        try {
            // call user callback always; 
            // detect error if an Exception is thrown *or* a WFUploadError is returned.
            // if the upload is already an error, it cannot be *not an error* after the callback of course
            $thumbnail = NULL;
            // need call_user_func_array due to call_user_func not supporting pass-by-reference
            // using call_user_func_array allows you to do &$thumbnail without a deprecated call-time pass-by-reference warning.
            $callbackResult = call_user_func_array($this->hasUploadCallback, array($this->page(), $this->page()->parameters(), $upload, $error, &$thumbnail));
            if ($callbackResult instanceof WFUploadError)
            {
                $error = $callbackResult;
            }

            if ($error)
            {
                $uploadOK = false;
                $resultMessage = $error->errorMessage();
            }
            else
            {
                $uploadOK = true;
                $resultMessage = $callbackResult;
            }
        } catch (Exception $e) {
            $uploadOK = false;
            $resultMessage = $e->getMessage();
        }

        return array(
            'name'        => $upload->originalFileName(),
            'mimeType'    => $upload->mimeType(),
            'description' => $resultMessage,
            'uploadOK'    => $uploadOK,
            'thumb'       => $thumbnail,
        );
    }

    /**
     * Internal callback function for handling a single uploaded file from the client-based async uploader.
     *
     * This function returns raw JSON data to the client.
     */
    function _handleAsyncSingleUpload()
    {
        if (count($this->uploads) > 1) throw new Exception("_handleAsyncSingleUpload called with multiple uploads.");

        if (count($this->uploads) === 0)
        {
            return array(
                'name'        => '<none>',
                'mimeType'    => NULL,
                'description' => 'No file was uploaded.',
                'uploadOK'    => false,
            );
        }

        $result = $this->_handleUploadedFile($this->uploads[0]);
        print json_encode($result);
        exit;
    }

    /**
     * Internal callback function for handling multiple uploads in a single widget
     */
    function _handleSyncMultipleUploads()
    {
        $allOk = true;
        foreach ($this->uploads as $f) {
            $result = $this->_handleUploadedFile($f);
            if (!$result['uploadOK'])
            {
                $allOk = false;
                $this->addError(new WFUploadError("Error processing \"{$result['name']}\": {$result['description']}"));
            }
        }
        if ($allOk)
        {
            $this->pullBindings();
            if ($this->autoRedirectToUrlOnCompleteAll)
            {
                header("Location: {$this->autoRedirectToUrlOnCompleteAll}");
                exit(0);
            }
        }
    }

    function render($blockContent = NULL)
    {
        $loader = WFYAHOO_yuiloader::sharedYuiLoader();
        // jquery
        $loader->addModule('jquery',
                           'js',
                           NULL,
                           'http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.js',
                           NULL,
                           NULL,
                           NULL,
                           NULL
                        );

        // jquery-ui
        $loader->addModule('jqueryui-css',
                           'css',
                           NULL,
                           'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css',
                           NULL,
                           NULL,
                           NULL,
                           NULL
                        );
        $loader->addModule('jqueryui',
                           'js',
                           NULL,
                           'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.js',
                           array('jquery', 'jqueryui-css'),
                           NULL,
                           NULL,
                           NULL
                        );

        // query-file-uploader
        $loader->addModule('jquery-file-uploader',
                           'js',
                           NULL,
                           $this->getWidgetWWWDir() . '/jquery.fileupload.js',
                           array('jquery'),
                           NULL,
                           NULL,
                           NULL
                        );
        // and the UI
        $loader->addModule('jquery-file-uploader-ui-css',
                           'css',
                           NULL,
                           $this->getWidgetWWWDir() . '/jquery.fileupload-ui.css',
                           NULL,
                           NULL,
                           array('jqueryui-css'),
                           NULL
                        );
        $loader->addModule('jquery-file-uploader-ui',
                           'js',
                           NULL,
                           $this->getWidgetWWWDir() . '/jquery.fileupload-ui.js',
                           array('jquery-file-uploader', 'jqueryui', 'jquery-file-uploader-ui-css'),
                           NULL,
                           NULL,
                           NULL
                        );
        $loader->yuiRequire('jquery-file-uploader-ui');

        // @todo In future this should not need to be a WFForm subclass; should be able to drop it in a form anywhere.
        //$form = $this->getForm();
        //if (!$form) throw new WFException("WFHTML5_Uploader must be a child of a WFForm.");
        $form = $this;

        // craft a WFRPC that we can insert into the form stream to have our callback fire
        $rpc = WFRPC::RPC()->setInvocationPath($this->page->module()->invocation()->invocationPath())
                           ->setTarget('#page#' . $this->id)
                           ->setAction('_handleAsyncSingleUpload')
                           ->setForm($this)
                           ->setIsAjax(true);
        $uploadFormData = json_encode($rpc->rpcAsParameters($this));

        // figure out vars for drop-in into HTML block
        $sequentialUploads = var_export($this->maxConcurrentUploads == 1, true);
        $autoupload = var_export($this->autoupload, true);
        $fileInputName = "{$this->getInputFileName()}[]";

        // HTML
        $formInnardsHTML = <<<END
                {$blockContent}
                <input type="file" name="{$fileInputName}" multiple>
                <button type="submit" name="action|{$this->id}">Upload</button>
                <div class="file_upload_label">Click or Drop to Upload</div>
END;
        $html = parent::render($formInnardsHTML);

        $maxUploadBytesJSON = WFJSON::encode($this->maxUploadBytes);

        // progress indicators after form since the blueimp plugin takes over the entire form area for drag-n-drop
        $html .= <<<END
<div id="{$this->id}_progressAll" style="display: none;"></div>
<table id="{$this->id}_table" style="display: none;"></table>
END;
        $withJqueryJS = <<<END
function() {
    jQuery.noConflict();
    jQuery(function () {
        window.uploader = jQuery('#{$form->id()}').fileUploadUI({
            formData: {$uploadFormData},
            sequentialUploads: {$sequentialUploads},
            beforeSend: function (event, files, index, xhr, handler, callBack) {
                jQuery('#{$this->id}_table, #{$this->id}_progressAll').show();
                if ({$maxUploadBytesJSON} && files[index].fileSize > {$maxUploadBytesJSON})
                {
                    var json = {
                        'name': files[index].name,
                        'description': ('File exceeds maximum file size of ' + {$maxUploadBytesJSON} + ' bytes.'),
                        'uploadOK': false
                    };
                    handler.downloadRow = handler.buildDownloadRow(json, handler);
                    handler.replaceNode(handler.uploadRow, handler.downloadRow, null);
                    return;
                }
                if ({$autoupload})
                {
                    callBack();
                }
            },
            progressAllNode: jQuery('#{$this->id}_progressAll'),
            uploadTable: jQuery('#{$this->id}_table'),
            onCompleteAll: function() {
                var autoRedirectToUrlOnCompleteAll = '{$this->autoRedirectToUrlOnCompleteAll}';
                if (autoRedirectToUrlOnCompleteAll)
                {
                    window.location.href = autoRedirectToUrlOnCompleteAll;
                }
            },
            buildUploadRow: function (files, index) {
                return jQuery('<tr>' +
                        '<td width="175">' + files[index].name + '<\/td>' +
                        '<td width="1">&nbsp;<\/td>' +
                        '<td width="250">&nbsp;<\/td>' +
                        '<td width="16" class="file_upload_cancel">' +
                            '<button class="ui-state-default ui-corner-all" title="Cancel">' +
                            '<span class="ui-icon ui-icon-cancel">Cancel<\/span>' +
                            '<\/button><\/td>' +
                        '<td class="file_upload_progress" style="width: 160px"><div><\/div><\/td>' +
                        '<\/tr>');
            },
            buildDownloadRow: function (file, handler) {
                var thumbHTML = '&nbsp;';
                if (file.thumb)
                {
                    thumbHTML = '<img src="'+file.thumb+'" style="float: right;"/>';
                }
                return jQuery('<tr>' +
                    '<td width="175">' + file.name + '<\/td>' +
                    '<td width="' + (thumbHTML ? 100 : 1) + '">' + thumbHTML + '<\/td>' +
                    '<td width="250">' + file.description + '<\/td>' + 
                    '<td width="16"><span class="ui-icon ' + (file.uploadOK ? 'ui-icon-check' : 'ui-icon-alert') + '"><\/span><\/td>' + 
                    '<td><\/td>' + 
                    '<\/tr>');
            }
        });
    });
}
END;

        $bootstrapJS = $loader->jsLoaderCode($withJqueryJS);
        $html .= <<<END
<script> 
{$bootstrapJS}
</script> 
END;
        return $html;
    }

    function canPushValueBinding() { return false; }
}

class WFUploadError extends WFError
{
    const ERR_PHP_INI_SIZE   = UPLOAD_ERR_INI_SIZE;      // 'Upload error 1; The uploaded file exceeds the upload_max_filesize directive in php.ini.',
    const ERR_PHP_FORM_SIZE  = UPLOAD_ERR_FORM_SIZE;     // 'Upload error 2; The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
    const ERR_PHP_PARTIAL    = UPLOAD_ERR_PARTIAL;       // 'Upload error 3; The uploaded file was only partially uploaded.',
    const ERR_PHP_NO_FILE    = UPLOAD_ERR_NO_FILE;       // 'Upload error 4; No file was uploaded.',
    const ERR_PHP_NO_TMP_DIR = UPLOAD_ERR_NO_TMP_DIR;    // 'Upload error 6; Missing a temporary folder. Introduced in PHP 4.3.10 and PHP 5.0.3.',
    const ERR_PHP_CANT_WRITE = UPLOAD_ERR_CANT_WRITE;    // 'Upload error 7; Failed to write file to disk. Introduced in PHP 5.1.0.',
    const ERR_PHP_EXTENSION  = UPLOAD_ERR_EXTENSION;     // 'Upload error 8; File upload stopped by extension. Introduced in PHP 5.2.0.',
    // custom errors start at 1000 to give us headroom for additional PHP errors in future
    const ERR_APPLICATION    = 1000;
    const ERR_HACKY          = 1001;

    public function __construct($msg, $errCode = 1000)
    {
        parent::__construct($msg, $errCode);
    }

    static function createFromPhpUploadError($phpUploadErrorNumber)
    {
        $errCodeMap = @array(
            UPLOAD_ERR_INI_SIZE   => ERR_PHP_INI_SIZE,
            UPLOAD_ERR_FORM_SIZE  => ERR_PHP_FORM_SIZE,
            UPLOAD_ERR_PARTIAL    => ERR_PHP_PARTIAL,
            UPLOAD_ERR_NO_FILE    => ERR_PHP_NO_FILE,
            UPLOAD_ERR_NO_TMP_DIR => ERR_PHP_NO_TMP_DIR,
            UPLOAD_ERR_CANT_WRITE => ERR_PHP_CANT_WRITE,
            UPLOAD_ERR_EXTENSION  => ERR_PHP_EXTENSION,
        );
        $errMsgMap = @array(
            UPLOAD_ERR_INI_SIZE   => 'File exceeds the maximum upload size.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds the maximum upload size.',
            UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'File upload stopped by extension.',
        );
        if (!isset($errCodeMap[$phpUploadErrorNumber])) throw new Exception("Unexpected PHP upload error: {$phpUploadErrorNumber}.");
        return new WFUploadError($errMsgMap[$phpUploadErrorNumber], $errCodeMap[$phpUploadErrorNumber]);
    }
}
