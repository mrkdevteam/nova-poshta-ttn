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
use plugins\NovaPoshta\classes\Region;

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
     * @return bool
     * @throws \Exception
     */
    protected function getIsCheckoutPoshtomat()
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
 * Class PoshtomatNP
 * @package plugins\NovaPoshta\classes
 */
class PoshtomatNP extends Poshtomat
{

    /**
     * @return string
     */
    protected static function _key()
    {
        return 'nova_poshta_warehouse';
    }

    /**
     * @return AbstractAreaRepository
     */
    protected function getRepository()
    {
        return AreaRepositoryFactory::instance()->poshtomatRepo();
    }
}
