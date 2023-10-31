<?php

/**
 * Fired during plugin activation
 *
 * @link       http://morkva.co.ua/
 * @since      1.0.0
 *
 * @package    nova-poshta-ttn
 * @subpackage nova-poshta-ttn/includes
 */
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    nova-poshta-ttn
 * @subpackage nova-poshta-ttn/includes
 * @author     MORKVA <hello@morkva.co.ua>
 */


class MNP_Plugin_Activator {

	/**
	 * @var string Register url
	 * */
	const API_URL_REGISTER = 'https://api2.morkva.co.ua/api/customers/register';

	/**
	 * The code that runs during plugin activation
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

	if ( is_plugin_active ( 'woo-shipping-for-nova-poshta/woo-shipping-for-nova-poshta.php' ) ) {
			$plugins = 'woo-shipping-for-nova-poshta/woo-shipping-for-nova-poshta.php';
			//deactivate_plugins( $plugins, $silent = false, $network_wide = null );
	}

   global $wpdb;

	$table_name = $wpdb->prefix . 'nova_poshta_region';
	if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name ) {
		// if table not exists, create this table in DB
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			ref VARCHAR(36) NOT NULL,
			description VARCHAR(255) NOT NULL,
			description_ru VARCHAR(255) NOT NULL,
			parent_ref VARCHAR(36) NOT NULL,
			updated_at INT(11) UNSIGNED NOT NULL,
			PRIMARY KEY(ref)
		) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta( $sql );
	} else {}

	$table_name = $wpdb->prefix . 'nova_poshta_city';
	if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name ) {
		// if table not exists, create this table in DB
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
		ref VARCHAR(36) NOT NULL,
		description VARCHAR(255) NOT NULL,
		description_ru VARCHAR(255) NOT NULL,
		parent_ref VARCHAR(36) NOT NULL,
		updated_at INT(11) UNSIGNED NOT NULL,
		PRIMARY KEY(ref)
		) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta( $sql );
	} else {}

	$table_name = $wpdb->prefix . 'nova_poshta_warehouse';
	$is_wh_table_column_exists = self::table_column_exists( $table_name, 'warehouse_type');
	if ( ! $is_wh_table_column_exists ) {
		$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
	}
	if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name ) {
		// if table not exists, create this table in DB
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
		ref VARCHAR(36) NOT NULL,
		description VARCHAR(255) NOT NULL,
		description_ru VARCHAR(255) NOT NULL,
		parent_ref VARCHAR(36) NOT NULL,
		warehouse_type TINYINT UNSIGNED NOT NULL DEFAULT 0,
		updated_at INT(11) UNSIGNED NOT NULL,
		PRIMARY KEY(ref),
		INDEX (warehouse_type)
		) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta( $sql );
	} else {}

	$table_name = $wpdb->prefix . 'nova_poshta_poshtomat';
	if ( ! $is_wh_table_column_exists ) {
		$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
	}
	// $table_name = $wpdb->prefix . 'nova_poshta_poshtomat';
	// if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name ) {
	// 	// if table not exists, create this table in DB
	// 	$charset_collate = $wpdb->get_charset_collate();

	// 	$sql = "CREATE TABLE $table_name (
	// 	ref VARCHAR(36) NOT NULL,
	// 	description VARCHAR(255) NOT NULL,
	// 	description_ru VARCHAR(255) NOT NULL,
	// 	parent_ref VARCHAR(36) NOT NULL,
	// 	updated_at INT(11) UNSIGNED NOT NULL,
	// 	PRIMARY KEY(ref),
	// 	) $charset_collate;";
	// 	require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
	// 	dbDelta( $sql );
	// } else {}

	$table_name = $wpdb->prefix . 'novaposhta_ttn_invoices';

	if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name ) {
		// if table not exists, create this table in DB
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
		id int(11) AUTO_INCREMENT,
		order_id int(11) NOT NULL,
		order_invoice varchar(255) NOT NULL,
		    invoice_ref varchar(255) NOT NULL,
		PRIMARY KEY(id)
		) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta( $sql );
	} else {

	}

	flush_rewrite_rules();
error_log('mrkvnppro activated!');
error_log('Plugin Version');error_log(MNP_PLUGIN_VERSION);
error_log('$_SERVER[REMOTE_ADDR]');error_log(print_r($_SERVER['REMOTE_ADDR'], 1));
$home_url = parse_url(home_url());
error_log('mrkvnppro Site Domain');error_log($home_url['host']);
	}

	public static function table_column_exists($table_name, $column_name)
	{
	    global $wpdb;

	    $column = $wpdb->get_results($wpdb->prepare(
	        "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",
	        DB_NAME,
	        $table_name,
	        $column_name
	    ));

	    if (!empty($column)) {
	        return true;
	    }

	    return false;
	}

}
