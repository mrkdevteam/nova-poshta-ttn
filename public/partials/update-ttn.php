<?php


//	createRecipients($_POST['recipient_name'], $_POST['woocommerce_nova_poshta_shipping_method_city'], $_POST['recipient_phone'] );

	$tomorrowtime = strtotime('+1 day');
	$tomorrowdate =  date('d.m.Y', $tomorrowtime);
	$methodProperties = array(
		"Ref" => $_GET['invoice'],
		"PayerType" => "Recipient",
		"PaymentMethod" => "Cash",
		"DateTime" => $tomorrowdate,
		"CargoType"=> "Parcel",
		"VolumeGeneral"=> $_POST['Volume'],
		"Weight"=> $_POST['Weight'],
		"ServiceType"=> "WarehouseWarehouse",// "WarehouseDoors",
		"SeatsAmount"=> "1",
		"Description"=> $_POST['invoicedescription'],
		"Cost"=> $_POST['cost'],
		"CitySender"=> $_POST['cityid'],
		"Sender"=> getCounterpartiestoref(),
		"SenderAddress"=> $_POST['mrkvnp_invoice_sender_warehouse_ref'],
		"ContactSender"=> $_POST['mrkvnp_invoice_sender_ref'],
		"SendersPhone"=> $_POST['sender_phone'],
		"CityRecipient"=> $_POST['woocommerce_nova_poshta_shipping_method_city'],
		"Recipient"=> $_POST['Recipient'],
		"RecipientAddress" => $_POST['mrkvnp_invoice_sender_warehouse_ref'],
		"ContactRecipient" => $_POST['ContactRecipient'],
		"RecipientsPhone" =>$_POST['recipient_phone'],
	);

	$backwardDeliveryData = array(
		"PayerType" => "Recipient",
		"CargoType" => "Money",
		"RedeliveryString" => $_POST['backward']
	);

	$methodProperties["BackwardDeliveryData"] =	$backwardDeliveryData ;

	// echo '<pre>';
	// print_r($methodProperties);
	// echo '</pre>';
	$invoiceData = array(
		"apiKey" => $api_key,
			"modelName" => "InternetDocument",
			"calledMethod" => "update",
			"methodProperties" => $methodProperties
	);

	$curl = curl_init();

	$url = "https://api.novaposhta.ua/v2.0/json/";
	curl_setopt_array($curl, array(
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => True,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "POST",
		CURLOPT_POSTFIELDS => json_encode( $invoiceData ),
		CURLOPT_HTTPHEADER => array("content-type: application/json",),
	));
	$response = curl_exec( $curl );
	$error = curl_error( $curl );
	curl_close( $curl );
	if ( $error ) {
	} else {
		$response_json = json_decode( $response, true );
		echo '<pre>';
		//print_r($response_json);
		echo '</pre>';
	 }
    ?>
