<?php

namespace plugins\NovaPoshta\classes;

use plugins\NovaPoshta\classes\base\DatabaseSync as BaseDatabaseSync;
use plugins\NovaPoshta\classes\repository\AreaRepositoryFactory;

/**
 * Class DatabaseSync
 * @package plugins\NovaPoshta\classes
 */
class DatabaseSync extends BaseDatabaseSync
{

    /**
     * @var DatabaseSync
     */
    private static $_instance;

    /**
     * @return DatabaseSync
     */
    public static function instance()
    {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Synchronize Nova Poshta areas, cities and warehouses
     * Synchronization every $this->interval but before insert
     * data to table check location has in order to identify any
     * changes so insertion will appear on in case of hash mismatch
     */
    public function synchroniseLocations()
    {
error_log('synchroniseLocations()');
        ob_start();
        $this->db->query('START TRANSACTION');
        try {
            $this->updateRegions();
echo 'updateRegions()';
            // $this->updateCities();

            // $this->updateWarehouses();

            // $this->updatePoshtomats();

            $this->setLocationsLastUpdateDate($this->updatedAt);
            if (!$this->db->last_error) {
                $this->db->query('COMMIT');
            } else {
                $this->addError('Synchronization failed. Rollback.');
                $this->db->query('ROLLBACK');
            }
        } catch (\Exception $e) {
            $this->addError($e->getMessage());
            //$this->log->error('Synchronization failed. ' . $e->getMessage(), Log::LOCATIONS_UPDATE);
            $this->db->query('ROLLBACK');
        }
         ob_end_clean() ;

        //$this->log->info("", Log::LOCATIONS_UPDATE);
    }

    public function synchroniseRegions()
    {
        ob_start();
        $this->updateRegions();
        ob_end_clean();
    }

    public function synchroniseCities()
    {
        ob_start();
        $this->updateCities();
        ob_end_clean();
    }

    public function synchroniseWarehouses()
    {
        ob_start();
        $this->updateWarehouses();
        ob_end_clean();
    }

    public function synchronisePostomats()
    {
        ob_start();
        $this->updatePoshtomats();
        ob_end_clean();
    }

    /**
     * Update content of table nova_poshta_area
     */
    private function updateRegions()
    {
        $table = AreaRepositoryFactory::instance()->regionRepo()->table();
        $areas = NPttn()->api->getAreas();
        $updatedAt = $this->updatedAt;
        $insert = array();
        foreach ($areas as $area) {
            $insert[] = $this->db->prepare(
                "('%s', '%s', '%s', %d)",
                $area['Ref'],
                $area['Description'],
                $this->getDescriptionRu($area),
                $updatedAt
            );
        }
        $queryInsert = "INSERT INTO $table (`ref`, `description`, `description_ru`, `updated_at`) VALUES ";
        $queryInsert .= implode(',', $insert);
        $queryInsert .= ' ON DUPLICATE KEY UPDATE
            `ref` = VALUES(`ref`),
            `description` = VALUES(`description`),
            `description_ru` = VALUES(`description_ru`),
            `updated_at` = VALUES(`updated_at`)';

        $queryDelete = $this->db->prepare("DELETE FROM $table WHERE `updated_at` < %d", $updatedAt);

        $rowsAffected = $this->db->query($queryInsert);
        $rowsDeleted = $this->db->query($queryDelete);
        $this->log->info("Areas were successfully updated, affected $rowsAffected rows, deleted $rowsDeleted rows", Log::LOCATIONS_UPDATE);
    }

    /**
     * Update content of table nova_poshta_city
     */
    private function updateCities()
    {
        $table = AreaRepositoryFactory::instance()->cityRepo()->table();
        echo '<script>console.log("table")</script>';
        $updatedAt = $this->updatedAt;
        $page = 1;
        $limit = 300;
        $rowsAffected = 0;
        while (count( (array) $cities = NPttn()->api->getCities($page++, $limit)) > 0) {
          //echo '<script>console.log("'.$cities[$page]['Description'].'")</script>';
            $rowsAffected += $this->saveCitiesPage($cities);
        }

        $queryDelete = $this->db->prepare("DELETE FROM $table WHERE `updated_at` < %d", $updatedAt);
        $rowsDeleted = $this->db->query($queryDelete);

        $this->log->info("Cities were successfully updated, affected $rowsAffected rows, deleted $rowsDeleted rows", Log::LOCATIONS_UPDATE);
    }

    /**
     * @param array $cities
     * @return int
     */
    private function saveCitiesPage($cities)
    {
        $table = AreaRepositoryFactory::instance()->cityRepo()->table();
        $updatedAt = $this->updatedAt;
        $insert = array();
        foreach ($cities as $city) {
            $insert[] = $this->db->prepare(
                "('%s', '%s', '%s', '%s', %d)",
                $city['Ref'],
                $city['Description'],
                $city['DescriptionRu'],
                $city['Area'],
                $updatedAt
            );
        }
        $queryInsert = "INSERT INTO $table (`ref`, `description`, `description_ru`, `parent_ref`, `updated_at`) VALUES ";
        $queryInsert .= implode(",", $insert);
        $queryInsert .= ' ON DUPLICATE KEY UPDATE
            `ref` = VALUES(`ref`),
            `description` = VALUES(`description`),
            `description_ru`=VALUES(`description_ru`),
            `parent_ref`=VALUES(`parent_ref`),
            `updated_at` = VALUES(`updated_at`)';
        return $this->db->query($queryInsert);
    }

    /**
     * Update content of table nova_poshta_warehouse
     */
    private function updateWarehouses()
    {
        $table = AreaRepositoryFactory::instance()->warehouseRepo()->table();
        $updatedAt = $this->updatedAt;
        $rowsAffected = 0;
        $page = 1;
        // $limit = 300;
        $limit = 500;
        while (count( (array) $warehouses = NPttn()->api->getWarehouses(null, $page++, $limit)) > 0) {
            $rowsAffected += $this->updateWarehousesPage($warehouses);
        }

        $queryDelete = $this->db->prepare("DELETE FROM $table WHERE `updated_at` < %d", $updatedAt);

        $rowsDeleted = $this->db->query($queryDelete);
        $this->log->info("Warehouses were successfully updated, affected $rowsAffected rows, deleted $rowsDeleted rows", Log::LOCATIONS_UPDATE);
    }

    /**
     * @param array $warehouses
     * @return int
     */
    private function updateWarehousesPage($warehouses)
    {
        $table = AreaRepositoryFactory::instance()->warehouseRepo()->table();
        $updatedAt = $this->updatedAt;
        $insert = array();
        foreach ($warehouses as $warehouse) {
            $warehouse['warehouse_type'] = $this->getWarehouseType($warehouse);
            $insert[] = $this->db->prepare(
                "('%s', '%s', '%s', '%s',%d, %d)",
                $warehouse['Ref'],
                $warehouse['Description'],
                $warehouse['DescriptionRu'],
                $warehouse['CityRef'],
                $warehouse['warehouse_type'],
                $updatedAt
            );
        }
        $queryInsert = "INSERT INTO $table (`ref`, `description`, `description_ru`, `parent_ref`, `warehouse_type`, `updated_at`) VALUES ";
        $queryInsert .= implode(",", $insert);
        $queryInsert .= ' ON DUPLICATE KEY UPDATE
            `ref` = VALUES(`ref`),
            `description` = VALUES(`description`),
            `description_ru`=VALUES(`description_ru`),
            `parent_ref`=VALUES(`parent_ref`),
            `warehouse_type`=VALUES(`warehouse_type`),
            `updated_at` = VALUES(`updated_at`)';
        return $this->db->query($queryInsert);

    }

    private function getWarehouseType(array $warehouse): int
    {
        if ($warehouse['TypeOfWarehouse'] === '9a68df70-0267-42a8-bb5c-37f427e36ee4') {
            return 3; // Вантажне відділення
        }

        if ( strpos($warehouse['Description'], 'Поштомат') !== false) {
            return 2; // Поштомат
        }

        return 1; // Поштове відділення або Parcel Shop (Пункт Видачі)
    }

    /**
     * Update content of table nova_poshta_poshtomat
     */
    private function updatePoshtomats()
    {
        $table = AreaRepositoryFactory::instance()->poshtomatRepo()->table();
        $updatedAt = $this->updatedAt;
        $rowsAffected = 0;
        $page = 1;
        $limit = 500;
        while (count( (array) $poshtomats = NPttnPM()->api->getPoshtomats(null, $page++, $limit)) > 0) {
            $rowsAffected += $this->updatePoshtomatsPage($poshtomats);
        }

        $queryDelete = $this->db->prepare("DELETE FROM $table WHERE `updated_at` < %d", $updatedAt);

        $rowsDeleted = $this->db->query($queryDelete);
        $this->log->info("Poshtomats were successfully updated, affected $rowsAffected rows, deleted $rowsDeleted rows", Log::LOCATIONS_UPDATE);
    }

    /**
     * @param array $warehouses
     * @return int
     */
    private function updatePoshtomatsPage($poshtomats)
    {
        $table = AreaRepositoryFactory::instance()->poshtomatRepo()->table();
        $updatedAt = $this->updatedAt;
        $insert = array();
        foreach ($poshtomats as $poshtomat) {
            $insert[] = $this->db->prepare(
                "('%s', '%s', '%s', '%s', %d)",
                $poshtomat['Ref'],
                $poshtomat['Description'],
                $poshtomat['DescriptionRu'],
                $poshtomat['CityRef'],
                $updatedAt
            );
        }
        $queryInsert = "INSERT INTO $table (`ref`, `description`, `description_ru`, `parent_ref`, `updated_at`) VALUES ";
        $queryInsert .= implode(",", $insert);
        $queryInsert .= ' ON DUPLICATE KEY UPDATE
            `ref` = VALUES(`ref`),
            `description` = VALUES(`description`),
            `description_ru`=VALUES(`description_ru`),
            `parent_ref`=VALUES(`parent_ref`),
            `updated_at` = VALUES(`updated_at`)';
        return $this->db->query($queryInsert);

    }

    /**
     * @param int $value
     */
    private function setLocationsLastUpdateDate($value)
    {
        NPttn()->options->setLocationsLastUpdateDate($value);
    }

    /**
     * @param array $area
     * @return string
     */
    private function getDescriptionRu($area)
    {
        $areas = $this->areas;
        if (array_key_exists($area['Ref'], $areas)) {
            return $areas[$area['Ref']]['AreaRu'];
        } else {
            return $area['Description'];
        }
    }

    /**
     * @param string $message
     */
    private function addError($message)
    {
        add_action('admin_notices', function () use ($message) {
            $class = 'notice notice-error';
            $message = esc_html('Morkva Nova Poshta synchronisation failed: ' . $message);
            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), $message);
        });
    }

    /**
     * @access private
     */
    private function __construct()
    {
    }

    /**
     * @access private
     */
    private function __clone()
    {
    }

}
