<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/** 
* @copyright Copyright (c) 2002 Alan Pinstein. All Rights Reserved.
* @version $Id: smarty_showcase.php,v 1.3 2005/02/01 01:24:37 alanpinstein Exp $
* @author Alan Pinstein <apinstein@mac.com>                        
* @package UI
* @subpackage Template
*/

/**
* OVERLOAD SMARTY TEMPLATE ENGINE INIT
*/
class WFSmarty extends Smarty implements WFPageRendering
{
    protected $template;

    function __construct()
    {
        parent::Smarty();

        $this->template = NULL;     // by default, no template!

        // smarty stuff
        // runtime directories
        $this->compile_dir = WFWebApplication::appDirPath(WFWebApplication::DIR_RUNTIME) . '/smarty/templates_c/';
        $this->cache_dir = WFWebApplication::appDirPath(WFWebApplication::DIR_RUNTIME) . '/smarty/cache/';

        // default directories
        $this->template_dir = WFWebApplication::appDirPath(WFWebApplication::DIR_SMARTY) . '/templates/';
        $this->config_dir = WFWebApplication::appDirPath(WFWebApplication::DIR_SMARTY) . '/configs/';
        // add a local plugins dir
        array_push($this->plugins_dir, FRAMEWORK_DIR . '/smarty/plugins/');

        // GLOBAL variables for all smarty templates
        $this->assign('WWW_ROOT', WWW_ROOT);
    }

    function setTemplate($template)
    {
        $this->template = $template;
    }

    function template()
    {
        return $this->template;
    }

    function assign($name, $value)
    {
        parent::assign($name, $value);
    }

    function get($name)
    {
        return $this->get_template_vars($name);
    }

    function render($display = true)
    {
        if (!file_exists($this->template)) throw( new WFRequestController_NotFoundException("Template file '{$this->template}' could not be read.") );
        if ($display) {
            parent::display($this->template);
            return NULL;
        } else {
            return parent::fetch($this->template);
        }
    }

    function getPage()
    {
        return $this->get_template_vars('__page');
    }

    function getCurrentWidget($params)
    {
        if (empty($params['id'])) throw(new Exception("id required."));

        $page = $this->getPage();
        return $page->outlet($params['id']);
    }
}

?>
