<?php

use plugins\NovaPoshta\classes\AjaxRoute;
use plugins\NovaPoshta\classes\base\ArrayHelper;
use plugins\NovaPoshta\classes\Calculator;
use plugins\NovaPoshta\classes\Checkout;
use plugins\NovaPoshta\classes\CheckoutPoshtomat;
use plugins\NovaPoshta\classes\CheckoutAddress;
use plugins\NovaPoshta\classes\Log;
use plugins\NovaPoshta\classes\base\Base;
use plugins\NovaPoshta\classes\base\Options;
use plugins\NovaPoshta\classes\Database;
use plugins\NovaPoshta\classes\DatabaseSync;
use plugins\NovaPoshta\classes\NovaPoshtaApi;

/**
 * NovattnPoshtaAddress class for shipping method 'npttn_address_shipping_method'
 */
class NovattnPoshtaAddress extends NovattnPoshta
{
    const LOCALE_RU = 'ru_RU';

    /**
     * Register main plugin hooks
     */
    public function init()
    {
            // Register shipping method on address
            add_action('woocommerce_shipping_init', array($this, 'initNovaPoshtaAddressShippingMethod'));
            add_filter('woocommerce_shipping_methods', array($this, 'addNovaPoshtaAddressShippingMethod'));

            CheckoutAddress::instance()->init();
    }

    /**
     * @return bool
     */
    public function isCheckoutAddress()
    {
        return CheckoutAddress::instance()->isCheckoutAddress;
    }

    /**
     * This method can be used safely only after woocommerce_after_calculate_totals hook
     * when $_SERVER['REQUEST_METHOD'] == 'GET'
     *
     * @return bool
     */
    public function isNPttnA()
    {
        /** @noinspection PhpUndefinedFieldInspection */
        $sessionMethods = WC()->session->chosen_shipping_methods;

        $chosenMethods = array();
        if ($this->isPost() && ($postMethods = (array)ArrayHelper::getValue($_POST, 'shipping_method', array()))) {
            $chosenMethods = $postMethods;
        } elseif (isset($sessionMethods) && count($sessionMethods) > 0) {
            $chosenMethods = $sessionMethods;
        }
        echo '<script>console.log("'.
        $chosenMethods[0] == "nova_poshta_shipping_method"
        .'")</script>';
        //return true;
        return in_array(NOVA_POSHTA_TTN_SHIPPING_METHOD, $chosenMethods);
    }

    /**
     * @param array $methods
     * @return array
     */
    public function addNovaPoshtaAddressShippingMethod($methods)
    {
        // $methods[] = 'WC_NovaPoshta_Shipping_Method';
        $methods['npttn_address_shipping_method'] = 'WC_NovaPoshtaAddress_Shipping_Method';
        // $methods[] = 'WC_NovaPoshta_Shipping_Method_Poshtomat';
        return $methods;
    }

    /**
     * Init NovaPoshta shipping method class
     */
    public function initNovaPoshtaAddressShippingMethod()
    {
        require_once NOVA_POSHTA_TTN_SHIPPING_PLUGIN_DIR . 'classes/WC_NovaPoshtaAddress_Shipping_Method.php';
    }

    /**
     * @var NovattnPoshta
     */
    private static $_instance;

    /**
     * @return NovaPoshta
     */
    public static function instance()
    {
        if (static::$_instance == null) {
            static::$_instance = new static();
        }
        return static::$_instance;
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
