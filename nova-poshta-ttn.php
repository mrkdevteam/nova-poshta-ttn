<?php
/**
 * Plugin Name: Shipping for Nova Poshta
 * Plugin URI: https://morkva.co.ua/shop/nova-poshta-ttn-pro-lifetime
 * Description: Плагін 2-в-1: спосіб доставки Нова Пошта та генерація накладних Нова Пошта.
 * Version: 1.19.2
 * Author: MORKVA
 * Text Domain: nova-poshta-ttn
 * Domain Path: /i18n/
 * Tested up to: 6.6
 * WC requires at least: 3.8
 * WC tested up to: 8.8
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

use plugins\NovaPoshta\classes\Database;
use plugins\NovaPoshta\classes\DatabasePM;
use plugins\NovaPoshta\classes\DatabaseSync;
use plugins\NovaPoshta\classes\invoice\InvoiceModel;

require_once ABSPATH . 'wp-admin/includes/plugin.php';

$plugData = get_plugin_data(__FILE__);

define('MNP_PLUGIN_VERSION', $plugData['Version']);
define('MNP_PLUGIN_NAME', $plugData['Name']);

define('NOVA_POSHTA_TTN_SHIPPING_PLUGIN_DIR', trailingslashit(dirname(__FILE__)));
define('NOVA_POSHTA_TTN_SHIPPING_PLUGIN_URL', trailingslashit(plugin_dir_url(__FILE__)));
define('NOVA_POSHTA_TTN_SHIPPING_TEMPLATES_DIR', trailingslashit(NOVA_POSHTA_TTN_SHIPPING_PLUGIN_DIR . 'templates'));
define('NOVA_POSHTA_TTN_SHIPPING_CLASSES_DIR', trailingslashit(NOVA_POSHTA_TTN_SHIPPING_PLUGIN_DIR . 'classes'));
define('NOVA_POSHTA_TTN_DOMAIN', untrailingslashit(basename(dirname(__FILE__))));
define('NOVA_POSHTA_TTN_SHIPPING_METHOD', 'nova_poshta_shipping_method');
define('NOVA_POSHTA_TTN_SHIPPING_METHOD_POSHTOMAT', 'nova_poshta_shipping_method_poshtomat');
define('NOVA_POSHTA_TTN_ADDRESS_SHIPPING_METHOD', 'npttn_address_shipping_method');


require_once __DIR__ . '/functions.php'; //pro features

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/autoload.php';


function logiftestpage($stringtolog, $variabletolog)
{
    if (isset($_GET['test'])) {
        echo '<details>
                <summary>'.$stringtolog.'</summary>
            <pre>';
        echo '</pre></details><hr>';
    }
}

function mrkvnp_show_status_dbtables($htmlElId = 'mrkvnplastupdate')
{
    global $wpdb;
    $region_table_name = $wpdb->prefix . 'nova_poshta_region';
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$region_table_name'" ) == $region_table_name ) {
        $region_res = $wpdb->get_results( 'SELECT DISTINCT updated_at FROM ' . $region_table_name );
    }
    $time = $region_res[0]->updated_at ?? 0;
    $city_table_name = $wpdb->prefix . 'nova_poshta_city';
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$city_table_name'" ) == $city_table_name ) {
        $city_res = $wpdb->get_results( 'SELECT COUNT( `ref` ) as result  FROM ' . $city_table_name );
    }
    $city_res_show = $city_res[0]->result ?? 0;
    $city_msg = $city_res_show ?: 'міста';
    $wh_table_name = $wpdb->prefix . 'nova_poshta_warehouse';
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$wh_table_name'" ) == $wh_table_name ) {
        $wh_res = $wpdb->get_results( 'SELECT COUNT( `ref` ) as result FROM ' . $wh_table_name );
    }
    $wh_res_show = $wh_res[0]->result ?? 0;
    $wh_msg = $wh_res_show ?: 'відділення';
    if ( 'mrkvnplastupdate' == $htmlElId || 'mrkvnpajaxupdate' == $htmlElId ) {
        echo '<span style="width:max-content;font-size:small;display:inline-block;" id="' . $htmlElId . '">Останнє оновлення: ' .
            date("Y-m-d H:i:s", $time) . ' (UTC)<br>' . $city_res_show . ' міст / ' . $wh_res_show . ' відділень </span>';
    }
    if ( ( 'mrkvnpnoticewp' == $htmlElId ) && ( ! $city_msg || ! $wh_msg ) ) {
        echo '<div id="' . $htmlElId . '" class="error ml0" style="margin:10px 0">
                <p style="color:#000"><b>Shipping for Nova Poshta</b>: Дані про <span style="font-style: italic;">' .
                $city_msg . ' ' . $wh_msg . ' ' . '</span> відсутні.</p></div>';
    }
}

// The AJAX function: show message about warehouses was inserted in DB
add_action( 'wp_ajax_mrkvnp_warehouses_updated', 'mrkvnp_warehouses_updated' ); // for logged in users only
function mrkvnp_warehouses_updated() {
    DatabaseSync::instance()->synchroniseRegions();
    DatabaseSync::instance()->synchroniseCities();
    DatabaseSync::instance()->synchroniseWarehouses();
    echo '<br><span style="color:green;">База відділень оновлена успішно.</span>';
    \mrkvnp_show_status_dbtables('mrkvnpajaxupdate');
    wp_die();// this is required to terminate immediately and return a proper response
}

// The AJAX function - autocomplete for City on Checkout page for SelectDB
add_action('wp_ajax_npdata_fetch' , 'npdata_fetch');
add_action('wp_ajax_nopriv_npdata_fetch','npdata_fetch');
function npdata_fetch(){
    global $wpdb;
    $table = $wpdb->prefix . 'nova_poshta_city';
    $results = $wpdb->get_results( "SELECT `ref`, `description` FROM " . $table .
        " WHERE `description` LIKE '" . $wpdb->_real_escape($_POST['npcityname']) .
        "%' ORDER BY `description` LIMIT 0,6" );
    $items = array();
    if ( ! empty( $results ) ) {
        foreach ( $results as $key => $value ) {
            $items[$value->ref] = $value->description;
        }
    }
    if ( mb_strlen($_POST['npcityname']) < 3 ) echo 'Введіть більше символів...';
    $i = 0;
    $out = '<ul id="cities-list">';
    foreach ($items as $k => $v) {
        if ( mb_strlen( $_POST['npcityname'] ) > 2 ) {
            $out .= '<li style="padding-left:10px;" class="npcityli"
                onclick="selectCity(' . '\'' . esc_attr( str_replace( "'", "\'", $v ) ) . '\'' . ', ' . '\'' . esc_attr($k) . '\'' . ')">' . esc_html($v) .  '</li>';
            $i++;
            if ( $i > 5 ) break;
        }
    }
    echo $out . '</ul>';
    die();
}

// The AJAX function - autocomplete for Warehouse on Checkout page for SelectDB
add_action('wp_ajax_npdata_fetchwh' , 'npdata_fetchwh');
add_action('wp_ajax_nopriv_npdata_fetchwh','npdata_fetchwh');
function npdata_fetchwh() {
    global $wpdb;
    $shipping_method = $_POST['shipping_method'];
    if ( false === strpos( $shipping_method, 'poshtomat' ) ) {
        $table = $wpdb->prefix . 'nova_poshta_warehouse';
    } else {
        $table = $wpdb->prefix . 'nova_poshta_poshtomat';
    }

    $npcityref = isset($_POST['npcityref']) ? $_POST['npcityref'] : '';
    $results = $wpdb->get_results( "SELECT `ref`, `description` FROM " . $table .
        " WHERE `parent_ref` LIKE '" . $npcityref . "'");
    $items = array();
    if ( ! empty( $results ) ) {
        foreach ( $results as $key => $value ) {
            $items[$value->ref] = $value->description;
        }
    }
    $out = '<ul id="warehouses-list">';
    foreach ($items as $k => $v) {
        $out .= '<li style="padding-left:10px; white-space:nowrap;" class="npwhli"
            onclick="selectWarehouse(' . '\'' . esc_attr($v) . '\'' . ', ' . '\'' . esc_attr($k) . '\'' . ')">' . esc_html($v) .  '</li>';
    }
    echo $out . '</ul>';
    die();
}

add_action('wp_ajax_novaposhta_updbasesnp', 'novaposhta_updbasesnp');
add_action('wp_ajax_nopriv_novaposhta_updbasesnp', 'novaposhta_updbasesnp');
function novaposhta_updbasesnp() // This function is disabled temporally
{
    global $wpdb;
    $citiescountsqlobject=$wpdb->get_results('SELECT COUNT(`ref`) as result  FROM `'.$wpdb->prefix.'nova_poshta_city`');
    $citycountsqlobjectresult = $citiescountsqlobject[0]->result;
    $warehousecountsqlobject=$wpdb->get_results('SELECT COUNT(`ref`) as result FROM `'.$wpdb->prefix.'nova_poshta_warehouse`');
    $warehousecountsqlobjectresult = $warehousecountsqlobject[0]->result;
    $poshtomatcountsqlobject=$wpdb->get_results('SELECT COUNT(`ref`) as result FROM `'.$wpdb->prefix.'nova_poshta_poshtomat`');
    $poshtomatcountsqlobjectresult = $poshtomatcountsqlobject[0]->result;
    if ( ( $citycountsqlobjectresult < 4300 ) || ( $warehousecountsqlobjectresult < 6000 ) || ( $poshtomatcountsqlobjectresult < 10000 ) ) {
        Database::instance()->upgrade();
        DatabasePM::instance()->upgrade();
        DatabaseSync::instance()->synchroniseLocations();
        echo 'nova poshta db updated';
        wp_die();
    } else {
        echo 'nova poshta db is up to date';
        wp_die();
    }
}

add_action('wp_ajax_my_actionfogetnpshippngcost', 'my_actionfogetnpshippngcost_callback');
add_action('wp_ajax_nopriv_my_actionfogetnpshippngcost', 'my_actionfogetnpshippngcost_callback');
function my_actionfogetnpshippngcost_callback() // Warehouse
{
    global $woocommerce;
    $url = "https://api.novaposhta.ua/v2.0/json/";
    $invoiceModel = new invoiceModel();
    WC()->cart->calculate_shipping();
    WC()->cart->calculate_totals();
    $weight_total = max(1, WC()->cart->cart_contents_weight);
    $weight_unit  =  get_option('woocommerce_weight_unit');
    $weightarray = array(
        'g' => 0.001,
        'kg' => 1,
        'lbs' => 0.45359,
        'oz' => 0.02834
    );
    foreach ($weightarray as $unit => $value) {
        if ($unit == $weight_unit) {
            $weight_total = $weight_total * $value;
        }
    }
    if ($weight_total < 0.5) {
        $weight_total = 0.5;
    }
    $total = intval($woocommerce->cart->total);
    $shipping_settings = get_option('woocommerce_nova_poshta_shipping_method_settings');
    $sender_city = $shipping_settings["city"];//old settings
    if (!empty(get_option('woocommerce_nova_poshta_shipping_method_city'))) {
        $sender_city = get_option('woocommerce_nova_poshta_shipping_method_city');
    }
    $cod = "";
    $c2 = isset( $_POST['c2'] ) ? $_POST['c2'] : '';
    if (isset($_POST['cod'])) {
        $cod = $_POST['cod'];
    }
    $serviceType = "WarehouseWarehouse";

    if (get_option('mrkvnp_invoice_sender_warehouse_type')) {
        $serviceType = 'DoorsWarehouse';
    }
    $codarray = array("CargoType" => "Money",   "Amount" => $total);

    $methodProperties = array(
        "CitySender" => $sender_city,
        "CityRecipient" => $c2,
        "Weight" => $weight_total,
        "ServiceType" => $serviceType ,
        "Cost" => $total,
        "SeatsAmount" => "1"
    );

    if ($cod == 'checked') {
        $methodProperties['RedeliveryCalculate'] = $codarray;
    }
    $costs = array(
        "modelName" => "InternetDocument",
        "calledMethod" => "getDocumentPrice",
        "methodProperties" => $methodProperties,
        "apiKey" => get_option('mrkvnp_sender_api_key')
    );
    $obj = $invoiceModel->sendPostRequest( $url, $costs );
    $err = $obj['errors'];

    if ( ( $err ) || ( ! get_option('mrkvnp_is_show_delivery_price' ) ) ) {
        echo 'apinperrors'; // signal to stop calculating injection
    } else {
        $echovar = 0.00;
        $echovar += isset( $obj["data"][0]["Cost"] ) ? $obj["data"][0]["Cost"] : 0.00;
        if ( isset( $obj["data"][0]["CostRedelivery"] ) ) {
            $echovar += $obj["data"][0]["CostRedelivery"];
        }
        echo $echovar;
    }
    wp_die();
}

add_action('wp_ajax_actionMrkvNpGetPostomatCost', 'actionMrkvNpGetPostomatCost_cb');
add_action('wp_ajax_nopriv_actionMrkvNpGetPostomatCost', 'actionMrkvNpGetPostomatCost_cb');
function actionMrkvNpGetPostomatCost_cb() // Poshtomat
{
    global $woocommerce;
    $url = "https://api.novaposhta.ua/v2.0/json/";
    $invoiceModel = new invoiceModel();
    WC()->cart->calculate_shipping();
    WC()->cart->calculate_totals();
    $weight_total = max(1, WC()->cart->cart_contents_weight);
    $weight_unit  =  get_option('woocommerce_weight_unit');
    $weightarray = array(
        'g' => 0.001,
        'kg' => 1,
        'lbs' => 0.45359,
        'oz' => 0.02834
    );
    foreach ($weightarray as $unit => $value) {
        if ($unit == $weight_unit) {
            $weight_total = $weight_total * $value;
        }
    }
    if ($weight_total < 0.5) {
        $weight_total = 0.5;
    }
    $total = intval($woocommerce->cart->total);
    $shipping_settings = get_option('woocommerce_nova_poshta_shipping_method_settings');
    $sender_city = $shipping_settings["city"]; // old settings
    if (!empty(get_option('woocommerce_nova_poshta_shipping_method_city'))) {
        $sender_city = get_option('woocommerce_nova_poshta_shipping_method_city');
    }
    $cod = "";
    $c2 = isset( $_POST['c2'] ) ? $_POST['c2'] : '';
    if (isset($_POST['cod'])) {
        $cod = $_POST['cod'];
    }
    $serviceType = "WarehousePostomat";

    if (get_option('mrkvnp_invoice_sender_warehouse_type')) {
        $serviceType = 'DoorsPostomat';
    }
    $codarray = array("CargoType" => "Money",   "Amount" => $total);

    $methodProperties = array(
        "CitySender" => $sender_city,
        "CityRecipient" => $c2,
        "Weight" => $weight_total,
        "ServiceType" => $serviceType ,
        "Cost" => $total,
        "SeatsAmount" => "1"
    );

    if ($cod == 'checked') {
        $methodProperties['RedeliveryCalculate'] = $codarray;
    }
    $costs = array(
        "modelName" => "InternetDocument",
        "calledMethod" => "getDocumentPrice",
        "methodProperties" => $methodProperties,
        "apiKey" => get_option('mrkvnp_sender_api_key')
    );
    $obj = $invoiceModel->sendPostRequest( $url, $costs );
    $err = $obj['errors'];
    if ( ( $err ) || ( ! get_option('mrkvnp_is_show_delivery_price' ) ) ) {
        echo 'apinperrors'; // signal to stop calculating injection
    } else {
        $echovar = 0.00;
        $echovar += isset( $obj["data"][0]["Cost"] ) ? $obj["data"][0]["Cost"] : 0.00;
        if ( isset( $obj["data"][0]["CostRedelivery"] ) ) {
            $echovar += $obj["data"][0]["CostRedelivery"];
        }
        echo $echovar;
    }
    wp_die();
}

add_action('wp_ajax_actionMrkvNpGetAddressCost', 'actionMrkvNpGetAddressCost_cb');
add_action('wp_ajax_nopriv_actionMrkvNpGetAddressCost', 'actionMrkvNpGetAddressCost_cb');
function actionMrkvNpGetAddressCost_cb() // Address
{
    global $woocommerce;
    $url = "https://api.novaposhta.ua/v2.0/json/";
    $invoiceModel = new invoiceModel();
    WC()->cart->calculate_shipping();
    WC()->cart->calculate_totals();
    $weight_total = max(1, WC()->cart->cart_contents_weight);
    $weight_unit  =  get_option('woocommerce_weight_unit');
    $weightarray = array(
        'g' => 0.001,
        'kg' => 1,
        'lbs' => 0.45359,
        'oz' => 0.02834
    );
    foreach ($weightarray as $unit => $value) {
        if ($unit == $weight_unit) {
            $weight_total = $weight_total * $value;
        }
    }
    if ($weight_total < 0.5) {
        $weight_total = 0.5;
    }
    $total = intval($woocommerce->cart->total);
    $shipping_settings = get_option('woocommerce_nova_poshta_shipping_method_settings');
    $sender_city = $shipping_settings["city"]; // old settings
    if (!empty(get_option('woocommerce_nova_poshta_shipping_method_city'))) {
        $sender_city = get_option('woocommerce_nova_poshta_shipping_method_city');
    }
    $cod = "";
    $c2 = isset( $_POST['c2'] ) ? $_POST['c2'] : '';
    if (isset($_POST['cod'])) {
        $cod = $_POST['cod'];
    }
    $serviceType = "WarehouseDoors";

    if (get_option('mrkvnp_invoice_sender_warehouse_type')) {
        $serviceType = 'DoorsDoors';
    }
    $codarray = array("CargoType" => "Money",   "Amount" => $total);

    $methodProperties = array(
        "CitySender" => $sender_city,
        "CityRecipient" => $c2,
        "Weight" => $weight_total,
        "ServiceType" => $serviceType ,
        "Cost" => $total,
        "SeatsAmount" => "1"
    );

    if ($cod == 'checked') {
        $methodProperties['RedeliveryCalculate'] = $codarray;
    }
    $costs = array(
        "modelName" => "InternetDocument",
        "calledMethod" => "getDocumentPrice",
        "methodProperties" => $methodProperties,
        "apiKey" => get_option('mrkvnp_sender_api_key')
    );
    $obj = $invoiceModel->sendPostRequest( $url, $costs );
    $err = $obj['errors'];
    if ( ( $err ) || ( ! get_option('mrkvnp_is_show_delivery_price' ) ) ) {
        echo 'apinperrors'; // signal to stop calculating injection
    } else {
        $echovar = 0.00;
        $echovar += isset( $obj["data"][0]["Cost"] ) ? $obj["data"][0]["Cost"] : 0.00;
        if ( isset( $obj["data"][0]["CostRedelivery"] ) ) {
            $echovar += $obj["data"][0]["CostRedelivery"];
        }
        echo $echovar;
    }
    wp_die();
}

function np_get_price_shipping($citycost) // Ця функція не задіяна
{
    $cartWeight = max(1, WC()->cart->cart_contents_weight);
    $cartTotal = max(1, WC()->cart->cart_contents_total);


    $weight_unit  =  get_option('woocommerce_weight_unit');
    $weightarray = array(
          'g' => 0.001,
          'kg' => 1,
          'lbs' => 0.45359,
          'oz' => 0.02834
  );

    foreach ($weightarray as $unit => $value) {
        if ($unit == $weight_unit) {
            $cartWeight = $cartWeight * $value;
        }
    }
    $addw = 0;
    $rt=0;
    $addr = 'unchecker';

    $citycost = '';

    if (isset($_COOKIE['city'])) {
        $citycost = $_COOKIE['city'];
    }



    if (true) {
        $uptarifs = array(
      '0.5' => '40',
      '1' => '45',
      '2'=>'50',
      '5'=>'55',
      '10'=>'65',
      '20'=>'85',
      '30'=>'105'
 );
        foreach ($uptarifs as $kilo => $price) {
            if (($kilo > $cartWeight) && (!$addw)) {
                $addw = $price;
            }
        }
        $rt += $addw;

        if ($addr == 'checked') {
            $rt += 25;
        } else {
        }
        return $rt;
    }
}

/**
* Gets shipping method number index for WordPress option (modal in Shipping Zone)
*/
function morkva_get_shipping_index( $shipping_id ) {
    if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
        return;
    }
    global $woocommerce;
    $all_shipping_methods = WC()->shipping->get_shipping_methods();
    foreach ($all_shipping_methods as $key => $value) {
        if ( $value->id == $shipping_id ) {
            $method_index = $key;
            break;
        }
    }
    return ( isset( $method_index ) ) ? $method_index : null;
}

function pnp_adjust_shipping_rate($rates)
{
    global $woocommerce;
    $method_index =  morkva_get_shipping_index( NOVA_POSHTA_TTN_SHIPPING_METHOD );
    $modal_settings = (array) get_option( 'woocommerce_nova_poshta_shipping_method_' . $method_index . '_settings' );
    $plugin_settings = get_option( 'woocommerce_nova_poshta_shipping_method_settings' );
    if ( $modal_settings && $plugin_settings ) {
        $fin_plugin_settings = array_merge( $plugin_settings, $modal_settings );
        update_option( 'woocommerce_nova_poshta_shipping_method_settings', $fin_plugin_settings );
    }

    $index = 0;
    foreach ($rates as $rate) {
        $billing_city = "";
        if (isset($_COOKIE['city'])) {
            $billing_city = $_COOKIE['city'];
        }
        if ( ( $rate->get_method_id() == 'nova_poshta_shipping_method' )) {
            $cost = $rate->cost;
            $rate->cost = $cost;
        } elseif ( ( $rate->get_method_id() == 'nova_poshta_shipping_method' )) {
            $rate->cost = 0;
        }
    }
    return $rates;
}
add_filter('woocommerce_package_rates', 'pnp_adjust_shipping_rate', 50, 1);

/*
clear shipping rates cache because woocommerce caching these values
*/
/*function clear_wc_shipping_rates_cache_np()
{
    $packages = WC()
        ->cart
        ->get_shipping_packages();

    foreach ($packages as $key => $value) {
        $shipping_session = "shipping_for_package_$key";

        unset(WC()
            ->session
            ->$shipping_session);
    }
}
add_filter('woocommerce_checkout_update_order_review', 'clear_wc_shipping_rates_cache_np');*/

// Add shipping price for 'Nova Poshta Poshtomat' ('nova_poshta_shipping_method_poshtomat') on Checkout page
function mrkvnp_adjust_shipping_rate_poshtomat($rates)
{
    global $woocommerce;
    $index = 0;
    foreach ($rates as $rate) {
        if ( ( $rate->get_method_id() == 'nova_poshta_shipping_method_poshtomat' )) {
            $cost = $rate->cost;
            $rate->cost = $cost;
        } elseif ( ( $rate->get_method_id() == 'nova_poshta_shipping_method_poshtomat' )) {
            $rate->cost = 0;
        }
    }
    return $rates;
}
add_filter('woocommerce_package_rates', 'mrkvnp_adjust_shipping_rate_poshtomat', 60, 1);

// Add shipping price for `Nova Poshta` method ('nova_poshta_shipping_method') on Checkout page
function adjust_shipping_rate_np($rates)
{
    global $woocommerce;
    $index = 0;
    foreach ($rates as $rate) {
        if ( $rate->get_method_id() == 'npttn_address_shipping_method'  ) {
            $cost = $rate->cost;
            $rate->cost = $cost;
        } elseif ( ( $rate->get_method_id() == 'npttn_address_shipping_method' ) ) {
            $rate->cost = 0;
        }
    }
    return $rates;
}
add_filter('woocommerce_package_rates', 'adjust_shipping_rate_np', 50, 1);

function get_address_shipping_cost()
{
    $shipping_settings = get_option('woocommerce_nova_poshta_shipping_method_settings');
    $method_index =  morkva_get_shipping_index( NOVA_POSHTA_TTN_ADDRESS_SHIPPING_METHOD );
    $modal_settings = get_option( 'woocommerce_npttn_address_shipping_method_' . $method_index . '_settings' );
    if ( $modal_settings ) {
        $cart_total = intval(WC()->cart->get_total());
        if ($modal_settings['use_shipping_price_on_delivery'] && $modal_settings['use_fixed_price_on_delivery'] && $modal_settings['fixed_price'] > 0 ) {
            return $modal_settings['fixed_price'];
        }
        if ( isset( $modal_settings['free_shipping_min_sum'] ) && $modal_settings['free_shipping_min_sum'] > 0 &&
                $cart_total >= $modal_settings['free_shipping_min_sum'] ) {
            return 0.00;
        }
    }

    $city = isset( $shipping_settings['city_name'] ) ? $shipping_settings['city_name'] : '';
    $address = isset( $_POST['city'] ) ? $_POST['city'] : '';

    if ($city == '' || $address == '') {
        return 0.00;
    } else {
        return 0.00;
    }
}

function get_city_id_by_name($city)
{
    if (!class_exists('MNP_Plugin_Invoice_Controller')) {
        require PLUGIN_PATH.'public/partials/morkvanp-plugin-invoice-controller.php';
    }
    $invoiceController = new MNP_Plugin_Invoice_Controller();
    $url = "https://api.novaposhta.ua/v2.0/json/";
    $methodProperties = array(
        "FindByString" => $city
    );
    $senderCity = array(
        "modelName" => "Address",
        "calledMethod" => "getCities",
        "methodProperties" => $methodProperties,
        "apiKey" => get_option('mrkvnp_sender_api_key')
    );
    $curl = curl_init();
    $invoiceController -> createRequest($url, $senderCity, $curl);
    $response = curl_exec($curl);
    $err = curl_error($curl);
    if ($err) {
        echo('Вибачаємось, але сталась помилка');
    } else {
        $obj = json_decode($response, true);

        if (sizeof($obj["data"])>0) {
            $ref = $obj["data"][0]["Ref"];
            return $ref;
        } else {
            return '';
        }
    }
}

function nova_poshta_address_delivery_calculate($city, $address)
{
    global $woocommerce;
    $weight_total = max(1, WC()->cart->cart_contents_weight);
    $weight_unit  =  get_option('woocommerce_weight_unit');
    $weightarray = array(
        'g' => 0.001,
        'kg' => 1,
        'lbs' => 0.45359,
        'oz' => 0.02834
        );
    foreach ($weightarray as $unit => $value) {
        if ($unit == $weight_unit) {
            $weight_total = $weight_total * $value;
        }
    }
    if ($weight_total < 0.5) {
        $weight_total = 0.5;
    }
    $total = intval($woocommerce->cart->total);
    $shipping_settings = get_option('woocommerce_nova_poshta_shipping_method_settings');
    $sender_city = isset($shipping_settings["city"]) ? $shipping_settings["city"] : '';//old settings
    if (!empty(get_option('woocommerce_nova_poshta_shipping_method_city'))) {
        $sender_city = get_option('woocommerce_nova_poshta_shipping_method_city');
    }
    $cod = "";
    if (get_city_id_by_name($city) == '') {
        return 0;
    } else {
        $c2 = get_city_id_by_name($city);
    }
    $serviceType = "WarehouseDoors";

    if (get_option('mrkvnp_invoice_sender_warehouse_type')) {
        $serviceType = 'DoorsDoors';
    }
    $codarray = array("CargoType" => "Money",   "Amount" => $total);
    $methodProperties = array(
        "CitySender" => $sender_city,
        "CityRecipient" => $c2,
        "Weight" => $weight_total,
        "ServiceType" => $serviceType ,
        "Cost" => $total,
        "SeatsAmount" => "10"
    );
    if ($cod == 'checked') {
        $methodProperties['RedeliveryCalculate'] = $codarray;
    }
    $costs = array("modelName" => "InternetDocument","calledMethod" => "getDocumentPrice","methodProperties" => $methodProperties,"apiKey" => get_option('mrkvnp_sender_api_key'));
    $curl = curl_init();
    $url = "https://api.novaposhta.ua/v2.0/json/";
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($costs),

        CURLOPT_HTTPHEADER => array("content-type: application/json",),
    ));
    $response = curl_exec($curl);
    $obj = json_decode($response, true);
    curl_close($curl);
    if (isset($obj['data'][0]['Cost'])) {
        return($obj['data'][0]['Cost']);
    } else {
        return 0;
    }
}

// ensureNovaPoshta callback
add_action('wp_ajax_my_action_for_wc_get_chosen_method_ids', 'my_action_for_wc_get_chosen_method_ids');
add_action('wp_ajax_nopriv_my_action_for_wc_get_chosen_method_ids', 'my_action_for_wc_get_chosen_method_ids');

function my_action_for_wc_get_chosen_method_ids()
{
    $method_ids     = array();
    $chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
    foreach ($chosen_methods as $chosen_method) {
        $chosen_method = explode(':', $chosen_method);
        $method_ids[]  = current($chosen_method);
    }
    echo ( is_array( $method_ids ) && isset( $method_ids[0]) )
        ? $method_ids[0] : 'Morkvanp Plugin Notice:  Shipping Method not chosen!';
    wp_die();
}
// end ensureNovaPoshta callback

// Attach the plugin shipping methods php-classes
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    function morkvanp_shipping_methods_init()
    {
        require_once NOVA_POSHTA_TTN_SHIPPING_PLUGIN_DIR . 'classes/WC_NovaPoshta_Shipping_Method.php';
        require_once NOVA_POSHTA_TTN_SHIPPING_PLUGIN_DIR . 'classes/WC_NovaPoshta_Shipping_Method_Poshtomat.php';
        require_once NOVA_POSHTA_TTN_SHIPPING_PLUGIN_DIR . 'classes/WC_NovaPoshtaAddress_Shipping_Method.php';
    }
    add_action('woocommerce_shipping_init', 'morkvanp_shipping_methods_init');

    function morkvanp_shipping_methods_add($methods)
    {
        $methods['nova_poshta_shipping_method'] = 'WC_NovaPoshta_Shipping_Method';
        $methods['nova_poshta_shipping_method_poshtomat'] = 'WC_NovaPoshta_Shipping_Method_Poshtomat';
        $methods['npttn_address_shipping_method'] = 'WC_NovaPoshtaAddress_Shipping_Method';
        return $methods;
    }
    add_filter('woocommerce_shipping_methods', 'morkvanp_shipping_methods_add');
}

///////start php-classes
require_once __DIR__ . '/includes/NovattnPoshta.php';
require_once __DIR__ . '/includes/NovattnPoshtaPoshtomat.php';
require_once __DIR__ . '/includes/NovattnPoshtaAddress.php';
///////finish

NovattnPoshta::instance()->init();
NovattnPoshtaPoshtomat::instance()->init();
NovattnPoshtaAddress::instance()->init();


/**
 * @return NovattnPoshta
 */
function NPttn()
{
    return NovattnPoshta::instance();
}

/**
 * @return NovattnPoshtaPoshtomat
 */
function NPttnPM()
{
    return NovattnPoshtaPoshtomat::instance();
}

/**
 * @return NovattnPoshtaAddress
 */
function NPttnA()
{
    return NovattnPoshtaAddress::instance();
}


define('PLUGIN_URL', plugin_dir_url(__FILE__));
define('PLUGIN_PATH', plugin_dir_path(__FILE__));
/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-morkvanp-plugin-activator.php
 */
function activate_morkvanp_plugin()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-morkvanp-plugin-activator.php';
    MNP_Plugin_Activator::activate();

    if( get_option('mrkv_nova_poshta_free_version') ) {
        update_option('mrkv_nova_poshta_free_version', MNP_PLUGIN_VERSION);
    }
    else{
        add_option('mrkv_nova_poshta_free_version', MNP_PLUGIN_VERSION);

        set_transient( 'mrkv-admin-novaposhta-settings', true, 5 );
        Database::instance()->drop_first_table();
        Database::instance()->create_main_table();
    }
}
/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-morkvanp-plugin-deactivator.php
 */
function deactivate_morkvanp_plugin()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-morkvanp-plugin-deactivator.php';
    MNP_Plugin_Deactivator::deactivate();
}
register_activation_hook(__FILE__, 'activate_morkvanp_plugin');
register_deactivation_hook(__FILE__, 'deactivate_morkvanp_plugin');
/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-morkvanp-plugin.php';
/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_morkvanp_plugin()
{
    $plugin = new MNP_Plugin();
    $plugin->run();
}

add_action( 'before_woocommerce_init', function() {
    run_morkvanp_plugin();
} );

add_action( 'admin_notices', 'mrkv_admin_notice_example_notice' );
add_action( 'upgrader_process_complete', 'mrkv_upgrade_completed', 100, 2);

function mrkv_upgrade_completed($upgrader_object, $options){
    // The path to our plugin's main file
    $our_plugin = plugin_basename( __FILE__ );

    // If an update has taken place and the updated type is plugins and the plugins element exists
    if( $options['action'] == 'update' && $options['type'] == 'plugin' && isset( $options['plugins'] ) ) {
        // Iterate through the plugins being updated and check if ours is there
        foreach( $options['plugins'] as $plugin ) {
            if( $plugin == $our_plugin ) {
                if( get_option('mrkv_nova_poshta_free_version') ) {
                    update_option('mrkv_nova_poshta_free_version', MNP_PLUGIN_VERSION);
                }
                else{
                    add_option('mrkv_nova_poshta_free_version', MNP_PLUGIN_VERSION);

                    set_transient( 'mrkv-admin-novaposhta-settings', true, 5 );
                    Database::instance()->drop_first_table();
                    Database::instance()->create_main_table();
                }
            }
        }
    }
}

function mrkv_admin_notice_example_notice(){

    /* Check transient, if available display notice */
    if( get_transient( 'mrkv-admin-novaposhta-settings' ) ){
        ?>
        <div class="updated notice is-dismissible notice-new-np">
            <div class="mrkv_block_notification" style="display: flex; padding: 18px 5px;">
                <div>
                    <img style="width: 150px; margin-right: 20px; margin-top: 20px;" src="<?php echo plugin_dir_url(__FILE__) . 'assets/images/nova-poshta-icon.gif'; ?>" alt="Morkva Nova Poshta Shipping">
                </div>
                <div>
                    <h2>Ви оновили плагін Shipping for Nova Poshta</h2>
                    <strong>Важливо! Перейдіть на сторінку налаштувань плагіна, та</strong>
                    <ol>
                        <li>Переконайтеся що ключ АРІ введений.</li>
                        <li>Натисніть "оновити базу відділень", дочекайтеся оновлення.</li>
                        <li>Заповніть всі налаштування в розділі "Значення за замовчуванням".</li>
                        <li>Збережіть налаштування.</li>
                        <li>В розділі "відправник" оберіть контактну особу з списку.</li>
                        <li>Ще раз збережіть налаштування.</li>
                    </ol>
                    <button class="button-primary notice-dismiss-np">Зрозуміло</button>
                </div>    
            </div>
            <button type="button" class="notice-dismiss notice-dismiss-np"><span class="screen-reader-text">Dismiss this notice.</span></button>
        </div>
        <?php
    }
}

add_action( 'wp_ajax_mrkv_np_remove_notice', 'mrkv_np_remove_notice_ajax' ); 
add_action( 'wp_ajax_nopriv_mrkv_np_remove_notice', 'mrkv_np_remove_notice_ajax' );

function mrkv_np_remove_notice_ajax(){
    /* Delete transient, only display this notice once. */
    delete_transient( 'mrkv-admin-novaposhta-settings' );
}