<?php

namespace plugins\NovaPoshta\classes\repository;

use plugins\NovaPoshta\classes\Area;
use plugins\NovaPoshta\classes\base\ArrayHelper;
use plugins\NovaPoshta\classes\base\Base;
use plugins\NovaPoshta\classes\base\OptionsHelper;

/**
 * Class AreaRepository
 * @property string areaClass
 * @package plugins\NovaPoshta\classes
 */
abstract class AbstractAreaRepository extends Base
{

    /**
     * @return string
     */
    abstract public function table();

    /**
     * @return string
     */
    abstract protected function getAreaClass();

    /**
     * Ajax requests entry point
     */
    public function ajaxGetAreasByNameSuggestion()
    {
        // Get city names by region name and warehouse names by city name
        $areaRef = ArrayHelper::getValue($_POST, 'parent_ref', null);
        $name = ArrayHelper::getValue($_POST, 'name', null);
        $shipping_method = ArrayHelper::getValue($_POST, 'npchosenmethod', null);
        $table = $this->table();

        if(isset(WC()->session->get( 'chosen_shipping_methods' )[0]) && 
            'nova_poshta_shipping_method_poshtomat' == WC()->session->get( 'chosen_shipping_methods' )[0] && 
            str_contains($table, 'nova_poshta_city')){
            $areas = $this->findByParentRefAndNameSuggestionCityPoshtomat($areaRef, $name);
        }
        else{
            $areas = $this->findByParentRefAndNameSuggestion($areaRef, $name);
        }

        $result = OptionsHelper::getList($areas);
        if ( isset ($shipping_method) ) {
            if ( 'warehouse' == $shipping_method ) $result = OptionsHelper::getList($areas);
            if ( 'poshtomat' == $shipping_method ) $result = OptionsHelper::getListPM($areas);
        }
        natsort($result);
        echo json_encode($result);
        exit;
    }

    /**
     * @return Area[]
     */
    public function findAll()
    {
        return $this->findByParentRefAndNameSuggestion(null, null);
    }

    /**
     * @param string|null $parentRef
     * @param string|null $name
     * @return Area[]
     */
    public function findByParentRefAndNameSuggestionCityPoshtomat($parentRef = null, $name = null)
    {
        $searchCriteria = [];
        $searchCriteria[] = '(1=1)';

        global $wpdb;

        $table_prefix = $wpdb->prefix;

        if ($parentRef !== null) {
            NPttn()->db->escape_by_ref($parentRef);
            $searchCriteria[] = sprintf("(" . $table_prefix . "nova_poshta_city.parent_ref = '%s')", $parentRef);
        }
        if ($name !== null) {
            NPttn()->db->escape_by_ref($name);
            $searchCriteria[] =sprintf("(" . $table_prefix . "nova_poshta_city.description LIKE CONCAT('%s', '%%') OR " . $table_prefix . "nova_poshta_city.description_ru LIKE CONCAT('%s', '%%'))", $name, $name);
        }

        $searchCriteria[] = '(' . $table_prefix . 'nova_poshta_warehouse.warehouse_type = 2)';

        $table = $this->table();

        $table_poshtomat_city = $table . ' LEFT JOIN `' . $table_prefix . 'nova_poshta_warehouse` ON ' . $table_prefix . 'nova_poshta_city.ref = ' . $table_prefix . 'nova_poshta_warehouse.parent_ref';

        $query = "SELECT " . $table_prefix . "nova_poshta_city.ref, " . $table_prefix . "nova_poshta_city.description, " . $table_prefix . "nova_poshta_city.description_ru, " . $table_prefix . "nova_poshta_city.parent_ref, " . $table_prefix . "nova_poshta_city.updated_at FROM $table_poshtomat_city WHERE " . implode(' AND ', $searchCriteria) . " GROUP BY " . $table_prefix . "nova_poshta_city.ref";



        return $this->findByQuery($query);
    }

    /**
     * @param string|null $parentRef
     * @param string|null $name
     * @return Area[]
     */
    public function findByParentRefAndNameSuggestion($parentRef = null, $name = null)
    {
        $searchCriteria = [];
        $searchCriteria[] = '(1=1)';
        if ($parentRef !== null) {
            $searchCriteria[] = $this->getParentRefSearchCriteria($parentRef);
        }
        if ($name !== null) {
            $searchCriteria[] = $this->getNameSearchCriteria($name);
        }
        $table = $this->table();
        $query = "SELECT * FROM $table WHERE " . implode(' AND ', $searchCriteria);
        return $this->findByQuery($query);
    }

    /**
     * @param string $query
     * @return Area[]
     */
    public function findByQuery($query)
    {
        $class = $this->areaClass;
        $result = NPttn()->db->get_results($query);
        return array_map(function ($location) use ($class) {
            return new $class($location);
        }, $result);
    }

    /**
     * @param string $parentRef
     * @return string
     */
    protected function getParentRefSearchCriteria($parentRef)
    {
        NPttn()->db->escape_by_ref($parentRef);
        return sprintf("(`parent_ref` = '%s')", $parentRef);
    }

    /**
     * @param string $name
     * @return string
     */
    protected function getNameSearchCriteria($name)
    {
        NPttn()->db->escape_by_ref($name);
        return sprintf("(`description` LIKE CONCAT('%s', '%%') OR `description_ru` LIKE CONCAT('%s', '%%'))", $name, $name);
    }

}
