<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * Support classes, interfaces, and functions for the various uploader widgets.
 *
 * @package UI
 * @subpackage Widgets
 * @copyright Copyright (c) 2011 Alan Pinstein. All Rights Reserved.
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

interface WFUploadedFile
{
    function tmpFileName();
    function originalFileName();
    function mimeType();
}

class WFUploadedFile_Basic extends WFObject implements WFUploadedFile
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

    protected $thumbnail;
    protected $errors;

    function __construct($tmpFileName, $mimeType, $originalFileName)
    {
        $this->tmpFileName = $tmpFileName;
        $this->mimeType = $mimeType;
        $this->originalFileName = $originalFileName;
        $this->thumbnail = NULL;
        $this->error = array();
    }

    function tmpFileName()
    {
        return $this->tmpFileName;
    }

    function getTmpFileName()
    {
        return $this->tmpFileName();
    }

    function mimeType()
    {
        return $this->mimeType;
    }

    function getMimeType()
    {
        return $this->mimeType();
    }

    function originalFileName()
    {
        return $this->originalFileName;
    }
    function getOriginalFileName()
    {
        return $this->originalFileName();
    }

    function hasErrors()
    {
        return count($this->errors);
    }

    function addError($uploadError) 
    {
        if (!($uploadError instance of WFError)) throw new WFException("WFError required.");
        $this->errors[] = $uploadError;
    }

    function setThumbnail($thumbUrl)
    {
        $this->thumbnail = $thumbUrl;
    }
}

class WFUploaderUtils extends WFObject
{
    function getIniSpecifiedUploadMaxFilesizeAsBytes()
    {
        return self::convertIniSizeToBytes(ini_get('upload_max_filesize'));
    }
    function convertIniSizeToBytes($val)
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        switch($last) {
            // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
        }
        return $val;
    }

    function restoreState($fileInputName)
    {
        $uploads = array();

        if (isset($_FILES[$fileInputName]))
        {
            $count = count($_FILES[$fileInputName]['name']);
            for ($i = 0; $i < $count; $i++) {
                // we silently eat UPLOAD_ERR_NO_FILE; otherwise we keep track of success & error uploads
                if ($_FILES[$fileInputName]['error'][$i] === UPLOAD_ERR_NO_FILE) continue;

                $upload = new WFUploadedFile_Basic($_FILES[$fileInputName]['tmp_name'][$i], $_FILES[$fileInputName]['type'][$i], $_FILES[$fileInputName]['name'][$i]);

                // check for errors
                if ($_FILES[$fileInputName]['error'][$i] == UPLOAD_ERR_OK)
                {
                    if (!is_uploaded_file($_FILES[$fileInputName]['tmp_name'][$i]))
                    {
                        $upload->addError(new WFUploadError("File: '{$_FILES[$fileInputName]['name'][$i]}' is not a legitimate PHP upload. This is a hack attempt.", WFUploadError::ERR_HACKY));
                    }
                }
                else
                {
                    $upload->addError(WFUploadError::createFromPhpUploadError($_FILES[$fileInputName]['error'][$i]));
                }

                $uploads[] = $upload;
            }
        }

        return $uploads;
    }
}
