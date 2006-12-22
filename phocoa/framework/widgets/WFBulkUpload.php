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
 * A Bulk Upload widget for our framework.
 *
 * This is useful if you want to give the user the opporuntity to upload many files at once.
 *
 * This widget will present a list of "browse" buttons, and/or an ActiveX control to upload multiple files at once, yet presents the same interface for
 * dealing with the multiple files to the client.
 */
class WFBulkUpload extends WFWidget
{
    /**
     * @var bool Will be true if at least one file SUCCESSFULLY uploaded.
     */
    private $hasUpload;
    protected $uploads;
    protected $uploadErrors;
    protected $numberOfUploadSlots; // display only; activeX allows arbitrary amount

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->numberOfUploadSlots = 5;
        $this->hasUpload = false;
        $this->uploads = array();
        $this->uploadErrors = array();
    }

    /**
     *  Get a list of the uploaded files.
     *
     *  @return array An array of {@link WFBulkUploadFile} objects.
     */
    function uploads()
    {
        return $this->uploads;
    }

    /**
     *  Is there at least one valid upload?
     *
     *  @return boolean
     */
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
            $count = count($_FILES[$this->name]);
            for ($i = 0; $i < $count; $i++) {
                // check for errors
                if ($_FILES[$this->name]['error'][$i] == 0)
                {
                    if (is_uploaded_file($_FILES[$this->name]['tmp_name'][$i]))
                    {
                        $this->uploads[] = new WFBulkUploadFile($_FILES[$this->name]['tmp_name'][$i], $_FILES[$this->name]['type'][$i], $_FILES[$this->name]['name'][$i]);
                        $this->hasUpload = true;
                    }
                    else
                    {
                        $this->errors[] = "File: '{$_FILES[$this->name]['name'][$i]}' is not a legitimate upload.";
                    }
                }
                else if ($_FILES[$this->name]['error'][$i] != UPLOAD_ERR_NO_FILE)
                {
                    $this->errors[] = "File: '{$_FILES[$this->name]['name'][$i]}' reported error: {$_FILES[$this->name]['error'][$i]}.";
                }
            }
        }
    }

    function render($blockContent = NULL)
    {
        $html = '';
        $html .= '';    // put activeX thing here
        for ($i = 0; $i < $this->numberOfUploadSlots; $i++) {
            $html .= '<input type="file" name="' . $this->name . '[]" /><br />';
        }
        return $html;
    }

    function canPushValueBinding() { return false; }
}

class WFBulkUploadFile extends WFObject
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

    function __construct($tmpFileName, $mimeType, $originalFileName)
    {
        $this->tmpFileName = $tmpFileName;
        $this->mimeType = $mimeType;
        $this->originalFileName = $originalFileName;
    }

    function getTmpFileName()
    {
        return $this->tmpFileName;
    }

    function getMimeType()
    {
        return $this->mimeType;
    }

    function getOriginalFileName()
    {
        return $this->originalFileName;
    }
}
?>
