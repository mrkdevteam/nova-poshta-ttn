<?php

namespace plugins\NovaPoshta\classes;

use plugins\NovaPoshta\classes\base\Base;
use plugins\NovaPoshta\classes\repository\AreaRepositoryFactory;
use wpdb;

/**
 * Class Base
 * @package plugins\NovaPoshta\classes
 * @property string tableLocations
 * @property string tableLocationsUpdate
 * @property wpdb $db
 * @property mixed last_error
 * @method prepare($query, $args)
 * @method get_row($query)
 * @method get_results($query)
 * @method query($query);
 * @method insert($table, $data, $format = null)
 * @method get_var($query = null, $x = 0, $y = 0)
 */
class Database extends Base
{

    /**
     * @var self
     */
    private static $_instance;

    /**
     * @return Database
     */
    public static function instance()
    {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Database upgrade entry point
     */
    public function upgrade()
    {
error_log('upgrade()');
        $this->dropTables();
        $this->createTables();
    }

    /**
     * Database upgrade entry point
     */
    public function create_main_table()
    {
        $this->createTables();
    }

    public function drop_first_table()
    {
        $this->dropTables();
    }

    /**
     * Database downgrade entry point
     */
    public function downgrade()
    {
        $this->dropTables();
        delete_site_option('nova_poshta_db_version');
    }

    /**
     * @return wpdb
     */
    protected function getDb()
    {
        return NPttn()->db;
    }

    private function createTables()
    {
error_log('createTables()');
        $factory = AreaRepositoryFactory::instance();
// error_log('$factory');error_log(print_r($factory,1));
        if ($this->db->has_cap('collation')) {
            $collate = $this->db->get_charset_collate();
        } else {
            $collate = '';
        }
        // if ( ! empty( $this->db->charset ) ) {
        //     $collate = "DEFAULT CHARACTER SET $this->db->charset";
        // }
        // if ( ! empty( $this->db->collate ) ) {
        //     $collate .= " COLLATE $this->db->collate";
        // }
error_log('$collate');error_log($collate);
        /*
        * create Regions table
        */
       $regionTableName = $factory->regionRepo()->table();
       $regionQuery =
           "CREATE TABLE {$regionTableName} (
               `ref` VARCHAR(36) NOT NULL,
               `description` VARCHAR(255) NOT NULL,
               `description_ru` VARCHAR(255) NOT NULL,
               `updated_at` INT(11) UNSIGNED NOT NULL,
               PRIMARY KEY (`ref`)
           ) $collate;";

       $this->db->query($regionQuery);
error_log('$this->db->query($regionQuery)');
// echo '<span>Завантаження областей..</span><br>';
//        $indexQuery = <<<INDEX
// ALTER TABLE {$regionTableName} ADD INDEX idx_nova_poshta_region_description (description);
// INDEX;
//        $this->db->query($indexQuery);

//        $indexQuery = <<<INDEX
// ALTER TABLE {$regionTableName} ADD INDEX idx_nova_poshta_region_description_ru (description_ru)
// INDEX;
//        $this->db->query($indexQuery);

        /*
         * Create cities table
         */
        $cityTableName = $factory->cityRepo()->table();
error_log($cityTableName);
        $cityQuery =
            "CREATE TABLE $cityTableName (
                ref VARCHAR(36) NOT NULL,
                description VARCHAR(255) NOT NULL,
                description_ru VARCHAR(255) NOT NULL,
                parent_ref VARCHAR(36) NOT NULL,
                updated_at INT(11) UNSIGNED NOT NULL,
                PRIMARY KEY (ref)
            ) $collate;";

        $this->db->query($cityQuery);
error_log('$this->db->query($cityQuery)');

        /*
         * create warehouses table
         */
        $warehouseTableName = $factory->warehouseRepo()->table();
error_log($warehouseTableName);
        $warehouseQuery =
            "CREATE TABLE $warehouseTableName (
                ref VARCHAR(36) NOT NULL,
                description VARCHAR(255) NOT NULL,
                description_ru VARCHAR(255) NOT NULL,
                parent_ref VARCHAR(255) NOT NULL,
                warehouse_type TINYINT UNSIGNED NOT NULL DEFAULT 0,
                updated_at INT(11) UNSIGNED NOT NULL,
                PRIMARY KEY (ref),
                INDEX (warehouse_type)
            ) $collate;";

        $this->db->query($warehouseQuery);

        /*
         * create postomats table
         */
        // $postomatTableName = $factory->poshtomatRepo()->table();
        // $postomatQuery =
        //     "CREATE TABLE $postomatTableName (
        //         ref VARCHAR(36) NOT NULL,
        //         description VARCHAR(255) NOT NULL,
        //         description_ru VARCHAR(255) NOT NULL,
        //         parent_ref VARCHAR(255) NOT NULL,
        //         updated_at INT(11) UNSIGNED NOT NULL,
        //         PRIMARY KEY (ref)
        //     ) $collate;";

        // $this->db->query($postomatQuery);
    }

    private function dropTables()
    {
error_log('dropTables()');
        $factory = AreaRepositoryFactory::instance();
        $factory->cityRepo()->table();
        $this->dropTableByName($factory->warehouseRepo()->table());
        $this->dropTableByName($factory->cityRepo()->table());
        $this->dropTableByName($factory->regionRepo()->table());
    }

    /**
     * @param string $table
     */
    private function dropTableByName($table)
    {
        $query = "DROP TABLE IF EXISTS {$table}";
        $this->db->query($query);
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
