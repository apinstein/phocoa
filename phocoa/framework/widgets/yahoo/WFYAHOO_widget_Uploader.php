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
 * This widget allows easy bulk uploading from the desktop to the web server with the YUI Uploader Flash widget. Requires Flash 9.
 *
 * To use, simply add a WFYAHOO_widget_Uploader instance, as a child of a WFForm, to your page. Then, place the widget on your page {WFView id="myUploaderId"}. There is no need to wrap
 * the widget inside the form in the HTML template.
 *
 * All you need to do to receive the uploaded files is create a single function to handle the uploaded file. When someone uploads file(s) through the bulk uploader, your module's {@link WFYAHOO_widget_Uploader::$hasUploadCallback hasUploadCallback} will be called, once for each file uploaded. From this function you can do as you please with each uploaded file.
 * 
 * NOTE: If you want to bulid a full ajax uploader with no page refreshes, this widget will fire a 'WFYAHOO_widget_Uploader:allUploadsComplete' event when all uploads have completed. You can register handlers with document.observe('WFYAHOO_widget_Uploader:allUploadsComplete').
 *
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * 
 * <b>Optional:</b><br>
 * {@link WFYAHOO_widget_Uploader::$allowMultiple allowMultiple}
 * {@link WFYAHOO_widget_Uploader::$addButtonLabel addButtonLabel}
 * {@link WFYAHOO_widget_Uploader::$uploadButtonLabel uploadButtonLabel}
 * {@link WFYAHOO_widget_Uploader::$continueURL continueURL}
 */
class WFYAHOO_widget_Uploader extends WFYAHOO implements WFUploadedFile
{
    /**
     * @var boolean Allow multiple uploads? Default: FALSE
     */
    protected $allowMultiple;
    /**
     * @var string The label used on the "Add Files" button.
     */
    protected $addButtonLabel;
    /**
     * @var string The label used on the "Start Upload" button.
     */
    protected $uploadButtonLabel;
    /**
     * @var string The URL to redirect the user to once the upload has completed. Default: NULL (no redirect).
     */
    protected $continueURL;

    /**
     * @var string The temp file name of the uploaded file.
     */
    protected $tmpFileName;
    /**
     * @var string The mime type of the uploaded file. This is the mime-type reported by the browser, so remember that it can be faked!
     */
    protected $mimeType;
    /**
     * @var string The name of the actual file.
     */
    protected $originalFileName;
    /**
     * @var bool Will be true if a file SUCCESSFULLY uploaded.
     */
    private $hasUpload;
    /**
     * @var mixed A valid php callback object that will be called on each uploaded file. The prototype is: void handleUploadedFile($page, $params, object WFYAHOO_widget_Uploader).
     */
    protected $hasUploadCallback;
    /**
     * @var int The maximum file size in bytes to allow. A warning will be displayed for any file over that size and no upload will be attempted on that file. Default NULL (no limit).
     */
    protected $maxUploadBytes;


    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);

        $this->yuiloader()->yuiRequire('uploader,json,element,yahoo,dom,event');
        $this->allowMultiple = false;
        $this->addButtonLabel = "Select Files";
        $this->uploadButtonLabel = "Upload Files";
        $this->setHasUploadCallback('handleUploadedFile');
        $this->maxUploadBytes = NULL;
        $this->continueURL = NULL;
    }

    function getForm()
    {
        $p = $this->parent();
        if (!($p instanceof WFForm)) throw( new WFException("WFYAHOO_widget_Uploader must be the direct child of a WFForm.") );
        return $p;
    }

    function setContinueURL($url)
    {
        $this->continueURL = $url;
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

    function tmpFileName()
    {
        return $this->tmpFileName;
    }

    function originalFileName()
    {
        return $this->originalFileName;
    }

    function mimeType()
    {
        return $this->mimeType;
    }

    function hasUpload()
    {
        return $this->hasUpload;
    }

    function handleUploadedFile()
    {
        if ($this->hasUpload())
        {
            try {
                call_user_func($this->hasUploadCallback, $this->page(), $this->page()->parameters(), $this);
                // send back success
                print "UPLOAD OK";
            } catch (Excpetion $e) {
                // send back error+noretry
                print "UPLOAD ERROR: " . $e->getMessage();
            }
            exit;
        }
    }

    function restoreState()
    {
        //  must call super
        parent::restoreState();

        if (isset($_FILES[$this->name]))
        {
            if (is_array($_FILES[$this->name]['name'])) throw (new Exception("WFYAHOO_widget_Uploader expected a single upload files but multiple found.") );

            if ($_FILES[$this->name]['error'] == UPLOAD_ERR_OK)
            {
                if (is_uploaded_file($_FILES[$this->name]['tmp_name']))
                {
                    $this->hasUpload = true;
                    $this->tmpFileName = $_FILES[$this->name]['tmp_name'];
                    $this->originalFileName = $_FILES[$this->name]['name'];
                    $this->mimeType = $_FILES[$this->name]['type'];
                }
                else
                {
                    throw( new WFException("File: '{$_FILES[$this->name]['name']}' is not a legitimate PHP upload. This is a hack attempt.") );
                }
            }
            else if ($_FILES[$this->name]['error'] != UPLOAD_ERR_NO_FILE)
            {
                // send back error+noretry
                print "UPLOAD ERROR: NO FILE SENT";
                exit;
            }
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
            // render the tab's content block
            $html = parent::render($blockContent);
            $html .= '
            <div id="' . $this->id . '_uiElements" style="display: inline;">
                <div id="' . $this->id . '_uplaoderContainer">
                    <div id="' . $this->id . '" style="position: absolute; z-index: 2;">Unable to load Flash content. The YUI Uploader requires Flash Player 9.0.45 or higher.</div>
                    <div id="' . $this->id . '_selectFilesLink" style="z-index:1">
                        <a id="' . $this->id . '_browseTrigger" >' . $this->addButtonLabel . '</a>
                    </div>
                </div>
                <div id="' . $this->id . '_uploadFilesLink">
                    <a id="' . $this->id . '_uploadTrigger" >' . $this->uploadButtonLabel . '</a>
                </div>
            </div>
            <div id="' . $this->id . '_progress"></div>
            <div id="' . $this->id . '_fileList" style="display: none; border: 1px solid;"></div>
';
            return $html;
        }
    }

    function initJS($blockContent)
    {
        // craft a WFRPC that we can insert into the form stream to have our callback fire
        $rpc = WFRPC::RPC()->setInvocationPath($this->page->module()->invocation()->invocationPath())
                           ->setTarget('#page#' . $this->id)
                           ->setAction('handleUploadedFile')
                           ->setForm($this->getForm())
                           ->setRunsIfInvalid(true)
                           ->setIsAjax(true);

        $html = "
        PHOCOA.widgets.{$this->id}.emptyFileListDisplay = function() {
            PHOCOA.widgets.{$this->id}.filesToUploadTracker = {};
            PHOCOA.widgets.{$this->id}.filesToUploadTotalBytes = 0;
            $('{$this->id}_fileList').update('').hide();
        };
        PHOCOA.widgets.{$this->id}.init = function() {
            YAHOO.util.Event.onDOMReady(function () { 
                var uiLayer = YAHOO.util.Dom.getRegion('{$this->id}_browseTrigger');
                var overlay = YAHOO.util.Dom.get('{$this->id}');
                YAHOO.util.Dom.setStyle(overlay, 'width', uiLayer.right-uiLayer.left + 'px');
                YAHOO.util.Dom.setStyle(overlay, 'height', uiLayer.bottom-uiLayer.top + 'px');
            });

            YAHOO.widget.Uploader.SWFURL = '" . $this->yuiPath() . "/uploader/assets/uploader.swf'; 
            var uploader = new YAHOO.widget.Uploader('{$this->id}');
            PHOCOA.runtime.addObject(uploader, '{$this->id}');

            // can't customize until the SWF is ready
            uploader.addListener('contentReady', function() {
                uploader.setAllowMultipleFiles(" . ($this->allowMultiple ? 'true' : 'false')  . ");
            });

            $('{$this->id}_uploadTrigger').observe('click', function() {
                PHOCOA.runtime.getObject('{$this->id}').uploadAll('" . $rpc->url() . "', 'POST', " . WFJSON::encode($rpc->rpcAsParameters()) . ", '{$this->id}');
            });

            uploader.addListener('fileSelect', function(e) {
                // fileSelect sends ALL files tracked by flash, not just the ones added in the most recent file select dialog
                PHOCOA.widgets.{$this->id}.emptyFileListDisplay();
                $('{$this->id}_fileList').show();

                var files = \$H(e.fileList).values();
                files.pluck('id').each(function(o) {
                    PHOCOA.widgets.{$this->id}.filesToUploadTracker[o] = {
                        id: e.fileList[o].id,
                        name: e.fileList[o].name,
                        size: e.fileList[o].size,
                        sizeProgress: 0
                        };
                });

                var makePrettySize = function(sz, decimals) {
                    if (typeof decimals === 'undefined')
                    {
                        decimals = 2;
                    }
                    var suffixes = ['Bytes','KB','MB','GB','TB'];
                    var i = 0;

                    while (sz >= 1024 && (i < suffixes.length - 1)){
                        sz /= 1024;
                        i++;
                    }
                    return Math.round(sz*Math.pow(10,decimals))/Math.pow(10,decimals) + ' '  + suffixes[i];
                };";

            if ($this->maxUploadBytes !== NULL)
            {
                $html .= "
                var tooBig = [];
                var justRight = {};
                \$H(PHOCOA.widgets.{$this->id}.filesToUploadTracker).values().each(function(o) {
                    if (o.size > {$this->maxUploadBytes})
                    {
                        PHOCOA.runtime.getObject('{$this->id}').removeFile(o.id);
                        tooBig.push(o);
                    }
                    else
                    {
                        justRight[o.id] = o;
                    }
                });
                PHOCOA.widgets.{$this->id}.filesToUploadTracker = justRight;
                if (tooBig.length)
                {
                    alert('The following files will be skipped because they are more than ' + makePrettySize({$this->maxUploadBytes}) + \":\\n- \" + tooBig.pluck('name').join(\"\\n- \"));
                }
                ";
            }
            $html .= "
                var allFilesToUpload = \$H(PHOCOA.widgets.{$this->id}.filesToUploadTracker).values();
                allFilesToUpload.pluck('size').each(function(o) {
                    PHOCOA.widgets.{$this->id}.filesToUploadTotalBytes += o;
                });
                $('{$this->id}_fileList').update(allFilesToUpload.length + ' file(s) selected: <br />' + allFilesToUpload.collect(function(o) { 
                                                                                                                            return o.name + ' ' + makePrettySize(o.size);
                                                                                                                        }).join('<br />'));
            });
            uploader.addListener('uploadStart', function(e) {
                PHOCOA.widgets.{$this->id}.updateProgress();
            });
            uploader.addListener('uploadProgress', function(e) {
                PHOCOA.widgets.{$this->id}.filesToUploadTracker[e.id].sizeProgress = e.bytesLoaded;
                PHOCOA.widgets.{$this->id}.updateProgress();
            });
            uploader.addListener('uploadComplete', function(e) {
                PHOCOA.widgets.{$this->id}.filesToUploadTracker[e.id].sizeProgress = PHOCOA.widgets.{$this->id}.filesToUploadTracker[e.id].size;
                PHOCOA.widgets.{$this->id}.updateProgress();

                // are we done with ALL uploads?
                var uploadComplete = true;
                \$H(PHOCOA.widgets.{$this->id}.filesToUploadTracker).values().each(function(o) {
                    uploadComplete = uploadComplete && (o.sizeProgress === o.size)
                });
                if (uploadComplete)
                {
                    $('{$this->id}_progress').update('Upload complete.');
                    PHOCOA.runtime.getObject('{$this->id}').clearFileList();    // remove files from flash
                    PHOCOA.widgets.{$this->id}.emptyFileListDisplay();
                    document.fire('WFYAHOO_widget_Uploader:allUploadsComplete');
                    if (" . WFJSON::encode( !empty($this->continueURL) ) . ")
                    {
                        window.location = '{$this->continueURL}';
                    }
                }
            });
        };
        PHOCOA.widgets.{$this->id}.updateProgress = function() {
            var uploadProgressBytes = 0;
            \$H(PHOCOA.widgets.{$this->id}.filesToUploadTracker).values().pluck('sizeProgress').each( function(o) {
                uploadProgressBytes += o;
            });
            var msg = 'Upload progress: ' + Math.round(uploadProgressBytes*100 / PHOCOA.widgets.{$this->id}.filesToUploadTotalBytes) + '%';
            $('{$this->id}_progress').update(msg);
        };
        ";

        return $html;
    }

}
