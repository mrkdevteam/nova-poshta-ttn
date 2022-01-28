<?php

/**
 * Plugin Name: Shipping for Nova Poshta
 * Plugin URI: https://morkva.co.ua/shop/nova-poshta-ttn-pro-lifetime
 * Description: Плагін 2-в-1: спосіб доставки Нова Пошта та генерація накладних Нова Пошта.
 * Version: 1.12.5
 * Author: MORKVA
 * Text Domain: morkvanp-plugin
 * Domain Path: /i18n/
 * WC requires at least: 3.8
 * WC tested up to: 6.1
 */

if (! defined('WPINC')) {
    die;
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';

$plugData = get_plugin_data(__FILE__);
if ($plugData['Name'] == 'Shipping for Nova Poshta') {
    if (file_exists('freemius/freemiusimport.php')) {
        require_once 'freemius/freemiusimport.php';
    }
}
define('MNP_PLUGIN_VERSION', $plugData['Version']);
define('MNP_PLUGIN_NAME', $plugData['Name']);

define('NOVA_POSHTA_TTN_SHIPPING_PLUGIN_DIR', trailingslashit(dirname(__FILE__)));
define('NOVA_POSHTA_TTN_SHIPPING_PLUGIN_URL', trailingslashit(plugin_dir_url(__FILE__)));
define('NOVA_POSHTA_TTN_SHIPPING_TEMPLATES_DIR', trailingslashit(NOVA_POSHTA_TTN_SHIPPING_PLUGIN_DIR . 'templates'));
define('NOVA_POSHTA_TTN_SHIPPING_CLASSES_DIR', trailingslashit(NOVA_POSHTA_TTN_SHIPPING_PLUGIN_DIR . 'classes'));
define('NOVA_POSHTA_TTN_DOMAIN', untrailingslashit(basename(dirname(__FILE__))));
define('NOVA_POSHTA_TTN_SHIPPING_METHOD', 'nova_poshta_shipping_method');
define('NOVA_POSHTA_TTN_SHIPPING_METHOD_POSHTOMAT', 'nova_poshta_shipping_method_poshtomat');


require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/autoload.php';


function logiftestpage($stringtolog, $variabletolog)
{//using html5 details tag
    if (isset($_GET['test'])) {
        echo '<details>
    <summary>'.$stringtolog.'</summary>
    <pre>';
        print_r($variabletolog);
        echo '</pre></details><hr>';
    }
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
function npdata_fetchwh(){

    global $wpdb;
    $table = $wpdb->prefix . 'nova_poshta_warehouse';
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
function novaposhta_updbasesnp()
{
    global $wpdb;
    $citiescountsqlobject=$wpdb->get_results('SELECT COUNT(`ref`) as result  FROM `'.$wpdb->prefix.'nova_poshta_city`');
    $citycountsqlobjectresult = $citiescountsqlobject[0]->result;
    $warehousecountsqlobject=$wpdb->get_results('SELECT COUNT(`ref`) as result FROM `'.$wpdb->prefix.'nova_poshta_warehouse`');
    $warehousecountsqlobjectresult = $warehousecountsqlobject[0]->result;
    if (($citycountsqlobjectresult < 4300) || ($warehousecountsqlobjectresult < 6000)) {
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

function my_actionfogetnpshippngcost_callback()
{
    WC()->cart->calculate_shipping();
    WC()->cart->calculate_totals();


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
    $sender_city = $shipping_settings["city"];//old settings
    if (!empty(get_option('woocommerce_nova_poshta_shipping_method_city'))) {
        $sender_city = get_option('woocommerce_nova_poshta_shipping_method_city');
    }
    $cod = "";

    $c2 = $_POST['c2'] ;
    if (isset($_POST['cod'])) {
        $cod = $_POST['cod'];
    }
    $serviceType = "WarehouseWarehouse";

    if (get_option('woocommerce_nova_poshta_sender_address_type')) {
        $serviceType = 'DoorsWarehouse';
    }
    $codarray = array(
        "CargoType" => "Money",
        "Amount" => $total
    );

    $codarray = array("CargoType" => "Money",   "Amount" => $total);

    $methodProperties = array("CitySender" => $sender_city,"CityRecipient" => $c2,"Weight" => $weight_total,"ServiceType" => $serviceType ,"Cost" => $total,"SeatsAmount" => "10" );

    if ($cod == 'checked') {
        $methodProperties['RedeliveryCalculate'] = $codarray;
    }

    $costs = array("modelName" => "InternetDocument","calledMethod" => "getDocumentPrice","methodProperties" => $methodProperties,"apiKey" => get_option('text_example'));

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

    $err = curl_error($curl);
    curl_close($curl);
    //
    if (($err) || (!get_option('show_calc'))) {
        echo '01234';//signal to stop calculating injection
    } else {
        $obj = json_decode($response, true);
        $echovar = 0;
        $echovar += $obj["data"][0]["Cost"];
        if ($obj["data"][0]["CostRedelivery"]) {
            $echovar += $obj["data"][0]["CostRedelivery"];
        }
        echo $echovar;
    }
    wp_die();
}

function np_get_price_shipping($citycost)
{
    $cartWeight = max(1, WC()->cart->cart_contents_weight);
    $cartTotal = max(1, WC()->cart->cart_contents_total);

    // Parsing posted data on checkout
    $post = array();
    $vars = explode('&', $posted_data);
    foreach ($vars as $k => $value) {
        $v = explode('=', urldecode($value));
        $post[$v[0]] = $v[1];
    }

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



function pnp_adjust_shipping_rate($rates)
{
    global $woocommerce;
    $all_shipping_methods = WC()->shipping->get_shipping_methods();
    foreach ($all_shipping_methods as $key => $value) {
        if ( $value->id == 'nova_poshta_shipping_method' ) {
            $method_index = $key;
            break;
        }
    }
    $all_shipping_methods[$method_index]; // Метод доставки Новою Поштою
    $modal_settings = get_option( 'woocommerce_nova_poshta_shipping_method_' . $method_index . '_settings' );
    $plugin_settings = get_option( 'woocommerce_nova_poshta_shipping_method_settings' );
    $fin_plugin_settings = array_merge( $plugin_settings, $modal_settings );
    update_option( 'woocommerce_nova_poshta_shipping_method_settings', $fin_plugin_settings );

    $index = 0;
    foreach ($rates as $rate) {
        $billing_city = "";
        if (isset($_COOKIE['city'])) {
            $billing_city = $_COOKIE['city'];
        }
        if (($rate->get_method_id() == 'nova_poshta_shipping_method') && (get_option('plus_calc'))) {
            $cost = $rate->cost;
            // $rate->cost = np_get_price_shipping($billing_city);
            $rate->cost = $cost;
        } elseif ( ($rate->get_method_id() == 'nova_poshta_shipping_method') && ! ( get_option( 'plus_calc' ) ) ) {
        	$rate->cost = 0;
        }
    }
    return $rates;
}
add_filter('woocommerce_package_rates', 'pnp_adjust_shipping_rate', 50, 1);





/*
clear shipping rates cache because woocommerce caching these values
*/
add_filter('woocommerce_checkout_update_order_review', 'clear_wc_shipping_rates_cache_np');

function clear_wc_shipping_rates_cache_np()
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

// Add shipping price for 'Nova Poshta Poshtomat' ('nova_poshta_shipping_method_poshtomat') on Checkout page
function mrkvnp_adjust_shipping_rate_poshtomat($rates)
{
    global $woocommerce;
    $index = 0;
    foreach ($rates as $rate) {
        if (($rate->get_method_id() == 'nova_poshta_shipping_method_poshtomat') && get_option('plus_calc')) {
            $cost = $rate->cost;
            $rate->cost = get_address_shipping_cost();
        } elseif ( ( $rate->get_method_id() == 'nova_poshta_shipping_method_poshtomat' ) && ! get_option( 'plus_calc' ) ) {
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
        if (($rate->get_method_id() == 'npttn_address_shipping_method') && get_option('plus_calc')) {
            $cost = $rate->cost;
            $rate->cost = get_address_shipping_cost();
        } elseif ( ( $rate->get_method_id() == 'npttn_address_shipping_method' ) && ! get_option( 'plus_calc' ) ) {
            $rate->cost = 0;
        }
    }
    return $rates;
}
add_filter('woocommerce_package_rates', 'adjust_shipping_rate_np', 50, 1);

function get_address_shipping_cost()
{
    $city = isset($_POST['city']) ? $_POST['city'] : '';
    $address = isset($_POST['city']) ? $_POST['city'] : '';

    if ($city == '' || $address == '') {
        return 0;
    } else {
        return nova_poshta_address_delivery_calculate($city, $address);
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
        "apiKey" => get_option('text_example')
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

    if (get_option('woocommerce_nova_poshta_sender_address_type')) {
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

    $costs = array("modelName" => "InternetDocument","calledMethod" => "getDocumentPrice","methodProperties" => $methodProperties,"apiKey" => get_option('text_example'));

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
    $chosen_methods = WC()->session->get('chosen_shipping_methods', array());
    foreach ($chosen_methods as $chosen_method) {
        $chosen_method = explode(':', $chosen_method);
        $method_ids[]  = current($chosen_method);
    }
    echo $method_ids[0];
    wp_die();
}
// end ensureNovaPoshta callback

// Attach the plugin shipping methods php-classes
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    function morkvanp_shipping_methods_init()
    {
        require_once NOVA_POSHTA_TTN_SHIPPING_PLUGIN_DIR . 'classes/WC_NovaPoshta_Shipping_Method.php';
        require_once NOVA_POSHTA_TTN_SHIPPING_PLUGIN_DIR . 'classes/WC_NovaPoshta_Shipping_Method_Poshtomat.php';
        // require_once NOVA_POSHTA_TTN_SHIPPING_PLUGIN_DIR . 'classes/WC_NovaPoshtaAddress_Shipping_Method.php';
    }
    add_action('woocommerce_shipping_init', 'morkvanp_shipping_methods_init');

    function morkvanp_shipping_methods_add($methods)
    {
        $methods['nova_poshta_shipping_method'] = 'WC_NovaPoshta_Shipping_Method';
        $methods['nova_poshta_shipping_method_poshtomat'] = 'WC_NovaPoshta_Shipping_Method_Poshtomat';
        // $methods['npttn_address_shipping_method'] = 'WC_NovaPoshtaAddress_Shipping_Method';
        return $methods;
    }
    add_filter('woocommerce_shipping_methods', 'morkvanp_shipping_methods_add');
}

///////start class
require_once __DIR__ . '/includes/NovattnPoshta.php';
require_once __DIR__ . '/includes/NovattnPoshtaPoshtomat.php';
///////finish

NovattnPoshta::instance()->init();
NovattnPoshtaPoshtomat::instance()->init();


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
run_morkvanp_plugin();


function np_wc_add_my_account_orders_column($columns)
{
    $new_columns = array();

    foreach ($columns as $key => $name) {
        $new_columns[ $key ] = $name;

        // add ship-to after order status column
        if ('order-status' === $key) {
            $new_columns['order-ship-to'] = __('TTN', 'textdomain');
        }
    }

    return $new_columns;
}

add_filter('woocommerce_my_account_my_orders_columns', 'np_wc_add_my_account_orders_column');

function np_wc_my_orders_ship_to_column($order)
{
    $outputdataid = get_post_meta($order->get_id(), 'novaposhta_ttn', true);
    $link = '<a target="_blank" href=https://novaposhta.ua/tracking/?cargo_number='.$outputdataid.'>ТТН</a>';
    echo ! empty($outputdataid) ? $link : '–';
}
add_action('woocommerce_my_account_my_orders_column_order-ship-to', 'np_wc_my_orders_ship_to_column');

add_action('woocommerce_thankyou', 'enroll_order', 10, 1);
function enroll_order($order_id)
{
    if (! $order_id) {
        return;
    }

    // Allow code execution only once
    $meta_key = 'novaposhta_ttn';
    $meta_values = get_post_meta($order_id, $meta_key, true);

    if ((empty($meta_values)) && (get_option('autoinvoice'))) {
        // Get an instance of the WC_Order object
        $order = wc_get_order($order_id);
        $message_note = 'Невдача';
        $path = PLUGIN_PATH . 'public/partials/invoice_auto.php';
        if (file_exists($path)) {
            require $path;
            require PLUGIN_PATH . 'public/partials/functions.php';
            $number = Invoice_auto::invoiceauto($order_id);
            if ($number > 0) {
                $message_note = $number;
                $note = "ТТН: ".$message_note;
                echo $note;
                echo '<style>#nnnid{display:none;}</style>';
            }
        }
        //$note = "Створення накладної під час замовлення: ".$message_note;
        //$order->add_order_note( $note );
        $order->save();
    }
}

add_action('init', 'checkupdatespluscron');

function QueryArgFilter($query)
{
    $query['product'] = 'nova-poshta-ttn-pro';
    $query['secret'] = MNP_PLUGIN_VERSION;
    $query['website'] = get_home_url();
    return $query;
}

function checkupdatespluscron()
{
    $path = PLUGIN_PATH . '/includes/update-check.php';
    if (file_exists($path)) {
        $checkedauto = get_option('np_invoice_auto_ttn');
        if (isset($checkedauto) && ($checkedauto==1)) {
            require 'includes/cron.php';
        }
        require 'includes/update-check.php';
        $Checker = Checker::buildUpdateChecker('http://api.morkva.co.ua/api.json', __FILE__);
        $Checker->addQueryArgFilter('QueryArgFilter');
    }
}
