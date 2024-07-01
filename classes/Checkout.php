<?php

namespace plugins\NovaPoshta\classes;

use plugins\NovaPoshta\classes\base\ArrayHelper;
use plugins\NovaPoshta\classes\base\Base;
use plugins\NovaPoshta\classes\base\OptionsHelper;
use plugins\NovaPoshta\classes\repository\AreaRepositoryFactory;
use plugins\NovaPoshta\classes\City;
use plugins\NovaPoshta\classes\Warehouse;
use plugins\NovaPoshta\classes\Poshtomat;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Class Checkout
 * @property bool isCheckout
 * @property Customer $customer
 * @package plugins\NovaPoshta\classes
 */
class Checkout extends Base
{

    /**
     * @var Checkout
     */
    private static $_instance;

    /**
     * @return Checkout
     */
    public static function instance()
    {
        if (static::$_instance == null) {
            static::$_instance = new static();
        }
        return static::$_instance;
    }

    /**
     * @return void
     */
    public function init()
    {
        add_filter( 'woocommerce_checkout_fields', array( $this, 'maybeDisableDefaultShippingMethods' ) );

        add_filter('woocommerce_billing_fields', array($this, 'addNovaPoshtaBillingFields'), 99999, 1);
        add_filter('woocommerce_shipping_fields', array($this, 'addNovaPoshtaShippingFields'), 99999, 1);

        add_action('woocommerce_checkout_process', array($this, 'saveNovaPoshtaOptions'), 10, 2);
        add_action('woocommerce_checkout_update_order_meta', array($this, 'updateOrderMeta'));

        add_action('woocommerce_thankyou', array($this, 'displayShippingPhoneOnThankyou'), 20);

        add_filter('woocommerce_cart_shipping_packages', array($this, 'updatePackages'));

        add_filter('nova_poshta_disable_default_fields', array($this, 'disableDefaultFields'));
        add_filter('nova_poshta_disable_nova_poshta_fields', array($this, 'disableNovaPoshtaFields'));

        add_filter('default_checkout_billing_nova_poshta_region', array($this, 'getDefaultRegion'));
        add_filter('default_checkout_billing_nova_poshta_city', array($this, 'getDefaultCity'));
        add_filter('default_checkout_billing_nova_poshta_warehouse', array($this, 'getDefaultWarehouse'));
        add_filter('default_checkout_billing_nova_poshta_street', array($this, 'getDefaultStreet'));
        add_filter('default_checkout_shipping_nova_poshta_region', array($this, 'getDefaultRegion'));
        add_filter('default_checkout_shipping_nova_poshta_city', array($this, 'getDefaultCity'));
        add_filter('default_checkout_shipping_nova_poshta_warehouse', array($this, 'getDefaultWarehouse'));

        add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'addDefaultRegionCustomField') );
        add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'addDefaultCityCustomField') );
        add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'addDefaultWarehouseCustomField') );
        add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'addDefaultStreetCustomField') );
    }

    public function addDefaultStreetCustomField( $order )
    {
        // Add 'np_street_name' custom field on 'Edit order' admin page
        $order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;

        if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled())
        {
            $streetName = $order->get_meta('nova_poshta_region');
            if ( $streetName )
            {
                if(!$order->get_meta('np_street_name'))
                {
                    $order->add_meta_data( 'np_street_name', $streetName );
                }

                $order->save();
            }
        }
        else
        {
            $streetName = get_post_meta( $order_id, 'nova_poshta_region', true );
            if ( $streetName )
            {
                if(!get_post_meta( $order_id, 'np_street_name', true ))
                {
                   add_post_meta( $order_id, 'np_street_name', $streetName, true ); 
                }
            }
        }
    }

    public function addDefaultRegionCustomField( $order ) {
        // Add 'np_region_ref' custom field on 'Edit order' admin page
        $order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;

        if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled())
        {
            $order = wc_get_order( $order_id );

            $regionRef = $order->get_meta('billing_nova_poshta_region');
            if ( $regionRef )
            {
                if(!$order->get_meta('np_region_ref'))
                {
                    $order->add_meta_data( 'np_region_ref', $regionRef );
                }

                $order->save();
            }
        }
        else
        {
            $regionRef = get_post_meta( $order_id, 'billing_nova_poshta_region', true );
            if ( $regionRef )
            {
                if(!get_post_meta( $order_id, 'np_region_ref', true ))
                {
                    add_post_meta( $order_id, 'np_region_ref', $regionRef, true );
                }
            }
        }
    }

    public function addDefaultCityCustomField( $order ) {
        // Add 'np_city_ref' custom field on 'Edit order' admin page
        $order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;

        if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled())
        {
            $order = wc_get_order( $order_id );

            $cityRef = $order->get_meta('_billing_nova_poshta_city');
            if ( $cityRef )
            {
                if(!$order->get_meta('np_city_ref'))
                {
                    $order->add_meta_data( 'np_city_ref', $cityRef );
                }

                $order->save();
            }
        }
        else
        {
            $cityRef = get_post_meta( $order_id, '_billing_nova_poshta_city', true );
            if ( $cityRef )
            {
                if(!get_post_meta( $order_id, 'np_city_ref', true ))
                {
                    add_post_meta( $order_id, 'np_city_ref', $cityRef, true );
                }
            }
        }
    }

    public function addDefaultWarehouseCustomField( $order ) {
        // Add 'np_warehouse_ref' custom field on 'Edit order' admin page
        $order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;

        if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled())
        {
            $order = wc_get_order( $order_id );

            $cityRef = $order->get_meta('_billing_nova_poshta_warehouse');
            if ( $cityRef )
            {
                if(!$order->get_meta('np_warehouse_ref'))
                {
                    $order->add_meta_data( 'np_warehouse_ref', $cityRef );
                }

                $order->save();
            }
        }
        else
        {
            $cityRef = get_post_meta( $order_id, '_billing_nova_poshta_warehouse', true );
            if ( $cityRef )
            {
                if(!get_post_meta( $order_id, 'np_warehouse_ref', true ))
                {
                    add_post_meta( $order_id, 'np_warehouse_ref', $cityRef, true );
                }
            }
        }
    }

    public function displayShippingPhoneOnThankyou($order_id) {
        $order = wc_get_order( $order_id );
        $order_item_shipping = $order->get_data()['shipping_lines'];
        foreach ( $order_item_shipping as $key => $value ) {
            $is_nova_poshta_shipping_method = ( 'nova_poshta_shipping_method' == $value->get_data()['method_id'] ) ? true: false;
        }

        if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled())
        {
            $is_shipping_phone = ( ! empty( $order->get_shipping_phone() )
            ? sanitize_text_field( $order->get_shipping_phone() )
            : false );

            $streetName = $order->get_meta('nova_poshta_region');
            if ( $streetName )
            {
                if(!$order->get_meta('np_street_name'))
                {
                    $order->add_meta_data( 'np_street_name', $cityRef );
                }
            }

            $regionRef = $order->get_meta('billing_nova_poshta_region');
            if ( $regionRef )
            {
                if(!$order->get_meta('np_region_ref'))
                {
                    $order->add_meta_data( 'np_region_ref', $cityRef );
                }            
            }
            
            $cityRef = $order->get_meta('_billing_nova_poshta_city');
            if ( $cityRef )
            {
                if(!$order->get_meta('np_city_ref'))
                {
                    $order->add_meta_data( 'np_city_ref', $cityRef );
                }
            }

            $cityRef = $order->get_meta('_billing_nova_poshta_warehouse');
            if ( $cityRef )
            {
                if(!$order->get_meta('np_warehouse_ref'))
                {
                    $order->add_meta_data( 'np_warehouse_ref', $cityRef );
                }
            }

            $order->save();
        }
        else
        {
            $is_shipping_phone = ( ! empty( get_post_meta( $order_id, 'shipping_phone', true ) )
            ? sanitize_text_field( get_post_meta( $order_id, 'shipping_phone', true ) )
            : false );

            $streetName = get_post_meta( $order_id, 'nova_poshta_region', true );
            if ( $streetName )
            {
                if(!get_post_meta( $order_id, 'np_street_name', true ))
                {
                    add_post_meta( $order_id, 'np_street_name', $streetName, true );
                }
            }

            $regionRef = get_post_meta( $order_id, 'billing_nova_poshta_region', true );
            if ( $regionRef )
            {
                if(!get_post_meta( $order_id, 'billing_nova_poshta_region', true ))
                {
                    add_post_meta( $order_id, 'np_region_ref', $regionRef, true );
                }
            }

            $cityRef = get_post_meta( $order_id, '_billing_nova_poshta_city', true );
            if ( $cityRef )
            {
                if(!get_post_meta( $order_id, 'np_city_ref', true ))
                {
                    add_post_meta( $order_id, 'np_city_ref', $cityRef, true );
                }
            }

            $cityRef = get_post_meta( $order_id, '_billing_nova_poshta_warehouse', true );
            if ( $cityRef )
            {
                if(!get_post_meta( $order_id, 'np_warehouse_ref', true ))
                {
                    add_post_meta( $order_id, 'np_warehouse_ref', $cityRef, true );
                }
            }
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function saveNovaPoshtaOptions()
    {
        if ( NPttn()->isPost() && NPttn()->isNPttn() && NPttn()->isCheckout() ||
            NPttnPM()->isPost() && NPttnPM()->isNPttnPM() && NPttnPM()->isCheckoutPoshtomat() ) {
            // Nova Poshta on warehouse or Nova Poshta on poshtomat
            $location = $this->getLocation();
            $region = ArrayHelper::getValue($_POST, Region::key($location));
            $city = ArrayHelper::getValue($_POST, City::key($location));
            $warehouse = ArrayHelper::getValue($_POST, Warehouse::key($location));

            $this->customer->setMetadata('nova_poshta_region', $region, $location);
            $this->customer->setMetadata('nova_poshta_city', $city, $location);
            $this->customer->setMetadata('nova_poshta_warehouse', $warehouse, $location);
        }
    }

    /**
     * Filter for hook woocommerce_shipping_init
     * @param $fields
     * @return mixed
     */
    public function maybeDisableDefaultShippingMethods($fields)
    {
        if ( NPttn()->isPost() && NPttn()->isNPttn() && NPttn()->isCheckout() ||
                NPttnPM()->isPost() && NPttnPM()->isNPttnPM() && NPttnPM()->isCheckoutPoshtomat() ) {
            // Nova Poshta on warehouse or Nova Poshta on poshtomat
            $fields = apply_filters('nova_poshta_disable_default_fields', $fields);
            $fields = apply_filters('nova_poshta_disable_nova_poshta_fields', $fields);
        } else {
            $location = $this->getLocation();
            $fields[$location][$location . '_postcode']['required'] = false;
            $fields[$location][$location . '_state']['required'] = false;
            $fields[$location][$location . '_address_1']['required'] = false;
            $fields[$location][$location . '_mrkvnp_street']['required'] = false;
            $fields[$location][$location . '_mrkvnp_house']['required'] = false;
            $fields['shipping']['shipping_mrkvnp_street']['required'] = false;
            $fields['shipping']['shipping_mrkvnp_house']['required'] = false;
        }
        return $fields;
    }

    /**
     * Hook for adding nova poshta billing fields
     * @param array $fields
     * @return array
     */
    public function addNovaPoshtaBillingFields($fields)
    {
        return $this->addNovaPoshtaFields($fields, Area::BILLING);
    }

    /**
     * Hook for adding nova poshta shipping fields
     * @param array $fields
     * @return array
     */
    public function addNovaPoshtaShippingFields($fields)
    {
        return $this->addNovaPoshtaFields( $fields, Area::SHIPPING );
    }

    /**
     * Update the order meta with field value
     * @param int $orderId
     */
    public function updateOrderMeta($orderId)
    {
        //address shipping method address_trigger
        $billing_city = "";
        if ( isset( $_POST['billing_city'] ) ) {
            $billing_city = $_POST['billing_city'];
        }
        $billing_address = "";
        if ( isset( $_POST['billing_address_1'] ) ) {
            $billing_address = $_POST['billing_address_1'];
        }

        $order = wc_get_order( $orderId );

        if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled())
        {
            if ( ! $order->get_meta('_billing_city')) {
                $order->update_meta_data( '_billing_city', $billing_city );
                $order->update_meta_data( '_billing_address_1', $billing_address );
            }
            if ( ! $order->get_meta('_shipping_city')) {
                $order->update_meta_data( '_shipping_city', $billing_city );
                $order->update_meta_data( '_shipping_address_1', $billing_address );
            }

            $order->save();
        }
        else{
            if ( ! get_post_meta($orderId, '_billing_city' ) ) {
                update_post_meta($orderId, '_billing_city', $billing_city);
                update_post_meta($orderId, '_billing_address_1', $billing_address);
            }
            if ( ! get_post_meta($orderId, '_shipping_city'  ) ) {
                update_post_meta($orderId, '_shipping_city', $billing_city);
                update_post_meta($orderId, '_shipping_address_1', $billing_address);
            }
        }

        if ( NPttn()->isNPttn() && NPttn()->isCheckout() ||
                NPttnPM()->isNPttnPM() && NPttnPM()->isCheckoutPoshtomat() ) {
            // Nova Poshta on warehouse or Nova Poshta on poshtomat
            $fieldGroup = $this->getLocation();

            $regionKey = Region::key($fieldGroup);
            $regionRef = isset( $_POST['npregionref'] ) ? sanitize_text_field($_POST['npregionref']) : '';
            $area = new Region($regionRef);
            if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled())
            {
                $order->update_meta_data( '_' . $fieldGroup . '_state', $area->description );
            }
            else
            {
               update_post_meta($orderId, '_' . $fieldGroup . '_state', $area->description); 
            }
            

            $cityKey = City::key($fieldGroup);

            $cityRef = isset($_POST['npcityref']) ? sanitize_text_field($_POST['npcityref']) : sanitize_text_field($_POST[$cityKey]);
            $city = new City($cityRef);

            if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled())
            {
                $order->update_meta_data( '_' . $fieldGroup . '_city', $city->description );
            }
            else
            {
                update_post_meta($orderId, '_' . $fieldGroup . '_city', $city->description);
            }

            $warehouseKey = Warehouse::key($fieldGroup);
            $warehouseRef = isset($_POST['npwhref']) ? sanitize_text_field($_POST['npwhref']) : sanitize_text_field($_POST[$warehouseKey]);

            if ( NPttn()->isNPttn() && NPttn()->isCheckout() ) $warehouse = new Warehouse($warehouseRef);
            if ( NPttnPM()->isNPttnPM() && NPttnPM()->isCheckoutPoshtomat() ) $warehouse = new Poshtomat($warehouseRef);

            if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled())
            {
                $order->update_meta_data('_' . $fieldGroup . '_address_1', $warehouse->description );
            }
            else
            {
               update_post_meta($orderId, '_' . $fieldGroup . '_address_1', $warehouse->description); 
            }
            
            if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled())
            {
                //TODO this part should be refactored
                $shippingFieldGroup = Area::SHIPPING;
                if ($this->shipToDifferentAddress()) {
                    $order->update_meta_data( '_' . Region::key($shippingFieldGroup), $area->ref );
                    $order->update_meta_data( '_' . City::key($shippingFieldGroup), $city->ref );
                    $order->update_meta_data( '_' . Warehouse::key($shippingFieldGroup), $warehouse->ref );
                    $order->update_meta_data( '_' . $fieldGroup . '_state', $area->description );
                } else {
                    $order->update_meta_data( '_' . $fieldGroup . '_state', $area->description );
                    $order->update_meta_data( '_' . $shippingFieldGroup . '_state', $area->description );
                    $order->update_meta_data( '_' . $shippingFieldGroup . '_city', $city->description );
                    $order->update_meta_data( '_' . $shippingFieldGroup . '_address_1', $warehouse->description );
                }

                $deliveryprice = isset( $_POST['deliveryprice'] ) ? $_POST['deliveryprice'] : '';
                $order->update_meta_data('deliveryprice', $deliveryprice );

                $shipping_phone = isset( $_POST['shipping_phone'] ) ? sanitize_text_field( $_POST['shipping_phone'] ) : '';
                $order->update_meta_data('shipping_phone', $shipping_phone );;
            }
            else
            {
                //TODO this part should be refactored
                $shippingFieldGroup = Area::SHIPPING;
                if ($this->shipToDifferentAddress()) {
                    update_post_meta($orderId, '_' . Region::key($shippingFieldGroup), $area->ref);
                    update_post_meta($orderId, '_' . City::key($shippingFieldGroup), $city->ref);
                    update_post_meta($orderId, '_' . Warehouse::key($shippingFieldGroup), $warehouse->ref);
                    update_post_meta($orderId, '_' . $fieldGroup . '_state', $area->description);
                } else {
                    update_post_meta($orderId, '_' . $fieldGroup . '_state', $area->description);
                    update_post_meta($orderId, '_' . $shippingFieldGroup . '_state', $area->description);
                    update_post_meta($orderId, '_' . $shippingFieldGroup . '_city', $city->description);
                    update_post_meta($orderId, '_' . $shippingFieldGroup . '_address_1', $warehouse->description);
                }

                $deliveryprice = isset( $_POST['deliveryprice'] ) ? $_POST['deliveryprice'] : '';
                update_post_meta( $orderId, 'deliveryprice', $deliveryprice );

                $shipping_phone = isset( $_POST['shipping_phone'] ) ? sanitize_text_field( $_POST['shipping_phone'] ) : '';
                update_post_meta( $orderId, 'shipping_phone', $shipping_phone );
            }

            $order->save();
        }

    }

    /**
     * @param array $packages
     * @return array
     */
    public function updatePackages(array $packages)
    {
        if (false) {
            $location = $this->getLocation();
            $warehouse = $this->customer->getMetadata('nova_poshta_warehouse', $location);
            $city = $this->customer->getMetadata('nova_poshta_city', $location);

            $cii = new City($city);

            if (get_locale() == 'ru_RU') {
                $desc1 = $cii->content->description_ru;
            } else {
                if (isset($cii->content->description)) {
                    $desc1 = $cii->content->description;
                } else {
                    $desc1 = '';
                }
            }

            $region = $this->customer->getMetadata('nova_poshta_region', $location);
            $wai = new Warehouse($warehouse);
            if (get_locale() == 'ru_RU') {
                $desc2 = $wai->content->description_ru;
            } else {
                if (isset($wai->content->description)) {
                    $desc2 = $wai->content->description;
                } else {
                    $desc2 = '';
                }
            }
            foreach ($packages as &$package) {
                $package['destination']['address_1'] = $desc2; //$warehouse;
                $package['destination']['city'] = $desc1; //$city;
                $package['destination']['state'] = $region; //$region;
            }
        }
        return $packages;
    }

    /**
     * @param array $fields
     * @return array
     */
    public function disableNovaPoshtaFields($fields)
    {
        $location = $this->shipToDifferentAddress() ? Area::BILLING : Area::SHIPPING;

        $fields[$location][$location . '_state']['required'] = false;

        $fields[$location][$location . '_state']['required'] = false;
        $fields[$location][$location . '_postcode']['required'] = false;
        $fields[$location][$location.'_address_1']['required'] = false;
        $fields[$location][$location.'_city']['required'] = false;
        $fields[$location][$location.'_mrkvnp_street']['required'] = false;
        $fields[$location][$location.'_mrkvnp_house']['required'] = false;
        $fields[$location][$location.'_mrkvnp_patronymics']['required'] = false;

        $fields[$location][Region::key($location)]['required'] = false;
        $fields[$location][City::key($location)]['required'] = false;
        $fields[$location][Warehouse::key($location)]['required'] = false;

        return $fields;
    }

    /**
     * @param array $fields
     * @return array
     */
    public function disableDefaultFields($fields)
    {
        $location = $this->getLocation();
        if (array_key_exists($location . '_state', $fields[$location])) {
            $fields[$location][$location . '_state']['required'] = false;
        }
        if (array_key_exists($location . '_city', $fields[$location])) {
            $fields[$location][$location . '_city']['required'] = false;
        }
        if (array_key_exists($location . '_address_1', $fields[$location])) {
            $fields[$location][$location . '_address_1']['required'] = false;
        }
        if (array_key_exists($location . '_postcode', $fields[$location])) {
            $fields[$location][$location . '_postcode']['required'] = false;
        }
        if (array_key_exists($location . '_mrkvnp_street', $fields[$location])) {
            $fields[$location][$location . '_mrkvnp_street']['required'] = false;
        }
        if (array_key_exists($location . '_mrkvnp_patronymics', $fields[$location])) {
            $fields[$location][$location . '_mrkvnp_patronymics']['required'] = false;
        }
        return $fields;
    }

    /**
     * Get address type which stores nova poshta options: either shipping or billing
     * @return string
     */
    public function getLocation()
    {
        return $this->shipToDifferentAddress() ? Area::SHIPPING : Area::BILLING;
    }

    /**
     * @return bool
     */
    public function shipToDifferentAddress()
    {
        $shipToDifferentAddress = isset($_POST['ship_to_different_address']);

        if ( isset( $_POST['shiptobilling'] ) ) {
            _deprecated_argument('WC_Checkout::process_checkout()', '2.1', 'The "shiptobilling" field is deprecated. The template files are out of date');
            $shipToDifferentAddress = !$_POST['shiptobilling'];
        }

        // Ship to billing option only
        if (wc_ship_to_billing_address_only()) {
            $shipToDifferentAddress = false;
        }
        return $shipToDifferentAddress;
    }

    /**
     * Check Woocommerce version, does it satisfy code requirements
     * @param string $version minimum version, lower versions of Woocommerce are legacy
     * @return bool
     */
    public function isLegacyWoocommerce(/** @noinspection PhpUnusedParameterInspection */
        $version = '3.0'
    )
    {
        //TODO compare with woocommerce version
        return !method_exists(WC()->customer, 'set_billing_address_1');
    }

    /**
     * @return string
     */
    public function getDefaultRegion()
    {
        $location = $this->getLocation();
        return $this->customer->getMetadata('nova_poshta_region', $location);
    }

    /**
     * @return string
     */
    public function getDefaultCity()
    {
        return $this->customer->getMetadata('nova_poshta_city', Area::SHIPPING);
    }

    /**
     * @return string
     */
    public function getDefaultWarehouse()
    {
        return $this->customer->getMetadata('nova_poshta_warehouse', Area::SHIPPING);
    }

    public function getDefaultStreet()
    {
        return $this->customer->getMetadata('nova_poshta_street', Area::BILLING);
    }

    /**
     * @return string
     */
    public function getDefaultPoshtomat()
    {
        return $this->customer->getMetadata('nova_poshta_poshtomat', Area::SHIPPING);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    protected function getIsCheckout()
    {
        if (function_exists('is_checkout')) {
            return is_checkout();
        } else {
            //for backward compatibility with woocommerce 2.x.x
            global $post;
            $checkoutPageId = get_option('woocommerce_checkout_page_id');
            $pageId = ArrayHelper::getValue($post, 'ID', null);
            return $pageId && $checkoutPageId && ($pageId == $checkoutPageId);
        }
    }

    /**
     * @return Customer
     */
    protected function getCustomer()
    {
        return Customer::instance();
    }

    /**
     * @param array $fields
     * @param string $location
     * @return array
     */
    private function addNovaPoshtaFields($fields, $location)
    {
        // if ( 'uk' !== get_locale() ) return $fields;
    	if(isset($_COOKIE['shipping_country'])) {
    	    $shipping_country = $_COOKIE['shipping_country'];
    	}

        $area = $this->customer->getMetadata('nova_poshta_region', $location);
        $city = $this->customer->getMetadata('nova_poshta_city', $location);
        $street = $this->customer->getMetadata('nova_poshta_street', $location);
        $required = NPttn()->isGet() ?: (NPttn()->isNPttn() || NPttnPM()->isNPttnPM() && NPttn()->isCheckout());
        error_log('$required');error_log($required);

        $value_for_checkout_selects = esc_attr(get_option('morkvanp_checkout_count', '3fields'));

        $warehouse_label = __( 'Відділення', NOVA_POSHTA_TTN_DOMAIN );
        if ( NPttnPM()->isNPttnPM() && NPttnPM()->isCheckoutPoshtomat() || NPttnPM()->isPost() ) {
            $warehouse_label = __( 'Поштомат', NOVA_POSHTA_TTN_DOMAIN );
        }

        if ('shipping' == $location) {
            $fields['shipping_phone'] = array(
                'label'        => __('Phone', 'woocommerce'),
                'type'         => 'text',
                'required'     => true,
                'class'        => array('form-row-wide'),
                'priority'     => 25,
                'clear'        => true
            );
        }

        $region_name = __('Region', NOVA_POSHTA_TTN_DOMAIN);
        $city_name = __('City', NOVA_POSHTA_TTN_DOMAIN);

        $choose_region_text = __('Choose region', NOVA_POSHTA_TTN_DOMAIN);
        $choose_city_text = __('Choose city', NOVA_POSHTA_TTN_DOMAIN);

        if (get_locale() == 'uk_UA'){
            $region_name = __('Область', NOVA_POSHTA_TTN_DOMAIN);
            $city_name = __('Місто', NOVA_POSHTA_TTN_DOMAIN);

            $choose_region_text = __('Оберіть область', NOVA_POSHTA_TTN_DOMAIN);
            $choose_city_text = __('Оберіть місто', NOVA_POSHTA_TTN_DOMAIN);
        }

        if ( $value_for_checkout_selects == '3fields' ) {
            $factory = AreaRepositoryFactory::instance();
            $fields[Region::key($location)] = [
                'label' => $region_name,
                'type' => 'select',
                'default' => '',
                'options' => OptionsHelper::getList($factory->regionRepo()->findAll(), true),
                'class' => array(),
                'priority'     => 120,
                'custom_attributes' => array(),
                'placeholder' => $choose_region_text
            ];

            $fields[City::key($location)] = [
                'label' => $city_name,
                'type' => 'select',
                'required' => $required,
                'options' => OptionsHelper::getList($factory->cityRepo()->findByParentRefAndNameSuggestion($area, true)),
                'class' => array(),
                'priority'     => 122,
                'value' => '',
                'custom_attributes' => array(),
                'placeholder' => $choose_city_text,
            ];
            $warehouse_options = OptionsHelper::getList( $factory->warehouseRepo()->findByParentRefAndNameSuggestion($city) );
            $fields[Warehouse::key($location)] = [
                'label' => $warehouse_label,
                'type' => 'select',
                'required' => $required,
                'options' => $warehouse_options,
                'class' => array(),
                'priority'     => 124,
                'value' => '',
                'custom_attributes' => array(),
                'placeholder' => __( 'Choose an option', NOVA_POSHTA_TTN_DOMAIN ),
            ];
        } elseif ( $value_for_checkout_selects == '2fields' ) {
              $fields[City::key($location)] = [
                'label' => __('City', NOVA_POSHTA_TTN_DOMAIN),
                'type' => 'select',
                'required' => $required,
                'options' => OptionsHelper::getList($factory->cityRepo()->findAll()),
                'class' => array(),
                'value' => '',
                'custom_attributes' => array(),
                'placeholder' => __('Choose city', NOVA_POSHTA_TTN_DOMAIN),
            ];
            $fields[Warehouse::key($location)] = [
                'label' => __('Nova Poshta Warehouse (#)', NOVA_POSHTA_TTN_DOMAIN),
                'type' => 'select',
                'required' => $required,
                'options' => OptionsHelper::getList($factory->warehouseRepo()->findByParentRefAndNameSuggestion($city)),
                'class' => array(),
                'value' => '',
                'custom_attributes' => array(),
                'placeholder' => __('Choose warehouse', NOVA_POSHTA_TTN_DOMAIN),
            ];
        } elseif ( $value_for_checkout_selects == '2fieldsdb' ) {
            $fields[CityNP::key($location)] = [
              'label' => __('City', NOVA_POSHTA_TTN_DOMAIN),
              'type' => 'text',
              'required' => $required,
              'class' => array(),
              'id' => 'billing_mrk_nova_poshta_city',
              'value' => '',
              'custom_attributes' => array('onkeyup' => "fetchCities()"),
              'placeholder' => __('Choose city', NOVA_POSHTA_TTN_DOMAIN),
              'autocomplete' => "off"
            ];
            $fields[WarehouseNP::key($location)] = [
                'label' => __('Nova Poshta Warehouse (#)', NOVA_POSHTA_TTN_DOMAIN),
                'type' => 'text',
                'required' => $required,
                'class' => array(),
                'id' => 'billing_mrk_nova_poshta_warehouse',
                'value' => '',
                'custom_attributes' => array('onkeyup' => "fetchWarehouses()"),
                'placeholder' => __('Choose warehouse', NOVA_POSHTA_TTN_DOMAIN),
                'autocomplete' => "off"
            ];
        }

        return $fields;
    }

    /**
     * NovaPoshta constructor.
     *
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

/**
 * Class CityNP
 * @package plugins\NovaPoshta\classes
 */
class CityNP extends City
{

    /**
     * @return string
     */
    protected static function _key()
    {
        return 'mrk_nova_poshta_city';
    }

    /**
     * @return AbstractAreaRepository
     */
    protected function getRepository()
    {
        return AreaRepositoryFactory::instance()->cityRepo();
    }

}

/**
 * Class WarehouseNP
 * @package plugins\NovaPoshta\classes
 */
class WarehouseNP extends Warehouse
{

    /**
     * @return string
     */
    protected static function _key()
    {
        //return '_warehouse';
        return 'mrk_nova_poshta_warehouse';
    }

    /**
     * @return AbstractAreaRepository
     */
    protected function getRepository()
    {
        return AreaRepositoryFactory::instance()->warehouseRepo();
    }
}
