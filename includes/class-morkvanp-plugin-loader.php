<?php
/**
 * Register all actions and filters for the plugin
 *
 * @link        http://morkva.co.ua/
 * @since       1.0.0
 *
 * @package     nova-poshta-ttn
 * @subpackage  nova-poshta-ttn/includes
 */
/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 *
 * @package    nova-poshta-ttn
 * @subpackage nova-poshta-ttn/includes
 * @author     MORKVA <hello@morkva.co.ua>
 */

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Utilities\OrderUtil;

require("class-morkvanp-plugin-callbacks.php");
// $path = PLUGIN_PATH . '/public/partials/morkvanp-plugin-invoices-page.php';
// if (file_exists($path)) {
//     require("class-morkvanp-plugin-callbacks-pro.php");
// }

class MNP_Plugin_Loader
{

    /**
     * The array of pages for plugin menu
     *
     * @since 1.0.0
     * @access protected
     * @var array $pages    Pages for plugin menu
     */
    protected $pages;

    /**
     * The array of subpages for plugin menu
     *
     * @since 1.0.0
     * @access protected
     * @var array $subpages     Subpages for plugin menu
     */
    protected $subpages;

    /**
     * Array of settings groups fields for plugin
     *
     * @since 1.0.0
     * @access protected
     * @var array $settings
     */
    protected $settings;

    /**
     * Array of sections for settings fields for plugin
     *
     * @since 1.0.0
     * @access protected
     * @var array $sections
     */
    protected $sections;

    /**
     * Array of fields for settings fields for plugin
     *
     * @since 1.0.0
     * @access protected
     * @var array $fields
     */
    protected $fields;

    /**
     * The array of actions registered with WordPress.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $actions    The actions registered with WordPress to fire when the plugin loads.
     */
    protected $actions;
    /**
     * The array of filters registered with WordPress.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $filters    The filters registered with WordPress to fire when the plugin loads.
     */
    protected $filters;

    /**
     * Object of callbacks class
     *
     * @since   1.0.0
     * @access  protected
     * @var     string $callbacks       Class of callbacks
     */
    protected $callbacks;

    /**
     * Initialize the collections used to maintain the actions and filters.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        global $wp_settings_sections;
        $this->actions = array();
        $this->filters = array();
        $this->pages = array();
        $this->subpages = array();
        $this->settings = array();
        $this->sections = array();
        $this->fields = array();

        $this->callbacks = new MNP_Plugin_Callbacks();
        // $path = PLUGIN_PATH . '/public/partials/morkvanp-plugin-invoices-page.php';
        // if (file_exists($path)) {
        //     $this->callbacks = new MNP_Plugin_Callbacks_Pro();
        // }

        $this->add_settings_fields();
        $this->register_fields_sections();
        $this->register_settings_fields();

        $this->register_menu_pages();
        $this->register_menu_subpages();

        add_action('admin_menu', array( $this, 'register_plugin_menu' ));
        add_action('add_meta_boxes', array( $this, 'mv_add_meta_boxes' ));
        add_action('admin_init', array( $this, 'register_plugin_settings' ));
        
        if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled()){
            add_filter('manage_woocommerce_page_wc-orders_columns', array( $this, 'woo_custom_column' ));
            add_action('manage_woocommerce_page_wc-orders_custom_column', array( $this, 'woo_column_get_data_hpos' ), 20, 2 );
        }
        else{
            add_filter('manage_edit-shop_order_columns', array( $this, 'woo_custom_column' ));
            add_action('manage_shop_order_posts_custom_column', array( $this, 'woo_column_get_data' ));
        }

        add_filter('wp_mail_from_name', array( $this, 'my_mail_from_name' ));

        add_action( 'wp_ajax_mrkv_np_remove_ttn', array( $this, 'mrkv_np_remove_ttn_func' ) ); 
        add_action( 'wp_ajax_nopriv_mrkv_np_remove_ttn', array( $this, 'mrkv_np_remove_ttn_func' ) );

        # Add order column
        add_filter( 'woocommerce_account_orders_columns', array( $this, 'mrkv_np_add_account_orders_column' ), 10, 1 );

        add_action( 'woocommerce_my_account_my_orders_column_order-ship-to', array( $this, 'mrkv_np_add_account_orders_column_rows' ) );

    }
    /**
     * Add a new action to the collection to be registered with WordPress.
     *
     * @since    1.0.0
     * @param    string               $hook             The name of the WordPress action that is being registered.
     * @param    object               $component        A reference to the instance of the object on which the action is defined.
     * @param    string               $callback         The name of the function definition on the $component.
     * @param    int                  $priority         Optional. The priority at which the function should be fired. Default is 10.
     * @param    int                  $accepted_args    Optional. The number of arguments that should be passed to the $callback. Default is 1.
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1)
    {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }
    /**
     * Add a new filter to the collection to be registered with WordPress.
     *
     * @since    1.0.0
     * @param    string               $hook             The name of the WordPress filter that is being registered.
     * @param    object               $component        A reference to the instance of the object on which the filter is defined.
     * @param    string               $callback         The name of the function definition on the $component.
     * @param    int                  $priority         Optional. The priority at which the function should be fired. Default is 10.
     * @param    int                  $accepted_args    Optional. The number of arguments that should be passed to the $callback. Default is 1
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1)
    {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }
    /**
     * A utility function that is used to register the actions and hooks into a single
     * collection.
     *
     * @since    1.0.0
     * @access   private
     * @param    array                $hooks            The collection of hooks that is being registered (that is, actions or filters).
     * @param    string               $hook             The name of the WordPress filter that is being registered.
     * @param    object               $component        A reference to the instance of the object on which the filter is defined.
     * @param    string               $callback         The name of the function definition on the $component.
     * @param    int                  $priority         The priority at which the function should be fired.
     * @param    int                  $accepted_args    The number of arguments that should be passed to the $callback.
     * @return   array                                  The collection of actions and filters registered with WordPress.
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args)
    {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );
        return $hooks;
    }
    /**
     * Register the filters and actions with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        foreach ($this->filters as $hook) {
            add_filter($hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args']);
        }
        foreach ($this->actions as $hook) {
            add_action($hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args']);
        }
    }

    /**
     * Registering plugin pages to menu
     *
     * @since   1.0.0
     */
    public function register_menu_pages()
    {
        $this->pages = array(
            array(
                'page_title' => __(MNP_PLUGIN_NAME, 'textdomain'),
                'menu_title' => 'Nova Poshta ',
                'capability' => 'manage_woocommerce',
                'menu_slug' => 'morkvanp_plugin',
                'callback' => array($this, 'add_settings_page'),
                'icon_url' => plugins_url("nova_poshta_25px.png", __FILE__),
                'position' => 60
            )
        );

        return $this;
    }

    /**
     *  Add Plugin Settings page
     *
     *  @since  1.0.0
     */
    public function add_settings_page()
    {
        require_once(PLUGIN_PATH . 'public/partials/morkvanp-plugin-settings.php');
    }

    /**
     * Registering subpages for menu of plugin
     *
     * @since   1.0.0
     */
    public function register_menu_subpages()
    {
        $title = "Налаштування";

        if (get_option('invoice_short')) {
            $this->subpages = array(
            array(
                'parent_slug'   => 'morkvanp_plugin',
                'page_title'    => 'Налаштування',
                'menu_title'    => 'Налаштування',
                'capability'    => 'manage_woocommerce',
                'menu_slug'     => 'morkvanp_plugin',
                'callback'      => array( $this, 'add_settings_page' )
            ),
            array(
                'parent_slug'   => 'morkvanp_plugin',
                'page_title'    => 'Створити Накладну',
                'menu_title'    => 'Створити Накладну',
                'capability'    => 'manage_woocommerce',
                'menu_slug'     => 'morkvanp_invoice',
                'callback'      =>  array( $this, 'add_invoice_page' )
            ),
            array(
                'parent_slug'   => 'morkvanp_plugin',
                'page_title'    => 'Мої накладні',
                'menu_title'    => 'Мої накладні',
                'capability'    => 'manage_woocommerce',
                'menu_slug'     => 'morkvanp_invoices',
                'callback'      => array( $this, 'invoices_page' )
            ),array(
                'parent_slug'   => 'morkvanp_plugin',
                'page_title'    => 'Шорткоди',
                'menu_title'    => 'Шорткоди',
                'capability'    => 'manage_woocommerce',
                'menu_slug'     => 'morkvanp_short',
                'callback'      => array( $this, 'add_settings_page' )
            ),
        );
        } else {
            $this->subpages = array(
            array(
                'parent_slug'   => 'morkvanp_plugin',
                'page_title'    => 'Налаштування',
                'menu_title'    => 'Налаштування',
                'capability'    => 'manage_woocommerce',
                'menu_slug'     => 'morkvanp_plugin',
                'callback'      => array( $this, 'add_settings_page' )
            ),
            array(
                'parent_slug'   => 'morkvanp_plugin',
                'page_title'    => 'Створити Накладну',
                'menu_title'    => 'Створити Накладну',
                'capability'    => 'manage_woocommerce',
                'menu_slug'     => 'morkvanp_invoice',
                'callback'      =>  array( $this, 'add_invoice_page' )
            ),
            array(
                'parent_slug'   => 'morkvanp_plugin',
                'page_title'    => 'Мої накладні',
                'menu_title'    => 'Мої накладні',
                'capability'    => 'manage_woocommerce',
                'menu_slug'     => 'morkvanp_invoices',
                'callback'      => array( $this, 'invoices_page' )
            ),
        );
        }

        return $this;
    }

    /**
     * Adding subpage of plugin
     *
     * @since 1.0.0
     */
    public function add_invoice_page()
    {
        require_once(PLUGIN_PATH . '/public/partials/morkvanp-plugin-form.php');
    }

    /**
     * Add invoices subpage of plugin
     *
     * @since 1.0.0
     */
    public function invoices_page()
    {
        $path = PLUGIN_PATH . '/public/partials/morkvanp-plugin-invoices-page.php';
        if (file_exists($path)) {
            // Include style.css file for Invoices tab
            $plugin_data = get_plugin_data( __FILE__ );
            wp_enqueue_style( 'mrkvnp-tabs-style', PLUGIN_URL . 'public/css/style.css', array(), MNP_PLUGIN_VERSION, 'all' );
            require_once($path);
        } else {
            $path = PLUGIN_PATH . '/public/partials/morkvanp-plugin-invoices-page-demo.php';
            require_once($path);
        }
    }

    /**
     * Register plugin menu
     *
     * @since   1.0.0
     */
    public function register_plugin_menu()
    {
        foreach ($this->pages as $page) {
            add_menu_page($page['page_title'], $page['menu_title'], $page['capability'], $page['menu_slug'], $page['callback'], $page['icon_url'], $page['position']);
        }

        foreach ($this->subpages as $subpage) {
            add_submenu_page($subpage['parent_slug'], $subpage['page_title'], $subpage['menu_title'], $subpage['capability'], $subpage['menu_slug'], $subpage['callback']);
        }
    }

    /**
     * Add setting fields for plugin
     *
     * @since   1.0.0
     */
    public function add_settings_fields()
    {
        $args = array(

            // *** Base Settings
            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_sender_api_key'
            ),
            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_auto_detect_lang'
            ),
            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_individual_checkout_text'
            ),
            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_checkout_spinner_color'
            ),
            // *** Sender
            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_invoice_sender_ref'
            ),
            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_invoice_sender_names'
            ),
            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_invoice_sender_region_name'
            ),
            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'region'
            ),
            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'warehouse'
            ),
            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'city'
            ),
            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_invoice_sender_warehouse_name'
            ),
            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'woocommerce_nova_poshta_shipping_method_address'
            ),
            // *** Default settings
            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_invoice_cargo_type'
            ),
            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_invoice_payer'
            ),
            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_invoice_payment_type'
            ),
            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_invoice_redelivery_payer'
            ),
            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_invoice_description'
            ),
            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_invoice_weight'
            ),
            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_invoice_length'
            ),
            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_invoice_width'
            ),
            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_invoice_height'
            ),
            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_invoice_volume'
            ),
            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_calc_shipcost_from_orderparms'
            ),
            // *** Automation
            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_is_payment_control_on'
            ),
            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_is_auto_invoice_creating'
            ),
            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_is_order_auto_status_changing'
            ),
            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_invoice_patranomic_required'
            ),
            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_invoice_patranomic_disable'
            ),
            




            // *** Інше (старі налаштування, зараз не виводяться на екран)
            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'morkvanp_checkout_count'
            ),
            // array(
            //     'option_group' => 'morkvanp_options_group',
            //     'option_name' => 'mrkvnp_invoice_sender_names'
            // ),
            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_sender_phone'
            ),

            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'woocommerce_nova_poshta_shipping_method_area'
            ),

            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_invoice_sender_city_name'
            ),

            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'woocommerce_nova_poshta_shipping_method_city'
            ),

            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_invoice_sender_warehouse_ref'
            ),

            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_invoice_sender_building_number'
            ),

            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_invoice_sender_warehouse_type'
            ),

            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_invoice_sender_flat_number'
            ),

            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_invoice_sender_address_name'
            ),

            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_is_show_delivery_price'
            ),

            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_is_add_delivery_price'
            ),

            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_is_auto_update_db'
            ),

            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_spinner_color'
            ),

            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'parcel_terminal'
            ),

            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'city'
            ),

            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'region'
            ),

            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'flat'
            ),
            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'warehouse'
            ),

            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'invoice_short'
            ),

            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'invoice_cron'
            ),

            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_email_template'
            ),
            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'mrkvnp_email_subject'
            ),

            array(
                'option_group' => 'morkvanp_options_group',
                'option_name' => 'morkvanp_shipping_method_settings'
            )
        );

        $this->settings = $args;

        return $this;
    }

    /**
     *  Register all sections for settings fields
     *
     *  @since   1.0.0
     */
    public function register_fields_sections()
    {
        $args = array(
            array( // Base settings
                'id' => 'mrkvnp_base_settings',
                'title' => '',
                'callback' => function () {
                    // <img src="' . NOVA_POSHTA_TTN_SHIPPING_PLUGIN_URL . 'includes/img/settings_icons/Setting.png' . '"> - Це працює!
                    echo '<div class="h2 mrkvnp-settings">
                        <span class="dashicons dashicons-admin-generic"></span> ' .
                        esc_html__( 'Базові налаштування', 'nova-poshta-ttn' ) .
                    '</div>';
                },
                'page' => 'morkvanp_plugin'
            ),
            array( // Sender
                'id' => 'mrkvnp_sender',
                'title' => '',
                'callback' => function () {
                    echo '<div class="h2 mrkvnp-settings"><span class="dashicons dashicons-admin-users"></span> ' .
                        esc_html__( 'Відправник', 'nova-poshta-ttn' ) .
                    '</div>';
                },
                'page' => 'morkvanp_plugin'
            ),
            array( // Default settings
                'id' => 'mrkvnp_default_settings',
                'title' => '',
                'callback' => function () {
                    echo '<div class="h2 mrkvnp-settings"><span class="dashicons dashicons-screenoptions"></span> ' .
                        esc_html__( 'Значення за замовчуванням', 'nova-poshta-ttn' ) .
                    '</div>';
                },
                'page' => 'morkvanp_plugin'
            ),
            array( // Automation
                'id' => 'mrkvnp_automation',
                'title' => '',
                'callback' => function () {
                    echo '<div class="h2 mrkvnp-settings"><span class="dashicons dashicons-star-half"></span> ' .
                        esc_html__( 'Автоматизація', 'nova-poshta-ttn' ) .
                    '</div>';
                },
                'page' => 'morkvanp_plugin'
            ),
            // array( // *** Інше
            //     'id' => 'morkvanp_admin_index',
            //     'title' => '',
            //     'callback' => function () {
            //         echo '<div class="h2 mrkvnp-settings"><span class="dashicons dashicons-screenoptions"></span> ' .
            //             esc_html__( 'Інше', 'nova-poshta-ttn' ) .
            //         '</div>';
            //     },
            //     'page' => 'morkvanp_plugin'
            // )
        );

        $this->sections = $args;

        return $this;
    }

    /**
     * Register settings callbacks fields
     *
     * @since   1.0.0
     */
    public function register_settings_fields()
    {

            $args = array(

            // *** Base Settings
            array(
                'id' => 'mrkvnp_sender_api_key',
                'title' => __('API ключ','nova-poshta-ttn'),
                'callback' => array( $this->callbacks, 'setSenderAPIkey' ),
                'page' => 'morkvanp_plugin',
                'section' => 'mrkvnp_base_settings',
                'args' => array(
                    'label_for' => 'mrkvnp_sender_api_key',
                )
            ),
            array(
                'id' => 'morkvanp_checkout_count', // Поля при оформленні замовлення (deprecated)
                'title' => '',
                'callback' => function() {
                    update_option( 'morkvanp_checkout_count', '3fields', true );
                },
                'page' => 'morkvanp_plugin',
                'section' => 'mrkvnp_base_settings',
                'args' => array(
                    'label_for' => 'morkvanp_checkout_count',
                    'class' => 'mrkvnp-tr-h0'
                )
            ),
            array(
                'id' => 'mrkvnp_auto_detect_lang',
                'title' => __('Визначати мову автоматично<br>
                    <small style="font-weight:normal;color: #9b9b9b;">Очікується згодом</small>','nova-poshta-ttn'),
                'callback' => array( $this->callbacks, 'setAutoDetectLang' ),
                'page' => 'morkvanp_plugin',
                'section' => 'mrkvnp_base_settings',
                'args' => array(
                    'label_for' => 'mrkvnp_auto_detect_lang',
                    'class' => 'mrkvnp-pt0 mrkvnp-chkbx mrkvnp-th-colorgray'
                )
            ),
            array(
                'id' => 'mrkvnp_individual_checkout_text',
                'title' => __('Індивідуальне налаштування тексту для сторінки чекаут<br>
                    <small style="font-weight:normal;color: #9b9b9b;">Очікується згодом</small>','nova-poshta-ttn'),
                'callback' => array( $this->callbacks, 'setIndividualCheckoutText' ),
                'page' => 'morkvanp_plugin',
                'section' => 'mrkvnp_base_settings',
                'args' => array(
                    'label_for' => 'mrkvnp_individual_checkout_text',
                    'class' => 'mrkvnp-pt0 mrkvnp-chkbx mrkvnp-th-colorgray'
                )
            ),
            array(
                'id' => 'mrkvnp_checkout_spinner_color',
                'title' => __('Колір спінера на сторінці Checkout','nova-poshta-ttn'),
                'callback' => array( $this->callbacks, 'setCheckoutSpinnerColor' ),
                'page' => 'morkvanp_plugin',
                'section' => 'mrkvnp_base_settings',
                'args' => array(
                    'label_for' => 'mrkvnp_checkout_spinner_color',
                    'class' => 'mrkvnp-pt0 mrkvnp-chkbx'
                )
            ),
            array(
                'id' => 'mrkvnp_is_auto_update_db',
                'title' => __('Оновлювати бази відділень/поштоматів автоматично<br>
                    <small style="font-weight:bold;color:#dc3232;">Лише у Про-версії</small>','nova-poshta-ttn'),
                'callback' => array( $this->callbacks, 'mrkvnpIsAutoUpdateDB' ),
                'page' => 'morkvanp_plugin',
                'section' => 'mrkvnp_base_settings',
                'args' => array(
                    'label_for' => 'mrkvnp_is_auto_update_db',
                    'class' => 'mrkvnp-pt0 mrkvnp-chkbx mrkvnp-th-colorgray'
                )
            ),
            array(
                'id' => 'mrkvnp_is_byhand_update_db',
                'title' => __('Оновлювати бази відділень/поштоматів вручну<br>
                    <small style="font-weight:normal;">Це може зайняти до 30 сек.</small>','nova-poshta-ttn'),
                'callback' => array( $this->callbacks, 'mrkvnpIsByHandUpdateDB' ),
                'page' => 'morkvanp_plugin',
                'section' => 'mrkvnp_base_settings',
                'args' => array(
                    'label_for' => 'mrkvnp_is_byhand_update_db',
                    'class' => 'mrkvnp-pt0 mrkvnp-chkbx'
                )
            ),

            // *** Sender
            array( // Choose sender
                'id' => 'mrkvnp_invoice_sender_ref',
                'title' => '',
                'callback' => array( $this->callbacks, 'mrkvnpGetSendersByAPIKey' ),
                'page' => 'morkvanp_plugin',
                'section' => 'mrkvnp_sender',
                'args' => array(
                    'label_for' => 'mrkvnp_invoice_sender_ref',
                    'class' => 'mrkvnp-pt0-dflex mrkvnp-mb0-pb0'
                )
            ),
            array( // Get sender names - hidden input
                'id' => 'mrkvnp_invoice_sender_names',
                'title' => '',
                'callback' => array( $this->callbacks, 'morkvanpGetSenderNames' ),
                'page' => 'morkvanp_plugin',
                'section' => 'mrkvnp_sender',
                'args' => array(
                    'label_for' => 'mrkvnp_invoice_sender_names',
                    'class' => 'mrkvnp-th-p0 mrkvnp-td-pt0mb0'
                )
            ),
            array( // Sender region input
                'id' => 'region',
                'title' => '',
                'callback' => array( $this->callbacks, 'mrkvnpSenderRegion' ),
                'page' => 'morkvanp_plugin',
                'section' => 'mrkvnp_sender',
                'args' => array(
                    'label_for' => 'region',
                    'class' => 'mrkvnp-pt0 mrkvnp-displnone'
                )
            ),
            array(
                'id' => 'city',
                'title' => '',
                'callback' => array( $this->callbacks, 'morkvanpSenderCity' ),
                'page' => 'morkvanp_plugin',
                'section' => 'mrkvnp_sender',
                'args' => array(
                    'label_for' => 'city',
                    'class' => 'mrkvnp-pt0 mrkvnp-displnone'
                )
            ),
            array( // Відправка з відділення
                'id' => 'mrkvnp_sender_from_warehouse',
                'title' => __('Відправка з Відділення:','nova-poshta-ttn'),
                'callback' => function () {},
                'page' => 'morkvanp_plugin',
                'section' => 'mrkvnp_sender',
                'args' => array(
                    'label_for' => 'mrkvnp_sender_from_warehouse',
                    'class' => 'mrkvnp-pt0 mrkvnp_sender_from_warehouse_line'
                )
            ),
            array(
                'id' => 'mrkvnp_invoice_sender_warehouse_name',
                'title' => '',
                'callback' => array( $this->callbacks, 'morkvanpWarehouseAddress'),
                'page' => 'morkvanp_plugin',
                'section' => 'mrkvnp_sender',
                'args' => array(
                    'label_for' => 'mrkvnp_invoice_sender_warehouse_name',
                    'class' => 'mrkvnp-pt0 mrkvnp-displnone mrkvnp-senderwh'
                )
            ),
            array( // Відправка з адреси
                'id' => 'woocommerce_nova_poshta_shipping_method_address',
                'title' => __('Відправка з адреси:','nova-poshta-ttn'),
                'callback' => array( $this->callbacks, 'morkvanpWarehouseAddress2'),
                'page' => 'morkvanp_plugin',
                'section' => 'mrkvnp_sender',
                'args' => array(
                    'label_for' => 'woocommerce_nova_poshta_shipping_method_address',
                    'class' => 'mrkvnp-pt0-dflex mrkvnp-mb0-pb0 mrkvnp_sender_from_warehouse_line mrkvnp_sender_from_warehouse_line-second'
                )
            ),

            // *** Default settings
            array(
                'id' => 'mrkvnp_invoice_cargo_type',
                'title' => __('Тип відправлення:','nova-poshta-ttn'),
                'callback' => array( $this->callbacks, 'mrkvnpInvoiceCargoType' ),
                'page' => 'morkvanp_plugin',
                'section' => 'mrkvnp_default_settings',
                'args' => array(
                    'label_for' => 'mrkvnp_invoice_cargo_type',
                    'class' => 'mrkvnp-mt7 mrkvnp-mb0 mrkvnp-tr-flex-row mrkvnp-default-settings mrkvnp-flexbasiscontent'
                )
            ),
            array(
                'id' => 'mrkvnp_invoice_payer',
                'title' => __('Платник доставки:','nova-poshta-ttn'),
                'callback' => array( $this->callbacks, 'mrkvnpInvoicePayer' ),
                'page' => 'morkvanp_plugin',
                'section' => 'mrkvnp_default_settings',
                'args' => array(
                    'label_for' => 'mrkvnp_invoice_payer',
                    'class' => 'mrkvnp-pt0 mrkvnp-flex-row mrkvnp-flexbasiscontent'
                )
            ),
            array(
                'id' => 'mrkvnp_invoice_payment_type',
                'title' => __('Тип оплати Відправника:','nova-poshta-ttn'),
                'callback' => array( $this->callbacks, 'mrkvnpInvoicePaymentType' ),
                'page' => 'morkvanp_plugin',
                'section' => 'mrkvnp_default_settings',
                'args' => array(
                    'label_for' => 'mrkvnp_invoice_payment_type',
                    'class' => 'mrkvnp-pt0 mrkvnp-w-100 mrkvnp-flex-row mrkvnp-flexbasiscontent'
                )
            ),
            array(
                'id' => 'mrkvnp_invoice_redelivery_payer',
                'title' => __('Платник за функцію післяплати: <br>
                    <small style="font-weight:bold;color:#dc3232;">Лише у Про-версії</small>','nova-poshta-ttn'),
                'callback' => array( $this->callbacks, 'mrkvnpInvoiceRedeliveryPayer' ),
                'page' => 'morkvanp_plugin',
                'section' => 'mrkvnp_default_settings',
                'desc_tip' => true,
                'description' => __( 'Якщо товар має декілька категорій на сайті' ),
                'args' => array(
                    'label_for' => 'mrkvnp_invoice_redelivery_payer mrkvnp-th-colorgray',
                    'class' => 'mrkvnp-pt0 mrkvnp-flex-row mrkvnp-flexbasiscontent'
                )
            ),
            array(
                'id' => 'mrkvnp_invoice_description',
                'title' => __('Опис відправлення: <br>
                    <small style="font-weight:bold;color:#dc3232;">Лише у Про-версії</small>','nova-poshta-ttn'),
                'callback' => array( $this->callbacks, 'mrkvnpInvoiceDescription' ),
                'page' => 'morkvanp_plugin',
                'section' => 'mrkvnp_default_settings',
                'args' => array(
                    'label_for' => 'mrkvnp_invoice_description',
                    'class' => 'mrkvnp-pt0-pb0 mrkvnp-pl0 mrkvnp-th-colorgray'
                )
            ),
            array(
                'id' => 'mrkvnp_invoice_weight',
                'title' => __('Вага відправлення, кг:','nova-poshta-ttn'),
                'callback' => array( $this->callbacks, 'mrkvnpInvoiceDefaultWeight' ),
                'page' => 'morkvanp_plugin',
                'section' => 'mrkvnp_default_settings',
                'args' => array(
                    'label_for' => 'mrkvnp_invoice_weight',
                    'class' => 'mrkvnp-mb0 mrkvnp-tr-flex-row mrkvnp-flexbasiscontent'
                )
            ),
            array(
                'id' => 'mrkvnp_invoice_length',
                'title' => __('Розміри відправлення, см:','nova-poshta-ttn'),
                'callback' => array( $this->callbacks, 'mrkvnpInvoiceDefaultLength' ),
                'page' => 'morkvanp_plugin',
                'section' => 'mrkvnp_default_settings',
                'args' => array(
                    'label_for' => 'mrkvnp_invoice_length',
                    'class' => 'mrkvnp-pt0 mrkvnp-flexbasiscontent mrkvnp_invoice_length_block'
                )
            ),
            array(
                'id' => 'mrkvnp_invoice_width',
                'title' => '',
                'callback' => array( $this->callbacks, 'mrkvnpInvoiceDefaultWidth' ),
                'page' => 'morkvanp_plugin',
                'section' => 'mrkvnp_default_settings',
                'args' => array(
                    'label_for' => 'mrkvnp_invoice_width',
                    'class' => 'mrkvnp-pt0 mrkvnp-vis-hid mrkvnp_invoice_width_block'
                )
            ),
            array(
                'id' => 'mrkvnp_invoice_height',
                'title' => '',
                'callback' => array( $this->callbacks, 'mrkvnpInvoiceDefaultHeight' ),
                'page' => 'morkvanp_plugin',
                'section' => 'mrkvnp_default_settings',
                'args' => array(
                    'label_for' => 'mrkvnp_invoice_height',
                    'class' => 'mrkvnp-pt0 mrkvnp-vis-hid mrkvnp-pos-rel-80 mrkvnp_invoice_height_block'
                )
            ),
            array(
                'id' => 'mrkvnp_caption_lwh',
                'title' => '',
                'callback' => function () {
                    /*_e( 'Довжина х Ширина х Висота', 'nova-poshta-ttn');*/
                },
                'page' => 'morkvanp_plugin',
                'section' => 'mrkvnp_default_settings',
                'args' => array(
                    'class' => 'mrkvnp-tr-flex-row mrkvnp-tr-pr-85t'
                )
            ),
            array(
                'id' => 'mrkvnp_invoice_volume',
                'title' => __('Об\'ємна вага відправлення:','nova-poshta-ttn') . '<br><span style="font-weight:normal;font-size: smaller;"> Вмикайте це налаштування,
                    якщо ВСІ товари на сайті мають вагу і розміри.</span>',
                'callback' => array( $this->callbacks, 'mrkvnpInvoiceDefaultVolume' ),
                'page' => 'morkvanp_plugin',
                'section' => 'mrkvnp_default_settings',
                'args' => array(
                    'label_for' => 'mrkvnp_invoice_volume',
                    'class' => 'mrkvnp-tr-flex-row mrkvnp-tr-pr-95t'
                )
            ),
            array(
                'id' => 'mrkvnp_calc_shipcost_from_orderparms',
                'title' => __('Розраховувати вартість доставки з параметрів товарів у замовленні<br>
                    <small style="font-weight:bold;color:#dc3232;">Лише у Про-версії</small>', 'nova-poshta-ttn'),
                'callback' => array( $this->callbacks, 'mrkvnpIsCalcShipCostOderParms' ),
                'page' => 'morkvanp_plugin',
                'section' => 'mrkvnp_default_settings',
                'args' => array(
                    'label_for' => 'mrkvnp_calc_shipcost_from_orderparms',
                    'class' => 'mrkvnp-pt0 mrkvnp-chkbx mrkvnp-tr-pr-50t mrkvnp-th-colorgray'
                )
            ),
            /*array(
                'id' => 'mrkvnp_caption_calc_shipcost',
                'title' => '',
                'callback' => function () {},
                'page' => 'morkvanp_plugin',
                'section' => 'mrkvnp_default_settings',
                'args' => array(
                    'class' => 'mrkvnp-pt0 mrkvnp-pb20 mrkvnp-tr-pr-125t'
                )
            ),*/

            array(
                'id' => 'mrkvnp_invoice_patranomic_required',
                'title' => __('По батькові','morkvanp-plugin') . '<br><span style="font-weight:normal;font-size: smaller;">Зробити поле по батькові обов\'язковим</span>',
                'callback' => array( $this->callbacks, 'mrkvnpInvoicePatranomicRequired' ),
                'page' => 'morkvanp_plugin',
                'section' => 'mrkvnp_default_settings',
                'args' => array(
                    'label_for' => 'mrkvnp_invoice_patranomic_required',
                    'class' => 'mrkvnp-tr-flex-row mrkvnp-tr-pr-95t mrkvnp-pt-20'
                )
            ),
            array(
                'id' => 'mrkvnp_invoice_patranomic_disable',
                'title' => __('Вимкнути По батькові','morkvanp-plugin') . '<br><span style="font-weight:normal;font-size: smaller;">Вимкнути поле по батькові зі сторінки оформлення</span>',
                'callback' => array( $this->callbacks, 'mrkvnpInvoicePatranomicDisabled' ),
                'page' => 'morkvanp_plugin',
                'section' => 'mrkvnp_default_settings',
                'args' => array(
                    'label_for' => 'mrkvnp_invoice_patranomic_disable',
                    'class' => 'mrkvnp-tr-flex-row mrkvnp-tr-pr-95t mrkvnp-pt-20'
                )
            ), 

            // *** Automation
            array(
                'id' => 'mrkvnp_is_payment_control_on',
                'title' => __('Контроль оплати<br>
                    <small style="font-weight:bold;color:#dc3232;">Лише у Про-версії</small>','nova-poshta-ttn'),
                'callback' => array( $this->callbacks, 'mrkvnpInvoiceIsPaymentControlOn' ),
                'page' => 'morkvanp_plugin',
                'section' => 'mrkvnp_automation',
                'args' => array(
                    'label_for' => 'mrkvnp_is_payment_control_on',
                    'class' => 'mrkvnp-tr-flex-row mrkvnp-mt7 mrkvnp-th-colorgray'
                )
            ),
            array(
                'id' => 'mrkvnp_is_auto_invoice_creating',
                'title' => 'Автоматично створювати накладні<br>
                    <small style="font-weight:bold;color:#dc3232;">Лише у Про-версії</small>',
                'callback' => array( $this->callbacks, 'mrkvnpInvoiceIsAutoCreating' ),
                'page' => 'morkvanp_plugin',
                'section' => 'mrkvnp_automation',
                'args' => array(
                    'label_for' => 'mrkvnp_is_auto_invoice_creating',
                    'class' => 'mrkvnp-pt0 mrkvnp-chkbx mrkvnp-th-colorgray'
                )
            ),
            array(
                'id' => 'mrkvnp_is_order_auto_status_changing',
                'title' => 'Автоматично змінювати статус замовлення<br>
                    <small style="font-weight:bold;color:#dc3232;">Лише у Про-версії</small>',
                'callback' => array( $this->callbacks, 'mrkvnpInvoiceIsAutoStatusOrderChanging' ),
                'page' => 'morkvanp_plugin',
                'section' => 'mrkvnp_automation',
                'args' => array(
                    'label_for' => 'mrkvnp_is_order_auto_status_changing',
                    'class' => 'mrkvnp-tr-flex-row mrkvnp-th-colorgray'
                )
            ),






            // *** Інше (старі налаштування, зараз на екран не виводяться) - deprecated
            array(
                'id' => 'morkvanp_checkout_count',
                'title' => 'Поля при оформленні замовлення',
                'callback' => array( $this->callbacks, 'mrkvnpCheckoutFieldsCount' ),
                'page' => 'morkvanp_plugin',
                'section' => 'morkvanp_admin_index',
                'args' => array(
                    'label_for' => 'morkvanp_checkout_count',
                    'class' => 'additional allsettings show'
                )
            ),
            // array(
            //     'id' => 'mrkvnp_invoice_sender_names',
            //     'title' => 'Назва Відправника (П.І.Б. повністю)',
            //     'callback' => array( $this->callbacks, 'morkvanpNames' ),
            //     'page' => 'morkvanp_plugin',
            //     'section' => 'morkvanp_admin_index',
            //     'args' => array(
            //         'label_for' => 'mrkvnp_invoice_sender_names',
            //         'class' => 'basesettings allsettings show'
            //     )
            // ),
            array(
                'id' => 'mrkvnp_sender_phone',
                'title' => 'Номер телефону Відправника',
                'callback' => array( $this->callbacks, 'morkvanpPhone' ),
                'page' => 'morkvanp_plugin',
                'section' => 'morkvanp_admin_index',
                'args' => array(
                    'label_for' => 'mrkvnp_sender_phone',
                    'class' => 'basesettings allsettings show'
                )
            ),
            array(
                'id' => 'mrkvnp_is_show_delivery_price',
                'title' => 'Показати розрахунок вартості доставки при оформленні замовлення',
                'callback' => array( $this->callbacks, 'morkvanpcalc' ),
                'page' => 'morkvanp_plugin',
                'section' => 'morkvanp_admin_index',
                'args' => array(
                    'label_for' => 'mrkvnp_is_show_delivery_price',
                    'class' => 'allsettings additional show'
                )
            ),
            array(
                'id' => 'mrkvnp_is_add_delivery_price',
                'title' => 'Додати розрахунок вартості доставки до замовлення',
                'callback' => array( $this->callbacks, 'morkvanpcalcplus' ),
                'page' => 'morkvanp_plugin',
                'section' => 'morkvanp_admin_index',
                'args' => array(
                    'label_for' => 'mrkvnp_is_add_delivery_price',
                    'class' => 'allsettings additional show disabled-option'
                )
            ),
            array(
                'id' => 'parcel_terminal',
                'title' => 'Розмір комірки Поштомату за замовчуванням<span style="color:#dc3232;"> NEW</span>',
                'callback' => array( $this->callbacks, 'morkvanpParcelTerminal' ),
                'page' => 'morkvanp_plugin',
                'section' => 'morkvanp_admin_index',
                'args' => array(
                    'label_for' => 'parcel_terminal',
                    'class' => 'additional allsettings  show'
                )
            ),
            // Відправка e-mail з ТТН
            array(
                'id' => 'title_email_with_ttn',
                'title' => '<h3 style="margin:0;">Відправка e-mail з ТТН</h3>',
                'callback' => function () {},
                'page' => 'morkvanp_plugin',
                'section' => 'morkvanp_admin_index',
            ),
            array(
                'id' => 'mrkvnp_email_subject',
                'title' => 'Шаблон заголовку email повідомлення',
                'callback' => array( $this->callbacks, 'morkvanpEmailSubject' ),
                'page' => 'morkvanp_plugin',
                'section' => 'morkvanp_admin_index',
                'args' => array(
                    'label_for' => 'mrkvnp_email_subject',
                    'class' => 'allsettings additional mrkvnp_email_subject show'
                )
            ),
            array(
                'id' => 'invoice_email_template',
                'title' => 'Шаблон email',
                'callback' => array( $this->callbacks, 'morkvanpEmailTemplate' ),
                'page' => 'morkvanp_plugin',
                'section' => 'morkvanp_admin_index',
                'args' => array(
                    'label_for' => 'invoice_date',
                    'class' => 'allsettings additional show'
                )
            ),
            // Створення накладних
            array(
                'id' => 'title_create_invoices',
                'title' => '<h3 style="margin:0;">Створення накладних</h3>',
                'callback' => function () {},
                'page' => 'morkvanp_plugin',
                'section' => 'morkvanp_admin_index',
            ),

            array(
                'id' => 'morkvanp_shipping_method_settings',
                'title' => '',
                'callback' => array( $this->callbacks, 'morkvanpShippingMethodSettings' ),
                'page' => 'morkvanp_plugin',
                'section' => 'morkvanp_admin_index',
                'args' => array(
                    'label_for' => 'morkvanp_shipping_method_settings',
                    'class' => 'autosettings allsettings hidden'
                )
            ),

        );

        $this->fields = $args;

        return $this;
    }

    /**
     *  Registering all settings fields for plugin
     *
     *  @since   1.0.0
     */
    public function register_plugin_settings()
    {
        foreach ($this->settings as $setting) {
            register_setting($setting["option_group"], $setting["option_name"], (isset($setting["callback"]) ? $setting["callback"] : ''));
        }

        foreach ($this->sections as $section) {
            add_settings_section($section["id"], $section["title"], (isset($section["callback"]) ? $section["callback"] : ''), $section["page"]);
        }

        foreach ($this->fields as $field) {
            add_settings_field($field["id"], $field["title"], (isset($field["callback"]) ? $field["callback"] : ''), $field["page"], $field["section"], (isset($field["args"]) ? $field["args"] : ''));
        }
    }

    /**
     * Add meta box to WooCommerce order's page
     *
     * @since 1.0.0
     */
    public function add_plugin_meta_box()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $methodid = "";

        if (isset($_GET["post"])  || isset($_GET["id"])) {
            
            $order_id = '';
            if(isset($_GET["post"])){
                $order_id = $_GET["post"];    
            }
            else{
                $order_id = $_GET["id"];
            }

            $order_data0 = wc_get_order($order_id);
            $order_data = $order_data0->get_data();

            foreach ($order_data0->get_items('shipping') as $item_id => $shipping_item_obj) {
                $shipping_item_data = $shipping_item_obj->get_data();
                $methodid = $shipping_item_data['method_id'];
            }



            if ((strpos($methodid, 'nova_poshta_shipping')!==false) || (strpos($methodid, 'nova_poshta_address_shipping')!==false)) {
            } else {
                if ((strpos($methodid, 'npttn_address_shipping_method')!==false)) {
                    echo '<style>#npttn_newttn{display:block;}</style>';
                } else {
                    if ( ! \get_option( 'mrkvnp_invoice_payer' ) ) {
                        echo '<style>#npttn_newttn{display:none;}</style>';
                    }
                }
            }



            if (isset($order_id)) {
                $order_data = wc_get_order($order_id);
                $order = $order_data->get_data();
            }
            echo '<h4 style="margin-bottom:5px;">' . __('Створити накладну', 'nova-poshta-ttn') . '</h4>';
            echo '<div><img src="'.NOVA_POSHTA_TTN_SHIPPING_PLUGIN_URL.'/includes/nova_poshta_25px.png"
                style="height: 25px;width: 25px; margin-right: 20px; margin-top: 2px;">';
            echo "<a class='button button-primary send' href='admin.php?page=morkvanp_invoice&post=$order_id'>Створити</a></div>";
            echo '<h4 style="margin-bottom:5px;">' . __('Додати вручну створену накладну', 'nova-poshta-ttn') . '</h4>';
            echo '<p style="color:#dc3232; margin-top:0;">Лише у Про-версії</p>';
            echo '<div style="opacity: 0.7;"><input disabled type="text" name="mrkv_np_add_custom_ttn" value=""><span class="button button-primary mrkv_np_add_custom_ttn__send">' . __('Додати', 'nova-poshta-ttn') . '</span></div>';
            echo '<h4 style="margin-bottom:5px;">' . __('Накладна', 'nova-poshta-ttn') . '</h4>';
            
            $this->invoice_meta_box_info($order_id);

        } else {
            if ( ! \get_option( 'mrkvnp_invoice_payer' ) ) {
                echo '<style>#npttn_newttn{display:none;}</style>';
            }
        }
    }

    /**
     * Generating meta boxes
     *
     * @since 1.0.0
     */
    public function mv_add_meta_boxes()
    {
        # Check hpos
        if(class_exists( CustomOrdersTableController::class )){
            $screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id( 'shop-order' )
            : 'shop_order';
        }
        else{
            $screen = 'shop_order';
        }

        add_meta_box('npttn_newttn', __('Нова Пошта', 'woocommerce'), array( $this, 'add_plugin_meta_box' ), $screen, 'side', 'core');
    }

    /**
     * Creating custom column at woocommerce order page
     *
     * @since 1.1.0
     */
    public function woo_custom_column($columns)
    {
        $columns['created_invoice'] = 'Накладна';
        $columns['invoice_number'] = 'Номер накладної';
        return $columns;
    }

    /**
     * Getting data of order column at order page
     *
     * @since 1.1.0
     */
    public function woo_column_get_data($column)
    {
        global $the_order;

        if ($column == 'created_invoice') { //will be deprecated
            $meta_ttn = $the_order->get_meta('novaposhta_ttn');

            if (!empty($meta_ttn)) {
                $img = "/nova_poshta_25px.png";
                echo '<img src="' . site_url() . '/wp-content/plugins/' . plugin_basename(__DIR__) . $img . '" />';
            } else {
                $img = '/nova_poshta_grey_25px.png';
                echo '<img src="' . site_url() . '/wp-content/plugins/' . plugin_basename(__DIR__) . $img . '" />';
            }
        }

        if ($column == 'invoice_number') {
            $meta_ttn = $the_order->get_meta('novaposhta_ttn');

            if ( ! empty( $meta_ttn ) ) {
                echo '<a taget="_blank" href="https://novaposhta.ua/tracking/?cargo_number=' .
                    $meta_ttn . '">' . $meta_ttn . '</a>';
            } else {
                echo "";
            }
        }
    }

    /**
     * Getting data of order column at order page
     *
     * @since 1.1.0
     */
    public function woo_column_get_data_hpos($column, $the_order)
    {

        if ($column == 'created_invoice') { //will be deprecated
            $meta_ttn = $the_order->get_meta('novaposhta_ttn');

            if (!empty($meta_ttn)) {
                $img = "/nova_poshta_25px.png";
                echo '<img src="' . site_url() . '/wp-content/plugins/' . plugin_basename(__DIR__) . $img . '" />';
            } else {
                $img = '/nova_poshta_grey_25px.png';
                echo '<img src="' . site_url() . '/wp-content/plugins/' . plugin_basename(__DIR__) . $img . '" />';
            }
        }

        if ($column == 'invoice_number') {
            $meta_ttn = $the_order->get_meta('novaposhta_ttn');

            if ( ! empty( $meta_ttn ) ) {
                echo '<a taget="_blank" href="https://novaposhta.ua/tracking/?cargo_number=' .
                    $meta_ttn . '">' . $meta_ttn . '</a>';
            } else {
                echo "";
            }
        }
    }

    /**
     * Add info of invoice meta box
     *
     * @since 1.1.0
     */
    public function invoice_meta_box_info($order_id)
    {
        global $wpdb;
        $api_key = get_option('mrkvnp_sender_api_key');
        $selected_order = wc_get_order($order_id);

        $order = $selected_order->get_data();

        if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled())
        {
            $meta_ttn = $selected_order->get_meta('novaposhta_ttn');
        }
        else
        {
            $meta_ttn = get_post_meta($order_id, 'novaposhta_ttn', true);
        }
        
        if (empty($meta_ttn)) {//legacy support
            global $wpdb;
            $result = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}novaposhta_ttn_invoices WHERE order_id = '$order_id'", ARRAY_A);
            if (isset($result[0]['order_invoice'])) {
                $meta_ttn = $result[0]['order_invoice'];
                //номер тут не  завжди правильно показує
            }
        }
        $invoice_email = $order['billing']['email'];

        if (! empty($meta_ttn)) {
            $invoice_number = $meta_ttn;
            echo 'Номер накладної: ' . $meta_ttn;
            echo '<a style="margin: 5px;" href="https://my.novaposhta.ua/orders/printDocument/orders[]/' .  $invoice_number . '/type/pdf/apiKey/' .  $api_key . '" class="button" target="_blank">' . '<img src="' . plugins_url('img/004-printer.png', __FILE__) . '" height="15" width="15" />' . ' Друк накладної</a>';
            echo '<a style="margin: 5px;" href="https://my.novaposhta.ua/orders/printMarkings/orders[]/' . $invoice_number . '/type/pdf/apiKey/' . $api_key . '" class="button" target="_blank">' . '<img src="' . plugins_url('img/003-barcode.png', __FILE__) . '" height="15" width="15"  />' . ' Друк стікера</a>';
            echo '<div style="margin: 5px;" href="" class="button mrkv-np-remove-ttn" target="_blank">' . '<img src="' . plugins_url('img/001-delete-button.png', __FILE__) . '" height="15" width="15"  />' . __('Видалити накладну', 'nova-poshta-ttn') . '</div>';
            echo '<script>
                jQuery(".mrkv-np-remove-ttn").click(function(event){
                    event.preventDefault();
                    jQuery.ajax({
                        url: "'. admin_url( "admin-ajax.php" ) . '",
                        type: "POST",
                        data: "action=mrkv_np_remove_ttn&order_id=' . $order_id . '&ttn=' . $invoice_number . '", 
                        success: function( data ) {
                            location.reload();
                        }
                    });
                });
            </script>';


            $api_key = get_option('mrkvnp_sender_api_key');

            $methodProperties = array(
            "Documents" => array(
                array(
                    "DocumentNumber" => $invoice_number
                    ),
                )
        );

            $invoiceData = array(
            "apiKey" => $api_key,
            "modelName" => "TrackingDocument",
            "calledMethod" => "getStatusDocuments",
            "methodProperties" => $methodProperties
        );

            $curl = curl_init();

            $url = "https://api.novaposhta.ua/v2.0/json/";

            curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($invoiceData),
            CURLOPT_HTTPHEADER => array("content-type: application/json",),
        ));

            $response = curl_exec($curl);
            $error = curl_error($curl);
            curl_close($curl);

            if ($error) {
            } else {
                $response_json = json_decode($response, true);

                // echo "<pre>";
                if (isset($response_json["data"][0])) {
                    $obj = (array) $response_json["data"][0];
                }
            }

            // var_dump( $obj['Number'] ); ?>
            
            <input type="text" name="invoice_email" id="invoice_email" value="<?php echo $invoice_email; ?>" style="display: none;" />
            <input type="text" name="invoice_number" id="invoice_number" value="<?php echo $invoice_number; ?>" style="display: none;" />
            <input type="text" name="order_id" id="order_id" value="<?php echo $order_id; ?>" style="display: none;" />
            <input type="text" id="date_created" value="<?php // echo $obj['DateCreated']; ?>" style="display: none;" />
            <?php
        } else {
            echo 'Номер накладної: -';
        }
    }

    /**
     * Remove invoice 
     * */
    public function mrkv_np_remove_ttn_func()
    {
        # Check order id
        if(isset($_POST['order_id']) && isset($_POST['ttn']))
        {
            # Get order data
            $invoice_number = $_POST['ttn'];
            $order_id = $_POST['order_id'];

            # Get api key
            $api_key = get_option('mrkvnp_sender_api_key');
            $api_url = 'https://api.novaposhta.ua/v2.0/json/';

            # Get invoice ref
            global $wpdb;
            $resultstring = "SELECT `invoice_ref` FROM {$wpdb->prefix}novaposhta_ttn_invoices WHERE `order_id` = '{$order_id}'";
            $invoice_ref = $wpdb->get_var($resultstring);

            # Check invoice ref
            if($invoice_ref)
            {
                # Set method delete
                $methodProperties_delete = array(
                    "DocumentRefs" => $invoice_ref
                );

                # Create params for delete
                $deleteBulkInvoices = array(
                    "apiKey" => $api_key,
                    "modelName" => "InternetDocument",
                    "calledMethod" => "delete",
                    "methodProperties" => $methodProperties_delete
                );

                # Set args data
                $args = array(
                    'timeout' => 30,
                    'redirection' => 10,
                    'httpversion' => '1.0',
                    'blocking' => true,
                    'headers' => array( "content-type" => "application/json" ),
                    'body' => \json_encode( $deleteBulkInvoices ),
                    'cookies' => array(),
                    'sslverify' => false,
                );

                # Send request 
                $response = wp_remote_post( $api_url, $args );

                # Set database name
                $delete_table_name = $wpdb->prefix . 'novaposhta_ttn_invoices';
                # Set database name
                $delete_table_name_meta = $wpdb->prefix . 'postmeta';

                if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled())
                {
                    $order = wc_get_order( $order_id );
                    # Delete ttn meta
                    $order->delete_meta_data( "novaposhta_ttn" );

                    $order->save();
                }
                else
                {
                    # Delete ttn meta
                    delete_post_meta( $order_id , "novaposhta_ttn" );
                }

                # Delete ttn from db
                $delete_from_db = $wpdb->delete( $delete_table_name, array( 'invoice_ref' => $invoice_ref ) );

                # Delete ttn from db
                $delete_from_db = $wpdb->delete( $delete_table_name_meta, array( 'post_id' => $order_id, 'meta_key' => 'novaposhta_ttn' ) );
            }

            die;
        }   
    }

    /**
     * From name email
     *
     * @since 1.1.3
     */
    public function my_mail_from_name($name)
    {
        //$bloginfo = get_bloginfo();
        //$title = $bloginfo->name;

        return get_option('blogname');
    }

    /**
     * Helper
     *
     * @since 1.7.44
     */
    public function dd($variable)
    {
        echo '<pre>';
        // echo {$variable} . ' = ';
        var_dump($variable);
        // print_r($variable);
        echo '</pre>';
    }

    /**
     * Add custom column to my account
     * @param array All columns
     * @return array All columns
     * */
    public function mrkv_np_add_account_orders_column($columns)
    {
        # Create new column
        $new_columns = array();

        # Loop all columns
        foreach ($columns as $key => $name) 
        {
            $new_columns[ $key ] = $name;

            # Add ship-to after order status column
            if ('order-status' === $key) 
            {
                $new_columns['order-ship-to'] = __('НП ТТН', 'nova-poshta-ttn');
            }
        }
        return $new_columns;
    }

    /**
     * Add content to custom column My account
     * @param object Order
     * */
    public function mrkv_np_add_account_orders_column_rows($order)
    {
        # Get order id
        $order_id = $order->get_id();

        if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled())
        {
            # Get ttn data
            $meta_ttn = $order->get_meta('novaposhta_ttn');
        }
        else
        {
            # Get ttn data
            $meta_ttn = get_post_meta($order_id, 'novaposhta_ttn', true);   
        }
        

        # Legacy support
        if (empty($meta_ttn)) 
        {
            # Get ttn from database
            global $wpdb;
            $result = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}novaposhta_ttn_invoices WHERE order_id = '$order_id'", ARRAY_A);

            # Check reult
            if (isset($result[0]['order_invoice'])) 
            {
                $meta_ttn = $result[0]['order_invoice'];
            }
        }

        if($meta_ttn)
        {
            echo '<a target="_blank" href=https://novaposhta.ua/tracking/?cargo_number='.$meta_ttn.'>' . $meta_ttn . '</a>';
        }
        else{
            echo '-';
        }
    }
}
