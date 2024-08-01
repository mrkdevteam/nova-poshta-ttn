<?php

namespace plugins\NovaPoshta\classes;

use plugins\NovaPoshta\classes\base\ArrayHelper;
use plugins\NovaPoshta\classes\base\Base;
use plugins\NovaPoshta\classes\base\OptionsHelper;
use plugins\NovaPoshta\classes\repository\AreaRepositoryFactory;
use plugins\NovaPoshta\classes\Checkout;
use plugins\NovaPoshta\classes\City;
use plugins\NovaPoshta\classes\Warehouse;
use plugins\NovaPoshta\classes\Poshtomat;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Class Calculator
 * @property bool isCheckout
 * @property Customer $customer
 * @package plugins\NovaPoshta\classes
 */
class CheckoutAddress extends Checkout
{

    /**
     * @var CheckoutAddress
     */
    private static $_instance;

    /**
     * @return CheckoutAddress
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
        add_filter('woocommerce_checkout_fields', array($this, 'maybeDisableDefaultShippingMethods'));
        add_filter( 'woocommerce_default_address_fields', array( $this, 'addMrkvnpFields' ) );

        add_action('woocommerce_checkout_process', array($this, 'saveNovaPoshtaAddressOptions'), 10, 2);
        add_action('woocommerce_checkout_update_order_meta', array($this, 'updateOrderMeta'), 10);

        add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'addDefaultStreetCustomField') );
        add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'addDefaultShippingPhoneCustomField') );
        add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'addDefaultPatronymicsBillingCustomField') );
        add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'addDefaultPatronymicsShippingCustomField') );
    }

    public function addMrkvnpFields($fields)
    {
        $street_name = __('Street', NOVA_POSHTA_TTN_DOMAIN);
        $house_name = __('House number', NOVA_POSHTA_TTN_DOMAIN);
        $falt_name = __('Flat', NOVA_POSHTA_TTN_DOMAIN);
        $middle_name = __('Middle name', NOVA_POSHTA_TTN_DOMAIN);

        if (get_locale() == 'uk_UA'){
            $street_name = __('Вулиця', NOVA_POSHTA_TTN_DOMAIN);
            $house_name = __('Номер будинку', NOVA_POSHTA_TTN_DOMAIN);
            $falt_name = __('Квартира', NOVA_POSHTA_TTN_DOMAIN);
            $middle_name = __('По батькові', NOVA_POSHTA_TTN_DOMAIN);
        }

        $fields['mrkvnp_street']   = array(
            'label'        => $street_name,
            'required'     => false,
            'class'        => array( 'form-row-wide', 'my-custom-class' ),
            'priority'     => 130,
            'placeholder'  => __( 'Введіть перші три літери..', NOVA_POSHTA_TTN_DOMAIN ),
        );
        $fields['mrkvnp_house'] = array(
            'label'        => $house_name,
            'type'         => 'text',
            'required'     => false,
            'class'        => array('form-row-first'),
            'priority'     => 132,
            'clear'        => true
        );
        $fields['mrkvnp_flat'] = array(
            'label'        => $falt_name,
            'type'         => 'text',
            'required'     => false,
            'class'        => array('form-row-last'),
            'priority'     => 134,
            'clear'        => true
        );

        $patronymics_classes = array( 'form-row-wide', 'my-custom-class' );

        if(!get_option( 'mrkvnp_invoice_patranomic_disable' ))
        {
            if(!get_option( 'mrkvnp_invoice_patranomic_required' ))
            {
                $patronymics_classes[] = 'mrkv-np-not-required';
            }

            $fields['mrkvnp_patronymics']   = array(
                'label'        => $middle_name,
                'required'     => false,
                'class'        => $patronymics_classes,
                'priority'     => 136,
            );
        }

        return $fields;
    }

    public function addDefaultPatronymicsBillingCustomField($order)
    {
        $order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;

        if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled())
        {
            $order = wc_get_order( $order_id );

            if ( ! empty( $order->get_meta('_billing_mrkvnp_patronymics') ) ) {
                $patronymics = $order->get_meta('_billing_mrkvnp_patronymics');
            }
            if ( isset( $patronymics ) ) {
                if(!$order->get_meta('np_patronymics_name'))
                {
                    $order->add_meta_data( 'np_patronymics_name', $patronymics );
                }
                echo '<p><strong style="margin-left: 3px;">' . __( 'По батькові', NOVA_POSHTA_TTN_DOMAIN ) .
                    ':</strong> ' . $patronymics . '</p>';
            }

            $order->save();
        }
        else
        {
            if ( ! empty( get_post_meta( $order_id, '_billing_mrkvnp_patronymics', true ) ) ) {
                $patronymics = get_post_meta( $order_id, '_billing_mrkvnp_patronymics', true );
            }
            if ( isset( $patronymics ) ) {
                if(!get_post_meta( $order_id, 'np_patronymics_name', true ))
                {
                    add_post_meta( $order_id, 'np_patronymics_name', $patronymics, true );    
                }
                
                echo '<p><strong style="margin-left: 3px;">' . __( 'По батькові', NOVA_POSHTA_TTN_DOMAIN ) .
                    ':</strong> ' . $patronymics . '</p>';
            }
        }
    }

    public function addDefaultPatronymicsShippingCustomField($order)
    {
        $order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;

        if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled())
        {
            $order = wc_get_order( $order_id );

            if ( ! empty( $order->get_meta('_shipping_mrkvnp_patronymics') ) ) {
                $patronymics = $order->get_meta('_shipping_mrkvnp_patronymics');
            }
            if ( isset( $patronymics ) && ! empty( $patronymics ) ) {
                if(!$order->get_meta('np_patronymics_name'))
                {
                    $order->add_meta_data( 'np_patronymics_name', $patronymics );
                }
                
                echo '<p><strong style="margin-left: 3px;">' . __( 'По батькові', NOVA_POSHTA_TTN_DOMAIN ) .
                    ':</strong> ' . $patronymics . '</p>';
            }

            $order->save();
        }
        else
        {
            if ( ! empty( get_post_meta( $order_id, '_shipping_mrkvnp_patronymics', true ) ) ) {
                $patronymics = get_post_meta( $order_id, '_shipping_mrkvnp_patronymics', true );
            }
            if ( isset( $patronymics ) && ! empty( $patronymics ) ) {
                if(!get_post_meta( $order_id, 'np_patronymics_name', true ))
                {
                    add_post_meta( $order_id, 'np_patronymics_name', $patronymics, true );    
                }
                
                echo '<p><strong style="margin-left: 3px;">' . __( 'По батькові', NOVA_POSHTA_TTN_DOMAIN ) .
                    ':</strong> ' . $patronymics . '</p>';
            }
        }
    }

    public function addDefaultStreetCustomField($order)
    {
        // Add 'np_street_name' custom field on 'Edit order' admin page
        $order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;

        if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled())
        {
            $order = wc_get_order( $order_id );

            if ( ! empty( $order->get_meta('_billing_mrkvnp_street') ) ) {
                $streetName = $order->get_meta('_billing_mrkvnp_street');
            } else {
                $streetName = $order->get_meta('_shipping_mrkvnp_street');
            }
            if ( $streetName )
            {
                if(!$order->get_meta('np_street_name'))
                {
                    $order->add_meta_data( 'np_street_name', $streetName );
                }
            } 

            $order->save();
        }
        else
        {
            if ( ! empty( get_post_meta( $order_id, '_billing_mrkvnp_street', true ) ) ) {
                $streetName = get_post_meta( $order_id, '_billing_mrkvnp_street', true );
            } else {
                $streetName = get_post_meta( $order_id, '_shipping_mrkvnp_street', true );
            }
            if ( $streetName )
            {
                if(!get_post_meta( $order_id, 'np_street_name', true ))
                {
                    add_post_meta( $order_id, 'np_street_name', $streetName, true );
                }
            } 
        }
    }

    public function addDefaultRegionCustomField($order) {
        // Add 'np_region_ref' custom field on 'Edit order' admin page
        $order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;

        if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled())
        {
            $order = wc_get_order( $order_id );

            if ( ! empty( $order->get_meta('_billing_nova_poshta_city')) ) {
                $regionRef = $order->get_meta('_billing_nova_poshta_city');
            } else {
                $regionRef = $order->get_meta('_shipping_nova_poshta_city');
            }
            if ( $regionRef )
            {
                if(!$order->get_meta('np_region_ref'))
                {
                   $order->add_meta_data( 'np_region_ref', $regionRef ); 
                }
            } 

            $order->save();
        }
        else
        {
            if ( ! empty( get_post_meta( $order_id, '_billing_nova_poshta_city', true ) ) ) {
                $regionRef = get_post_meta( $order_id, '_billing_nova_poshta_city', true );
            } else {
                $regionRef = get_post_meta( $order_id, '_shipping_nova_poshta_city', true );
            }
            if ( $regionRef )
            {
                if(!get_post_meta( $order_id, 'np_region_ref', true ))
                {
                   add_post_meta( $order_id, 'np_region_ref', $regionRef, true ); 
                }
            } 
        }
    }

    public function addDefaultCityCustomField($order) {
        // Add 'np_city_ref' custom field on 'Edit order' admin page
        $order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;

        if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled())
        {
            $order = wc_get_order( $order_id );

            if ( ! empty( $order->get_meta('_billing_nova_poshta_city') ) ) {
                $cityRef = $order->get_meta('_billing_nova_poshta_city');
            } else {
                $cityRef = $order->get_meta('_shipping_nova_poshta_city');
            }
            if ( $cityRef )
            {
                if(!$order->get_meta('np_city_ref'))
                {
                    $order->add_meta_data( 'np_city_ref', $cityRef );
                }
            } 

            $order->save();
        }
        else
        {
            if ( ! empty( get_post_meta( $order_id, '_billing_nova_poshta_city', true ) ) ) {
                $cityRef = get_post_meta( $order_id, '_billing_nova_poshta_city', true );
            } else {
                $cityRef = get_post_meta( $order_id, '_shipping_nova_poshta_city', true );
            }
            if ( $cityRef )
            {
                if(!get_post_meta( $order_id, 'np_city_ref', true ))
                {
                    add_post_meta( $order_id, 'np_city_ref', $cityRef, true );
                }  
            } 
        }
    }

    public function addDefaultShippingPhoneCustomField($order) {
        // Add 'np_shipping_phone' custom field on 'Edit order' admin page
        $shippingPhone = '';
        $order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;

        if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled())
        {
            $order = wc_get_order( $order_id );

            if ( ! empty( $order->get_shipping_phone() ) ) {
                $shippingPhone = $order->get_shipping_phone();
            }
            if ( $shippingPhone )
            {
                if(!$order->get_meta('np_shipping_phone'))
                {
                    $order->add_meta_data( 'np_shipping_phone', $shippingPhone );
                }
            } 

            $order->save();
        }
        else
        {
            if ( ! empty( get_post_meta( $order_id, '_shipping_phone', true ) ) ) {
                $shippingPhone = get_post_meta( $order_id, '_shipping_phone', true );
            }
            if ( $shippingPhone )
            {
                if(!get_post_meta( $order_id, 'np_shipping_phone', true ))
                {
                    add_post_meta( $order_id, 'np_shipping_phone', $shippingPhone, true );
                }
            } 
        }
    }

    public function displayBillingPatronymicsInOrderMeta($order)
    {
        if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled())
        {
            if ( $order->get_meta('_billing_mrkvnp_patronymics') ) {
                echo '<p><strong>' . __( 'По батькові', NOVA_POSHTA_TTN_DOMAIN ) .':</strong><br> ' .
                    $order->get_meta('np_patronymics_name') . '</p>';
            }

            $order->save();
        }
        else
        {
            if ( get_post_meta( $order->get_id(), '_billing_mrkvnp_patronymics' ) ) {
                echo '<p><strong>' . __( 'По батькові', NOVA_POSHTA_TTN_DOMAIN ) .':</strong><br> ' .
                    get_post_meta( $order->get_id(), 'np_patronymics_name', true ) . '</p>';
            }
        }
    }

    public function displayShippingPatronymicsInOrderMeta($order)
    {
        if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled())
        {
            if ( $order->get_meta('_order_shipping') ) {
                echo '<p><strong>' . __( 'По батькові', NOVA_POSHTA_TTN_DOMAIN ) .':</strong><br> ' .
                    $order->get_meta('np_patronymics_name') . '</p>';
            }

            $order->save();
        }
        else
        {
            if ( get_post_meta( $order->get_id(), '_order_shipping' ) ) {
                echo '<p><strong>' . __( 'По батькові', NOVA_POSHTA_TTN_DOMAIN ) .':</strong><br> ' .
                    get_post_meta( $order->get_id(), 'np_patronymics_name', true ) . '</p>';
            }
        }
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
        return $this->addNovaPoshtaFields($fields, Area::SHIPPING);
    }

        /**
     * Update the order meta with field value
     * @param int $orderId
     */
    public function updateOrderMeta($orderId)
    {
        //address shipping method address_trigger
        $billing_region = "";
        if ( isset( $_POST['billing_nova_poshta_region'] ) ) {
            $billing_region = $_POST['billing_nova_poshta_region'];
        }

        $order = wc_get_order( $orderId );

        if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled())
        {
            if ( ! $order->get_meta('_state') ) {
                $order->update_meta_data( '_state', $billing_region );
            }
        }
        else
        {
            if ( ! get_post_meta($orderId, '_state' ) ) {
                update_post_meta($orderId, '_state', $billing_region);
            }
        }
        
        $billing_city = "";
        if ( isset( $_POST['billing_city'] ) ) {
            $billing_city = $_POST['billing_city'];
        }
        $billing_address = "";
        if ( isset( $_POST['billing_address_1'] ) ) {
            $billing_address = $_POST['billing_address_1'];
        }

        if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled())
        {
            if ( ! $order->get_meta('_billing_city') ) {
                $order->update_meta_data( '_billing_city', $billing_city );
                $order->update_meta_data( '_billing_address_1', $billing_address );
            }
            if ( ! $order->get_meta('_shipping_city') ) {
                $order->update_meta_data( '_shipping_city', $billing_city );
                $order->update_meta_data( '_shipping_address_1', $billing_address );
            }

            $order->save();
        }
        else
        {
            if ( ! get_post_meta($orderId, '_billing_city' ) ) {
                update_post_meta($orderId, '_billing_city', $billing_city);
                update_post_meta($orderId, '_billing_address_1', $billing_address);
            }
            if ( ! get_post_meta($orderId, '_shipping_city'  ) ) {
                update_post_meta($orderId, '_shipping_city', $billing_city);
                update_post_meta($orderId, '_shipping_address_1', $billing_address);
            }
        }
        

        if ( NPttnA()->isGet() ?: (NPttnA()->isANPttn() && NPttnA()->isCheckoutAddress()) ) {
            // Nova Poshta on address
            $fieldGroup = $this->getLocation();

            $regionKey = Region::key($fieldGroup);
            // $regionRef = isset( $_POST['npregionref'] ) ? sanitize_text_field($_POST['npregionref']) : '';
            $regionRef = isset( $_POST['npregionref'] ) ? sanitize_text_field($_POST['npregionref']) : '';
            $area = new Region($regionRef);

            if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled())
            {
                $order->update_meta_data( '_' . $fieldGroup . '_nova_poshta_region', $area->description );
                $order->update_meta_data( '_' . $fieldGroup . '_state', $area->description );
            }
            else
            {
                update_post_meta($orderId, '_' . $fieldGroup . '_nova_poshta_region', $area->description);
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

            $streetName = isset($_POST[$fieldGroup . '_mrkvnp_street'])
                ? sanitize_text_field($_POST[$fieldGroup . '_mrkvnp_street']) : '';
            $house = isset($_POST[$fieldGroup . '_mrkvnp_house'])
                ? sanitize_text_field($_POST[$fieldGroup . '_mrkvnp_house']) : '';
            $streetNameHouse = $streetName . ', ' . $house;

            if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled())
            {
                $order->update_meta_data( '_' . $fieldGroup . '_address_1', $streetNameHouse );
                
                $flat = isset($_POST[$fieldGroup . '_mrkvnp_flat'])
                    ? sanitize_text_field($_POST[$fieldGroup . '_mrkvnp_flat']) : '';

                $order->update_meta_data( '_' . $fieldGroup . '_address_2', $flat );
                

                $patronymics = isset( $_POST[$fieldGroup . '_mrkvnp_patronymics'] )
                    ? sanitize_text_field($_POST[$fieldGroup . '_mrkvnp_patronymics']) : '';

                $first_name = $order->get_meta('_' . $fieldGroup . '_first_name');
                $order->update_meta_data( '_' . $fieldGroup . '_mrkvnp_patronymics', $patronymics );

                //TODO this part should be refactored
                $shippingFieldGroup = Area::SHIPPING;
                if ( $this->shipToDifferentAddress() ) {
                    $order->update_meta_data( '_' . Region::key($shippingFieldGroup), $area->ref );
                    $order->update_meta_data( '_' . City::key($shippingFieldGroup), $city->ref );
                    $order->update_meta_data( '_' . $fieldGroup . '_address_1', $streetNameHouse );
                } else {
                    $order->update_meta_data( '_' . $fieldGroup . '_state', $area->description );
                    $order->update_meta_data( '_' . $fieldGroup . '_city', $city->description );
                    $order->update_meta_data( '_' . $fieldGroup . '_address_1', $streetNameHouse );
                    $order->update_meta_data( '_' . $fieldGroup . '_address_2', $flat );
                    $order->update_meta_data( '_' . $shippingFieldGroup . '_state', $area->description );
                    $order->update_meta_data( '_' . $shippingFieldGroup . '_city', $city->description );
                    $order->update_meta_data( '_' . $shippingFieldGroup . '_address_1', $streetNameHouse );
                    $order->update_meta_data( '_' . $shippingFieldGroup . '_address_2', $flat );
                }

                $deliveryprice = isset( $_POST['deliveryprice'] ) ? $_POST['deliveryprice'] : '';
                $order->update_meta_data( 'deliveryprice', $deliveryprice );

                $shipping_phone = isset( $_POST['shipping_phone'] ) ? sanitize_text_field( $_POST['shipping_phone'] ) : '';
                $order->update_meta_data( 'np_shipping_phone', $shipping_phone );
            }
            else
            {
                update_post_meta($orderId, '_' . $fieldGroup . '_address_1', $streetNameHouse);
                $flat = isset($_POST[$fieldGroup . '_mrkvnp_flat'])
                    ? sanitize_text_field($_POST[$fieldGroup . '_mrkvnp_flat']) : '';
                update_post_meta($orderId, '_' . $fieldGroup . '_address_2', $flat);

                $patronymics = isset( $_POST[$fieldGroup . '_mrkvnp_patronymics'] )
                    ? sanitize_text_field($_POST[$fieldGroup . '_mrkvnp_patronymics']) : '';
                $first_name = get_post_meta( $orderId, '_' . $fieldGroup . '_first_name', true );
                update_post_meta( $orderId, '_' . $fieldGroup . '_mrkvnp_patronymics', $patronymics );

                //TODO this part should be refactored
                $shippingFieldGroup = Area::SHIPPING;
                if ( $this->shipToDifferentAddress() ) {
                    update_post_meta($orderId, '_' . Region::key($shippingFieldGroup), $area->ref);
                    update_post_meta($orderId, '_' . City::key($shippingFieldGroup), $city->ref);
                    update_post_meta($orderId, '_' . $fieldGroup . '_address_1', $streetNameHouse);
                } else {
                    update_post_meta($orderId, '_' . $fieldGroup . '_state', $area->description);
                    update_post_meta($orderId, '_' . $fieldGroup . '_city', $city->description);
                    update_post_meta($orderId, '_' . $fieldGroup . '_address_1', $streetNameHouse);
                    update_post_meta($orderId, '_' . $fieldGroup . '_address_2', $flat);
                    update_post_meta($orderId, '_' . $shippingFieldGroup . '_state', $area->description);
                    update_post_meta($orderId, '_' . $shippingFieldGroup . '_city', $city->description);
                    update_post_meta($orderId, '_' . $shippingFieldGroup . '_address_1', $streetNameHouse);
                    update_post_meta($orderId, '_' . $shippingFieldGroup . '_address_2', $flat);
                }

                $deliveryprice = isset( $_POST['deliveryprice'] ) ? $_POST['deliveryprice'] : '';
                update_post_meta( $orderId, 'deliveryprice', $deliveryprice );

                $shipping_phone = isset( $_POST['shipping_phone'] ) ? sanitize_text_field( $_POST['shipping_phone'] ) : '';
                update_post_meta( $orderId, 'np_shipping_phone', $shipping_phone );
            }

            $order->save();
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function saveNovaPoshtaAddressOptions()
    {
        // Nova Poshta on address
        if ( NPttnA()->isPost() && NPttnA()->isANPttn() && NPttnA()->isCheckoutAddress() ) { 
            // Nova Poshta on address
            $location = $this->getLocation();

            if ( ! $_POST[$location . '_nova_poshta_region'] )
                wc_add_notice( __( '<b>Поле Область</b> - обов\'язкове поле.' ), 'error' );
            if ( ! $_POST[$location . '_nova_poshta_city'] )
                wc_add_notice( __( '<b>Поле Місто</b> - обов\'язкове поле.' ), 'error' );
            if ( ! $_POST[$location . '_mrkvnp_patronymics'] && get_option( 'mrkvnp_invoice_patranomic_required' ) && !get_option( 'mrkvnp_invoice_patranomic_disable' ))
                wc_add_notice( __( '<b>Поле По батькові</b> - обов\'язкове поле.' ), 'error' );
            if ( ! $_POST[$location . '_mrkvnp_street'] )
                wc_add_notice( __( '<b>Поле Вулиця</b> - обов\'язкове поле.' ), 'error' );
            if ( ! $_POST[$location . '_mrkvnp_house'] )
                wc_add_notice( __( '<b>Поле Номер будинку</b> - обов\'язкове поле.' ), 'error' );

            $region = ArrayHelper::getValue($_POST, Region::key($location));
            $city = ArrayHelper::getValue($_POST, City::key($location));
            $street = ArrayHelper::getValue($_POST, $location . '_nova_poshta_street');
            $house = ArrayHelper::getValue($_POST, $location . '_nova_poshta_house');
            $flat = ArrayHelper::getValue($_POST, $location . '_nova_poshta_flat');
            $patronymics = ArrayHelper::getValue($_POST, $location . '_mrkvnp_patronymics');

            $this->customer->setMetadata('nova_poshta_region', $region, $location);
            $this->customer->setMetadata('nova_poshta_city', $city, $location);
            $this->customer->setMetadata('nova_poshta_street', $street, $location);
            $this->customer->setMetadata('nova_poshta_house', $house, $location);
            $this->customer->setMetadata('nova_poshta_flat', $flat, $location);
            $this->customer->setMetadata('mrkvnp_patronymics', $patronymics, $location);
        }
    }

     public function maybeDisableDefaultShippingMethods($fields)
     {
        $location = $this->getLocation();
        if ( NPttnA()->isPost() && NPttnA()->isANPttn() && NPttnA()->isCheckoutAddress() ) {
            // Nova Poshta on address
            $fields = apply_filters('nova_poshta_disable_default_fields', $fields);
            $fields = apply_filters('nova_poshta_disable_nova_poshta_fields', $fields);
        }
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
        if (array_key_exists($location . '_address_2', $fields[$location])) {
            $fields[$location][$location . '_address_2']['required'] = false;
        }
        if (array_key_exists($location . '_postcode', $fields[$location])) {
            $fields[$location][$location . '_postcode']['required'] = false;
        }
        if (array_key_exists($location . '_state', $fields[$location])) {
            $fields[$location][$location . '_state']['required'] = false;
        }
        if (array_key_exists($location . '_country', $fields[$location])) {
            $fields[$location][$location . '_country']['required'] = false;
        }
        unset($fields['billing']['billing_country']);
        unset($fields['shipping']['shipping_country']);
        return $fields;
    }

    /**
     * @param array $fields
     * @return array
     */
    public function disableNovaPoshtaFields($fields)
    {
        $location = $this->shipToDifferentAddress() ? Area::BILLING : Area::SHIPPING;

        $fields[$location][$location . '_state']['required'] = false;
         unset( $fields['billing']['billing_state']['validate'] );

        $fields[$location][$location . '_mrkvnp_street']['required'] = false;
        $fields[$location][$location . '_mrkvnp_house']['required'] = false;
        $fields[$location][$location . '_mrkvnp_patronymics']['required'] = false;
        $fields[$location][$location . '_postcode']['required'] = false;
        $fields[$location][$location.'_address_1']['required'] = false;
        $fields[$location][$location.'_city']['required'] = false;

        $fields[$location][City::key($location)]['required'] = false;
        $fields[$location][Warehouse::key($location)]['required'] = false;
        $fields[$location][Region::key($location)]['required'] = false;

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

         if (isset($_POST['shiptobilling'])) {
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
     * @return string
     */
    public function getDefaultRegion()
    {
        return $this->customer->getMetadata('nova_poshta_region', Area::SHIPPING);
    }

    /**
     * @return string
     */
    public function getDefaultCity()
    {
        return $this->customer->getMetadata('nova_poshta_city', Area::SHIPPING);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    protected function getIsCheckoutAddress()
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
 * Class AddressNP
 * @package plugins\NovaPoshta\classes
 */
class AddressNP extends Region
{

    /**
     * @return string
     */
    protected static function _key()
    {
        return 'nova_poshta_region';
    }

    /**
     * @return AbstractAreaRepository
     */
    protected function getRepository()
    {
        return AreaRepositoryFactory::instance()->regionRepo();
    }
}
