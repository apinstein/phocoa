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
     * @var array An array of ('upload' => WFUploadedFile_Basic, 'error' => WFUploadError)
     */
    protected $uploads;

    /**
     * @var mixed A valid php callback object that will be called on each valid uploaded file. The prototype is:
     *            $page         WFPage
     *            $params       array
     *            $upload       WFUploadedFile
     *            string handleUploadedFile($page, $params, $upload)
     *                   +-> throws Exception
     * If the upload is processed successfully, optionally return a STRING to display to the user about the upload. NULL to use the default message.
     * If the upload cannot be processed, throw an Exception; the message will be shown to the user.
     *
     * Note that the handleUploadedFile() callback is not called if there is an error in the uploaded file.
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
     * @var boolean Set to true when multiple simultaneous uploads are detected.
     */
    private $hasMultipleSimultaneousUploads;
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

        $fileInputName = $this->getInputFileName();

        if (isset($_FILES[$fileInputName]))
        {
            $count = count($_FILES[$fileInputName]['name']);
            $this->hasMultipleSimultaneousUploads = ($count > 1);
            for ($i = 0; $i < $count; $i++) {
                // we silently eat UPLOAD_ERR_NO_FILE; otherwise we keep track of success & error uploads
                if ($_FILES[$fileInputName]['error'][$i] === UPLOAD_ERR_NO_FILE) continue;

                $upload = new WFUploadedFile_Basic($_FILES[$fileInputName]['tmp_name'][$i], $_FILES[$fileInputName]['type'][$i], $_FILES[$fileInputName]['name'][$i]);
                $error = NULL;

                // check for errors
                if ($_FILES[$fileInputName]['error'][$i] == UPLOAD_ERR_OK)
                {
                    if (!is_uploaded_file($_FILES[$fileInputName]['tmp_name'][$i]))
                    {
                        $error = new WFUploadError("File: '{$_FILES[$fileInputName]['name'][$i]}' is not a legitimate PHP upload. This is a hack attempt.", WFUploadError::ERR_HACKY);
                    }
                }
                else
                {
                    $error = WFUploadError::createFromPhpUploadError($_FILES[$fileInputName]['error'][$i]);
                }

                $this->uploads[] = compact('upload', 'error');
            }
        }
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

        if (!$error)
        {
            try {
                $resultMessage = call_user_func($this->hasUploadCallback, $this->page(), $this->page()->parameters(), $upload);
                if ($resultMessage === NULL)
                {
                    $resultMessage = "File uploaded successfully.";
                }
                $uploadOK = true;
            } catch (Exception $e) {
                $error = new WFUploadError($e->getMessage());
            }
        }

        if ($error)
        {

            if ($this->hasMultipleSimultaneousUploads)
            {
                $error->setErrorMessage("Error with {$upload->originalFileName()}: {$error->errorMessage()}");
            }
            $this->addError($error);
            $resultMessage = $error->errorMessage();
        }

        return array(
            'name'        => $upload->originalFileName(),
            'mimeType'    => $upload->mimeType(),
            'description' => $resultMessage,
            'uploadOK'    => $uploadOK,
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
     * Internal callback function for handling multiple uploads in a single widget.
     * This is used from non-javascript HTML uploader. The latest browsers allow you to submit multiple files in one POST.
     */
    function _handleSyncMultipleUploads()
    {
        $allOk = true;
        foreach ($this->uploads as $f) {
            $result = $this->_handleUploadedFile($f);
            if (!$result['uploadOK'])
            {
                $allOk = false;
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
                document.fire ('WFHTML5_Uploader:uploadStart');
                jQuery('#{$this->id}_table, #{$this->id}_progressAll').show();
                var fileSize = files[index].fileSize ? files[index].fileSize : files[index].size  // Firefox calls it file.size instead of file.fileSize
                if ({$maxUploadBytesJSON} && fileSize > {$maxUploadBytesJSON})
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
                    document.fire ('WFHTML5_Uploader:uploadAllComplete');
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
                    thumbHTML = '<img s'+'rc="'+file.thumb+'" style="float: right;"/>';
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

