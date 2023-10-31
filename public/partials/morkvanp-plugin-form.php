<?php

use plugins\NovaPoshta\classes\invoice\InvoiceModel;
use plugins\NovaPoshta\classes\invoice\InvoiceController;

if ( ! isset( $_GET['post'] ) ) {
    return wp_die('<h3>Для створення накладної перейдіть на <a href="edit.php?post_type=shop_order">сторінку Замовлення</a></h3>');
}
$order_id = $_GET['post'] ?? '';
if ( $order_id > 0 ) {
    $order_obj = \wc_get_order( $order_id );
    if ( is_object( $order_obj ) ) {
        $order_data = $order_obj->get_data() ?? '';
    }
}

$invoiceModel = new invoiceModel();
$shipping_first_name = $invoiceModel->getShippingFirstName( $order_data );    
$shipping_last_name = $invoiceModel->getShippingLastName( $order_data );
$shipping_middle_name = $invoiceModel->getShippingMiddleName( $order_id );
// error_log('$order_data');error_log(print_r($order_data,1));
$shipping_phone = $invoiceModel->getShippingPhone( $order_data );
$shipping_email = $invoiceModel->getShippingEmail( $order_data );

$shippingMethodId = $invoiceModel->getShippingMethod($order_obj);

$shipping_warehouse_name = $invoiceModel->getShippingWarehouseName( $order_data );
$shipping_city_name = $invoiceModel->getShippingCityName( $order_data );
$shipping_state_name = $invoiceModel->getShippingStateName( $order_data );

if ( 'npttn_address_shipping_method' == $shippingMethodId ) {
	$streetName = $invoiceModel->getShippingStreetName( $order_data );
	$shippingBuldingNumber = $invoiceModel->getShippingBuildingNumber($order_data);
    $shipping_flat = $invoiceModel->getShippingFlat( $order_data );
}

$alternate_all = $invoiceModel->alternate_all( $order_data ) ?? array(); // Якщо створювати накладну без замовлення, то замовлення порожнє
$alternate_vol = ( $alternate_all['alternate_vol'] > 0 ) ? $alternate_all['alternate_vol'] : 0.001;
$list = $alternate_all['list'];
$list2 = $alternate_all['list2'];
$list3 = $alternate_all['list3'];
$prod_quantity = $alternate_all['prod_quantity'];
$prod_quantity2 = $alternate_all['prod_quantity2'];
$alternate_weight = $alternate_all['weight'];
$dimentions = $alternate_all['dimentions'];
$volumemessage = $alternate_all['volumemessage'];
$weighte = $alternate_all['weighte'];
?>

<div class="row">
	<?php require NOVA_POSHTA_TTN_SHIPPING_PLUGIN_DIR . 'public/partials/invoice/view/invoice_header.php'; ?>
	<div class="h2 mrkvnp-create-invoice">
        <span class="dashicons dashicons-screenoptions"></span> Створити накладну
    </div>
	<div class="create-invoice-all">
		<form class="form-invoice" action="admin.php?page=morkvanp_invoice<?php if ( ! empty( $order_id ) ) echo "&post=$order_id" ?>" method="post" name="invoice">
			<div class="create-invoice-sender-recipient">
				<?php require NOVA_POSHTA_TTN_SHIPPING_PLUGIN_DIR . 'public/partials/invoice/view/invoice_sender.php'; ?>
				<?php require NOVA_POSHTA_TTN_SHIPPING_PLUGIN_DIR . 'public/partials/invoice/view/invoice_recipient.php'; ?>
			</div>
	        <hr style="color:#f0f0f0;margin:auto 30px;">
	        <div class="create-invoice-params">
				<?php require NOVA_POSHTA_TTN_SHIPPING_PLUGIN_DIR . 'public/partials/invoice/view/invoice_params.php'; ?>
			</div>
			<div class='create-invoice-submit'>
				<div class="invoice-submit-btn-div">
					<input type="submit" value="Створити" name="create_invoice" class="checkforminputs button button-secondary"
						id="submit" />
				</div>
			</div>
		</form>
	</div>
</div>

<?php
if ( isset( $_POST['create_invoice'] ) ) {
    $invoice = new InvoiceController( $order_id );
    $invoiceController = $invoice->init();
    $invoiceController->isEmpty(); // If sender phone or sender city name are empty then object $invoice make empty too
    $invoiceController->howCosts();
    $invoiceController->createInvoice();
    if (isset($invoice->req)) {
        echo "<div style=display:none><p>Запит:</p>" . $invoice->req . "</div>";
    }
}
