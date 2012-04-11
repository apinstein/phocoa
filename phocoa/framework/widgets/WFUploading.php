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

    function __construct($tmpFileName, $mimeType, $originalFileName)
    {
        $this->tmpFileName = $tmpFileName;
        $this->mimeType = $mimeType;
        $this->originalFileName = $originalFileName;
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
