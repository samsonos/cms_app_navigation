<?php 
namespace samson\cms\web\navigation;

use samson\cms\CMS;

/**
 * SamsonCMS Structure application module
 * Mainly interacts with "structure", "structurefield" database tables
 * @author Vitaly Egorov <egorov@samsonos.com>
 */

class StructureApplication extends \samson\cms\App
{
    /** Application name */
    public $name = 'Структура';

    /** Application icon */
    public $icon = 'fa-cubes';

    /** Identifier */
    protected $id = 'structure';

    /** Dependencies */
    protected $requirements = array('activerecord', 'cmsapi');

    /** @see \samson\cms\App::main() */
    public function main()
    {
        // Get new 5 structures
        $query = dbQuery('samson\cms\CMSNav')
                ->join('user')
                ->Active(1)
                ->order_by('Created', 'DESC')
                ->limit(5);
        if ($query->exec($db_navs)) {
            // Render material rows
            $rows_html = '';
            foreach ($db_navs as $db_nav) {
                if (isset($db_nav->onetoone['_user'])) {
                    $rows_html .= $this->view('main/row')
                        ->nav($db_nav)
                        ->user($db_nav->onetoone['_user'])
                        ->output();
                } else {
                    $rows_html .= $this->view('main/row')
                        ->nav($db_nav)
                        ->output();
                }
            }

            // Add empty rows if needs
            for ($i = sizeof($db_navs); $i < 5; $i++) {
                $rows_html .= $this->view('main/row')->output();
            }
            // Render main template
            return $this->view('main/index')->rows($rows_html)->output();
        }
    }

    /**
     * Default controller
     */
    public function __HANDLER()
    {
        $parent = CMSNav::fullTree();
        $tree = new \samson\treeview\SamsonTree('tree/tree-template', 0, 'structure/addchildren');
        // Установим дерево ЭСС
        m()->view('index')
            ->title(t('Элементы структуры содержания сайта', true))
            ->tree($tree->htmlTree($parent));
    }

    public function __creatematerial($navID)
    {
        /** @var \samson\cms\web\navigation\CMSNav $navigation */
        $navigation = null;
        if (dbQuery('\samson\cms\web\navigation\CMSNav')->id($navID)->first($navigation)) {
            $material = $navigation->createMaterial();
            $navigation->MaterialID = $material;
            $navigation->save();
            url()->redirect('material/form/'.$material);
        } else {
            url()->redirect('structure');
        }
    }

    /**
     * Controller for showing tree
     * @return array Ajax response
     */
    public function __async_showall() {
        $parent = CMSNav::fullTree();
        $tree = new \samson\treeview\SamsonTree('tree/tree-template', 0, 'structure/addchildren');
        $html = m()->view('index')
            ->title(t('Элементы структуры содержания сайта', true))
            ->tree($tree->htmlTree($parent))
            ->output();
        return array(
            'status'=>1,
            'tree'=>$html
        );
    }

    /**
     * Opening form for editing or creating new structure
     * @param int $parentID Identifier of parent structure for tag select
     * @param int $navID Current structure identifier for editing
     *
     * @return array Ajax response
     */
    public function __async_form($parentID = 0, $navID = 0)
    {
        /** @var CMSNav $data */
        $data = null;

        if (dbQuery('\samson\cms\web\navigation\CMSNav')->StructureID($navID)->first($data)) {
            if (dbQuery('\samson\cms\CMSMaterial')->id($data->MaterialID)->first($mat)) {
                m()->cmsmaterial($mat);
            }
            $parent_id = $parentID;
        } else {
            $parent_id = 0;
            if (dbQuery('\samson\cms\web\navigation\CMSNav')->id($parentID)->first($nav)) {
                if (isset($nav->parent()->id)) {
                    $parent_id = $nav->parent()->id;
                }
            }
        }

        // Render form
        $html = m()->view('form/form')
            ->parent_select(CMSNav::createSelect($parentID))
            ->cmsnav($data)
            ->parent_id($parent_id)
            ->output();

        return array(
            'status'=>1,
            'html'=>$html
        );
    }

    /**
     * Построение нового дерева. (Вызывается в цепи контроллеров)
     * @param null $currentMainNavID - указатель на родителя
     *
     * @return array
     */
    public function __async_tree($currentMainNavID = null)
    {
        /** @var CMSNav $currentMainNav */
        $currentMainNav = null;
        if (dbQuery('\samson\cms\web\navigation\CMSNav')->StructureID($currentMainNavID)->first($currentMainNav)) {
            $currentMainNav->currentNavID = $currentMainNavID;
        } else {
            $currentMainNav = CMSNav::fullTree();
        }

        $tree = new \samson\treeview\SamsonTree('tree/tree-template', 0, 'structure/addchildren');

        $sub_menu = m()->view('main/sub_menu')->parentnav_id($currentMainNavID)->nav_id($currentMainNavID)->output();

        // return Ajax response
        return array(
            'status' => 1,
            'tree' => $tree->htmlTree($currentMainNav),
            'sub_menu' => $sub_menu
        );
    }

    /**
     * Saving or creating new structure
     * @param int $navID structure identifier
     * @return array Ajax response
     */
    public function __async_save($navID = 0)
    {
        /** @var \samson\cms\web\navigation\CMSNav $data */
        $data = null;

        if (dbQuery('\samson\cms\web\navigation\CMSNav')->StructureID($navID)->first($data)) {
            // Update structure data
            $data->update();
        } else {
            // Create new structure
            $nav = new \samson\cms\web\navigation\CMSNav(false);
            $nav->Created = date('Y-m-d H:m:s');
            $nav->fillFields();
        }

        // return Ajax response
        return array('status'=>1);
    }

    /**
     * @param int $navID - идентификатор структуры
     *
     * удаление выбранной структуры из таблицы
     *
     * @return array
     */
    public function __async_delete($navID = 0)
    {
        $data = null;
        $response = array ('status'=>0);

        if (dbQuery('\samson\cms\web\navigation\CMSNav')->StructureID($navID)->first($data)) {
            $data->delete(); //удаляем структуру
            $response['status'] = 1;
        }

        return $response;
    }

    /**
     * Изменение порядкового номера структуры в дереве
     *
     * @param int $navID селектор-ИД структуры
     * @param     $direction - вид изменения
     *
     * @return array - AJAX - response
     */
    public function __async_priority($navID = 0, $direction)
    {
        /** @var CMSNav $data */
        $data = null;
        $response = array ('status'=>0);

        if (dbQuery('\samson\cms\web\navigation\CMSNav')->id($navID)->first($data)) {
            $data->priority($direction);
            $response['status'] = 1;
        }

        return $response;
    }

    /**
     * @param null $structure_id Current structure identifier
     *
     * @return array Ajax response
     */
    public function __async_showtree($structure_id = null)
    {
        $db_structure = null;
        // Проверим есть ли элемент структуры с таким ИД
        if (dbQuery('\samson\cms\web\navigation\CMSNav')->StructureID($structure_id)->first($db_structure)) {
            $db_structure->currentNavID = $structure_id;
        }

        $tree = new \samson\treeview\SamsonTree('tree/tree-template', 0, 'structure/addchildren');

        $html = m()->view('index')
            ->title(t('Элементы структуры содержания сайта', true))
            ->parent($db_structure)
            ->tree($tree->htmlTree($db_structure))
            ->output();

        return array(
            'status'=>1,
            'tree'=>$html
        );
    }

    public function __async_addchildren($structure_id)
    {
        if (dbQuery('\samson\cms\web\navigation\CMSNav')->StructureID($structure_id)->first($db_structure)) {
            $tree = new \samson\treeview\SamsonTree('tree/tree-template', 0, 'structure/addchildren');
            return array('status' => 1, 'tree' => $tree->htmlTree($db_structure));
        }

        return array('status' => 0);
    }

    /**
     * Render sub menu of this app
     * @param int $structure_id Parent structure identifier
     *
     * @return array Ajax response
     */
    public function __async_rendermenu($structure_id = 0)
    {
        $sub_menu = m()->view('main/sub_menu')->parentnav_id($structure_id)->nav_id($structure_id)->output();

        return array(
            'status'=>1,
            'sub_menu' => $sub_menu
        );
    }
}
