<?php

namespace plugins\NovaPoshta\classes\invoice;

use plugins\NovaPoshta\classes\invoice\InvoiceModel;
use Automattic\WooCommerce\Utilities\OrderUtil;

// If this file is called directly, abort.
defined( 'ABSPATH' ) or die();

class Recipient
{
    public $order_id;
    public $order_data;

    public $api_key;
    public $api_url = 'https://api.novaposhta.ua/v2.0/json/';

    public $recipient_first_name;
    public $recipient_middle_name;
    public $recipient_last_name;
    public $recipient_names;

    public $recipient_city_name;
    public $recipient_state;
    public $recipient_city_ref;

    public $recipient_ref;
    public $recipient_contact_ref;

    public $recipient_address_name;
    public $recipient_warehouse_name;

    public $recipient_warehouse_ref;

	public $recipient_phone;

    public $servicetype;

    public $invoice_model_obj;

    public function __construct($order_id = '', $invoice_model = '')
    {
        if($invoice_model)
        {
            $this->invoice_model_obj = $invoice_model;
        }
        else
        {   
            $this->invoice_model_obj = new InvoiceModel();
        }
        if($order_id)
        {
            $this->order_id = $order_id;
        }
        else
        {
            $this->order_id = $this->invoice_model_obj->getOrderId();
        }

        $this->api_key = \sanitize_text_field( get_option( 'mrkvnp_sender_api_key' ) );

        if ( isset( $this->order_id ) ) {
            $this->order_data = $this->invoiceModel()->getOrderData( $this->order_id );
        }

        $this->recipient_first_name = $this->getRecipientFirstName();
        $this->recipient_last_name = $this->getRecipientLastName();
        $this->recipient_middle_name = $this->getRecipientMiddleName();

        $this->recipient_names = ( isset( $_POST['mrkvnp_invoice_recipient_last_name'] ) )
            ? ( \sanitize_text_field( $_POST['mrkvnp_invoice_recipient_last_name'] ) . ' ' .
                \sanitize_text_field( $_POST['mrkvnp_invoice_recipient_first_name'] ) )
            : $this->recipient_first_name . ' ' . $this->recipient_last_name;

        $this->recipient_phone = $this->getRecipientPhone();
        $this->recipient_state = $this->getRecipientRegionName($this->order_id);
        $this->recipient_city_name = $this->getRecipientCityName( $this->order_id );
        $this->recipient_city_ref = $this->getRecipientCityByNameRef();

        $this->recipient_warehouse_name = $this->getRecipientWarehouseName();
        $this->recipient_address_name = ( isset( $_POST['mrkvnp_invoice_recipient_warehouse_name'] ) )
            ? \sanitize_text_field( $_POST['mrkvnp_invoice_recipient_warehouse_name'] )
            : $this->getRecipientWarehouseName();
        $this->servicetype = $this->invoiceModel()->getServiceType( $this->order_id );
        if ( 'DoorsDoors' !== $this->servicetype ||
             'WarehouseDoors' !== $this->servicetype ) {
            $this->recipient_warehouse_ref = $this->getRecipientWarehouseRef();
        }

        $this->recipient_ref = $this->createRecipientRef();
        $this->recipient_contact_ref = $this->getRecipientContactRef();

        return $this;
    }

    public function invoiceModel()
    {
        return $this->invoice_model_obj;
    }

    public function getOrderData($order_id)
    {
        $order = \wc_get_order($order_id);
        $this->order_data = $order->get_data();
        return  ! empty( $order_data ) ? $this->order_data : false;
    }

    public function createRecipientRef()
    {
        if ( empty( $_POST['create_invoice'] ) ) return;
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
            "modelName" => "Counterparty",
            "calledMethod" => "save",
            "methodProperties" => $methodProperties
        );
        $obj = $this->invoiceModel()->sendPostRequest( $this->api_url, $counterpartyRecipient );
        return $obj['data'][0]['Ref'];
    }

    public function getRecipientContactRef()
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
            "modelName" => "Counterparty",
            "calledMethod" => "save",
            "methodProperties" => $methodProperties
        );
        $obj = $this->invoiceModel()->sendPostRequest( $this->api_url, $counterpartyRecipient );
        if ( isset( $obj['data'][0]['ContactPerson']['data'][0]['Ref'] ) ) {
            return $obj['data'][0]['ContactPerson']['data'][0]['Ref'];
        }
        return '';
    }

    public function getRecipientFirstName()
    {
        $shipping_first_name = ! empty( $this->order_data['shipping']['first_name'] )
            ? esc_html( $this->order_data['shipping']['first_name'] )
            : esc_html( $this->order_data['billing']['first_name'] );

        $shipping_first_name = str_replace("ʼ", "'", $shipping_first_name);

        $shipping_middle_name = $this->getRecipientMiddleName();
        if ( ! empty( $shipping_middle_name ) && strpos( $shipping_first_name, $shipping_middle_name ) !== false) {
            // If patronymics exists remove it from recipient first name
            $shipping_first_name = substr( $shipping_first_name, 0,
                strlen( $shipping_first_name ) - strlen( $shipping_middle_name ) - 1 );
        }
        return stripslashes($shipping_first_name);

    }

    public function getRecipientLastName()
    {
        $shipping_last_name = ! empty( $this->order_data['shipping']['last_name'] )
            ? esc_html( $this->order_data['shipping']['last_name'] )
            : esc_html( $this->order_data['billing']['last_name'] );

        $shipping_last_name = str_replace("ʼ", "'", $shipping_last_name);

        return stripslashes($shipping_last_name);
    }

    public function getRecipientMiddleName()
    {
        if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled())
        {
            $order = wc_get_order( $this->order_id );

            if ( ! empty( $order->get_meta('_shipping_mrkvnp_patronymics') ) ) {
                $patronymics = $order->get_meta('_shipping_mrkvnp_patronymics');
                $patronymics = str_replace("ʼ", "'", $patronymics);
                return $patronymics;
            }
            if ( ! empty( $order->get_meta('_billing_mrkvnp_patronymics') ) ) {
                $patronymics = $order->get_meta('_billing_mrkvnp_patronymics');
                $patronymics = str_replace("ʼ", "'", $patronymics);
                return $patronymics;
            }
            

            $order->save();
        }
        else
        {
            if ( ! empty( get_post_meta( $this->order_id, '_shipping_mrkvnp_patronymics', true ) ) ) {
                $patronymics = get_post_meta( $this->order_id, '_shipping_mrkvnp_patronymics', true );
                $patronymics = str_replace("ʼ", "'", $patronymics);
                return $patronymics;
            } 
           if ( ! empty( get_post_meta( $this->order_id, '_billing_mrkvnp_patronymics', true ) ) ) {
                $patronymics = get_post_meta( $this->order_id, '_billing_mrkvnp_patronymics', true );
                $patronymics = str_replace("ʼ", "'", $patronymics);
                return $patronymics;
            }
        }
        
        return '';
    }

    public function getRecipientPhone()
    {
        if ( isset( $this->order_id ) ) {
            $recipient_shipping_phone = ! empty( $this->order_data['shipping']['phone'] )
                ? str_replace( array('+', ' ', '(' , ')', '-'), '', esc_html( $this->order_data['shipping']['phone'] ) )
                : str_replace( array('+', ' ', '(' , ')', '-'), '', esc_html( $this->order_data['billing']['phone'] ) );
            $shipping_phone = isset( $_POST['mrkvnp_invoice_recipient_phone'] )
                ? \sanitize_text_field( $_POST['mrkvnp_invoice_recipient_phone'] )
                : $recipient_shipping_phone;
            if ( $this->stringStartsWith( '38', $shipping_phone ) ) return substr( $shipping_phone, 2 );
            if ( $this->stringStartsWith( '+38', $shipping_phone ) ) return substr( $shipping_phone, 3 );
            if ( $this->stringStartsWith( '8', $shipping_phone ) ) return substr( $shipping_phone, 1 );
            return $shipping_phone;
        }
    }

    public function stringStartsWith($startString, $string)
    {
        $len = strlen( $startString );
        return ( substr( $string, 0, $len ) === $startString );
    }

    public function getRecipientCityName($order_id)
    {
        $order_data = $this->order_data;
        if ( isset( $order_data['shipping']['city'] ) && ! empty( $order_data['shipping']['city'] ) ) {
            $recipient_city_name = \sanitize_text_field( $order_data['shipping']['city'] );
        }
        elseif ( isset( $order_data['billing']['city'] ) && ! empty( $order_data['billing']['city'] ) ) {
            $recipient_city_name = \sanitize_text_field( $order_data['billing']['city'] );
        }  else $recipient_city_name = '';
        return ( isset( $_POST['mrkvnp_invoice_recipient_city_name'] ) && ! empty( $_POST['mrkvnp_invoice_recipient_city_name'] ) )
            ? \sanitize_text_field( $_POST['mrkvnp_invoice_recipient_city_name'] ) : $recipient_city_name;
    }

    public function createRecipientAddressRef($order_id)
    {
        $recipientAddress = array(
            "apiKey" => $this->api_key,
            "modelName" => "Address",
            "calledMethod" => "save",
            "methodProperties" => array(
                "CounterpartyRef" => $this->createRecipientRef(),
                "StreetRef" => $this->getRecipientStreetRef( $order_id ),
                "BuildingNumber" => $this->getRecipientBuildingNumber($order_id),
                "Flat" => $this->getRecipientFlatNumber( $order_id ),
                "Note" => ""
            )
        );
        $obj = $this->invoiceModel()->sendPostRequest( $this->api_url, $recipientAddress );
        if ( $obj['errors'] ) {
            $apinp_errors = implode('<br>', $obj['errors'] );
            //echo '<script>alert('. '"API Нова Пошта: ' . 'Помилки при створенні одержувача - ' . $apinp_errors . '."' . '); </script>';
            wp_die( 'Помилки в даних відділення.' );
        }
        return $obj['data'][0]['Ref'];
    }

    public function getRecipientAddressName($order_id)
    {
        $recipientAddressName = array(
            "apiKey" => $this->api_key,
            "modelName" => "Address",
            "calledMethod" => "save",
            "methodProperties" => array(
                "CounterpartyRef" => $this->createRecipientRef(),
                "StreetRef" => $this->getRecipientStreetRef( $order_id ),
                "BuildingNumber" => $this->getRecipientBuildingNumber($order_id),
                "Flat" => $this->getRecipientFlatNumber( $order_id ),
                "Note" => ""
            )
        );
        $obj = $this->invoiceModel()->sendPostRequest( $this->api_url, $recipientAddressName );
        if ( $obj['errors'] ) {
            $apinp_errors = implode('<br>', $obj['errors'] );
            //echo '<script>alert('. '"API Нова Пошта: ' . 'Помилки в даних доставки на адресу - ' . $apinp_errors . '."' . '); </script>';
            wp_die( 'Помилки в даних відділення.' );
        }
        return $obj['data'][0]['Description'];
    }

    public function getRecipientAddressFull($order_id)
    {
        $streetHouseArr = array();
        $order = \wc_get_order( $order_id );
        $order_data = $order->get_data();
        if ( isset( $order_data['shipping']['address_1'] ) &&
                ! empty( $order_data['shipping']['address_1'] ) ) {
            $streetName = $order_data['shipping']['address_1'];
        }
        elseif ( isset( $order_data['billing']['address_1'] ) &&
                ! empty( $order_data['billing']['address_1'] ) ) {
            $streetName = $order_data['billing']['address_1'];
        }  else {}
        $posBlank = strpos( $streetName, ' ');
        $posComma = strpos( $streetName, ',' );
        $streetLen   = $posComma - $posBlank - 1;
        return substr( $streetName, $posBlank + 1, $streetLen );
        $streetHouse = \trim( $streetName );
        $posBlank = strpos( $streetHouse, ' ');
        $posComma = strpos( $streetHouse, ',' );
        $streetHouseArr = explode( " ", $streetHouse );
        $streetLen   = $posComma - $posBlank - 1;
        return substr( $streetHouse, $posBlank + 1, $streetLen );
    }

    public function getRecipientStreetRef($order_id)
    {
        $recipientStreet = array(
            "apiKey" => $this->api_key,
            "modelName" => "Address",
            "calledMethod" => "getStreet",
            "methodProperties" => array(
                "CityRef" => $this->getRecipientCityRef( $order_id ),
                "FindByString" => $this->getRecipientAddressFull( $order_id )
            )
        );
        $obj = $this->invoiceModel()->sendPostRequest( $this->api_url, $recipientStreet );
        if ( $obj['errors'] ) {
            $apinp_errors = implode('<br>', $obj['errors'] );
            //echo '<script>alert('. '"API Нова Пошта: ' . 'Помилки в назві вулиці одержувача - ' . $apinp_errors . '."' . '); </script>';
            wp_die( 'Помилки в даних вулиці одрежувача.' );
        }
        return $obj['data'][0]['Ref'];
    }

    public function getRecipientStreetName($order_id)
    {
        $recipientStreet = array(
            "apiKey" => $this->api_key,
            "modelName" => "Address",
            "calledMethod" => "getStreet",
            "methodProperties" => array(
                "CityRef" => $this->getRecipientCityRef( $order_id ),
                "FindByString" => $this->getRecipientAddressFull( $order_id )
            )
        );
        $obj = $this->invoiceModel()->sendPostRequest( $this->api_url, $recipientStreet );
        if ( $obj['errors'] ) {
            $apinp_errors = implode('<br>', $obj['errors'] );
            //echo '<script>alert('. '"API Нова Пошта: ' . 'Помилки в даних вулиці одержувача - ' . $apinp_errors . '."' . '); </script>';
            wp_die( 'Помилки в даних вулиці одрежувача.' );
        }
        return $obj['data'][0]['StreetsType'] . ' ' . $obj['data'][0]['Description'] . ',';
    }

    public function getRecipientCityRef($order_id)
    {
        if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled())
        {
            $order = wc_get_order( $order_id );
            if ( ! empty( $order->get_meta('_shipping_nova_poshta_city') ) ) {
                return $order->get_meta('_shipping_nova_poshta_city');
            }
            elseif ( ! empty( $order->get_meta('_billing_nova_poshta_city') ) ) {
                return $order->get_meta('_billing_nova_poshta_city');
            }  else return $order->get_meta('np_city_ref');

            $order->save();
        }
        else{
            if ( ! empty( get_post_meta( $order_id, '_shipping_nova_poshta_city', true ) ) ) {
                return get_post_meta( $order_id, '_shipping_nova_poshta_city', true );
            }
            elseif ( ! empty( get_post_meta( $order_id, '_billing_nova_poshta_city', true ) ) ) {
                return get_post_meta( $order_id, '_billing_nova_poshta_city', true );
            }  else return get_post_meta( $order_id, 'np_city_ref', true );
        }
    }

    public function getRecipientRegionName($order_id)
    {
        if ( isset( $_POST['shipping_nova_poshta_region'] ) ) {
            return \sanitize_text_field( $_POST['shipping_nova_poshta_region'] );
        }
        elseif ( isset( $_POST['billing_nova_poshta_region'] ) ) {
            return \sanitize_text_field( $_POST['billing_nova_poshta_region'] );
        }  else return '';
    }

    public function getRecipientRegionRef($order_id)
    {
        if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled())
        {
            $order = wc_get_order( $order_id );

            if ( ! empty( $order->get_meta('_shipping_nova_poshta_region') ) ) {
                return $order->get_meta('_shipping_nova_poshta_region');
            }
            elseif ( ! empty( $order->get_meta('_billing_nova_poshta_region') ) ) {
                return $order->get_meta('_billing_nova_poshta_region');
            }  else return $order->get_meta('np_region_ref');

            $order->save();
        }
        else
        {
            if ( ! empty( get_post_meta( $order_id, '_shipping_nova_poshta_region', true ) ) ) {
                return get_post_meta( $order_id, '_shipping_nova_poshta_region', true );
            }
            elseif ( ! empty( get_post_meta( $order_id, '_billing_nova_poshta_region', true ) ) ) {
                return get_post_meta( $order_id, '_billing_nova_poshta_region', true );
            }  else return get_post_meta( $order_id, 'np_region_ref', true );
        }
    }

    public function getRecipientCityByNameRef()
    {
		$searchSettlement = array(
			"apiKey" => $this->api_key,
			"modelName" => "Address",
            "calledMethod" => "getCities",
			"methodProperties" => array(
                "FindByString" => $this->recipient_city_name,
			)
		);
		$obj = $this->invoiceModel()->sendPostRequest( $this->api_url, $searchSettlement );
        if ( isset( $obj['errors'] ) && ! empty( $obj['errors'] ) ) {

		} else {
            if ( isset( $obj['data'][0]['Ref'] ) ) {
                return $obj['data'][0]['Ref'];
            }
		}
    }

    public function getRecipientWarehouseData($recipient_city_ref, $recipient_warehouse_name)
    {
        $recipientWarehouse = array(
            "apiKey" => $this->api_key,
            "modelName" => "Address",
            "calledMethod" => "getWarehouses",
            "methodProperties" => array(
                "CityRef" => $recipient_city_ref,
                "FindByString" => $recipient_warehouse_name
            )
        );
        $obj = $this->invoiceModel()->sendPostRequest( $this->api_url, $recipientWarehouse );
        return $obj;
    }

    public function getRecipientWarehouseRef()
    {
        if ( isset( $this->recipient_warehouse_ref ) ) return;
        if ( empty( $_POST['create_invoice'] ) ) return;
		$recipientWarehouse = array(
			"apiKey" => $this->api_key,
			"modelName" => "Address",
			"calledMethod" => "getWarehouses",
			"methodProperties" => array(
                // "CityName" => $this->recipient_city_name,
                "CityRef" => $this->recipient_city_ref,
				"FindByString" => (string) $this->recipient_warehouse_name
			)
		);
        // sleep(1);
		$obj = $this->invoiceModel()->sendPostRequest( $this->api_url, $recipientWarehouse );
        if ( empty( $obj['errors'] ) ) return $obj['data'][0]['Ref'];
        $apinp_errors = implode('<br>', $obj['errors'] );
        //echo '<script>alert('. '"API Нова Пошта: ' . 'Помилки в даних відділення одержувача - ' . $apinp_errors . '."' . '); </script>';
    }

    public function getRecipientWarehouseNumber() // Get warehouse number
    {
        $order_data = $this->order_data;

        $recipient_city_name = '';

        if ( isset( $order_data['shipping']['city'] ) && ! empty( $order_data['shipping']['city'] ) ) {
            $recipient_city_name = $order_data['shipping']['city'];
        }
        elseif ( isset( $order_data['billing']['city'] ) && ! empty( $order_data['billing']['city'] ) ) {
            $recipient_city_name = $order_data['billing']['city'];
        }  else $recipient_city_name = '';
        
        $recipient_address_name = array(
            "apiKey" => $this->api_key,
            "modelName" => "Address",
            "calledMethod" => "getWarehouses",
            "methodProperties" => array(
                "CityName" => $recipient_city_name,
                "FindByString" => $this->recipient_warehouse_name
            )
        );
        $obj = $this->invoiceModel()->sendPostRequest( $this->api_url, $recipient_address_name );
        if ( isset( $obj['data'][0]['Number'] ) ) {
            return $obj['data'][0]['Number'];
        }
        else
        {
            preg_match_all('!\d+!', $this->recipient_warehouse_name, $matches);

            if(isset($matches[0][0]))
            {
                return $matches[0][0];
            }
        }
        return '';
    }

    public function getRecipientWarehouseName()
    {
        if ( isset( $this->order_id ) ) {
            $shipping_warehouse_name = ! empty( $this->order_data['shipping']['address_1'] )
                ? $this->order_data['shipping']['address_1'] : $this->order_data['billing']['address_1'];
            return $shipping_warehouse_name;
        }
    }

    public function getRecipientTypeOfWarehouse()
    {
        if ( isset( $this->order_id ) ) {
            $recipientTypeOfWarehouse = array(
                "apiKey" => $this->api_key,
                "modelName" => "Address",
                "calledMethod" => "getWarehouses",
                "methodProperties" => array(
                    "CityRef" => $this->recipient_city_ref,
                    "FindByString" => $this->recipient_warehouse_name
                )
            );
            $obj = $this->invoiceModel()->sendPostRequest( $this->api_url, $recipientTypeOfWarehouse );
            return $obj['data'][0]['TypeOfWarehouse'];
        }
    }

    public function getRecipientBuildingNumber($order_id)
    {
        $order = \wc_get_order( $order_id );
        $order_data = $order->get_data();
        if ( isset( $order_data['shipping']['address_1'] ) &&
                ! empty( $order_data['shipping']['address_1'] ) ) {
            $streetHouseString = $order_data['shipping']['address_1'];
        }
        elseif ( isset( $order_data['billing']['address_1'] ) &&
                ! empty( $order_data['billing']['address_1'] ) ) {
            $streetHouseString = $order_data['billing']['address_1'];
        }  else {
            return '';
        }
        $streetHouse = \trim( $streetHouseString );
        $posComma = strpos( $streetHouse, ',' );
        return substr( $streetHouse, $posComma + 1 );
    }

    public function getRecipientFlatNumber($order_id)
    {
        $order = \wc_get_order( $order_id );
        $order_data = $order->get_data();
        if ( isset( $order_data['shipping']['address_2'] ) &&
                ! empty( $order_data['shipping']['address_2'] ) ) {
            return $order_data['shipping']['address_2'];
        }
        elseif ( isset( $order_data['billing']['address_2'] ) &&
                ! empty( $order_data['billing']['address_2'] ) ) {
            return $order_data['billing']['address_2'];
        }  else {
            return '';
        }
    }

    public function getRecipientAddressNote($order_data)
    {
        $order = \wc_get_order( $order_id );
        $order_data = $order->get_data();
        if ( isset( $order_data['shipping']['address_2'] ) &&
                ! empty( $order_data['shipping']['address_2'] ) ) {
            $flatNumberString = $order_data['shipping']['address_2'];
        }
        elseif ( isset( $order_data['billing']['address_2'] ) &&
                ! empty( $order_data['billing']['address_2'] ) ) {
            $flatNumberString = $order_data['billing']['address_2'];
        }  else {
            return '';
        }
        $addressNote = \trim( $flatNumberString );
        $posComma = strpos( $addressNote, ',' );
        return substr( $addressNote, $posComma + 1 );
    }

    public function isRecipientTypeOfWarehousePoshtomat()
    {
        // Types of warehouses Nova Poshta:
        // 9a68df70-0267-42a8-bb5c-37f427e36ee4 - Вантажне відділення
        // 841339c7-591a-42e2-8233-7a0a00f0ed6f - Поштове відділення
        // 6f8c7162-4b72-4b0a-88e5-906948c6a92f - Parcel Shop
        // f9316480-5f2d-425d-bc2c-ac7cd29decf0 - Пoштомат
        return ( 'f9316480-5f2d-425d-bc2c-ac7cd29decf0' == $this->getRecipientTypeOfWarehouse() ) ? true : false;
    }

}
