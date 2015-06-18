<?php

defined('_JEXEC') or die;

jimport( 'joomla.application.component.controller' );

class MenuModulesControllerMenuModules extends JController
{

    public function display($cachable = false, $urlparams = false)
    {
        parent::display($cachable, $urlparams);
    }

    public function getMenuModules() {
        $this->setJSONReturnHeader();

        $input = new JInput();
        $menuId = $input->get('menuid');

        if (!$menuId) {
            echo $this->errorJSON('Menu id not specified');
            jexit();
        }

        $model = $this->getModel('menumodules');
        $menuModules = $model->getModules($menuId);

        echo json_encode($menuModules);
        jexit();
    }

    public function updateMenuModules() {
        $this->setJSONReturnHeader();

        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput);

        if (!$input || is_string($input)) {
            echo $this->errorJSON('Error parsing request JSON');
            jexit();
        }

        $menuId = $input->menuid;
        $modules = $input->modules;

        if (!$menuId) {
            echo $this->errorJSON('Menu id not specified');
            jexit();
        }

        if (empty($modules)) {
            // nothing to do
            echo json_encode(array("success" => 'true'));
            jexit();
        }

        $model = $this->getModel('menumodules');
        if (!$model->updateMenuModules($menuId, $modules)) {
            echo $this->errorJSON('An error occurred while updating modules');
            jexit();
        }

        echo json_encode(array("success" => 'true'));
        jexit();
    }

    private function setJSONReturnHeader() {
        $document = JFactory::getDocument();
        // Set the MIME type for JSON output.
        $document->setMimeEncoding('application/json');
        header("Content-Type: application/json");
        JRequest::setVar('tmpl','raw');
    }

    private function errorJSON($errormsg = '') {
        return json_encode(array('success' => 'false', 'msg' => $errormsg));
    }

}