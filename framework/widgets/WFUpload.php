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
 * Includes
 */
require_once('framework/widgets/WFWidget.php');

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

        if (isset($_FILES[$this->name]) and $_FILES[$this->name]['error'] == 0)
        {
            $this->hasUpload = true;
            $this->tmpFileName = $_FILES[$this->name]['tmp_name'];
            $this->originalFileName = $_FILES[$this->name]['name'];
            $this->mimeType = $_FILES[$this->name]['type'];
        }
    }

    function render($blockContent = NULL)
    {
        return '<input type="file" name="' . $this->name . '" />';
    }

    function canPushValueBinding() { return false; }
}

?>
