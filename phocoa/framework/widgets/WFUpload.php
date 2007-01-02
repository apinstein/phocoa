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
 * A Upload widget for our framework.
 */
class WFUpload extends WFWidget
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
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->tmpFileName = NULL;
        $this->mimeType = NULL;
        $this->originalFileName = NULL;
        $this->hasUpload = false;
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
            if (is_array($_FILES[$this->name]['name'])) throw (new Exception("WFUpload expected a single upload files but multiple found.") );

            // use @ so that pre-php-5.2 users don't see warnings b/c some of these didn't exist
            $phpUploadErrors = @array(
                    UPLOAD_ERR_OK => 'Value: 0; There is no error, the file uploaded with success.',
                    UPLOAD_ERR_INI_SIZE => 'Value: 1; The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                    UPLOAD_ERR_FORM_SIZE => 'Value: 2; The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                    UPLOAD_ERR_PARTIAL => 'Value: 3; The uploaded file was only partially uploaded.',
                    UPLOAD_ERR_NO_FILE => 'Value: 4; No file was uploaded.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Value: 6; Missing a temporary folder. Introduced in PHP 4.3.10 and PHP 5.0.3.',
                    UPLOAD_ERR_CANT_WRITE => 'Value: 7; Failed to write file to disk. Introduced in PHP 5.1.0.',
                    UPLOAD_ERR_EXTENSION => 'Value: 8; File upload stopped by extension. Introduced in PHP 5.2.0.',
            );
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
                    $this->addError(new WFError("File: '{$_FILES[$this->name]['name']}' is not a legitimate PHP upload. This is a hack attempt."));
                }
            }
            else if ($_FILES[$this->name]['error'] != UPLOAD_ERR_NO_FILE)
            {
                $this->addError(new WFError("File: '{$_FILES[$this->name]['name']}' reported error: " . $phpUploadErrors[$_FILES[$this->name]['error']]));
            }
        }
    }

    function render($blockContent = NULL)
    {
        return '<input type="file" name="' . $this->name . '" />';
    }

    function canPushValueBinding() { return false; }
}

?>
