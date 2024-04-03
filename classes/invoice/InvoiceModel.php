<?php

namespace plugins\NovaPoshta\classes\invoice;
use Automattic\WooCommerce\Utilities\OrderUtil;

// If this file is called directly, abort.
defined( 'ABSPATH' ) or die();

class InvoiceModel
{
    public $api_key;

    public $api_url = 'https://api.novaposhta.ua/v2.0/json/';

    public $order_obj;

    public $servicetype;

    public function __construct()
    {
        $this->api_key = \sanitize_text_field( \get_option( 'mrkvnp_sender_api_key' ) );
        $this->order_obj = $this->getOrderObj( $this->getOrderId() );
        if ( $this->getOrderId() ) {
            $this->servicetype = $this->getServiceType( $this->getOrderId() );
        }
    }

    #---------- Get order data ----------

    public function getOrderObj($order_id)
    {
        return  \wc_get_order($order_id);
    }

    public function getOrderId()
    {
        if ( ! empty( $_GET['post'] ) ) {
            return $_GET['post'];
        }
        if ( isset($_SESSION['order_id'] ) ) {
            return $_SESSION['order_id'];
        }
        if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
            $referer_url = parse_url( $_SERVER['HTTP_REFERER'], PHP_URL_QUERY );
            parse_str( $referer_url, $query_url_arr );
            if ( ! empty( $query_url_arr['post'] ) ) {
                $_SESSION['order_id'] = $query_url_arr['post'];
                return $query_url_arr['post'];
            }
        }
        if ( isset( $_SERVER['REQUEST_URI'] ) ) {
            $uri_parts = explode( '/', $_SERVER['REQUEST_URI'] );
            return $uri_parts[count($uri_parts) - 2];
        }
        return false;
    }

    public function getOrderData($order_id)
    {
        if ( ! $this->getOrderId() ) return;
        $order_id = $this->getOrderId();
        $order = \wc_get_order( $order_id );
        $order_data = $order->get_data();
        return  ! empty( $order_data ) ? $order_data : false;
    }

    public function setInternalOrderNumber()
    {
        if ( isset( $_POST['InfoRegClientBarcodes'] ) ) {
            return \sanitize_text_field( $_POST["InfoRegClientBarcodes"] );
        }
    }

    public function getShippingFirstName($order_data)
    {
        return ! empty( $order_data['shipping']['first_name'] )
            ? esc_html( $order_data['shipping']['first_name'] )
            : esc_html( $order_data['billing']['first_name'] );
    }

    public function getShippingLastName($order_data)
    {
        return ! empty( $order_data['shipping']['last_name'] )
            ? esc_html( $order_data['shipping']['last_name'] )
            : esc_html( $order_data['billing']['last_name'] );
    }

    public function getShippingMiddleName($order_id)
    {
        if ( isset( $_POST['mrkvnp_invoice_recipient_middle_name'] ) &&
                ! empty( $_POST['mrkvnp_invoice_recipient_middle_name'] ) ) {
            return \sanitize_text_field( $_POST['mrkvnp_invoice_recipient_middle_name'] );
        }
        if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled())
        {
            $order = wc_get_order( $order_id );

            if ( ! empty( $order->get_meta('_shipping_mrkvnp_patronymics') ) ) {
                return $order->get_meta('_shipping_mrkvnp_patronymics');
            }
            if ( ! empty( $order->get_meta('_billing_mrkvnp_patronymics') ) ) {
                return $order->get_meta('_billing_mrkvnp_patronymics');
            }
            

            $order->save();
        }
        else
        {
            if ( ! empty( get_post_meta( $order_id, '_shipping_mrkvnp_patronymics', true ) ) ) {
                return get_post_meta( $order_id, '_shipping_mrkvnp_patronymics', true );
            }
            if ( ! empty( get_post_meta( $order_id, '_billing_mrkvnp_patronymics', true ) ) ) {
                return get_post_meta( $order_id, '_billing_mrkvnp_patronymics', true );
            }
            
        }
        return '';
    }

    public function getShippingWarehouseName($order_data)
    {
        if ( isset( $order_data['shipping']['address_1'] ) &&
                ! empty( $order_data['shipping']['address_1'] ) ) {
            return $order_data['shipping']['address_1'];
        }
        return $order_data['billing']['address_1'];
    }

    public function getShippingStreetName($order_data)
    {
        $streetHouseArr = array();
        $streetHouse = \trim( $this->getShippingWarehouseName( $order_data ) );
        $posBlank = strpos( $streetHouse, ' ');
        $posComma = strpos( $streetHouse, ',' );
        $streetHouseArr = explode( " ", $streetHouse );
        $streetLen   = $posComma - $posBlank - 1;
        return substr( $streetHouse, $posBlank + 1, $streetLen );
    }

    public function getShippingBuildingNumber($order_data)
    {
        $streetHouseArr = array();
        $streetHouse = \trim( $this->getShippingWarehouseName( $order_data ) );
        $posComma = strpos( $streetHouse, ',' );
        return substr( $streetHouse, $posComma + 1 );
    }

    public function getShippingCityName($order_data)
    {
        if ( isset( $_POST['mrkvnp_invoice_recipient_city_name'] ) && ! empty( $_POST['mrkvnp_invoice_recipient_city_name'] ) ) {
            return \sanitize_text_field( $_POST['mrkvnp_invoice_recipient_city_name'] );
        }
        return ! empty( $order_data['shipping']['city'] )
            ? $order_data['shipping']['city']
            : $order_data['billing']['city'];
    }

    public function getShippingStateName($order_data)
    {
        return ! empty( $order_data['shipping']['state'] )
            ? $order_data['shipping']['state']
            : $order_data['billing']['state'];
    }

    public function getShippingPhone($order_data)
    {
        return ! empty( $order_data['shipping']['phone'] )
            ? str_replace( array('+', ' ', '(' , ')', '-'), '', \sanitize_text_field( $order_data['shipping']['phone'] ) )
            : str_replace( array('+', ' ', '(' , ')', '-'), '', \sanitize_text_field( $order_data['billing']['phone'] ) );
    }

    public function getShippingEmail($order_data)
    {
        return ! empty( $order_data['shipping']['email'] )
            ? $order_data['shipping']['email']
            : $order_data['billing']['email'];
    }

    public function getShippingFlat($order_data)
    {
        return ! empty( $order_data['shipping']['address_2'] )
            ? $order_data['shipping']['address_2']
            : $order_data['billing']['address_2'];
    }

    public function createRecipientStreetRef()
    {
        $methodProperties = array(
            "FirstName" => $this->recipient_first_name,
            "MiddleName" => $this->recipient_middle_name,
            "LastName" => $this->recipient_last_name,
            "Phone" => $this->recipient_phone,
            "Email" => "",
            "CounterpartyType" => "PrivatePerson",
            "CounterpartyProperty" => "Recipient"
        );
        $counterpartyRecipient = array(
            "apiKey" => $this->api_key,
            "modelName" => "Address",
            "calledMethod" => "save",
            "methodProperties" => $methodProperties
        );
        $obj = $this->invoiceModel()->sendPostRequest( $this->api_url, $counterpartyRecipient );
        return $obj['data'][0]['Ref'];
    }

    public function getInvoicePrice($order_id)
    {
        return isset( $_POST['mrkvnp_order_total'] )
            ? $_POST['mrkvnp_order_total']
            : $this->order_obj->get_total();
    }

    public function getInvoiceDescription()
    {
        return ( isset( $_POST['mrkvnp_invoice_description'] ) )
            ? str_replace( "\'","'",$_POST['mrkvnp_invoice_description'] )
            : '';
    }

    public function getInvoiceDescriptionRed()
    {
        if ( isset( $_POST['mrkvnp_invoice_descriptionred']) && ! empty($_POST['mrkvnp_invoice_descriptionred'] ) ) {
            return  $_POST['mrkvnp_invoice_descriptionred'];
        }
    }

    public function getCargoType()
    {
        return isset( $_POST['mrkvnp_invoice_cargo_type'] )
            ? $_POST['mrkvnp_invoice_cargo_type']: \sanitize_text_field( \get_option( 'mrkvnp_invoice_cargo_type' ) );
    }

    public function getPaymentMethod($order_obj)
    {
        return $order_obj->get_payment_method();
    }

    public function getInvoicePayer($order_obj)
    {
        $invoicePayerOption = \get_option( 'mrkvnp_invoice_payer' );
        if ( isset( $_POST['mrkvnpinv_invoice_payer'] ) && $this->isFreeShipping( $order_obj ) ) {
            return 'Sender';
        } else {
            return $invoicePayerOption;
        }
    }

    public function isFreeShipping($order_obj)
    {
        $wc_cart_total_free_min = $this->get_free_shipping_minimum();
        $order_total = $order_obj->get_total();
        if ( isset( $order_total) && $order_total > 0
                && $order_total >= $wc_cart_total_free_min
                && $wc_cart_total_free_min > 0 ) {
            return true;
        }
        return false;

    }

    // Get minimum shipping sum for Nova Poshta
    protected function get_free_shipping_minimum($zone_name = 'Україна') {
        if ( ! isset( $zone_name ) ) return null;

        $result = null;
        $zone = null;

        $zones = \WC_Shipping_Zones::get_zones();
        foreach ( $zones as $z ) {
            if ( $z['zone_name'] == $zone_name ) {
                $zone = $z;
            }
        }

        if ( $zone ) {
            $shipping_methods_nl = $zone['shipping_methods'];
            $free_shipping_method = null;
            foreach ( $shipping_methods_nl as $method ) {
                if ( $method->id == 'nova_poshta_shipping_method' ||
                     $method->id == 'nova_poshta_shipping_method_poshtomat' ||
                     $method->id == 'npttn_address_shipping_method' ) {
                    $free_shipping_method = $method;
                    break;
                }
            }

            if ( $free_shipping_method ) {
                $threshold = ( $free_shipping_method->instance_settings['free_shipping_min_sum'] )
                    ?: 0;
                if ( $threshold > 0) {
                    return $threshold;
                } else {
                    return 0;
                }
            }
        }
        return 0;
    }

    public function isPaymentOnDelivery($order_id)
    {
        return ( isset( $_POST['mrkvnp_is_invoice_cod'] ) ) ? 'ON' : 'OFF';
    }

    public function isPaymentControl($order_id)
    {
        $redeliveryString = isset( $_POST['mrkvnp_order_total'] )
            ? $_POST['mrkvnp_order_total']
            : $this->getInvoicePrice( $order_id );
        $backwardDeliveryData = array(
            "PayerType" => $this->getCodPayer(),
            "CargoType" => "Money",
            "RedeliveryString" => $redeliveryString
        );
        return $backwardDeliveryData;
    }

    public function isDescriptionRed()
    {
        if ( isset( $_POST['mrkvnp_invoice_descriptionred'] ) && ! empty( $_POST['mrkvnp_invoice_descriptionred'] ) ) {
            return \sanitize_text_field( $_POST['mrkvnp_invoice_descriptionred'] );
        }
        else{
            return "";
        }
    }

    public function getCodPayer() // cod - cash on delivery (післяплата)
    {
        $invoiceCodPayerOption = '';
        return isset( $_POST['mrkvnp_cod_payer'] )
            ? \sanitize_text_field( $_POST['mrkvnp_cod_payer'] )
            : $invoiceCodPayerOption;
    }

    public function getInvoiceLength($order_data)
    {
        $dimensions = $this->calcOrderDimensions($order_data);
        return ( isset( $_POST['mrkvnp_invoice_cargo_length'] ) && $_POST['mrkvnp_invoice_cargo_length'] > 0 )
            ? \sanitize_text_field( intval( $_POST['mrkvnp_invoice_cargo_length'] ) / 100 ) : $dimensions[0];
    }

    public function getInvoiceWidth($order_data)
    {
        $dimensions = $this->calcOrderDimensions($order_data);
        return ( isset( $_POST['order_weight'] ) && $_POST['mrkvnp_invoice_cargo_width'] > 0 )
            ? \sanitize_text_field( intval( $_POST['mrkvnp_invoice_cargo_width'] ) / 100 ) : $dimensions[1];
    }

    public function getInvoiceHeight($order_data)
    {
        $dimensions = $this->calcOrderDimensions($order_data);
        return ( isset( $_POST['mrkvnp_invoice_cargo_height'] ) && $_POST['mrkvnp_invoice_cargo_height'] > 0 )
            ? \sanitize_text_field( intval( $_POST['mrkvnp_invoice_cargo_height'] ) ) / 100 : $dimensions[2];
    }

    public function getCargoWeight($order_obj) : string
    {
        if ( sizeof( $order_obj->get_items() ) > 0 ) {
            $order_weight = 0;
            foreach( $order_obj->get_items() as $item ) {
                if ( $item['product_id'] > 0 ) {
                    $_product = $item->get_product();
                    if ( ! $_product->is_virtual() ) {
                        $order_weight += floatval( $_product->get_weight() ) * intval( $item['qty'] );
                    }
                }
            }
        }
        $default_weight = \get_option('mrkvnp_invoice_weight') ?: '0.5';

        $default_weight = isset( $_POST['mrkvnp_invoice_cargo_weight'] )
            ? \sanitize_text_field( $_POST['mrkvnp_invoice_cargo_weight'] )
            : $default_weight;

        $weight_unit_new = get_option('woocommerce_weight_unit');
        if($weight_unit_new == 'g' && isset( $_POST['mrkvnp_invoice_cargo_weight'] )){
            $default_weight  = $default_weight / 1000;
            $order_weight = $order_weight / 1000;
        }

        return $order_weight > 0 ? (string) floatval( $order_weight ) : $default_weight;
    }

    public function getInvoiceVolume($order_data)
    {
        $invoice_length = intval( $this->getInvoiceLength( $order_data ) );
        $invoice_width = intval( $this->getInvoiceWidth( $order_data ) );
        $invoice_height = intval( $this->getInvoiceHeight( $order_data ) );
        return ( ( $_POST['mrkvnp_invoice_volume'] ) ?? ( $invoice_length * $invoice_width * $invoice_height / 4000 ) ) ?? 0.002;
    }

    public function getInvoicePlaces()
    {
        return isset( $_POST['mrkvnp_invoice_places'] ) ? intval( $_POST['mrkvnp_invoice_places'] ) : 1;
    }

    public function isPackingNumber() // 'Вказувати номер паковання?' checkbox
    {
        return isset( $_POST['mrkvnp_invoice_is_packing_number'] ) ? $_POST['mrkvnp_invoice_is_packing_number'] : \get_option( 'mrkvnp_invoice_is_packing_number' );
    }

    public function getShippingMethod($order_obj)
    {
        $shipping_method_arr = $order_obj->get_shipping_methods();
        $shipping_method = @array_shift( $shipping_method_arr );
        return $shipping_method['method_id'];
    }

    public function getRecipientNote($order_obj)
    {
        return $order_obj->get_customer_note();
    }

    public function getInvoiceDateTime()
    {
        # Change Timezone
        date_default_timezone_set("Europe/Kiev");

        return date( 'd.m.Y');
    }

    public function getDeliveryPaymentMethod()
    {
        $delivery_payer = $this->getInvoicePayer( $this->order_obj );
        if ( 'Recipient' == $delivery_payer ) {
            return 'Cash';
        }
        if ( 'Sender' == $delivery_payer ) {
            return get_option( 'mrkvnp_invoice_payment_type' );
        }
    }

    public function getShippingSettings($arr)
    {
        if( ! empty( get_option( 'mrkvnp_invoice_sender_city_name' ) ) )
            $arr['city_name']=get_option('mrkvnp_invoice_sender_city_name');
        if( ! empty( get_option( 'mrkvnp_invoice_sender_region_name' ) ) )
            $arr['area_name']=get_option('mrkvnp_invoice_sender_region_name');
        if( ! empty( get_option( 'woocommerce_nova_poshta_shipping_method_area' ) ) )
            $arr['area']=get_option('woocommerce_nova_poshta_shipping_method_area');
        if( ! empty( get_option( 'woocommerce_nova_poshta_shipping_method_city' ) ) )
            $arr['city']=get_option('woocommerce_nova_poshta_shipping_method_city');
        if( ! empty( get_option( 'mrkvnp_invoice_sender_warehouse_name' ) ) )
            $arr['warehouse_name']=get_option('mrkvnp_invoice_sender_warehouse_name');
        if( ! empty( get_option( 'mrkvnp_invoice_sender_warehouse_ref' ) ) )
            $arr['warehouse']=get_option('mrkvnp_invoice_sender_warehouse_ref');
        return $arr;
    }

    public function getServiceType($order_id)
    {
        if ( ! is_object( $this->order_obj ) ) return false;
        $orderItems = $this->order_obj->get_items( 'shipping' );
        $itemObj = array_shift( $orderItems );
        if(isset($itemObj)){
            $itemData = $itemObj->get_data();
            $shipMethodId = $itemData['method_id'];
            if ( ! get_option('mrkvnp_invoice_sender_warehouse_type' ) &&
                ( strpos( $shipMethodId, 'nova_poshta_shipping_method_poshtomat' ) !== false ) ) {
                return 'WarehousePostomat';
            }
            if ( ! get_option('mrkvnp_invoice_sender_warehouse_type' ) &&
                ( strpos( $shipMethodId, 'nova_poshta_shipping_method' ) !== false ) ) {
                return 'WarehouseWarehouse';
            }
            if ( ! get_option('mrkvnp_invoice_sender_warehouse_type' ) &&
                ( strpos( $shipMethodId, 'npttn_address_shipping_method' ) !== false ) ) {
                return 'WarehouseDoors';
            }
            if ( get_option('mrkvnp_invoice_sender_warehouse_type' ) &&
                ( strpos( $shipMethodId, 'nova_poshta_shipping_method_poshtomat' ) !== false ) ) {
                return 'DoorsWarehouse';
            }
            if ( get_option('mrkvnp_invoice_sender_warehouse_type' ) &&
                ( strpos( $shipMethodId, 'nova_poshta_shipping_method' ) !== false ) ) {
                return 'DoorsPostomat';
            }
            if ( get_option('mrkvnp_invoice_sender_warehouse_type' ) &&
                ( strpos( $shipMethodId, 'npttn_address_shipping_method' ) !== false ) ) {
                return 'DoorsDoors';
            }
        }
    }

    #---------- Make requests to API Nova Poshta ----------

    public function sendPostRequest($url, $bodyArr) // Send general request
    {
		$args = array(
			'timeout' => 30,
			'redirection' => 10,
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => array( "content-type" => "application/json" ),
			'body' => \json_encode( $bodyArr ),
			'cookies' => array(),
			'sslverify' => false,
		);
		$response = wp_remote_post( $url, $args ); // Error check
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			return "Что-то пошло не так: $error_message";
		} else {
			$body = wp_remote_retrieve_body( $response );
			$obj = json_decode($body, true);
		}
		return $obj;
    }

    public function getApiNPErrorsList($methodProperties) // Send request to get whole errors list
    {
        $api_url = 'https://api.novaposhta.ua/v2.0/json/';
        $getApiNPErrorsList = array(
            "apiKey" => $this->api_key,
            "modelName" => "CommonGeneral",
            "calledMethod" => "getMessageCodeText",
            "methodProperties" => $methodProperties
        );
        return $this->sendPostRequest( $api_url, $getApiNPErrorsList );
    }

    #---------- Show result of creating invoice messages ----------

    private function displayInvoiceModal($order_id, $title, $content) {
        $href = '/wp-admin/post.php?post=' . $order_id . '&amp;action=edit';
        ?>
        <div class="modal" id="modalId">
            <div class="inner_modal">
                <div class="modal_content">
                    <p><?php echo $title; ?>
                        <!-- <span class="dashicons dashicons-printer"></span>
                        <span class="dashicons dashicons-email-alt"></span>
                        <span class="dashicons dashicons-trash"></span> -->
                        <?php echo $content; ?>
                        <p class="mnpgap"></p>
                        <div class="modal_footer">
                            <a href="<?php echo $href; ?>" class="mnpmodalbtn button">
                                <span style="color:#E95420;" class="dashicons dashicons-arrow-left-alt"></span> До замовлення
                            </a>
                            <a href="/wp-admin/admin.php?page=morkvanp_invoices" class="mnpmodalbtn button"> Мої накладні</a>
                        </div>
                    </p>
                </div>
            </div>
        </div>

        <script>
            jQuery(document).ready(function () {
                function closeModal(modal) {
                    modal.removeClass('active');
                }
                function openModal(modal) {
                    modal.addClass('active');
                }
                jQuery(".modal").addClass("active");
                // jQuery("#wpwrap").addClass("bgcrgba01");
                // jQuery(".inner_modal").addClass("bgcrgba01");
                // jQuery(".mrkvnp-create-invoice").addClass("bgcrgba01");
                // jQuery(".create-invoice-all").addClass("bgcrgba01");
                // jQuery('.modal').on ('click', function(e) {
                jQuery(document).click(function (event) {
                    var $target = $(event.target);
                    if (!$target.closest('.modal_content').length && jQuery('.modal_content').is(":visible")) {
                        console.log("clicked outside the element")
                        jQuery('#modalId').slideUp();
                        // jQuery("#wpwrap").removeClass("bgcrgba01");
                    }
                });
                    // jQuery('#modalId').slideUp();
                    // jQuery("#wpwrap").removeClass("bgcrgba01");
                // });
                jQuery(document).keydown(function(e){
                    if(e.keyCode == 27) {
                        jQuery('#modalId').slideUp();
                        // jQuery("#wpwrap").removeClass("bgcrgba01");
                    }
                });
            });
        </script>
        <?php
    }

    public function displayErrorMsg($order_id, $obj_errors, $invoice_obj) // $obj_errors - all API-NP errors, $invoice_obj - invoice array that was got
    {
        $invoiceCodes = $invoiceUAMsgs = $invoiceENMsgs = $invoiceRUMsgs = array();
        if ( isset( $obj_errors['data'] ) && is_array( $obj_errors['data'] ) ) {
            for ( $i = 0; $i < \sizeof( $obj_errors['data'] ); $i++ ) {
                if ( \in_array( $obj_errors['data'][$i]['MessageCode'], $invoice_obj['errorCodes'] ) ) {
                    $invoiceCodes[] = $obj_errors['data'][$i]['MessageCode'];
                    $invoiceENMsgs[] = $obj_errors['data'][$i]['MessageText'];
                    $invoiceUAMsgs[] = $obj_errors['data'][$i]['MessageDescriptionUA'] ?? $obj_errors['data'][$i]['MessageText'];
                    $invoiceRUMsgs[] = $obj_errors['data'][$i]['MessageDescriptionRU'];
                }
            }
            $local = \get_locale();
            if ( 'uk' == $local ) $errormessages = $invoiceUAMsgs;
            if ( 'uk_UA' == $local ) $errormessages = $invoiceUAMsgs;
            if ( 'en_US' == $local ) $errormessages = $invoiceENMsgs;
            if ( 'ru_RU' == $local ) $errormessages = $invoiceRUMsgs;
            if ( 'ru' == $local ) $errormessages = $invoiceRUMsgs;
        }
        $errorText = implode( '. ', $errormessages );
        $errorCode = implode( ', ', $invoiceCodes );
        $content = $errorCode . ': ' . $errorText;
        $title = __('Увага! Помилки при створенні.', NOVA_POSHTA_TTN_DOMAIN );
        return $this->displayInvoiceModal( $order_id, $title, $content );

        // echo '<div id="errnonp" class="container">';
        //     echo '<div class="card">';
        //         echo '<h3>' . __( "Помилки з API Нова Пошта", "nova-poshta-ttn" ) . '</h3>';
        //         echo "<span>  ";
        //                 echo $errorText . ". ";
        //         echo "</span><br>";
        //         echo "<span> Коди помилок: ";
        //                 echo $errorCode . "." . " ";
        //         echo '</span><div class="clr"></div>';
        //     echo "</div>";
        // echo "</div>";
    }

    public function displaySuccessMsg($order_id, $invoice_id, $sender, $sender_address, $recipient, $recipient_address_name)
    {
        $title = __('Накладна створена успішно', NOVA_POSHTA_TTN_DOMAIN );
        return $this->displayInvoiceModal( $order_id, $title, $invoice_id );
        // if ( $invoice_id ) {
        //     echo '<div class="modal" id="modalId">
        //       <div class="inner_modal">
        //         <div class="modal_content">
        //           <p>Накладна створена успішно';
        //     echo '<span class="dashicons dashicons-printer"></span>
        //               <span class="dashicons dashicons-email-alt"></span>
        //               <span class="dashicons dashicons-trash"></span>';
        //     echo $invoice_id;
        //     echo '<p class="mnpgap"></p>';
        //     echo '<div class="modal_footer">
        //             <a href="/wp-admin/post.php?post=34&amp;action=edit" class="mnpmodalbtn button">
        //             <span style="color:#E95420;" class="dashicons dashicons-arrow-left-alt"></span> До замовлення</a>
        //             <a href="/wp-admin/admin.php?page=morkvanp_invoices" class="mnpmodalbtn button"> Мої накладні</a>
        //             </div>';
        //     echo '</p>
        //         </div>
        //       </div>
        //     </div>'; ?>

            <script>
                // function closeModal(modal) {
                //     modal.removeClass('active');
                // }
                // function openModal(modal) {
                //     modal.addClass('active');
                // }
                // jQuery(".modal").addClass("active");
                // jQuery("#wpwrap").addClass("bgcrgba01");
                // jQuery(".inner_modal").addClass("bgcrgba01");
                // jQuery(".mrkvnp-create-invoice").addClass("bgcrgba01");
                // jQuery(".create-invoice-all").addClass("bgcrgba01");
                // jQuery('.modal').click(function(e) {
                //     const clickTarget = jQuery(e.target);
                //     if (clickTarget.attr('class') == 'inner_modal') {
                //         closeModal(clickTarget.closest('.modal'));
                //         jQuery("#wpwrap").removeClass("bgcrgba01");
                //     }
                // });
                // jQuery(document).keydown(function(e){
                //     if(e.keyCode == 27) {
                //         jQuery('#modalId').hide();
                //         jQuery("#wpwrap").removeClass("bgcrgba01");
                //     }
                // });
            </script>
            <?php
        // }
    }

    #---------- Save created invoice data in DB ----------

    public function saveInvoiceRowDB($order_id, $order_invoice, $invoice_ref)
    {
        global $wpdb;
        if ( isset( $invoice_ref ) ) {
            $table_name = $wpdb->prefix.'novaposhta_ttn_invoices';
            $hasRowWithOrderId = $wpdb->get_row( "SELECT * FROM $table_name WHERE order_id = $order_id" );
            $existId =  \is_object( $hasRowWithOrderId ) ? $hasRowWithOrderId->id : null;
            $result_check = $wpdb->replace(
                $table_name,
                array(
                    'id'               => $existId,
                    'order_id'         => $order_id,
                    'order_invoice'    => $order_invoice,
                    'invoice_ref'      => $invoice_ref,
                ),
              array( '%d', '%s', '%s', '%s' )
            );
            $order = \wc_get_order( $order_id ); // Get current order object
            // Update invoice number in meta box 'Накладна' in the admin order edit page (right sidebar)

            if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled())
            {
                $order->update_meta_data( 'novaposhta_ttn', $order_invoice );
            }
            else
            {
                update_post_meta( $order_id, 'novaposhta_ttn', $order_invoice );                
            }
            
            // Add a custom order note in the admin order edit page (right sidebar)
            $order->add_order_note( 'Номер накладної: ' . $order_invoice . '. Нова Пошта' );
            $order->save();
            return $result_check ? true : false;
        }
    }

     #---------- Show 'Створити накладну' page elements ----------

    public function displayNav()  // Display tabs
    {
        $arr_of_pages = array(
            array( 'slug' => 'morkvanp_plugin', 'label' => 'Налаштування' ),
            array( 'slug' => 'morkvanp_invoice', 'label' => 'Створити Накладну' ),
            array( 'slug' => 'morkvanp_invoices', 'label' => 'Мої накладні' )
        );
        echo "<nav class=\"newnaw nav-tab-wrapper woo-nav-tab-wrapper\">";
        $wrs_page = $_GET['page'];
        for ( $i=0; $i < sizeof( $arr_of_pages ); $i++ ) {
            $echoclass = 'nav-tab';
            if ( $wrs_page == $arr_of_pages[$i]['slug'] ) {
                $echoclass = 'nav-tab-active nav-tab';
            }
            echo '<a href=admin.php?page=' . $arr_of_pages[$i]['slug'] . ' class="' . $echoclass . '">' . $arr_of_pages[$i]['label'] . '</a>';
        }
        echo "</nav>";

        // global $wpdb;
        // $cityerr = $warehouserr = $poshtomaterr = '';
        // $citiescountsqlobject = $wpdb->get_results( 'SELECT COUNT(`ref`) as result  FROM `' . $wpdb->prefix . 'nova_poshta_city`' );
        // $citycountsqlobjectresult = $citiescountsqlobject[0]->result ?? false;

        // if ( false === $citycountsqlobjectresult || 0 == $citycountsqlobjectresult ) $cityerr = 'міста';
        //     $warehousecountsqlobject = $wpdb->get_results( 'SELECT COUNT(`ref`) as result FROM `' . $wpdb->prefix . 'nova_poshta_warehouse`' );
        //     $warehousecountsqlobjectresult = $warehousecountsqlobject[0]->result ?? false;

        // if ( false === $warehousecountsqlobjectresult || 0 == $warehousecountsqlobjectresult ) $warehouserr = 'відділення';
        //     $poshtomatcountsqlobject = $wpdb->get_results( 'SELECT COUNT(`ref`) as result FROM `' . $wpdb->prefix . 'nova_poshta_poshtomat`' );
        //     $poshtomatcountsqlobjectresult = $poshtomatcountsqlobject[0]->result ?? false;

        // if ( false === $poshtomatcountsqlobjectresult || 0 == $poshtomatcountsqlobjectresult ) $poshtomaterr = 'поштомати';

        // if ( $cityerr || $warehouserr || $poshtomaterr )
        //     echo '<div id="message" class="error ml0" style="margin:10px 0">
        //         <p style="color:#000"><b>' . MNP_PLUGIN_NAME . '</b>: Дані про <span style="font-style: italic;">' .
        //         $cityerr . ' ' . $warehouserr . ' ' . $poshtomaterr . '</span> відсутні.</p></div>';
        \mrkvnp_show_status_dbtables('mrkvnpnoticewp');
    }

    public function showFormBlockTitle($title)
    {
        echo '<tr>
                <th colspan=2>
                    <h3 class="formblock_title">' . $title . '</h3>
                    <div id="errors"></div>
                </th>
            </tr>';
    }

    public function decodeInvoiceDescription($descriptionarea, $list3, $list2,  $list, $prod_quantity, $prod_quantity2, $orderId ){
        $descriptionarea = str_replace( "[list_qa]", $list2, $descriptionarea );
        $descriptionarea = str_replace( "[list_a]", $list3, $descriptionarea );
        $descriptionarea = str_replace( "[list]", $list, $descriptionarea );
        $descriptionarea = str_replace( "[q]", $prod_quantity, $descriptionarea );
        $descriptionarea = str_replace( "[qa]", $prod_quantity2, $descriptionarea );
        $descriptionarea = str_replace( "[mnporderid]", $orderId, $descriptionarea );
        return $descriptionarea;
    }

    public function showGetCargoType($recipient_city_ref, $recipient_warehouse_name)
    {
        $option = \sanitize_text_field( \get_option( 'mrkvnp_invoice_cargo_type' ) );
        if ( $this->isRecipientTypeOfWarehousePoshtomat( $recipient_city_ref, $recipient_warehouse_name ) ) {  // Поштомат
            $values = array('Parcel', 'Documents' );
            $labels = array('Посилка', 'Документи' );
        } else { // Відділення
            $values = array( 'Parcel', 'Pallet',  'Documents', 'TiresWheels' );
            $labels = array( 'Посилки', 'Палети','Документи', 'Шини-диски' );
        }
        $addattrs = array();
        for ( $i = 0; $i < sizeof( $values ); $i++ ) {
            $addattrs[$i] = '';
            if ( 'Documents' == $values[$i] || 'TiresWheels' == $values[$i] ) {
                $addattrs[$i] .= ' disabled';
            } else $addattrs[$i] = ' ';
            if ( $option == $values[$i] ) {
                $addattrs[$i] .= ' checked="checked"';
            }
        }
        for( $i = 0; $i < sizeof( $values ); $i++ ) {
            echo '<input type="radio" id="' . $values[$i] . '" name="mrkvnp_invoice_cargo_type" value="' .
                $values[$i] . '" ' . $addattrs[$i] . ' />';
            echo '<label for="' . $values[$i] . '" ' . $addattrs[$i] . ' > ' . $labels[$i] . ' </label>';
        }
    }

    public function showGetInvoicePayer($order_data)
    {
        // $orderTotalLimit = \sanitize_text_field( \get_option( 'invoice_dpay' ) );
        // $paymentMethod = \sanitize_text_field( \get_option( 'morkvanp_checkout_auto' ) );
        // $invoice_payer = \sanitize_text_field( \get_option( 'mrkvnp_invoice_payer' ) );
        $invoice_payer = \get_option( 'mrkvnp_invoice_payer' );
        // if ( $orderTotalLimit > 0 ) { // TODO
        //     echo '<select id="mrkvnp_invoice_payer" name="mrkvnp_invoice_payer" >';
        //         echo '<option value="Recipient"';
        //     if ( $order_data['total'] < $orderTotalLimit && ! $paymentMethod ) echo ' selected';
        //     if (  $order_data['total'] < $orderTotalLimit && $paymentMethod ) {
        //         if ( $paymentMethod == $order_data['payment_method'] ) echo ' selected';
        //     }
        //     echo ' >Одержувач</option>';
        //     echo '<option value="Sender" ';
        //     if ( $order_data['total'] > $orderTotalLimit && ! $paymentMethod ) echo ' selected';
        //     if ( $paymentMethod ) {
        //         if ( $paymentMethod != $order_data['payment_method'] ) echo ' selected';
        //     }
        //     echo ">Відправник</option></select>";
        // } else {
        //
        echo '<input type="radio" id="recipientinvpay" name="mrkvnpinv_invoice_payer" value="Recipient" ';
        if ( 'Recipient' == $invoice_payer ) echo ' checked="checked" />';
        echo '<label for="recipientinvpay" > Одержувач </label>';
        echo '<input type="radio" id="senderinvpay" name="mrkvnpinv_invoice_payer" value="Sender" ';
        if ( 'Sender' == $invoice_payer ) echo ' checked="checked" />';
        echo '<label for="senderinvpay" > Відправник </label>';
        // }
        return $invoice_payer;
    }

    public function showGetCodPayer($order_data)
    {
        $codPayer = 'Recipient';
        echo '<input type="radio" id="recipient" name="mrkvnp_cod_payer" value="Recipient" ';
        if ( 'Recipient' == $codPayer ) echo ' checked="checked" />';
        echo '<label for="recipient" > Одержувач </label>';
        echo '<input type="radio" id="sender" name="mrkvnp_cod_payer" value="Sender" ';
        if ( 'Sender' == $codPayer ) echo ' checked="checked" />';
        echo '<label for="sender" > Відправник </label>';
        return $codPayer;
    }

    public function showGetInvoicePaymentType($order_data)
    {
        $option =  \get_option( 'mrkvnp_invoice_payment_type' ) ;
        $values = array( 'Cash', 'NonCash' );
        $labels = array( 'Готівка', 'Безготівка' );
        $addattrs = array( '', '' );
        for ( $i = 0; $i < sizeof( $values ); $i++ ) {
            if ( $values[$i] == $option ) {
                $addattrs[$i] = ' checked="checked"';
            }
        }
        for ( $i = 0; $i < sizeof( $values ); $i++ ) {
            echo '<input type="radio" id="' . $values[$i] . '" name="mrkvnp_invoice_payment_type" value="' .
                $values[$i] . '"' . $addattrs[$i] . ' />';
            echo '<label for="' . $values[$i] . '" >' . ' ' . $labels[$i] . ' ' . '</label>';
        }
    }

    #---------- Make additional request to API Nova Poshta ----------

    public function getRecipientCityByNameRef($recipient_city_name)
    {
        $searchSettlement = array(
            "apiKey" => $this->api_key,
            "modelName" => "Address",
            "calledMethod" => "getCities",
            "methodProperties" => array(
                "FindByString" => $recipient_city_name,
            )
        );
        $obj = $this->sendPostRequest( $this->api_url, $searchSettlement );
        if ( isset( $obj['errors'] ) && ! empty( $obj['errors'] ) ) {

        } else {
            if ( isset( $obj['data'][0]['Ref'] ) ) {
                return $obj['data'][0]['Ref'];
            }
        }
    }

    public function getRecipientTypeOfWarehouse($recipient_city_ref, $recipient_warehouse_name)
    {
        if ( empty( $_POST['create_invoice'] ) ) return;
        $recipientTypeOfWarehouse = array(
            "apiKey" => $this->api_key,
            "modelName" => "Address",
            "calledMethod" => "getWarehouses",
            "methodProperties" => array(
                "CityRef" => $recipient_city_ref,
                // "FindByString" => $recipient_warehouse_name
            )
        );
        $obj = $this->sendPostRequest( $this->api_url, $recipientTypeOfWarehouse );
        return $obj['data'][0]['TypeOfWarehouse'];
    }

    public function isRecipientTypeOfWarehousePoshtomat($recipient_city_ref, $recipient_warehouse_name)
    {
        // Types of warehouses Nova Poshta:
        // 9a68df70-0267-42a8-bb5c-37f427e36ee4 - Вантажне відділення
        // 841339c7-591a-42e2-8233-7a0a00f0ed6f - Поштове відділення
        // 6f8c7162-4b72-4b0a-88e5-906948c6a92f - Parcel Shop
        // f9316480-5f2d-425d-bc2c-ac7cd29decf0 - Пoштомат
        if ( 'WarehousePostomat' == $this->servicetype ||
             'WarehouseWarehouse' == $this->servicetype ||
             'DoorsWarehouse' == $this->servicetype ||
             'DoorsPostomat' == $this->servicetype ) {
            return ( 'f9316480-5f2d-425d-bc2c-ac7cd29decf0' == $this->getRecipientTypeOfWarehouse(
                $recipient_city_ref,
                $recipient_warehouse_name
            ) ) ? true : false;
        }
    }

    #---------- Methods for 'My Invoices' ('Мої накладні') page ----------

    public function runMyInvoicesDeleteRowAction($document_ref, $document_number)
    {
        // Execute 'Видалити ЕН' row action on 'Мої накладні' tab
        $methodProperties_delete = array(
            "DocumentRefs" => $document_ref
        );
        $deleteInvoice = array(
            "apiKey" => $this->api_key,
            "modelName" => "InternetDocument",
            "calledMethod" => "delete",
            "methodProperties" => $methodProperties_delete
        );
        $obj = $this->sendPostRequest( $this->api_url, $deleteInvoice );
        if ( $document_ref == $obj['data'][0]['Ref'] ) {
            global $wpdb;
            $delete_table_name = $wpdb->prefix . 'novaposhta_ttn_invoices';
            $delete_from_db = $wpdb->delete( $delete_table_name, array( 'invoice_ref' => $document_ref ) );
        }
        if ( $obj['errors'] ) {
            $apinp_errors = implode('<br>', $obj['errors'] );
            //echo '<script>alert('. '"Помилки з API Нова Пошта: ' . $apinp_errors . '"' . '); </script>';
        } else {
            echo '<script>alert('. '"Накладна № ' . $document_number . ' видалена."' . '); location.reload(true); </script>';
        }
    }

    public function runMyInvoicesDeleteBulkActions($bulklistdelete)
    {
        // Execute 'Видалити' bulk action on 'Мої накладні' tab
        $pieces = explode( ",", $bulklistdelete );
        $orderinvoices = array();
        for( $i = 0; $i < sizeof( $pieces ); $i++ ) {
            try {
                $document_ref = $pieces[$i];
                $methodProperties_delete = array(
                    "DocumentRefs" => $document_ref
                );
                $deleteBulkInvoices = array(
                    "apiKey" => $this->api_key,
                    "modelName" => "InternetDocument",
                    "calledMethod" => "delete",
                    "methodProperties" => $methodProperties_delete
                );
                $obj = $this->sendPostRequest( $this->api_url, $deleteBulkInvoices );
                if ( $document_ref == $obj['data'][0]['Ref'] ) {
                    global $wpdb;
                    $delete_table_name = $wpdb->prefix . 'novaposhta_ttn_invoices';
                    $invoiceData = $wpdb->get_results( "SELECT * FROM `" . $delete_table_name . "` WHERE  invoice_ref = '" . $document_ref . "'", ARRAY_A );
                    $order_id = $invoiceData[$i]['order_id'];
                    $orderinvoices[$i] = $invoiceData[$i]['order_invoice'];
                    if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled())
                    {
                        $order = wc_get_order( $order_id );
                        if ( $order->get_meta('novaposhta_ttn') == $orderinvoices[$i] ) {
                            $order->delete_meta_data( "novaposhta_ttn" );
                        }
                        $order->save();
                    }
                    else
                    {
                        if ( get_post_meta( $order_id, "novaposhta_ttn") == $orderinvoices[$i] ) {
                            delete_post_meta( $order_id , "novaposhta_ttn" );
                        } 
                    }
                    
                    $delete_from_db = $wpdb->delete( $delete_table_name, array( 'invoice_ref' => $document_ref ) );
                }
                if ( $obj['errors'] ) {
                    $apinp_errors = implode('<br>', $obj['errors'] );
                    //echo '<script>alert('. '"Помилки з API Нова Пошта: ' . $apinp_errors . '"' . '); </script>';
                } else {
                    $orderinvoice = implode( ',', $orderinvoices );
                    //echo '<script>alert('. '"Накладні №№ ' . $orderinvoice . ' видалені."' . '); location.reload(true); </script>';
                }
            } catch( exception $e ) {}
        }
    }

    /**
     * Get list of invoices from the database
     * 
     * @return array List of invoices
     * */
    public function runMyInvoicesGetInvoicesFromDB()
    {
        global $wpdb;

        // Set data for pagination
        $post_per_page = isset( $_GET['limit'] ) ? abs( (int) $_GET['limit'] ) : 10;
        $page = isset( $_GET['cpage'] ) ? abs( (int) $_GET['cpage'] ) : 1;
        $offset = ( $page * $post_per_page ) - $post_per_page;

        // Create a request for a list of invoices
        $resultstring = "SELECT * FROM {$wpdb->prefix}novaposhta_ttn_invoices  ORDER BY id DESC LIMIT $post_per_page OFFSET $offset";
        $results = $wpdb->get_results( $resultstring , ARRAY_A );

        return $results;
    }

    /**
     * Get total of invoices from the database
     * 
     * @return string Total invoices 
     * */
    public function runMyInvoicesGetCountInvoicesFromDB()
    {
        global $wpdb;

        // Create a request for a count of invoices
        $resultstring = "SELECT COUNT(*) FROM {$wpdb->prefix}novaposhta_ttn_invoices";
        $total = $wpdb->get_var($resultstring);

        return $total;
    }

    public function runMyInvoicesGetInvoiceData($invoice, $sender_phone)
    {
        $invoice_number = $invoice['order_invoice'];
        $methodProperties = array(
                "Documents" => array(
                    array(
                        "DocumentNumber" => $invoice['order_invoice'],
                        "Phone" => $sender_phone
                )
            )
        );
        $invoiceData = array(
            "apiKey" => $this->api_key,
            "modelName" => "TrackingDocument",
            "calledMethod" => "getStatusDocuments",
            "methodProperties" => $methodProperties
        );
        $invoice_data_obj = $this->sendPostRequest( $this->api_url, $invoiceData );
        return $invoice_data_obj;
    }

    #---------- Calculate order dimensions and other data ----------

    public function calcOrderDimensions($order_data) {
        $alternate_all = $this->alternate_all($order_data);
        $dimentions = $alternate_all['dimentions']; // Розрахунок об'єму
        $length_array = array();
        $width_array = array();
        if ( $alternate_all['prod_quantity2'] < 2 ) {
            if ( ! empty( $dimentions ) && isset( $dimentions[0] ) ) {
                $max_length_prod = $dimentions[0]['length'];
                $max_width_prod = $dimentions[0]['width'];
                $max_height_prod = $dimentions[0]['height'];
            } else {
                $max_length_prod = 10;
                $max_width_prod = 10;
                $max_height_prod = 10;
            }
        } else {
            if ( ! empty( $dimentions ) ) {
                foreach($dimentions as $key => $value) {
                    $length_array[] = $value['length'];
                    $width_array[] = $value['width'];
                }
                $max_length_prod = max( 10, max( $length_array ) );
                $max_width_prod = max( 10, max( $width_array ) );
            } else {
                $max_length_prod = 10;
                $max_width_prod = 10;
                $max_height_prod = 10;
            }
            if ( isset( $_POST['mrkvnp_invoice_volume'] ) ) {
                $max_height_prod = $_POST['mrkvnp_invoice_volume'] / $max_length_prod / $max_width_prod * 100;
            } else {
                $max_height_prod = 23;
            }
        }
        $length_prod = $max_length_prod;
        $width_prod = $max_width_prod;
        $height_prod = $max_height_prod;
        if ( isset( $_POST['create_invoice'] ) ) {
            $length_prod = $_POST['mrkvnp_invoice_cargo_length'];
            $width_prod = $_POST['mrkvnp_invoice_cargo_width'];
            $height_prod = $_POST['mrkvnp_invoice_cargo_height'];
        }
        return $dimentions = [$length_prod, $width_prod, $height_prod];
    }

    public function alternate_all($order_data){
      // start calculating alternate weight
      $varia = null;
      if(isset ($order_data['line_items'])){
        $varia = $order_data['line_items'];
      }
      $alternate_weight = 0;
      $dimentions = array();
      $d_vol_all = 0;
      $weighte = '';
      $prod_quantity = 0;
      $prod_quantity2 = 0;
      $list = '';
      $list2 = '';
      $list3 = '';
      $descr = '';

      //alternative weight functions
      if(isset ($varia)){
        foreach ($varia as $item){
            $data = $item->get_data();
          $quantity = ($data['quantity']);
          $quanti = $quantity;
          if ( $data['variation_id'] ) {
              $pr_id = $data['variation_id'];
          } else {
              $pr_id = $data['product_id'];
          }
            $product = wc_get_product($pr_id);
            if ( $product->is_type('variable') ) {
            $var_id = $data['variation_id'];
            $variations      = $product->get_available_variations();
            for ($i=0; $i < sizeof($variations) ; $i++){
                    if($variations[$i]['variation_id'] == $var_id ){
                  //print_r($variations[$i]);
                  while ($quanti > 0) {
                    if (is_numeric(  $variations[$i]['weight'] )){
                      $alternate_weight += $variations[$i]['weight'];
                    }
                    if( !($variations[$i]['weight'] > 0)  ){
                      $weighte = 'Маса вказана не  для всіх товарів в кошику. Радимо уточнити цифри.';
                    }

                    array_push($dimentions, $variations[$i]['dimensions']);

                    if ( is_numeric( $variations[$i]['dimensions']['length'] ) && is_numeric( $variations[$i]['dimensions']['width'] ) && is_numeric( $variations[$i]['dimensions']['height'] ) ){
                      $d_vol = $variations[$i]['dimensions']['length'] * $variations[$i]['dimensions']['width'] * $variations[$i]['dimensions']['height'];
                        $d_vol_all += $d_vol;
                    }
                    $quanti--;
                  }
                  //$product = new WC_Product($var_id);
                  $sku = $variations[$i]['sku'];
                  if(!empty($sku)){
                    $sku = '('.$sku.')';
                  }
                  $name = $product->get_title();
                  $list2  .= $name .$sku. ' x '.$quantity.'шт ;';
                  $list3  .= $sku. ' x '.$quantity.'шт ;';
                  $list  .= $name .' x '.$quantity.'шт ;';
                  $prod_quantity += 1;
                  $prod_quantity2 += $quantity;
                    }
                }
            }
            else{
              $sku = $product->get_sku();
              if(!empty($sku)){
                $sku = '('.$sku.')';
              }
              $name = $product->get_title();
              $list2  .= $name .$sku. ' x '.$quantity.'шт ;';
              $list3  .= $sku. ' x '.$quantity.'шт ;';
              $list  .= $name . ' x '.$quantity.'шт ;';
              $prod_quantity += 1;
              $prod_quantity2 += $quantity;
                $diment =0;
                if( (is_numeric($product->get_width()) ) && (is_numeric($product->get_length())) && (is_numeric($product->get_height())) ) {
                $diment = $product->get_length() * $product->get_width() * $product->get_height();
                $d_array = array('length'=>$product->get_length(),'width'=> $product->get_width(), 'height'=>$product->get_height() );
                array_push($dimentions, $d_array);
                $d_vol_all += $diment;
                }
              while ($quantity > 0) {
                $weight = $product->get_weight();
                if ($weight > 0){
                  $alternate_weight += $weight;
                }
                else {
                  $weighte = 'Маса вказана не  для всіх товарів в кошику. Радимо уточнити цифри.';
                }
              $quantity--;
            }
          }
        }
      } //if(isset ($varia)){
      $alternate_vol = $d_vol_all;
      $volumemessage = '';
      if( $prod_quantity2 > 1 ){
        $alternate_vol = $d_vol_all;
        $volumemessage = '<span style="color: #dc3232;">УВАГА! </span> У Відправленні кілька товарів. Об\'єм пораховано з даних про товари. <span style="font-size: 12px;"> Довжина та ширина Відправлення - максимальний розмір товару у Замовленні. Висота дорівнює висоті обраної в налаштуваннях комірки. Ви можете вказати більш точне число.</span>' ;
      }
      else{
        if ( isset($variations) ){
            if ( is_numeric( $variations[0]['dimensions']['length'] ) &&  is_numeric( $variations[0]['dimensions']['width'] ) &&  is_numeric( $variations[0]['dimensions']['height'] ) ){
                    $alternate_vol = $variations[0]['dimensions']['length'] * $variations[0]['dimensions']['width'] * $variations[0]['dimensions']['height'];
                    $volumemessage = '';
            }
        }
      }
      $alternate_vol = $alternate_vol / 4000;
      $arrayreturn = array(
        'weight'=> $alternate_weight,
        'alternate_vol'=> $alternate_vol,
        'volumemessage'=>$volumemessage,
        'list'=>$list,
        'list2'=>$list2,
        'list3'=>$list3,
        'prod_quantity'=>$prod_quantity,
        'prod_quantity2'=>$prod_quantity2,
        'dimentions'=>$dimentions,
        'weighte'=>$weighte,
        'd_vol_all'=>$d_vol_all
      );
      return $arrayreturn;
    }

}
