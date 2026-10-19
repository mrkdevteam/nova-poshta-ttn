<div class="wrap">
<?php
global $wpdb;

$invoice_get = isset($_GET['invoice']) ? sanitize_text_field(wp_unslash($_GET['invoice'])) : '';

if (class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled()) {
    $order = wc_get_order( $invoice_get );
    $ttn = $order ? $order->get_meta('novaposhta_ttn') : '';
    if ($order) {
        $order->save();
    }
} else {
    $ttn = get_post_meta( intval($invoice_get), 'novaposhta_ttn', true ); 
}

$date = strtotime('-14 days');
$dd = date('d.m.Y', $date);

$methodProperties = array("Type" => "csv", "DateTime" => date('d.m.Y'), "DocumentRefs" => array($ttn));
$invoiceData = array(
    "apiKey" => $api_key,
    "modelName" => "InternetDocument",
    "calledMethod" => "generateReport",
    "methodProperties" => $methodProperties
);

$curl = curl_init();
$url = "https://api.novaposhta.ua/v2.0/json/";

curl_setopt_array($curl, array(
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => json_encode( $invoiceData ),
    CURLOPT_HTTPHEADER => array("content-type: application/json"),
));

$response = curl_exec( $curl );
$error = curl_error( $curl );
curl_close( $curl );

$obj2 = array();
if ( !$error ) {
    $response_json = json_decode( $response, true );
    if ( is_array($response_json) ) {
        $obj2 = $response_json;
    }
}

$obj2data = isset($obj2['data']) ? $obj2['data'] : array();
if (is_array($obj2data)) {
    for($i = 0; $i < sizeof($obj2data); $i++){
        $element = $obj2data[$i];
        if(isset($element['IntDocNumber']) && $element['IntDocNumber'] == $ttn){
            $obj2 = $element;
            break;
        }
    }
}
?>
<div class="gridttn h3cont">
    <div>
        <h3 class="wp-heading-inline">ЕКСПРЕС-НАКЛАДНА <br> № <?php echo esc_html($ttn); ?></h3>
    </div>
    <div><br>Статус: <br>
        <?php echo isset($obj['Status']) ? esc_html($obj['Status']) : ''; ?>
        <br>Час: <br>
        <?php echo isset($obj2['DateTime']) ? esc_html($obj2['DateTime']) : ''; ?>
    </div>
</div>

<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=morkvanp_invoices&invoice=' . $invoice_get ) ); ?>" class="gridttn">
    <input type="hidden" name="ref" value="<?php echo isset($obj2['Ref']) ? esc_attr($obj2['Ref']) : ''; ?>">
    <div class="">
        <div class="">
            <h4>Інформація про відправника</h4>
            <label for="">Місто відправника</label>
            <input type="text" name="city" readonly value="<?php echo isset($obj2["CitySenderDescription"]) ? esc_attr($obj2["CitySenderDescription"]) : ''; ?>">
            <input type="hidden" name="cityid" value="<?php echo isset($obj2["CitySender"]) ? esc_attr($obj2["CitySender"]) : ''; ?>">
            <br>
            <input type="hidden" name="sender_phone" value="<?php echo isset($obj2['SendersPhone']) ? esc_attr($obj2['SendersPhone']) : ''; ?>">
            <label for="">Контактна особа</label>
            <?php
            $senders = getsenders();
            echo '<select class="custom-select" id="mrkvnp_invoice_sender_ref" name="mrkvnp_invoice_sender_ref">';
            if (is_array($senders)) {
                for($s = 0; $s < sizeof($senders); $s++){
                    echo '<option namero="' . esc_attr($senders[$s]->Description) . '" phone="' . esc_attr($senders[$s]->Phones) . '" value="' . esc_attr($senders[$s]->Ref) . '">' . esc_html($senders[$s]->Description . ' ' . $senders[$s]->Phones) . '</option>';
                }
            }
            echo '</select>';
            
            $default_desc = (isset($senders[0]->Description)) ? $senders[0]->Description : '';
            echo '<input style="display:none" type="text" id="sender_name" name="mrkvnp_invoice_sender_names" class="input sender_name" value="' . esc_attr($default_desc) . '" />';
            ?>
            <br>
            <hr>
        </div>
        <h4>Інформація про одержувача</h4>
        <label for="">Місто одержувача</label>
        <input type="text" id="woocommerce_nova_poshta_shipping_method_city_all_name" class="input-text regular-input ui-autocomplete-input" name="" value="<?php echo isset($obj2["CityRecipientDescription"]) ? esc_attr($obj2["CityRecipientDescription"]) : ''; ?>">
        <input type="hidden" name="woocommerce_nova_poshta_shipping_method_city" id="woocommerce_nova_poshta_shipping_method_city" value="<?php echo isset($obj2['CityRecipient']) ? esc_attr($obj2['CityRecipient']) : ''; ?>">
        <br>
        <label for="">Відділення</label>
        <input class="uai input-text regular-input ui-autocomplete-input" id="mrkvnp_invoice_sender_warehouse_name" type="text" name="mrkvnp_invoice_sender_warehouse_name" value="<?php echo isset($obj2["RecipientAddressDescription"]) ? esc_attr($obj2["RecipientAddressDescription"]) : ''; ?>">
        <input class="input-text regular-input jjs-hide-nova-poshta-option" type="hidden" name="mrkvnp_invoice_sender_warehouse_ref" id="mrkvnp_invoice_sender_warehouse_ref" value="<?php echo isset($obj2["RecipientAddress"]) ? esc_attr($obj2["RecipientAddress"]) : ''; ?>">
        <br>
        <label for="">АБО Адреса </label>

        <table>
            <tbody>
                <tr>
                    <td>Вулиця</td>
                    <td>
                        <input type="text" placeholder="" class="input-text regular-input ui-autocomplete-input" id="mrkvnp_invoice_sender_address_name" name="mrkvnp_invoice_sender_address_name" value="" autocomplete="off">
                        <input type="hidden" name="woocommerce_nova_poshta_shipping_method_address" id="woocommerce_nova_poshta_shipping_method_address" value="8ceab873-4146-11dd-9198-001d60451983">
                    </td>
                </tr>
                <tr>
                    <td><label>Будинок</label></td>
                    <td><input type="text" name="mrkvnp_invoice_sender_building_number" value=""></td>
                </tr>
                <tr>
                    <td><label>Квартира/офіс</label></td>
                    <td><input type="text" name="mrkvnp_invoice_sender_flat_number" value=""></td>
                </tr>
            </tbody>
        </table>

        <br>
        <label for="">Телефон</label><br>
        <input type="text" name="recipient_phone" value="<?php echo isset($obj2['RecipientsPhone']) ? esc_attr($obj2['RecipientsPhone']) : ''; ?>"><br>
        <label for="">П.І.Б. контактної особи (повністю)</label>
        <input readonly type="text" name="recipient_name" value="<?php echo isset($obj2['RecipientContactPerson']) ? esc_attr($obj2['RecipientContactPerson']) : ''; ?>">
        <input type="hidden" name="ContactRecipient" value="<?php echo isset($obj2['ContactRecipient']) ? esc_attr($obj2['ContactRecipient']) : ''; ?>">
        <input type="hidden" name="Recipient" value="<?php echo isset($obj2['Recipient']) ? esc_attr($obj2['Recipient']) : ''; ?>">
        <br>
        <hr>
    </div>
    <div class="">
        <h4>Інформація про відправлення</h4>
        <label for="">Фактична вага</label><br>
        <input type="text" name="Weight" value="<?php echo isset($obj2["Weight"]) ? esc_attr($obj2["Weight"]) : ''; ?>">
        <br>
        <label for="">Об'єм</label><br>
        <input type="text" name="Volume" value="<?php echo isset($obj2["Volume"]) ? esc_attr($obj2["Volume"]) : ''; ?>">
        <br>
        <label for="">Оголошена вартість</label><br>
        <input type="text" name="cost" value="<?php echo isset($obj2["Cost"]) ? esc_attr($obj2["Cost"]) : ''; ?>">
        <label for="">Зворотня доставка (наложений платіж)</label><br>
        <input type="text" name="backward" value="<?php echo isset($obj2["BackwardDeliverySum"]) ? esc_attr($obj2["BackwardDeliverySum"]) : ''; ?>">
        <label for="">Повний опис відправлення</label><br>
        <textarea name="invoicedescription"><?php echo isset($obj2["Description"]) ? esc_textarea($obj2["Description"]) : ''; ?></textarea>
        <br>
    </div>
    <?php include 'card.php'; ?>
    <div></div>
    <div></div>
    <div>
        <button type="submit" name="updatettn">Оновити</button>
    </div>
</form>
</div>