<?php
namespace plugins\NovaPoshta\classes\base;

use plugins\NovaPoshta\classes\Area;

/**
 * Class OptionsHelper
 * @package plugins\NovaPoshta\classes\base
 */
class OptionsHelper
{
    /**
     * @param Area[] $locations
     * @param bool $enableEmpty
     * @return array
     */
    public static function getList($locations, $enableEmpty = true)
    {
        $result = array();
        if ($enableEmpty) {
            $result[''] = __('Choose region', NOVA_POSHTA_TTN_DOMAIN);
        }
        foreach ($locations as $location) {
            if ( isset( $location->content->warehouse_type ) ) {
                $result[$location->ref] = $location->description;
            }
            if ( ! isset( $location->content->warehouse_type ) ) {
                $result[$location->ref] = $location->description;
            }
        }
        return $result;
    }

    /**
     * @param Area[] $locations
     * @param bool $enableEmpty
     * @return array
     */
    public static function getListPM($locations, $enableEmpty = true)
    {
        $result = array();
        if ($enableEmpty) {
            $result[''] = __('Choose region', NOVA_POSHTA_TTN_DOMAIN);
        }
        foreach ($locations as $location) {
            if ( isset( $location->content->warehouse_type ) &&
                '2' == $location->content->warehouse_type ) {
                $result[$location->ref] = $location->description;
            }
            if ( ! isset( $location->content->warehouse_type ) ) {
                $result[$location->ref] = $location->description;
            }
        }
        return $result;
    }

}
