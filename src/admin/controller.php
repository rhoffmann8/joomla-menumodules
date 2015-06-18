<?php

// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.controller');

class MenuModulesController extends JController
{
    
    public function display($cachable = false, $urlparams = false)
    {
        $view   = JRequest::getCmd('view', 'menumodules');
        $layout = JRequest::getCmd('layout', 'default');
        $id     = JRequest::getInt('id');

        parent::display();

        return $this;
    }
}