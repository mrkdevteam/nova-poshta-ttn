<?php

use plugins\NovaPoshta\classes\AjaxRoute;
use plugins\NovaPoshta\classes\base\ArrayHelper;
use plugins\NovaPoshta\classes\Calculator;
use plugins\NovaPoshta\classes\Checkout;
use plugins\NovaPoshta\classes\CheckoutPoshtomat;
use plugins\NovaPoshta\classes\Log;
use plugins\NovaPoshta\classes\base\Base;
use plugins\NovaPoshta\classes\base\Options;
use plugins\NovaPoshta\classes\DatabasePM;
use plugins\NovaPoshta\classes\DatabaseSync;
use plugins\NovaPoshta\classes\NovaPoshtaApi;

/**
 * NovattnPoshtaPoshtomat class for shipping method 'nova_poshta_shipping_method_poshtomat'
 */
class NovattnPoshtaPoshtomat extends NovattnPoshta
{
    /**
     * @return bool
     */
    public function isCheckoutPoshtomat()
    {
        return CheckoutPoshtomat::instance()->isCheckoutPoshtomat;
    }

    /**
     * This method can be used safely only after woocommerce_after_calculate_totals hook
     * when $_SERVER['REQUEST_METHOD'] == 'GET'
     *
     * @return bool
     */
    public function isNPttnPM()
    {
        /** @noinspection PhpUndefinedFieldInspection */
        $sessionMethods = WC()->shipping->get_shipping_methods();

        $chosenMethods = array();
        if ($this->isPost() && ($postMethods = (array)ArrayHelper::getValue($_POST, 'shipping_method', array()))) {
            $chosenMethods = $postMethods;
        } elseif (isset($sessionMethods) && count($sessionMethods) > 0) {
            $chosenMethods = $sessionMethods;
        }
        return in_array(NOVA_POSHTA_TTN_SHIPPING_METHOD_POSHTOMAT, $chosenMethods);
    }

    /**
     * @param array $methods
     * @return array
     */
    public function addNovaPoshtaShippingMethod($methods)
    {
        $methods[] = 'WC_NovaPoshta_Shipping_Method_Poshtomat';
        return $methods;
    }

    /**
     * Init NovaPoshta shipping method class
     */
    public function initNovaPoshtaShippingMethod()
    {
        require_once NOVA_POSHTA_TTN_SHIPPING_PLUGIN_DIR . 'classes/WC_NovaPoshta_Shipping_Method_Poshtomat.php';
    }

    /**
     * @var NovattnPoshtaPoshtomat
     */
    private static $_instance;

    /**
     * @return NovaPoshtaPoshtomat
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
