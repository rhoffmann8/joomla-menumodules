<?php

defined('_JEXEC') or die;

jimport('joomla.application.component.view');
require_once JPATH_ADMINISTRATOR.'/components/com_menus/helpers/menus.php';

class MenuModulesViewMenuModules extends JView
{
    public function display($tpl = null)
    {
        $this->assetPath = JURI::base().'components/com_menumodules/assets';
        $this->menuTypes = MenusHelper::getMenuLinks();

        JToolBarHelper::title(JText::_('COM_MENUMODULES'));
        JToolBarHelper::preferences('com_menumodules');

        parent::display($tpl);
    }
}