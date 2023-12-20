<?php

namespace plugins\NovaPoshta\classes\invoice;

use plugins\NovaPoshta\classes\invoice\InvoiceModel;

// If this file is called directly, abort.
defined( 'ABSPATH' ) or die();

class Sender extends Singleton
{
    public $api_key;
    public $api_url = 'https://api.novaposhta.ua/v2.0/json/';

    public $sender_counterparties_ref;
    public $sender_ref; // The same as $sender_counterparties_ref
    public $sender_contacts_ref;

    public $sender_names;
    public $sender_last_name;
	public $sender_first_name;
	public $sender_middle_name;

    public $isSenderShippingFromAddress;

    public $sender_city_name;
    public $sender_city_ref;

    public $sender_street_name;
    public $sender_street_ref;
    public $sender_building_number;
    public $sender_flat;

	public $sender_phones;

    public $sender_warehouse_ref;
    public $sender_warehouse_number;

	public $sender_address_name;
    public $sender_addresses_ref;

    public function __construct()
    {
        $this->api_key = \sanitize_text_field( \get_option( 'mrkvnp_sender_api_key' ) );

        $this->sender_counterparties_ref = $this->getSenderCounterpartiesRef();
        $this->sender_ref = $this->sender_counterparties_ref;
        $this->sender_contacts_ref = $this->getSenderContactsRef();

        $this->sender_names = $this->getSenderNames();
        $this->sender_last_name = $this->getSenderLastName();
        $this->sender_first_name = $this->getSenderFirstName();
        $this->sender_middle_name = $this->getSenderMiddleName();

        $this->isSenderShippingFromAddress = \sanitize_text_field( get_option( 'mrkvnp_invoice_sender_warehouse_type' ) );
        $this->sender_city_name = $this->getSenderCityName();
        $this->sender_city_ref = $this->getSenderCityRef();

        $this->sender_street_name = $this->getSenderStreetName() ?? '';
        $this->sender_street_ref = $this->getSenderStreetRef() ?? '';
        $this->sender_building_number = $this->getSenderBildingNumber();
        $this->sender_flat = $this->getSenderFlat();

        $this->sender_phones = $this->getSenderPhones();

        $this->sender_warehouse_ref = $this->getSenderWarehouseRef();
        $this->sender_warehouse_number = $this->getSenderWarehouseNumber();

        $this->sender_address_name = $this->getSenderAddressesName();
        $this->sender_addresses_ref = $this->getSenderAddressesRef();

    }

    public function invoiceModel()
    {
        return new InvoiceModel();
    }

     #------------ Methods for Invoice from the sender warehouse -----------------

    public function getSenderCounterpartiesRef()
    {
        $senderCounterpartiesRef = array(
            "apiKey" => $this->api_key,
            "modelName" => "Counterparty",
            "calledMethod" => "getCounterparties",
            "methodProperties" => array(
                "CounterpartyProperty" => "Sender",
                "Page" => "1"
            ),
        );
        $obj = $this->invoiceModel()->sendPostRequest( $this->api_url, $senderCounterpartiesRef );
        if ( $obj['errors'] ) {
            $apinp_errors = implode('<br>', $obj['errors'] );
            echo '<script>alert('. '"API Нова Пошта: ' . 'Помилки при створенні Відправника - ' .
                $apinp_errors . '."' . '); </script>';
            return false;
        } else return $obj['data'][0]['Ref'];
    }

    public function getSenderContactsRef() // Get the first sender contact from list
    {
        $mrkvnp_invoice_sender_ref = isset( $_POST['mrkvnp_invoice_sender_ref'] )
            ? \sanitize_text_field( $_POST['mrkvnp_invoice_sender_ref'] )
            : get_option('mrkvnp_invoice_sender_ref');
        if ( $mrkvnp_invoice_sender_ref ) {
            return $mrkvnp_invoice_sender_ref;
        } else {
            $getSenderContactsRef = array(
                "apiKey" => $this->api_key,
                "modelName" => "Counterparty",
                "calledMethod" => "getCounterpartyContactPersons",
                "methodProperties" => array(
                    "Ref" => $this->sender_counterparties_ref,
                    "Page" => 1
                ),
            );
            $obj = $this->invoiceModel()->sendPostRequest( $this->api_url, $getSenderContactsRef );
            if ( isset( $obj['data'] ) && ! empty( $obj['data'] ) ) return $obj['data'][0]['Ref'];
            return false;
        }
    }

    public function getSendersContactsRef() // Get all sender contacts list
    {
        $getSendersContactsRef = array(
            "apiKey" => $this->api_key,
            "modelName" => "Counterparty",
            "calledMethod" => "getCounterpartyContactPersons",
            "methodProperties" => array(
                "Ref" => $this->sender_counterparties_ref,
                "Page" => 1
            ),
        );
        $obj = $this->invoiceModel()->sendPostRequest( $this->api_url, $getSendersContactsRef );
        return $obj;
    }

    public function getSenderNames()
    {
        $getSenderNames = array(
            "apiKey" => $this->api_key,
            "modelName" => "Counterparty",
            "calledMethod" => "getCounterpartyContactPersons",
            "methodProperties" => array(
                "Ref" => $this->sender_counterparties_ref,
                "Page" => 1
            ),
        );
        $obj = $this->invoiceModel()->sendPostRequest( $this->api_url, $getSenderNames );
        if ( isset( $obj['data'] ) && ! empty( $obj['data'] ) ) return $obj['data'][0]['Description'];
        return false;
    }

    public function getSenderLastName()
    {
        $getSenderLastName = array(
            "apiKey" => $this->api_key,
            "modelName" => "Counterparty",
            "calledMethod" => "getCounterpartyContactPersons",
            "methodProperties" => array(
                "Ref" => $this->sender_counterparties_ref,
                "Page" => 1
            ),
        );
        $obj = $this->invoiceModel()->sendPostRequest( $this->api_url, $getSenderLastName );
        if ( isset( $obj['data'] ) && ! empty( $obj['data'] ) ) return $obj['data'][0]['LastName'];
        return false;
    }

    public function getSenderFirstName()
    {
        $getSenderFirstName = array(
            "apiKey" => $this->api_key,
            "modelName" => "Counterparty",
            "calledMethod" => "getCounterpartyContactPersons",
            "methodProperties" => array(
                "Ref" => $this->sender_counterparties_ref,
                "Page" => 1
            ),
        );
        $obj = $this->invoiceModel()->sendPostRequest( $this->api_url, $getSenderFirstName );
        if ( isset( $obj['data'] ) && ! empty( $obj['data'] ) ) return $obj['data'][0]['FirstName'];
        return false;
    }

    public function getSenderMiddleName()
    {
        $getSenderMiddleName = array(
            "apiKey" => $this->api_key,
            "modelName" => "Counterparty",
            "calledMethod" => "getCounterpartyContactPersons",
            "methodProperties" => array(
                "Ref" => $this->sender_counterparties_ref,
                "Page" => 1
            ),
        );
        $obj = $this->invoiceModel()->sendPostRequest( $this->api_url, $getSenderMiddleName );
        if ( isset( $obj['data'] ) && ! empty( $obj['data'] ) ) return $obj['data'][0]['MiddleName'];
        return false;
    }

    public function getSenderPhones()
    {
        $getSenderPhones = array(
            "apiKey" => $this->api_key,
            "modelName" => "Counterparty",
            "calledMethod" => "getCounterpartyContactPersons",
            "methodProperties" => array(
                "Ref" => $this->sender_counterparties_ref,
                "Page" => 1
            ),
        );
        $obj = $this->invoiceModel()->sendPostRequest( $this->api_url, $getSenderPhones );
        if ( isset( $obj['data'] ) && ! empty( $obj['data'] ) ) return $obj['data'][0]['Phones'];
        return false;
    }

    public function getSenderCityName()
    {
        return ( isset( $_POST['mrkvnp_invoice_sender_city_name'] )
            && ! empty( $_POST['mrkvnp_invoice_sender_city_name'] ) )
            ? \sanitize_text_field( $_POST['mrkvnp_invoice_sender_city_name'] )
            : \sanitize_text_field( get_option( 'mrkvnp_invoice_sender_city_name' ) );
    }

    public function getSenderAddressesName()
    {
        if ( $this->isSenderShippingFromAddress ) {
            return $this->sender_city_name . ', ' .
                $this->sender_street_name . ', ' .  $this->sender_building_number . ', ' . $this->sender_flat;
        }
        return $this->sender_city_name . ', ' .
            \sanitize_text_field( \get_option( 'mrkvnp_invoice_sender_warehouse_name' ) );
    }

    public function getSenderAddressesRef()
    {
        if ( $this->isSenderShippingFromAddress ) {
            $getSenderAddressesRef = array(
                "apiKey" => $this->api_key,
                "modelName" => "Address",
                "calledMethod" => "save",
                "methodProperties" => array(
                    "CounterpartyRef" => $this->sender_ref,
                    "StreetRef" => $this->sender_street_ref,
                    "BuildingNumber" => $this->sender_building_number,
                    "Flat" => $this->sender_flat,
                    "Note" => ''
                ),
            );
            $obj = $this->invoiceModel()->sendPostRequest( $this->api_url, $getSenderAddressesRef );

            if(isset($obj) && isset($obj['data']) && isset($obj['data'][0]) && isset($obj['data'][0]['Ref'])){
                return $obj['data'][0]['Ref'];
            }
            else{
                return '';
            }
        } else return $this->sender_warehouse_ref;
    }

    public function getSenderWarehouseRef()
    {
        $findbystring = '1';
        if(\sanitize_text_field( \get_option( 'mrkvnp_invoice_sender_warehouse_name' ) )){
            $findbystring = \sanitize_text_field( \get_option( 'mrkvnp_invoice_sender_warehouse_name' ) );
        }

        $getSenderAddressesRef = array(
            "apiKey" => $this->api_key,
            "modelName" => "Address",
            "calledMethod" => "getWarehouses",
            "methodProperties" => array(
                "CityRef" => $this->getSenderCityRef(),
                "FindByString" => $findbystring
            ),
        );

        $obj = $this->invoiceModel()->sendPostRequest( $this->api_url, $getSenderAddressesRef );

        if(isset($obj) && isset($obj['data']) && isset($obj['data'][0]) && isset($obj['data'][0]['Ref'])){
            return $obj['data'][0]['Ref'];
        }
        else{
            $ref = '';
            if (get_option('mrkvnp_invoice_sender_warehouse_ref')) {
                $ref = get_option('mrkvnp_invoice_sender_warehouse_ref');
            }
            return $ref;
        }
    }

    public function getSenderWarehouseNumber()
    {
        $getSenderWarehouseNumber = array(
            "apiKey" => $this->api_key,
            "modelName" => "Address",
            "calledMethod" => "getWarehouses",
            "methodProperties" => array(
                "Ref" => $this->sender_warehouse_ref,
                "Page" => 1
            ),
        );
        $obj = $this->invoiceModel()->sendPostRequest( $this->api_url, $getSenderWarehouseNumber );
        if ( isset( $obj['data'] ) && ! empty( $obj['data'] ) ) return $obj['data'][0]['Number'];
        return false;
    }

    #------------ Methods for Invoice from the sender address -----------------

    public function getSenderCityRef()
    {
        $senderCityRef = array(
            "apiKey" => $this->api_key,
            "modelName" => "Address",
            "calledMethod" => "getCities",
            "methodProperties" => array(
                "FindByString" => $this->sender_city_name
            ),
        );
        $obj = $this->invoiceModel()->sendPostRequest( $this->api_url, $senderCityRef );
        if ( isset( $obj["data"][0]["Ref"] ) ) {
            return $obj["data"][0]["Ref"];
        }
    }

    public function getSenderStreetName()
    {
        if ( $this->isSenderShippingFromAddress ) {
            return \sanitize_text_field( \get_option( 'mrkvnp_invoice_sender_address_name' ) );
        }
    }

    public function getSenderStreetRef()
    {
        if ( $this->isSenderShippingFromAddress ) {

            $sender_street_name_correct = str_replace("вул. ", "", $this->sender_street_name);
            $sender_street_ref = array(
                "apiKey" => $this->api_key,
                "modelName" => "Address",
                "calledMethod" => "getStreet",
                "methodProperties" => array(
                    "CityRef" => $this->sender_city_ref,
                    "FindByString" => $sender_street_name_correct
                )
            );
            $obj = $this->invoiceModel()->sendPostRequest( $this->api_url, $sender_street_ref );
            
            if ( isset( $obj["data"][0]["Ref"] ) ) {
                return $obj["data"][0]["Ref"];
            }
        }
    }

    public function getSenderBildingNumber()
    {
        if ( $this->isSenderShippingFromAddress ) {
            $sender_building_number = ( null !== \get_option( 'mrkvnp_invoice_sender_building_number' ) )
                ? \sanitize_text_field( get_option( 'mrkvnp_invoice_sender_building_number' ) ) : '1';
            return $sender_building_number;
        }
        return $sender_building_number = '';
    }

    public function getSenderFlat()
    {
        if ( $this->isSenderShippingFromAddress ) {
            $sender_flat = ( null !== \get_option( 'mrkvnp_invoice_sender_flat_number' ) )
                ? \sanitize_text_field( get_option( 'mrkvnp_invoice_sender_flat_number' ) ) : '1';
            return $sender_flat;
        }
        return $sender_flat = '';
    }

}
