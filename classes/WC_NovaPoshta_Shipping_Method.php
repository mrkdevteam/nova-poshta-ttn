<?php

 use plugins\NovaPoshta\classes\base\ArrayHelper;
 use plugins\NovaPoshta\classes\base\Options;
 use plugins\NovaPoshta\classes\Checkout;
 use plugins\NovaPoshta\classes\Customer;

/**
 * Class WC_NovaPoshta_Shipping_Method
 */
if ( ! class_exists( 'WC_NovaPoshta_Shipping_Method' ) ) :
    class WC_NovaPoshta_Shipping_Method extends WC_Shipping_Method
    {
         public function __construct($instance_id = 0)
         {
            $this->instance_id = absint( $instance_id );
            parent::__construct( $instance_id );
            $this->id = NOVA_POSHTA_TTN_SHIPPING_METHOD;
            $this->method_title = __( 'Nova Poshta Warehouse', NOVA_POSHTA_TTN_DOMAIN );
            $this->method_description = $this->getDescription();
            $this->rate = 0.00;

            $this->supports = array(
                    'shipping-zones',
                    'instance-settings',
                    'instance-settings-modal',
                );

            $this->init();

            // Get setting values
            $this->title = $this->get_option( 'title' );//$this->settings['title'];
            $this->enabled = true;

            $this->enabled = $this->get_option( 'enabled' );
            // $this->use_fixed_price_on_delivery = $this->get_option( 'use_fixed_price_on_delivery' );


        }

        /**
         * Init your settings
         *
         * @access public
         * @return void
         */
        function init()
        {
            $this->init_form_fields();
            $this->init_settings();
            // Save settings in admin if you have any defined
            add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
        }

        public function test($packages)
        {

            return $packages;
        }

        /**
         * Initialise Gateway Settings Form Fields
         */
        public function init_form_fields()
        {
            $this->instance_form_fields = array(
                'title' => array(
                    'title' => __('Nova Poshta', NOVA_POSHTA_TTN_DOMAIN),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', NOVA_POSHTA_TTN_DOMAIN),
                    'default' => __('Nova Poshta', NOVA_POSHTA_TTN_DOMAIN)
                ),

                Options::USE_SHIPPING_PRICE_ON_DELIVERY => array(
                    'title' => __('Enable Price for Delivery.', NOVA_POSHTA_TTN_DOMAIN),
                    'label' => __('If checked, shipping price will be add for delivery.', NOVA_POSHTA_TTN_DOMAIN),
                    'type' => 'checkbox',
                    'default' => 'no',
                    'description' => '',
                ),
                Options::USE_FIXED_PRICE_ON_DELIVERY => array(
                    'title' => __('Set Fixed Price for Delivery.', NOVA_POSHTA_TTN_DOMAIN),
                    'label' => __('If checked, fixed price will be set for delivery.', NOVA_POSHTA_TTN_DOMAIN),
                    'type' => 'checkbox',
                    'default' => 'no',
                    'description' => 'Увага: мінімальна сума для безкоштовної доставки не буде враховуватися',
                ),
                Options::FIXED_PRICE => array(
                    'title' => __('Fixed price', NOVA_POSHTA_TTN_DOMAIN),
                    'type' => 'text',
                    'description' => __('Delivery Fixed price.', NOVA_POSHTA_TTN_DOMAIN),
                    'default' => 0.00
                ),

                Options::FREE_SHIPPING_MIN_SUM => array(
                    'title' => __('Мінімальна сума для безкоштовної доставки', NOVA_POSHTA_TTN_DOMAIN),
                    'type' => 'text',
                    'placeholder' => 'Вкажіть суму цифрами',
                    'description' => __('Введіть суму, при досягненні якої, доставка для покупця буде безкоштовною', NOVA_POSHTA_TTN_DOMAIN),
                ),
                Options::FREE_SHIPPING_TEXT => array(
                    'title' => __('Текст при безкоштовній доставці', NOVA_POSHTA_TTN_DOMAIN),
                    'type' => 'text',
                    'placeholder' => 'Ваш текст',
                    'description' => __('Введіть текст, який замінить назву способу доставки при досягненні мінімальної суми замовлення<br>Наприклад: "БЕЗКОШТОВНО на відділення Нової Пошти".', NOVA_POSHTA_TTN_DOMAIN),
                ),

                'settings' => array(
                    'title' => __('', NOVA_POSHTA_TTN_DOMAIN),
                    'type' => 'hidden',
                    'description' => __('Решта налаштувань доступні за <a href="admin.php?page=morkvanp_plugin">посиланям</a>.', NOVA_POSHTA_TTN_DOMAIN),
                    'default' => __(' ', NOVA_POSHTA_TTN_DOMAIN)
                ),
            );
        }


        /**
         * calculate_shipping function.
         *
         * @access public
         *
         * @param array $package
         */
        public function calculate_shipping($package = array())
        {
            $rate = array(
                'id' => $this->id,
                'label' => $this->title,
                'cost' => 0.00,
                'calc_tax' => 'per_item'
            );

            $cartTotal = WC()->cart->get_subtotal();

            if(('no' != $this->get_option( Options::USE_SHIPPING_PRICE_ON_DELIVERY ))){
                    if(! ( 'no' == $this->get_option( Options::USE_FIXED_PRICE_ON_DELIVERY ) )){
                        $rate['cost'] = $this->get_option( Options::FIXED_PRICE );
                    }
            }

            if($this->get_option( Options::FREE_SHIPPING_MIN_SUM ) && $this->get_option( Options::FREE_SHIPPING_MIN_SUM ) <= $cartTotal ){
                $rate['cost'] = 0.00;
                $rate['label'] = ( null != $this->get_option( Options::FREE_SHIPPING_TEXT ) ) ? $this->get_option( Options::FREE_SHIPPING_TEXT ) : $this->title;
                add_filter( 'woocommerce_cart_shipping_method_full_label', array($this, 'mrkv_no_display_shipping_cost' ), 10, 2 );
            }

            $this->add_rate($rate);
        }

        public function convert_weight_unit() {

            $weight_unit  =  get_option('woocommerce_weight_unit');

            if ( 'g' == $weight_unit ) return 0.001;
            if ( 'kg' == $weight_unit ) return 1;
            if ( 'lbs' == $weight_unit ) return 0.45359;
            if ( 'oz' == $weight_unit ) return 0.02834;
        }

        /**
        * Changes shipping label on '₴0.00', when rate cost is equal 0.00.
        */
        public function mrkv_display_zero_shipping_cost($label, $method) {
            if ( 'nova_poshta_shipping_method' == $method->get_id() ) {
                if( $method->cost == 0.00 ) {
                    $currency_symbol = get_woocommerce_currency_symbol();
                    $label  = $method->get_label() . ': ' . $currency_symbol . '0.00';
                }
            }
            return $label;
        }

        /**
        * Changes shipping label on fixed price value.
        */
        public function mrkv_display_fixed_shipping_cost($label, $method) {
            if ( 'nova_poshta_shipping_method' == $method->get_id() ) {
                $currency_symbol = get_woocommerce_currency_symbol();
                $cost = $this->get_option( Options::FIXED_PRICE );
                $label  = $method->get_label() . ': ' . $currency_symbol . $cost;
            }
            return $label;
        }

        /**
        * Changes shipping label on current rate cost value.
        */
        public function mrkv_display_custom_shipping_cost($label, $method) {
            if ( 'nova_poshta_shipping_method' == $method->get_id() ) {
                $currency_symbol = get_woocommerce_currency_symbol();
                $cost = $this->rate['cost'];
                $label  = $method->get_label() . ': ' . $currency_symbol . $cost;
            }
            return $label;
        }

        /**
        * Removes rate cost value/
        */
        public function mrkv_no_display_shipping_cost($label, $method) {
            if ( 'nova_poshta_shipping_method' == $method->get_id() ) {
                $label = $method->get_label();
            }
            return $label;
        }

        /**
         * Is this method available?
         * @param array $package
         * @return bool
         */
        public function is_available($package)
        {
            return $this->is_enabled();
        }

        /**
         * @return string
         */
        private function getDescription()
        {
            return '';
        }
    }
endif;
