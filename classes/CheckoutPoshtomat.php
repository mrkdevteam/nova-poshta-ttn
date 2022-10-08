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

/**
 * Class CheckoutPoshtomat
 * @property bool isCheckout
 * @property Customer $customer
 * @package plugins\NovaPoshta\classes
 */
class CheckoutPoshtomat extends Checkout
{
    /**
     * @var CheckoutPoshtomat
     */
    private static $_instance;

    /**
     * @return CheckoutPoshtomat
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
        add_filter('nova_poshta_disable_default_fields', array($this, 'disableDefaultFields'));
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
        return $fields;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    protected function getIsCheckoutPoshtomat()
    {
        if (function_exists('is_checkout')) {
            return is_checkout();
        } else {
            // For backward compatibility with woocommerce 2.x.x
            global $post;
            $checkoutPageId = get_option('woocommerce_checkout_page_id');
            $pageId = ArrayHelper::getValue($post, 'ID', null);
            return $pageId && $checkoutPageId && ($pageId == $checkoutPageId);
        }
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
 * Class PoshtomvatNP
 * @package plugins\NovaPoshta\classes
 */
class PoshtomatNP extends Poshtomat
{

    /**
     * @return string
     */
    protected static function _key()
    {
        return 'nova_poshta_poshtomat';
    }

    /**
     * @return AbstractAreaRepository
     */
    protected function getRepository()
    {
        return AreaRepositoryFactory::instance()->poshtomatRepo();
    }
}
