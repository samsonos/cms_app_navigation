<?php
/**
 * Created by PhpStorm.
 * User: p.onysko
 * Date: 03.03.14
 * Time: 13:46
 */
namespace samson\cms\web\navigation;


use samson\activerecord\dbRelation;

class CMSNav extends \samson\cms\CMSNav
{
    public $currentNavID = 0;

    /**
     * Help method for sorting structures
     * @param $str1 \samson\cms\CMSNav
     * @param $str2 \samson\cms\CMSNav
     *
     * @return bool
     */
    public static function strSorting($str1, $str2)
    {
        return $str1['PriorityNumber']>$str2['PriorityNumber'];
    }

    /**
     * Create select tag with selected parent
     * @param int $parentID selected structure identifier
     *
     * @return string html view for select
     */
    public static function createSelect($parentID = 0)
    {
        $select = '';

        $data = null;

        if (dbQuery(__CLASS__)->StructureID($parentID)->first($data)) {
            $select .= '<option title="'.$data->Name.'" selected value="'.$data->id.'">'.$data->Name.'</option>';
        } else {
            $select .= '<option title="' . t('Не выбрано', true) . '" value="NULL">'
                . t('Не выбрано', true) . '</option>';
        }

        if (dbQuery(__CLASS__)->Active(1)->exec($allNavs)) {
            foreach ($allNavs as $nav) {
                $select .= '<option title="'.$nav->Name.'" value="'.$nav->id.'">'.$nav->Name.'</option>';
            }
        }
        return $select;
    }

    /**
     * Fet list of additional fields of current structure
     * @return string Html view of list
     */
    public function getFieldList()
    {
        // Get additional fields of current structure
        $fields = dbQuery('\samson\cms\web\field\CMSField')
            ->join('\samson\cms\CMSNavField')
            ->cond('StructureID', $this->id)
            ->exec();

        // Create list view
        $items = '';

        // If structure has additional fields then add them to list
        if (sizeof($fields)) {
            foreach ($fields as $field) {
                $items .= m('structure')->view('form/field/field_item')->field($field)->structure($this)->output();
            }
        } else {
            // Add empty row
            $items = m('structure')->view('form/field/empty_field')->output();
        }

        // Return items view
        return $items;
    }

    /**
     * Filling the fields and creating relation of structure
     */
    public function fillFields()
    {
        // Fill the fields from $_POST array
        foreach ($_POST as $key => $val) {
            
            // Get int value form field parent id
            if ($key == 'ParentID' && $val == 0) {
                
                $this[$key] = null;
                
                // Get other fields
            } elseif ($key != 'StructureID') {
                
                $this[$key] = $val;
            }
        }

        // Save data about application
        $this['applicationGenerate'] = isset($_POST['generate-application'])&&($_POST['generate-application'] == true) ? 1 : 0;
        $this['applicationOutput'] = isset($_POST['output-application'])&&($_POST['output-application'] == true) ? 1 : 0;
        $this['applicationOutputStructure'] = isset($_POST['output-structure-application'])&&($_POST['output-structure-application'] == true) ? 1 : 0;
        $this['applicationRenderMain'] = isset($_POST['render-main-application'])&&($_POST['render-main-application'] == true) ? 1 : 0;

        // If application need to generate then set system property to 1 or 0
        $this['system'] = isset($_POST['generate-application'])&&($_POST['generate-application'] == true) ? 1: 0;

        // Save icon
        $icon = isset($_POST['icon-application']) ? filter_var($_POST['icon-application']) : null;
        if (strlen($icon) > 0) {
            $this['applicationIcon'] = $icon;
        }

        // Save object
        $this->save();

        if (isset($_POST['ParentID']) && $_POST['ParentID'] != 0) {
            // Create new relation
            $strRelation = new \samson\activerecord\structure_relation(false);
            $strRelation->parent_id = $_POST['ParentID'];
            $strRelation->child_id = $this->id;

            // Save relation
            $strRelation->save();
        }
    }

    /**
     * Updating structure
     */
    public function update()
    {
        /** @var array $relations array of structure_relation objects */
        $relations = null;

        // If CMSNav has old relations then delete it
        if (dbQuery('\samson\activerecord\structure_relation')->child_id($this->id)->exec($relations)) {
            /** @var \samson\activerecord\structure_relation $relation */
            foreach ($relations as $relation) {
                $relation->delete();
            }
        }

        // Update new fields
        $this->fillFields();
    }

    public static function fullTree(CMSNav & $parent = null, $recursion = 1)
    {
        $html = '';

        if (!isset($parent)) {
            $parent = new CMSNav(false);
            $parent->Name = 'Корень навигации';
            $parent->Url = 'NAVIGATION_BASE';
            $parent->StructureID = 0;
            $parent->base = 1;
            $db_navs = null;
            if (dbQuery(__CLASS__)
                ->Active(1)
                ->join('parents_relations')
                ->cond('parents_relations.parent_id', '', dbRelation::ISNULL)
                ->exec($db_navs)) {
                foreach ($db_navs as $db_nav) {
                    $parent->children['id_'.$db_nav->id] = $db_nav;
                }
            }
        }

        //$htmlTree = $parent->htmlTree($parent, $html, 'tree/tree-template', 0, $parent->currentNavID, $recursion);

        return $parent;
    }

    /**
     * @param CMSNav $parent
     * @param string $html
     * @param null $view
     * @param int $level
     * @param int $currentNavID
     * @return string
     */
    public function htmlTree(CMSNav & $parent = null, & $html = '', $view = null, $level = 0, $currentNavID = 0, $recursion = 1)
    {
        /** Collection of visited structures to avoid recursion */
        static $visited = array();

        // If no parent structure is passed
        if (!isset($parent)) {
            // Use current object
            $parent = & $this;
        }

        // If we have not visited this structure yet
        if (!isset($visited[$parent->id])) {
            // Store it as visited
            $visited[$parent->id] = $parent->Name;

            // Get structure children
            // TODO: What is the difference?
            if ($parent->base) {
                $children = $parent->children();
            } else {
                $children = $parent->baseChildren();
            }

            // If we have children collection for this node
            if (sizeof($children)) {

                // Sort children
                usort($children, array(__CLASS__, 'strSorting'));

                // Start html list
                $html .= '<ul>';

                // Iterate all children
                foreach ($children as $child) {
                    // If external view is set
                    if (isset($view)) {
                        if (!$recursion && sizeof($child->children())) {
                            // Start HTML list item and render this view
                            $html .= '<li class="hasChildren">';
                        } else {
                            $html .= '<li>';
                        }
                        // Start HTML list item and render this view
                        $html .= m()->view($view)
                                ->parentid($parent->id)
                                ->nav_id($currentNavID)
                                ->db_structure($child)
                                ->output();
                    } else { // Render just structure name
                        $html .= '<li>' . $child->Name;
                    }

                    if ($recursion) {
                        // Go deeper in recursion
                        $parent->htmlTree($child, $html, $view, $level++, $currentNavID, $recursion);
                    }

                    // Close HTML list item
                    $html .= '</li>';
                }

                // Close HTML list
                $html .= '</ul>';
            }
        }

        return $html;
    }

    public function priority($direction = NULL)
    {
        $parent = $this->parent();
        if (isset($parent))
        {
            $p_index = 0;

            foreach ( $parent as $id => $child )
            {
                if( $child->PriorityNumber != $p_index)
                {
                    $child->PriorityNumber = $p_index;

                    $child->save();
                }

                $p_index++;
            }

            $children = array_values($parent->children());

            $old_index = $this->PriorityNumber;

            $new_index =  $old_index - $direction;
            $new_index = $new_index == sizeof($children) ? 0 : $new_index;
            $new_index = $new_index == -1 ? sizeof($children) - 1 : $new_index;


            if( isset($children[ $new_index ] ) )
            {
                $second_nav = $children[ $new_index ];
                $second_nav->PriorityNumber = $old_index;
                $second_nav->save();

                $this->PriorityNumber = $new_index;
                $this->save();
            }
        }
    }

    public function createMaterial()
    {
        $material = new \samson\activerecord\material(false);
        $material->Name = $this->Name.' - material';
        $material->Active = 1;
        $material->Url = generate_password(10);
        $material->Published = 0;
        $material->save();

        /** @var CMSNav $seoNav */
        $seoNav = null;

        if (dbQuery('structure')->cond('Url', '__seo')->first($seoNav)) {
            $strmat = new \samson\activerecord\structurematerial(false);
            $strmat->MaterialID = $material->id;
            $strmat->StructureID = $seoNav->id;
            $strmat->Active = 1;
            $strmat->save();
        }

        return $material->id;
    }
}
