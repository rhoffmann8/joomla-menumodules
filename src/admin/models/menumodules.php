<?php

// no direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.model');
jimport('joomla.application.component.modellist');
jimport('joomla.application.component.helper');

require_once JPATH_ADMINISTRATOR.'/components/com_menus/helpers/menus.php';

class MenuModulesModelMenuModules extends JModelList
{

    // SQL for modules_menu transaction
    private $sql = array(
        'insert' => array(),
        'delete' => array()
    );

    public function __construct($config = array())
    {
        parent::__construct($config);
    }

    public function getModules($menuId)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        // Join on the module-to-menu mapping table.
        $query->select('a.id, a.title, a.position, a.published, map.menuid');
        $case_when = ' (CASE WHEN ';
        $case_when .= 'map2.menuid < 0 THEN map2.menuid ELSE NULL END) as ' . $db->qn('except');
        $case_when .=$query->select( $case_when);
        $query->from('#__modules AS a');
        $query->join('LEFT', '#__modules_menu AS map ON map.moduleid = a.id AND (map.menuid = 0 OR ABS(map.menuid) = '.(int) $menuId.')');
        $query->join('LEFT', '#__modules_menu AS map2 ON map2.moduleid = a.id AND map2.menuid < 0');

        // Join on the asset groups table.
        $query->select('ag.title AS access_title');
        $query->join('LEFT', '#__viewlevels AS ag ON ag.id = a.access');
        $query->where('a.published >= 0');
        $query->where('a.client_id = 0');
        $query->order('a.position, a.ordering');

        $db->setQuery($query);
        $modules = $db->loadObjectList();

        if ($db->getErrorNum()) {
            $this->setError($db->getErrorMsg());
            return false;
        }

        $menuModules = array();

        foreach ($modules as $module) {
            $item = array(
                'id' => $module->id,
                'title' => $module->title,
                'published' => $module->published
            );
            //if except is not null, use the module with "except" corresponding to this menu
            if (is_null($module->menuid)) {
                if ($module->except) {
                    $item['status'] = 'on';
                } else {
                    $item['status'] = 'off';
                }
            } else if ($module->menuid > 0) {
                $item['status'] = 'on';
            } else if ($module->menuid < 0) {
                $item['status'] = 'off';
            } else {
                $item['status'] = 'all';
            }

            $menuModules[$module->id] = $item;
        }

        // sort alpha
        usort($menuModules, function($a,$b) {
            return strtolower($a['title']) > strtolower($b['title']);
        });

        return $menuModules;
    }

    public function updateMenuModules($menuId, $modules)
    {
        if (empty($modules)) {
            return false;
        }

        $preferSelected = JComponentHelper::getParams('com_menumodules')->get('prefer_selected');

        $moduleIds = array_map(function($a) {
            return $a->id;
        }, $modules);

        // get modules to be changed
        $db     = JFactory::getDbo();
        $query  = $db->getQuery(true);
        $query->select('*');
        $query->from('#__modules_menu');
        $query->where('moduleid IN ('.implode(',', $moduleIds).')');
        $db->setQuery((string)$query);

        // if not an error but no rows are returned, then this module is assigned to no pages
        if (($result = $db->loadAssocList()) === null) {
            $this->setError($db->getErrorMsg());
            return false;
        }

        $menuIds = array();

        foreach($modules as $module) {
            // if the menuid is - then it's except
            // if the menuid is + then it's selected
            // if the menuid is 0 then it's all
            // if the menuid is not present for a specific module then the module either is set to none or
            //     is a selected/except that does not include this menu

            // get all the existing entries with this module id
            $tmp = array();
            foreach ($result as $row) {
                if (abs((int)$row['moduleid']) == (int)$module->id) {
                    array_push($tmp, $row);
                }
            }

            // group menuids by module
            // $menuIds[moduleid] => [menuid1, menuid2, ...]
            $menuIds[$module->id] = array_map(function($row) {
                return $row['menuid'];
            }, $tmp);

            // if module is changed to all, delete all other entries and insert with menuid 0
            if ($module->status == 'all') {
                foreach ($menuIds[$module->id] as $id) {
                    $this->addSql('delete', $id, $module->id);
                }
                $this->addSql('insert', 0, $module->id);
                continue;
            }

            if (empty($menuIds[$module->id])) {
                // none
                if ($module->status == 'on') $this->addSql('insert', $menuId, $module->id);
            } else if ($menuIds[$module->id][0] > 0) {
                // selected
                if ($module->status == 'on') {
                    if (!in_array($menuId, $menuIds[$module->id])) {
                        $this->addSql('insert', $menuId, $module->id);
                    }
                } else {
                    if (in_array($menuId, $menuIds[$module->id])) {
                        $this->addSql('delete', $menuId, $module->id);
                    }
                }
            } else if ($menuIds[$module->id][0] < 0) {
                // except
                if ($module->status == 'on') {
                    if (in_array(-$menuId, $menuIds[$module->id])) {
                        $this->addSql('delete', -$menuId, $module->id);

                        // if this would remove all menus from the array and this switch is on,
                        // then an "all" entry must be written to db
                        $filtered = array_filter($menuIds[$module->id], function($item) use($menuId) {
                            return abs($item) != abs($menuId);
                        });
                        if (empty($filtered)) {
                            $this->addSql('insert', 0, $module->id);
                        }
                    }
                } else {
                    if (!in_array($menuId, $menuIds[$module->id])) {
                        $this->addSql('insert', -$menuId, $module->id);
                    }
                }
            } else {
                // all
                if ($module->status == 'off') {
                    $this->addSql('delete', 0, $module->id);
                    if ($preferSelected) {
                        $menuTypes = MenusHelper::getMenuLinks();
                        foreach ($menuTypes as $menuType) {
                            foreach ($menuType->links as $link) {
                                if ($link->value == $menuId) continue;
                                $this->addSql('insert', $link->value, $module->id);
                            }
                        }
                    } else {
                        $this->addSql('insert', -$menuId, $module->id);
                    }
                }
            }
        }

        if (empty($this->sql['insert']) && empty($this->sql['delete'])) {
            // nothing to do
            return true;
        }

        $db->transactionStart();

        if (!empty($this->sql['insert'])) {
            $query->clear();
            $query->insert('#__modules_menu');
            $query->columns(array($db->quoteName('moduleid'), $db->quoteName('menuid')));
            foreach ($this->sql['insert'] as $insert) {
                $query->values((int)$insert['moduleid'] . ', ' . (int)$insert['menuid']);
            }
            $db->setQuery((string)$query);
            if (!$db->query()) {
                $this->setError($db->getErrorMsg());
                $db->transactionRollback();
                return false;
            }
        }

        if (!empty($this->sql['delete'])) {
            $query->clear();
            $query->delete('#__modules_menu');
            foreach ($this->sql['delete'] as $delete) {
                $query->where('('.$db->quoteName('moduleid').'='.(int)$delete['moduleid'].' AND '.$db->quoteName('menuid').'='.(int)$delete['menuid'].')', 'OR');
            }
            $db->setQuery((string)$query);
            if (!$db->query()) {
                $this->setError($db->getErrorMsg());
                $db->transactionRollback();
                return false;
            }
        }

        $db->transactionCommit();

        return true;
    }

    private function addSql($action, $menuId, $moduleId) {
        $this->sql[$action][] = array(
            'menuid' => $menuId,
            'moduleid' => $moduleId
        );
    }

}