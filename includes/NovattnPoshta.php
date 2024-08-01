<?php

use plugins\NovaPoshta\classes\AjaxRoute;
use plugins\NovaPoshta\classes\base\ArrayHelper;
use plugins\NovaPoshta\classes\Calculator;
use plugins\NovaPoshta\classes\Checkout;
use plugins\NovaPoshta\classes\CheckoutPoshtomat;
use plugins\NovaPoshta\classes\Log;
use plugins\NovaPoshta\classes\base\Base;
use plugins\NovaPoshta\classes\base\Options;
use plugins\NovaPoshta\classes\Database;
use plugins\NovaPoshta\classes\DatabaseSync;
use plugins\NovaPoshta\classes\NovaPoshtaApi;

/**
 * NovattnPoshta class for shipping method 'nova_poshta_shipping_method'
 */
class NovattnPoshta extends Base
{
    const LOCALE_RU = 'ru_RU';

    /**
     * Register main plugin hooks
     */
    public function init()
    {
        register_activation_hook(__FILE__, array($this, 'activatePlugin'));
        register_deactivation_hook(__FILE__, array($this, 'deactivatePlugin'));

        if ($this->isWoocommerce()) {
            // General plugin actions
            add_action('init', array(AjaxRoute::getClass(), 'init'));
            add_action('plugins_loaded', array($this, 'checkDatabaseVersion'));
            add_action('plugins_loaded', array($this, 'loadPluginDomain'));
            add_action('wp_head', array($this, 'mrkvnpCheckoutSpinnerColor'));
            add_action('wp_enqueue_scripts', array($this, 'scripts'));
            add_action('wp_enqueue_scripts', array($this, 'styles'));
            add_action('admin_enqueue_scripts', array($this, 'adminScripts'));
            add_action('admin_enqueue_scripts', array($this, 'adminStyles'));

            // Register new shipping method
            add_action('woocommerce_shipping_init', array($this, 'initNovaPoshtaShippingMethod'));
            add_filter('woocommerce_shipping_methods', array($this, 'addNovaPoshtaShippingMethod'));

            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'pluginActionLinks'));

            Checkout::instance()->init();
            CheckoutPoshtomat::instance()->init();
            Calculator::instance()->init();
        }
    }

    /**
     * @return bool
     */
    public function isWoocommerce()
    {
        return class_exists( 'woocommerce' )  || in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
    }

    /**
     * @return bool
     */
    public function isCheckout()
    {
        return Checkout::instance()->isCheckout;
    }

    /**
     * This method can be used safely only after woocommerce_after_calculate_totals hook
     * when $_SERVER['REQUEST_METHOD'] == 'GET'
     *
     * @return bool
     */
    public function isNPttn()
    {
        /** @noinspection PhpUndefinedFieldInspection */
        $sessionMethods = WC()->shipping->get_shipping_methods();

        $chosenMethods = array();
        if ($this->isPost() && ($postMethods = (array)ArrayHelper::getValue($_POST, 'shipping_method', array()))) {
            $chosenMethods = $postMethods;
        } elseif (isset($sessionMethods) && count($sessionMethods) > 0) {
            $chosenMethods = $sessionMethods;
        }
        return in_array(NOVA_POSHTA_TTN_SHIPPING_METHOD, $chosenMethods);
    }

    public function isANPttn()
    {
        $sessionMethods = WC()->session->chosen_shipping_methods;

        $chosenMethods = array();
        if ($this->isPost() && ($postMethods = (array)ArrayHelper::getValue($_POST, 'shipping_method', array()))) {
            $chosenMethods = $postMethods;
        } elseif (isset($sessionMethods) && count($sessionMethods) > 0) {
            $chosenMethods = $sessionMethods;
        }

        return in_array('npttn_address_shipping_method', $chosenMethods);
    }

    /**
     * @return bool
     */
    public function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * @return bool
     */
    public function isGet()
    {
        return !$this->isPost();
    }

    public function mrkvnpCheckoutSpinnerColor()
    {
        $spinner_color = get_option( 'mrkvnp_checkout_spinner_color' );
        if ( true === is_checkout() ) {
            echo '<style>.statenp-loading:after{border: 2px solid' .  $spinner_color . ';}';
            echo '.statenp-loading:after{border-left-color: #fff;}</style>';
        }
    }

    /**
     * Enqueue all required scripts
     */
    public function scripts()
    {
        $load = false;

        if (is_checkout()) {
            $load = true;
        }

        if ($load) {
            // $suffix = '.min.js';
            $suffix = '.js';
            $fileName = 'assets/js/nova-poshta-poshtomat' . $suffix;
            wp_register_script(
                'nova-poshta-poshtomat-js',
                NOVA_POSHTA_TTN_SHIPPING_PLUGIN_URL . $fileName,
                ['jquery-ui-autocomplete'],
                filemtime(NOVA_POSHTA_TTN_SHIPPING_PLUGIN_DIR . $fileName)
            );

            wp_enqueue_style('select2css', NOVA_POSHTA_TTN_SHIPPING_PLUGIN_URL.'assets/select2.min.css', array(), MNP_PLUGIN_VERSION);
            wp_register_script('select2js', NOVA_POSHTA_TTN_SHIPPING_PLUGIN_URL.'assets/select2.min.js', array(), MNP_PLUGIN_VERSION);
            wp_register_script('select2i18nuk', NOVA_POSHTA_TTN_SHIPPING_PLUGIN_URL.'assets/i18n/uk.js', array(), MNP_PLUGIN_VERSION);
            wp_register_script('select2i18nru', NOVA_POSHTA_TTN_SHIPPING_PLUGIN_URL.'assets/i18n/ru.js', array(), MNP_PLUGIN_VERSION);

            wp_enqueue_script('nova-poshta-poshtomat-js', array('jquery', 'select2js'));

            $this->localizeHelper('nova-poshta-poshtomat-js');
        }
    }

    /**
     * Enqueue all required styles
     */
    public function styles()
    {
        $load = false;

        if (is_checkout()) {
            $load = true;
        }

        if ($load) {
            global $wp_scripts;
            $jquery_version = isset($wp_scripts->registered['jquery-ui-core']->ver) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.9.2';
            wp_register_style('jquery-ui-style', NOVA_POSHTA_TTN_SHIPPING_PLUGIN_URL.'assets/jqueryui.css', array(), $jquery_version);
            wp_enqueue_style('jquery-ui-style');

            wp_register_style('np-frontend-style', NOVA_POSHTA_TTN_SHIPPING_PLUGIN_URL.'assets/css/frontend.css', array(), $jquery_version);
            wp_enqueue_style('np-frontend-style');
        }
    }

    /**
     * Enqueue all required styles for admin panel
     */
    public function adminStyles()
    {
        $suffix = $this->isDebug() ? '.css' : '.min.css';
        $fileName = 'assets/css/style' . $suffix;
        wp_register_style(
            'nova-poshta-style',
            NOVA_POSHTA_TTN_SHIPPING_PLUGIN_URL . $fileName,
            ['jquery-ui-style'],
            filemtime(NOVA_POSHTA_TTN_SHIPPING_PLUGIN_DIR . $fileName)
        );
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_style('nova-poshta-style');
    }

    /**
     * Enqueue all required scripts for admin panel
     */
    public function adminScripts()
    {
        $suffix = $this->isDebug() ? '.js' : '.min.js';
        $fileName = 'assets/js/nova-poshta-admin' . $suffix;
        wp_register_script(
            'nova-poshta-admin-js',
            NOVA_POSHTA_TTN_SHIPPING_PLUGIN_URL . $fileName,
            ['jquery-ui-autocomplete'],
            filemtime(NOVA_POSHTA_TTN_SHIPPING_PLUGIN_DIR . $fileName)
        );
        wp_enqueue_script( 'wp-color-picker' );

        $this->localizeHelper('nova-poshta-admin-js');

        $post_type = isset($_GET['post_type']) ? $_GET['post_type'] : '';
        if ((get_option('np_add_city_warehouse_to_handі_order') == '' && ($post_type == 'shop_order'))) {
            return;
        }

        global $pagenow;
        $screen = get_current_screen();
        if ( ( 'toplevel_page_morkvanp_plugin' === $screen->id || 'nova-poshta_page_morkvanp_invoice' === $screen->id ) ) {

            wp_enqueue_style( 'select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0-rc.0');
            wp_enqueue_script( 'select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', 'jquery', '4.1.0-rc.0');

        }
        // wp_enqueue_style('select2css', NOVA_POSHTA_TTN_SHIPPING_PLUGIN_URL.'assets/select2.min.css', array(), MNP_PLUGIN_VERSION);
        // wp_enqueue_script('select2js', NOVA_POSHTA_TTN_SHIPPING_PLUGIN_URL.'assets/select2.min.js', array(), MNP_PLUGIN_VERSION);
        // wp_enqueue_style('select2css');
        // wp_enqueue_script('select2js');


        wp_enqueue_script('nova-poshta-admin-js');
    }

    /**
     * @param string $handle
     */
    public function localizeHelper($handle)
    {
        wp_localize_script($handle, 'NovaPoshtaHelper', [
            'ajaxUrl' => admin_url('admin-ajax.php', 'relative'),
                        'textforcostcalc' => __('Розрахунок вартості доставки', NOVA_POSHTA_TTN_DOMAIN),
                        'textforcostcalcafter' => __('(оплата за доставку відбувається на відділенні нової пошти)', NOVA_POSHTA_TTN_DOMAIN),
                        'textforcostcalcen' => 'Delivery cost calculation',
            'chooseAnOptionText' => __('Choose an option', NOVA_POSHTA_TTN_DOMAIN),
            'getRegionsByNameSuggestionAction' => AjaxRoute::GET_REGIONS_BY_NAME_SUGGESTION,
            'getCitiesByNameSuggestionAction' => AjaxRoute::GET_CITIES_BY_NAME_SUGGESTION,
            'getWarehousesBySuggestionAction' => AjaxRoute::GET_WAREHOUSES_BY_NAME_SUGGESTION,
            'getPoshtomatsBySuggestionAction' => AjaxRoute::GET_POSHTOMATS_BY_NAME_SUGGESTION,
            'getCitiesAction' => AjaxRoute::GET_CITIES_ROUTE,
            'getWarehousesAction' => AjaxRoute::GET_WAREHOUSES_ROUTE,
            'getPoshtomatsAction' => AjaxRoute::GET_POSHTOMATS_ROUTE,
            'markPluginsAsRated' => AjaxRoute::MARK_PLUGIN_AS_RATED,
            'isShowDeliveryPrice' => \get_option('mrkvnp_is_show_delivery_price' ),
            'mrkvnpSenderAPIkey' => \get_option( 'mrkvnp_sender_api_key' ),
        ]);
    }

    /**
     * @param string $template
     * @param string $templateName
     * @param string $templatePath
     * @return string
     */
    public function locateTemplate($template, $templateName, $templatePath)
    {
        global $woocommerce;
        $_template = $template;
        if (!$templatePath) {
            $templatePath = $woocommerce->template_url;
        }

        $pluginPath = NOVA_POSHTA_TTN_SHIPPING_TEMPLATES_DIR . 'woocommerce/';

        // Look within passed path within the theme - this is priority
        $template = locate_template(array(
            $templatePath . $templateName,
            $templateName
        ));

        if (!$template && file_exists($pluginPath . $templateName)) {
            $template = $pluginPath . $templateName;
        }

        return $template ?: $_template;
    }

    /**
     * @param array $methods
     * @return array
     */
    public function addNovaPoshtaShippingMethod($methods)
    {
        $methods['nova_poshta_shipping_method'] = 'WC_NovaPoshta_Shipping_Method';
        return $methods;
    }

    /**
     * Init NovaPoshta shipping method class
     */
    public function initNovaPoshtaShippingMethod()
    {
        require_once NOVA_POSHTA_TTN_SHIPPING_PLUGIN_DIR . 'classes/WC_NovaPoshta_Shipping_Method.php';
    }

    /**
     * Activation hook handler
     */
    public function activatePlugin()
    {
        \update_option( 'mrkvnp_invoice_cargo_type', 'Parcel' );
    }

    /**
     * Deactivation hook handler
     */
    public function deactivatePlugin()
    {
    }

    public function checkDatabaseVersion()
    {
        if (version_compare($this->pluginVersion, get_site_option('nova_poshta_db_version'), '>')) {
            Database::instance()->upgrade();
            DatabaseSync::instance()->synchroniseLocations();
            update_site_option('nova_poshta_db_version', $this->pluginVersion);
        }
    }

    /**
     * Register translations directory
     * Register text domain
     */
    public function loadPluginDomain()
    {
        $path = sprintf('./%s/i18n', NOVA_POSHTA_TTN_DOMAIN);
        load_plugin_textdomain(NOVA_POSHTA_TTN_DOMAIN, false, $path);
    }

    /**
     * @return bool
     */
    public function isDebug()
    {
        return $this->options->isDebug();
    }

    /**
     * @param array $links
     * @return array
     */
    public function pluginActionLinks($links)
    {
        $href = admin_url('admin.php?page=wc-settings&tab=shipping&section=' . NOVA_POSHTA_TTN_SHIPPING_METHOD);
        $settingsLink = sprintf('<a href="' . $href . '" title="%s">%s</a>', esc_attr(__('View Plugin Settings', NOVA_POSHTA_TTN_DOMAIN)), __('Settings', NOVA_POSHTA_TTN_DOMAIN));
        array_unshift($links, $settingsLink);
        return $links;
    }

    /**
     * @return Options
     */
    protected function getOptions()
    {
        return Options::instance();
    }

    /**
     * @return Log
     */
    protected function getLog()
    {
        return Log::instance();
    }

    /**
     * @return wpdb
     */
    protected function getDb()
    {
        global $wpdb;
        return $wpdb;
    }

    /**
     * @return NovaPoshtaApi
     */
    protected function getApi()
    {
        return NovaPoshtaApi::instance();
    }

    /**
     * @return string
     */
    protected function getPluginVersion()
    {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        $pluginData = get_plugin_data(__FILE__);
        return $pluginData['Version'];
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
