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
 * A Java upload widget for our framework.
 *
 * This widget allows easy bulk uploading from the desktop to the web server.
 * 
 * This widget uses the {@link http://sourceforge.net/projects/postlet/ Postlet open-source Java applet}.
 * The code is GPL licensed and is distributed with PHOCOA for convenience. Full source is available from the above link.
 * The current bundled version is 0.14-alan (which is a customized version to support our needs -- we will upgraded to the latest version once our patches are accepted).
 */
class WFPostletUpload extends WFForm
{
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
     * @var mixed A valid php callback object that will be called on each uploaded file. The prototype is: void handleUploadedFile($page, $params, object WFPostletUpload).
     */
    protected $hasUploadCallback;

    /**#@+
     * @var mixed Same as the setting as {@link http://postlet.com/install/ documeted}. For convenience, color proeprties take in web colors (ie red = FF0000).
     */
    protected $baseurl;
    protected $dropimage;
    protected $dropimageupload;
    protected $maxthreads;
    protected $backgroundcolour;
    protected $tableheaderbackgroundcolour;
    protected $tableheadercolour;
    protected $warnmessage;
    protected $autoupload;
    protected $helpbutton;
    protected $removebutton;
    protected $addbutton;
    protected $uploadbutton;
    protected $endpage;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->tmpFileName = NULL;
        $this->mimeType = NULL;
        $this->originalFileName = NULL;
        $this->hasUpload = false;
        $this->setHasUploadCallback('handleUploadedFile');

        $this->baseurl = 'http://' . $_SERVER['HTTP_HOST'];
        $this->dropimage = NULL;
        $this->dropimageupload = NULL;
        $this->maxthreads = 5;
        $this->backgroundcolour = NULL;
        $this->tableheaderbackgroundcolour = NULL;
        $this->tableheadercolour = NULL;
        $this->warnmessage = false;
        $this->autoupload = false;
        $this->helpbutton = false;
        $this->removebutton = true;
        $this->addbutton = true;
        $this->uploadbutton = true;
        $this->endpage = NULL;
    }

    /**
     * Turn the widget into a fire-n-forget drop zone for file uploading.
     * 
     * Enabling this mode hides all buttons, enables the drop images, turns on autoupload, and requires an {@link WFPostletUpload::$endpage endpage} to be set.
     *
     * @param string The destination URL to go to when the upload is complete.
     */
    function enableAutoDropMode($endpage)
    {
        $this->dropimage = $this->getWidgetWWWDir() . '/image.jpg';
        $this->dropimageupload = $this->getWidgetWWWDir() . '/imageupload.jpg';
        $this->autoupload = true;
        $this->removebutton = false;
        $this->addbutton = false;
        $this->uploadbutton = false;
        $this->endpage = $endpage;
    }

    function setBackgroundcolour($webColor)
    {
        $this->backgroundcolour = hexdec($webColor);
    }

    function setTableheaderbackgroundcolour($webColor)
    {
        $this->tableheaderbackgroundcolour = hexdec($webColor);
    }

    function setTableheadercolour($webColor)
    {
        $this->tableheadercolour = hexdec($webColor);
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

    function restoreState()
    {
        //  must call super
        parent::restoreState();

        if (isset($_FILES[$this->name]))
        {
            if (is_array($_FILES[$this->name]['name'])) throw (new Exception("WFPostletUpload expected a single upload files but multiple found.") );

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
                print "POSTLET REPLY
POSTLET:NO
POSTLET:UNKNOWN ERROR
POSTLET:ABORT THIS
END POSTLET REPLY";
                exit;
            }
        }
    }

    function handleUploadedFile()
    {
        if ($this->hasUpload())
        {
            try {
                call_user_func($this->hasUploadCallback, $this->page(), $this->page()->parameters(), $this);
                // send back success
                print "POSTLET REPLY
POSTLET:YES
END POSTLET REPLY
";
            } catch (Excpetion $e) {
                // send back error+noretry
                print "POSTLET REPLY
POSTLET:NO
POSTLET:UNKNOWN ERROR
POSTLET:ABORT THIS
END POSTLET REPLY";
            }
            exit;
        }
    }

    function render($blockContent = NULL)
    {
        // craft a WFRPC that we can insert into the form stream to have our callback fire
        $rpc = WFRPC::RPC()->setInvocationPath($this->page->module()->invocation()->invocationPath())
                           ->setTarget('#page#' . $this->id)
                           ->setAction('handleUploadedFile')
                           ->setForm($this)
                           ->setIsAjax(true);

        return '
<applet name="postlet" code="Main.class" archive="' . $this->getWidgetWWWDir() . '/postlet.jar" width="305" height="200" mayscript="mayscript">
    <param name = "uploadparametername"      value = "' . $this->id . '" />
    <param name = "additionalformparameters" value = "' . http_build_query($rpc->rpcAsParameters($this), '', '&amp;') . '" />

    ' . ($this->dropimage ? '<param name = "dropimage"       value = "' . $this->baseurl . $this->dropimage . '"/>' : NULL ) . '
    ' . ($this->dropimageupload ? '<param name = "dropimageupload"       value = "' . $this->baseurl . $this->dropimageupload . '"/>' : NULL ) . '

    <param name = "maxthreads"      value = "' . $this->maxthreads . '" />
    <param name = "destination"     value = "' . $this->baseurl . '/' . $this->page->module()->invocation()->invocationPath() . '" />
    ' . ($this->backgroundcolour ? '<param name = "backgroundcolour" value = "' . $this->backgroundcolour . '" />' : NULL) . '
    ' . ($this->tableheaderbackgroundcolour ? '<param name = "tableheaderbackgroundcolour" value = "' . $this->tableheaderbackgroundcolour . '" />' : NULL) . '
    ' . ($this->tableheadercolour ? '<param name = "tableheadercolour" value = "' . $this->tableheadercolour . '" />' : NULL) . '
    <param name = "warnmessage" value = "' . ($this->warnmessage ? 'true' : 'false') . '" />
    <param name = "autoupload" value = "' . ($this->autoupload ? 'true' : 'false') . '" />
    <param name = "helpbutton" value = "' . ($this->helpbutton ? 'true' : 'false') . '" />
    <param name = "removebutton" value = "' . ($this->removebutton ? 'true' : 'false') . '" />
    <param name = "addbutton" value = "' . ($this->addbutton ? 'true' : 'false') . '" />
    <param name = "uploadbutton" value = "' . ($this->uploadbutton ? 'true' : 'false') . '" />
    <param name = "fileextensions" value = "JPEG Image Files,jpg,jpeg" />
    ' . ($this->endpage ? '<param name = "endpage" value = "' . $this->baseurl . $this->endpage . '" />' : NULL) . '
</applet>
<script type="text/javascript" src="' . WFWebApplication::webDirPath(WFWebApplication::WWW_DIR_FRAMEWORK) . '/js/embeddedcontent_min.js" defer="defer"></script>
        ';
    }

    function canPushValueBinding() { return false; }
}

?>
