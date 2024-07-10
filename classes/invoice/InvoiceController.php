<?php

namespace plugins\NovaPoshta\classes\invoice;

// If this file is called directly, abort.
defined( 'ABSPATH' ) or die();

/**
 * Providing invoice functions for plugin
 *
 *
 * @link       http://morkva.co.ua
 * @since      1.0.0
 *
 * @package    nova-poshta-ttn
 * @subpackage nova-poshta-ttn/public/partials
 */

use plugins\NovaPoshta\classes\invoice\Sender;
use plugins\NovaPoshta\classes\invoice\Recipient;
use plugins\NovaPoshta\classes\invoice\InvoiceModel;

class InvoiceController {

	public $api_key;
	public $api_url = 'https://api.novaposhta.ua/v2.0/json/';

	public $order_id;

	public $invoice_id;
	public $invoice_ref;

	public $req;

	public $shipping_settings;
	public $shipping_method_id;

	public $invoice_description;
	public $invoice_descriptionred;

	#-------------- Sender Data Block -------------

	public $sender_ref;

	public $sender_names;
	public $sender_first_name;
	public $sender_middle_name;
	public $sender_last_name;

	public $sender_city_name;
	public $sender_city_ref;

	public $sender_phones;

	public $sender_contacts_ref;

	public $sender_address_name;
	public $sender_addresses_ref;

	public $sender_area;
	public $sender_street;
	public $sender_street_ref;

	public $sender_warehouse_ref;
	public $sender_warehouse_number;
	public $sender_building;
	public $sender_flat;

	#------------- Recipient Data Block -----------

	public $recipient_names;
	public $recipient_first_name;
	public $recipient_middle_name;
	public $recipient_last_name;

	public $recipient_phone;

	public $recipient_city_name;
	public $recipient_city_ref;

	public $recipient_area;
	public $recipient_area_regions;
	public $recipient_area_ref;

	public $recipient_address_name;
	public $recipient_house;
	public $recipient_flat;
	public $recipient_warehouse_ref;
	public $recipient_type_of_warehouse;
	public $recipient_warehouse_number;

	public $recipient_ref;
	public $recipient_contact_ref;

	public $datetime;

	#------------- Cargo Data Block -----------

	public $cargo_type;
	public $cargo_weight;
	public $cost;

	public $payer;
	public $zpayer;

	public $price;

	public $is_payment_on_delivery;

	public $invoice_x;
	public $invoice_y;
	public $invoice_z;

	public $volume_general;

	public $invoice_places;

	public $invoice_volume;

	public $servicetype;

	public $packing_number;

	public $invoice_model_obj;
	public $order_obj_main;
	public $recipient_obj;
	public $sender_obj;

	public function __construct($order_id)
	{
		$this->order_id = $order_id;
		$order = wc_get_order($order_id );
        $this->order_obj_main = $order;
		$this->invoice_model_obj = new InvoiceModel($this->order_id);
		$query_string = $_SERVER['QUERY_STRING'];
		parse_str($query_string, $query_params);
		if ( 'morkvanp_invoice' !== $query_params['page'] ) return;
		$this->shipping_method_id = $this->getShippingMethodId( $this->order_id );
	}

	public function init()
	{
		$_SESSION['mrkvnp_errors'] = array();

		#---------- Model Data ----------

		$this->shipping_settings = $this->invoiceModel()->getShippingSettings(
			\get_option('woocommerce_nova_poshta_shipping_method_settings' ) );

		#---------- Sender Data ----------

		$this->api_key = $this->getApiKey();

		$this->sender_obj = new Sender($this->order_id, $this->invoice_model_obj);
		$this->sender_names = $this->sender()->sender_names;
		$this->sender_last_name = $this->sender()->sender_last_name;
		$this->sender_first_name = $this->sender()->sender_first_name;
		$this->sender_middle_name = $this->sender()->sender_middle_name;

		$this->sender_city_name = $this->sender()->sender_city_name;
		$this->sender_city_ref = $this->sender()->sender_city_ref;

		$this->sender_warehouse_ref = $this->sender()->sender_warehouse_ref;
		$this->sender_warehouse_number = $this->sender()->sender_warehouse_number;

		$this->sender_address_name = $this->sender()->sender_address_name;
		$this->sender_addresses_ref = $this->sender()->sender_addresses_ref;

		$this->sender_phones = $this->sender()->sender_phones;
		$this->sender_ref = $this->sender()->sender_ref;
		$this->sender_contacts_ref = $this->sender()->sender_contacts_ref;

		#---------- Recipient Data ----------

		$this->recipient_obj = new Recipient($this->order_id, $this->invoice_model_obj);
		$this->recipient_names = $this->recipient()->recipient_names;
		$this->recipient_first_name = $this->recipient()->recipient_first_name;
		$this->recipient_middle_name = $this->recipient()->recipient_middle_name;
		$this->recipient_last_name = $this->recipient()->recipient_last_name;

		$this->recipient_city_name = $this->recipient()->recipient_city_name;
		$this->recipient_city_ref = $this->recipient()->recipient_city_ref;
		$this->recipient_phone = $this->recipient()->recipient_phone;

		$this->recipient_address_name = $this->recipient()->recipient_address_name;

		if ( 'WarehousePostomat' == $this->servicetype ||
			 'WarehouseWarehouse' == $this->servicetype	||
			 'DoorsWarehouse' == $this->servicetype	||
			 'DoorsPostomat' == $this->servicetype ) {
			$this->recipient_type_of_warehouse = $this->recipient()->getRecipientTypeOfWarehouse();
			$this->recipient_warehouse_ref = $this->recipient()->recipient_warehouse_ref;
		} else {
			$this->recipient_house = $this->recipient()->getRecipientBuildingNumber( $this->order_id );
			$this->recipient_flat = $this->recipient()->getRecipientFlatNumber( $this->order_id );
		}

		$this->recipient_ref = $this->recipient()->recipient_ref;
		$this->recipient_warehouse_number = $this->recipient()->getRecipientWarehouseNumber();
		$this->recipient_contact_ref = $this->recipient()->recipient_contact_ref;

		#---------- Order Data ----------

		$this->invoice_description = $this->invoiceModel()->getInvoiceDescription();
		$this->invoice_descriptionred = $this->invoiceModel()->getInvoiceDescriptionRed();

		$this->cargo_type = $this->invoiceModel()->getCargoType();
		$this->cargo_weight = $this->invoiceModel()->getCargoWeight( $this->getOrderObj(
			$this->order_id ) );

		$this->datetime = $this->invoiceModel()->getInvoiceDateTime();

		$this->payer = $this->invoiceModel()->getInvoicePayer( $this->getOrderObj( $this->order_id ) );
		$this->zpayer = $this->invoiceModel()->getCodPayer();

		$this->price = $this->invoiceModel()->getInvoicePrice( $this->getOrderObj(
			$this->order_id ) );
		$this->is_payment_on_delivery = $this->invoiceModel()->isPaymentOnDelivery( $this->order_id );

		$this->invoice_x = intval( $this->invoiceModel()->getInvoiceLength(
			$this->getOrderData( $this->order_id ) ) );
		$this->invoice_y = intval( $this->invoiceModel()->getInvoiceWidth(
			$this->getOrderData( $this->order_id ) ) );
		$this->invoice_z = intval( $this->invoiceModel()->getInvoiceHeight(
			$this->getOrderData( $this->order_id ) ) );

		$this->invoice_places = $this->invoiceModel()->getInvoicePlaces();
		$this->invoice_volume = $this->invoiceModel()->getInvoiceVolume( $this->getOrderData(
			$this->order_id ) );
		$this->packing_number = $this->invoiceModel()->isPackingNumber();

		#---------- Invoice Data ----------

		$this->servicetype = $this->invoiceModel()->getServiceType( $this->order_id );

		return $this;
	}

	#---------- Get sender, recipient and model data ----------

	public function sender()
	{
		return $this->sender_obj;
	}

	public function recipient()
	{
		return $this->recipient_obj;
	}

	public function invoiceModel()
	{
		return $this->invoice_model_obj;
	}

	public function getApiKey()
	{
		return \sanitize_text_field( get_option( 'mrkvnp_sender_api_key' ) );
	}

	#---------- Get order data ----------

	public function getOrderObj($order_id)
	{
		return  $this->order_obj_main;
	}

    public function getShippingMethodId($order_id)
    {
        $order_obj = $this->getOrderObj( $order_id );
        $getShippingMethods = $order_obj->get_shipping_methods();
        $shipping_methods = array_shift( $getShippingMethods );
        $shipping_method = @$shipping_methods;
        return $shipping_method['method_id'];
    }

	public function getOrderData($order_id)
    {
        $order_obj = $this->getOrderObj($order_id);
        return $order_obj->get_data();
    }

    #---------- Get invoice data ----------

	public function isEmpty()
	{
		if ( empty( $this->sender_phones ) ) {
			$this->deleteData();
			exit('Помилка: Відсутній номер телефону Відправника.');
		} else if ( empty( $this->sender_city_name ) ) {
			$this->deleteData();
			exit('Помилка: Відсутня назва міста Відправника.');
		}
		return $this;
	}

	public function deleteData()
	{

		unset($this->sender_names);
		unset($this->sender_city_name);
		unset($this->sender_phones);
		unset($this->sender_contacts_ref);
		unset($this->sender_street);
		unset($this->sender_building);
		unset($this->sender_flat);
		unset($this->cargo_type);
		unset($this->cargo_weight);
		unset($this->recipient_city_name);
		unset($this->recipient_area);
		unset($this->recipient_area_regions);
		unset($this->recipient_address_name);
		unset($this->recipient_house);
		unset($this->payer);
		unset($this->datetime);
		unset($this->price);

	}

	#------------- Get cargo data -----------

	public function howCosts()
	{
		$methodProperties = array(
			"CitySender" => $this->sender_city_ref,
			"CityRecipient" => $this->recipient_city_ref,
			"Weight" => $this->cargo_weight,
			"ServiceType" => "WarehouseWarehouse",
			"Cost" => "100",
			"SeatsAmount" => "1"
		);
		$costs = array(
			"apiKey" => $this->api_key,
			"modelName" => "InternetDocument",
			"calledMethod" => "getDocumentPrice",
			"methodProperties" => $methodProperties
		);
		$obj = $this->invoiceModel()->sendPostRequest( $this->api_url, $costs );
		if ( isset( $obj['errors'] ) && ! empty( $obj['errors'] ) ) {

		} else {
			$this->cost = $obj["data"][0]["Cost"];
		}
		return $this;
	}

	#------------- Create Invoice -----------

	public function createInvoice()
	{
	$start_time = microtime(true); error_log('$start_time');error_log($start_time);
	$serviceType = $this->invoiceModel()->getServiceType( $this->order_id );
	$orderObj = $this->getOrderObj( $this->order_id );
	$recipient_address_ref = $orderObj->get_meta('_billing_nova_poshta_warehouse')
		?? $orderObj->get_meta('_shipping_nova_poshta_warehouse');

	if(!$recipient_address_ref){
		$recipient_address_ref = $orderObj->get_meta('_shipping_nova_poshta_warehouse');
	}

	$orderPaymentMethod = $orderObj->get_payment_method();
	$dimentions = $this->invoiceModel()->calcOrderDimensions( $this->getOrderData( $this->order_id ) );
		    $max_length_prod = $dimentions[0];
		    $max_width_prod = $dimentions[1];
		    $max_height_prod = $dimentions[2];
	if ( 'WarehouseDoors' == $serviceType ||
			'DoorsDoors' == $serviceType ) {
		$recipient_names_show = $this->recipient_middle_name
			? stripslashes($this->recipient_names) . ' ' . $this->recipient_middle_name
			: stripslashes($this->recipient_names);
		$recipient_address_ref = $this->recipient()->createRecipientAddressRef( $this->order_id );
		$recipient_street_ref = $this->recipient()->getRecipientStreetRef( $this->order_id );
		$recipient_contact_ref = $this->recipient()->getRecipientContactRef( $this->order_id );
		$recipient_city_ref = $this->recipient()->getRecipientCityRef( $this->order_id );
		$recipient_building_number = $this->recipient()->getRecipientBuildingNumber( $this->order_id );
		$recipient_flat_number = $this->recipient()->getRecipientFlatNumber( $this->order_id );
		$recipient_flat_number_show = $recipient_flat_number ? ' , кв. ' . $recipient_flat_number : '';
		$recipient_region_name = $this->recipient()->getRecipientRegionName( $this->order_id );
		$recipient_city_name = $this->recipient()->getRecipientCityName( $this->order_id );
		$recipient_address_name = $this->recipient()->getRecipientStreetName( $this->order_id );
		$recipient_address_name_show = $recipient_city_name . ', ' .
										$recipient_address_name .
										$recipient_building_number .
										$recipient_flat_number_show;

		$methodProperties = array(
			// General params
			"NewAddress" => "1", // Використання нового адресного довідника 1- ТАК, 0 - НІ
			"PayerType" => $this->payer, // By default - Recipient
			"PaymentMethod" => $this->invoiceModel()->getDeliveryPaymentMethod(),
			"DateTime" => $this->invoiceModel()->getInvoiceDateTime(),
			"ServiceType" => $this->servicetype, // By default WarehouseWarehouse
			// Cargo
			"CargoType" => $this->cargo_type,
			"Weight" => $this->cargo_weight,
			"SeatsAmount" => $this->invoice_places,
			"Description" => $this->invoice_description, // Max 100 symbols with spaces
			"Cost" => $this->price,
			// Sender
			"CitySender" => $this->sender_city_ref,
			"Sender" => $this->sender_ref,
			"SenderAddress" => $this->sender_addresses_ref,
			"ContactSender" => $this->sender_contacts_ref,
			"SendersPhone" => $this->sender_phones,
			// Recipient
			"Recipient" => $this->recipient_ref,
			"RecipientArea" => $recipient_region_name,
			"RecipientCityName" => $recipient_city_name,
            "RecipientAddressName" => $recipient_address_name,
            "ContactRecipient" => $recipient_contact_ref,
			"CityRecipient" => $this->recipient_city_ref,
			"RecipientHouse" => $recipient_building_number,
			"RecipientFlat" => $recipient_flat_number,
			"RecipientName" => stripslashes($this->recipient_names),
			"RecipientType" => "PrivatePerson",
			"RecipientsPhone" => $this->recipient_phone,
			// Additional info
			"AdditionalInformation"=>$this->invoice_description,
			"InfoRegClientBarcodes" => $this->order_id,
			"PackingNumber" => $this->packing_number,
			"OptionsSeat" => array(
				array (
					"volumetricVolume" => (int) $max_length_prod * (int) $max_width_prod * (int) $max_height_prod,
					"volumetricLength" => $max_length_prod,
					"volumetricWidth" => $max_width_prod,
					"volumetricHeight" => $max_height_prod,
					"weight" => $this->cargo_weight,
				)
			),
		);
	}
	else {
		$recipient_address_name_show = $this->recipient_address_name;
		$recipient_names_show = stripslashes($this->recipient_names);
		if ( $this->recipient()->isRecipientTypeOfWarehousePoshtomat() ) {  // Поштомат
			$methodProperties = array(
				// General params
				"PayerType" => $this->payer, // By default - Recipient
				"PaymentMethod" => $this->invoiceModel()->getDeliveryPaymentMethod(),
				"DateTime" => $this->invoiceModel()->getInvoiceDateTime(),
				"ServiceType" => $this->servicetype, // By default WarehouseWarehouse
				// Cargo
				"CargoType" => $this->cargo_type,
				"Weight" => $this->cargo_weight,
				"TypeOfWarehouseRef" => $this->recipient_type_of_warehouse,
				"OptionsSeat" => array(
					array (
						"volumetricVolume" => (int) $max_length_prod * (int) $max_width_prod * (int) $max_height_prod / 4000,
						"volumetricLength" => $max_length_prod,
						"volumetricWidth" => $max_width_prod,
						"volumetricHeight" => $max_height_prod,
						"weight" => $this->cargo_weight,
					)
				),
				"SeatsAmount" => $this->invoice_places,
				"Description" => $this->invoice_description, // Max 100 symbols with spaces
				"Cost" => $this->price,
				// Sender
				"CitySender" => $this->sender_city_ref,
				"Sender" => $this->sender_ref,
				"SenderAddress" => $this->sender_addresses_ref,
				"ContactSender" => $this->sender_contacts_ref,
				"SendersPhone" => $this->sender_phones,
				// Recipient
				"RecipientCityName" => $this->recipient_city_name,
				"RecipientAddressName" => $recipient_address_name_show,
				"RecipientAddress" => $recipient_address_ref,
				"CityRecipient" => $this->recipient_city_ref,
				"Recipient" => $this->recipient_ref,
				"ContactRecipient" => $this->recipient_contact_ref,
				"RecipientsPhone" => $this->recipient_phone,
				// Additional info
				"AdditionalInformation"=> $this->invoice_description,
				"InfoRegClientBarcodes" => $this->order_id,
				"PackingNumber" => $this->packing_number
			);
		} else { // Відділення

			$city_main = '';

            if($orderObj->get_billing_city()){
                $city_main = $orderObj->get_billing_city();
            }
            else
            {
                $city_main = $orderObj->get_shipping_city();
            }

			$recipient_address_name_show = $this->recipient_city_name . ', ' . $this->recipient_address_name;
			$recipient_names_show = stripslashes($this->recipient_names);
			$methodProperties = array(
				// General params
				"NewAddress" => "1", // Використання нового адресного довідника 1- ТАК, 0 - НІ
				"PayerType" => $this->payer, // By default - Recipient
				"PaymentMethod" => $this->invoiceModel()->getDeliveryPaymentMethod(),
				"DateTime" => $this->invoiceModel()->getInvoiceDateTime(),
				"ServiceType" => $this->servicetype, // By default WarehouseWarehouse
				// Cargo
				"CargoType" => $this->cargo_type,
				"Weight" => $this->cargo_weight,
				"SeatsAmount" => $this->invoice_places,
				"OptionsSeat" => array(
					array (
						"volumetricVolume" => (int) $max_length_prod * (int) $max_width_prod * (int) $max_height_prod / 4000,
						"volumetricLength" => $max_length_prod,
						"volumetricWidth" => $max_width_prod,
						"volumetricHeight" => $max_height_prod,
						"weight" => $this->cargo_weight,
					)
				),
				"Description" => $this->invoice_description, // Max 100 symbols with spaces
				"Cost" => $this->price,
				// Sender
				"CitySender" => $this->sender_city_ref,
				"Sender" => $this->sender_ref,
				"SenderAddress" => $this->sender_addresses_ref,
				"ContactSender" => $this->sender_contacts_ref,
				// "ContactSender" => $this->sender_warehouse_ref,
				"SendersPhone" => $this->sender_phones,
				// Recipient
				/*"Recipient" => $this->recipient_ref,
                "RecipientAddress" => $this->recipient_warehouse_ref,*/
                "RecipientAddressName" => $this->recipient_warehouse_number,
                /*"ContactRecipient" => $this->recipient_contact_ref,*/
				/*"CityRecipient" => $this->recipient_city_ref,*/
				"RecipientCityName" => $city_main,
				"RecipientHouse" => "",
				"RecipientFlat" => "",
				"RecipientName" => stripslashes($this->recipient_names),
				"RecipientType" => "PrivatePerson",
				"RecipientsPhone" => $this->recipient_phone,
				'SettlementType' => 'м.',
				// Additional info
				"AdditionalInformation"=>$this->invoice_description,
				"InfoRegClientBarcodes" => $this->order_id,
				"PackingNumber" => $this->packing_number,
				"VolumeGeneral" => $this->invoice_volume
			);
		}
	} //if ( 'WarehouseDoors' == $serviceType ) {

		// Add 'Red Box' service
		$methodProperties["RedBoxBarcode"] = $this->invoiceModel()->isDescriptionRed();

		$invoice = array(
			"apiKey" => $this->api_key,
			"modelName" => "InternetDocument",
			"calledMethod" => "save",
			"methodProperties" => $methodProperties,
		);
		$invoice_obj = $this->invoiceModel()->sendPostRequest( $this->api_url, $invoice ); // Create invoice
\error_log('$invoice InvoiceController php-class');error_log(print_r($invoice,1));
\error_log('$invoice_obj InvoiceController php-class');error_log(print_r($invoice_obj,1));

		// Show error messages if they are
		if ( isset( $invoice_obj['errors'] ) && ! empty( $invoice_obj['errors'] ) ) {
			if ( isset( $invoice_obj['errors'][0] ) ) {
				$errormessage = $invoice_obj['errors'][0];
				$_SESSION['mrkvnp_errors'][] = $errormessage;
			}
			$this->req = json_encode($invoice_obj);
			$obj_errors = $this->invoiceModel()->getApiNPErrorsList( $methodProperties );
			$this->invoiceModel()->displayErrorMsg( $this->order_id, $obj_errors, $invoice_obj );
			exit('Сталася помилка в createInvoice()');
		}

		// Show success message
		if ( empty( $invoice_obj['errors'] ) &&  empty( $invoice_obj['errorCodes'] ) ) {
			$this->invoice_id = $invoice_obj["data"][0]["IntDocNumber"];
			$this->invoice_ref = $invoice_obj["data"][0]["Ref"];
			$result = $this->invoiceModel()->saveInvoiceRowDB(
				$this->order_id,
				$this->invoice_id,
				$this->invoice_ref
			);
			if ( $result ) {
				$this->invoiceModel()->displaySuccessMsg(
                    $this->order_id,
                    $this->invoice_id,
                    $this->sender_names,
                    $this->sender_address_name,
                    $recipient_names_show,
                    $recipient_address_name_show
                );
			}
		}
		$end_time = microtime(true); error_log('$end_time_fin');error_log($end_time);
		$execution_time = ($end_time - $start_time); error_log('$execution_createInvoice_time');error_log($execution_time);
		echo " Execution create invoice time = ".$execution_time." sec";
	}

}
