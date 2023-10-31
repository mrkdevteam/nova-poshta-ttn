<?php

use plugins\NovaPoshta\classes\invoice\InvoiceModel;
use plugins\NovaPoshta\classes\invoice\Sender;



$invoiceModel = new InvoiceModel();

global $wpdb;
$api_key = $invoiceModel->api_key;
$bulklist = $bulklistdelete = '';

if ( isset( $_POST['bulklistnew'] ) ) {
	$bulklistnew = $_POST['bulklistnew'];
}

if ( isset( $_POST['bulklist'] ) && isset( $_POST['bulklistdelete'] ) ) {
	$bulklist = $_POST['bulklist'];
	$bulklistdelete = $_POST['bulklistdelete'];
}

$url = '';
if ( ! empty( $bulklist ) ) {
	if ( $_POST['action'] == 'print' ) {
		$url = 'https://my.novaposhta.ua/orders/printDocument/orders/' . $bulklist . '/type/pdf/apiKey/' . $api_key;
	}
	if ( ! empty( $url ) ) {
		header( 'Location:' . $url );
	}
	if ( $_POST['action'] == 'trash' ) {
        $invoiceModel->runMyInvoicesDeleteBulkActions( $bulklistdelete );
	}
} elseif ( ! empty( $bulklistnew ) ) {
	if ( $_POST['action'] == 'printnew' ) {
		$url = 'https://my.novaposhta.ua/orders/printDocument/orders/' . $bulklistnew . '/type/pdf/apiKey/' . $api_key;
		if ( ! empty( $url ) ) {
			header( 'Location:' . $url );
		}
	}
}

$invoiceModel->displayNav();

if ( isset( $_POST['updatettn'] ) ) {
	include 'update-ttn.php';
}
if ( isset( $_GET['invoice'] ) ) {
	include 'edit-ttn.php';
} else { ?>

<!-- My invoices page main block -->
<div class="wrap mrkvnp_my_invoices">
    <div class="row">
        <div class="col large-9">
            <!-- Table with invoices list -->
            <div class="mrkvnp_my_invoices__table">
                <!-- Table header -->
                <div class="mrkvnp-table-header">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path opacity="0.4" d="M12.0567 1.5H14.5962C15.6479 1.5 16.5001 2.35939 16.5001 3.41997V5.98089C16.5001 7.04148 15.6479 7.90087 14.5962 7.90087H12.0567C11.005 7.90087 10.1528 7.04148 10.1528 5.98089V3.41997C10.1528 2.35939 11.005 1.5 12.0567 1.5" fill="#E95420"/><path fill-rule="evenodd" clip-rule="evenodd" d="M3.40389 1.5H5.94337C6.99507 1.5 7.84726 2.35939 7.84726 3.41997V5.98089C7.84726 7.04148 6.99507 7.90087 5.94337 7.90087H3.40389C2.35219 7.90087 1.5 7.04148 1.5 5.98089V3.41997C1.5 2.35939 2.35219 1.5 3.40389 1.5ZM3.40389 10.0991H5.94337C6.99507 10.0991 7.84726 10.9585 7.84726 12.0191V14.58C7.84726 15.6399 6.99507 16.5 5.94337 16.5H3.40389C2.35219 16.5 1.5 15.6399 1.5 14.58V12.0191C1.5 10.9585 2.35219 10.0991 3.40389 10.0991ZM14.5961 10.0991H12.0566C11.0049 10.0991 10.1527 10.9585 10.1527 12.0191V14.58C10.1527 15.6399 11.0049 16.5 12.0566 16.5H14.5961C15.6478 16.5 16.5 15.6399 16.5 14.58V12.0191C16.5 10.9585 15.6478 10.0991 14.5961 10.0991Z" fill="#E95420"/></svg>
                    <h1><?php _e('Мої накладні', NOVA_POSHTA_TTN_DOMAIN); ?></h1>
                    <p><?php _e('Групові дії:', NOVA_POSHTA_TTN_DOMAIN); ?></p>
                    <!-- Action print stickers all enabled checkboxes  -->
                    <div class="group-action group__print-stickers icon-hover">
                        <form id="posts-filter" method="post" target="_blank">
                            <input type="hidden" name="bulklist" id="bulklist" value="">
                            <input type="hidden" name="bulklistdelete" id="bulklistdelete" value="">
                            <input type="hidden" name="bulklistnew" id="bulklistnew" value="">
                            <input type="hidden" name="action" value="print-sticker">
                            <button type="submit">
                                <svg class="action-regular" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17.5 2.5V9.16667C17.5 13.769 13.769 17.5 9.16667 17.5H2.5V2.5H17.5Z" stroke="#7C7C7C" stroke-linecap="square"/><path d="M7.5 17.5C9.72222 17.5 10.8333 16.3889 10.8333 14.1667C10.8333 14.1667 10.8333 13.0556 10.8333 10.8333H14.1667C16.3889 10.8333 17.5 9.72222 17.5 7.5" stroke="#7C7C7C" stroke-linecap="square"/></svg>
                                <svg class="action-hover" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17.5 2.5V9.16667C17.5 13.769 13.769 17.5 9.16667 17.5H2.5V2.5H17.5Z" stroke="#E95420" stroke-linecap="square"/><path d="M7.5 17.5C9.72222 17.5 10.8333 16.3889 10.8333 14.1667C10.8333 14.1667 10.8333 13.0556 10.8333 10.8333H14.1667C16.3889 10.8333 17.5 9.72222 17.5 7.5" stroke="#E95420" stroke-linecap="square"/></svg>
                                <span class="toolip-action"><?php _e('Надрукувати стікер', NOVA_POSHTA_TTN_DOMAIN); ?></span>
                            </button>
                        </form>
                    </div>
                    <!-- Action print all enabled checkboxes  -->
                    <div class="group-action group__print icon-hover">
                        <form id="posts-filter" method="post" target="_blank">
                            <input type="hidden" name="bulklist" id="bulklist" value="">
                            <input type="hidden" name="bulklistdelete" id="bulklistdelete" value="">
                            <input type="hidden" name="bulklistnew" id="bulklistnew" value="">
                            <input type="hidden" name="action" value="print">
                            <button type="submit">
                                <svg class="action-regular" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16.4062 5.00391V0.625H3.59375V5.00391C2.79333 5.04493 2.03917 5.39154 1.4868 5.97226C0.93442 6.55298 0.625951 7.32353 0.625 8.125V15.625H3.28125V14.375H1.875V8.125C1.87556 7.62789 2.07328 7.1513 2.42479 6.79979C2.7763 6.44828 3.25289 6.25056 3.75 6.25H16.25C16.7471 6.25056 17.2237 6.44828 17.5752 6.79979C17.9267 7.1513 18.1244 7.62789 18.125 8.125V14.375H16.4062V15.625H19.375V8.125C19.3741 7.32353 19.0656 6.55298 18.5132 5.97226C17.9608 5.39154 17.2067 5.04493 16.4062 5.00391ZM15.1562 5H4.84375V1.875H15.1562V5Z" fill="#7C7C7C"/><path d="M16.7188 7.8125H15.4688V9.0625H16.7188V7.8125Z" fill="#7C7C7C"/><path d="M4.53125 10.3125H2.96875V11.5625H4.53125V19.375H15.1562V11.5625H16.7188V10.3125H4.53125ZM13.9062 18.125H5.78125V11.5625H13.9062V18.125Z" fill="#7C7C7C"/>
                                </svg>
                                <svg class="action-hover" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_135_3902)"><path d="M16.4062 5.00391V0.625H3.59375V5.00391C2.79333 5.04493 2.03917 5.39154 1.4868 5.97226C0.93442 6.55298 0.625951 7.32353 0.625 8.125V15.625H3.28125V14.375H1.875V8.125C1.87556 7.62789 2.07328 7.1513 2.42479 6.79979C2.7763 6.44828 3.25289 6.25056 3.75 6.25H16.25C16.7471 6.25056 17.2237 6.44828 17.5752 6.79979C17.9267 7.1513 18.1244 7.62789 18.125 8.125V14.375H16.4062V15.625H19.375V8.125C19.374 7.32353 19.0656 6.55298 18.5132 5.97226C17.9608 5.39154 17.2067 5.04493 16.4062 5.00391V5.00391ZM15.1562 5H4.84375V1.875H15.1562V5Z" fill="#E95420"/><path d="M16.7188 7.8125H15.4688V9.0625H16.7188V7.8125Z" fill="#E95420"/><path d="M4.53125 10.3125H2.96875V11.5625H4.53125V19.375H15.1562V11.5625H16.7188V10.3125H4.53125ZM13.9062 18.125H5.78125V11.5625H13.9062V18.125Z" fill="#E95420"/></g><defs><clipPath id="clip0_135_3902"><rect width="20" height="20" fill="white"/></clipPath></defs>
                                </svg>
                                <span class="toolip-action"><?php _e('Надрукувати накладні', NOVA_POSHTA_TTN_DOMAIN); ?></span>
                            </button>
                        </form>
                    </div>
                    <!-- Action send email all enabled checkboxes  -->
                    <div class="group-action group__email icon-hover">
                        <form id="posts-filter" method="post" target="_blank">
                            <input type="hidden" name="bulklist" id="bulklist" value="">
                            <input type="hidden" name="bulklistdelete" id="bulklistdelete" value="">
                            <input type="hidden" name="bulklistnew" id="bulklistnew" value="">
                            <input type="hidden" name="action" value="">
                            <button type="submit">
                                <svg class="action-regular" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M0 2.5V17.5H20V2.5H0ZM17.8815 3.67647L11.0558 9.38047L10 10.2628L8.94418 9.38047L2.11847 3.67647H17.8815ZM1.17647 4.42241L8.02682 10.1471L1.17647 15.8717V4.42241ZM2.47041 16.3235L8.94418 10.9136L10 11.796L11.0558 10.9136L17.5295 16.3235H2.47041ZM18.8235 15.8717L11.9732 10.1471L18.8235 4.42241V15.8717Z" fill="#7C7C7C"/>
                                </svg>
                                <svg class="action-hover" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M0 2.5V17.5H20V2.5H0ZM17.8815 3.67647L11.0558 9.38047L10 10.2628L8.94418 9.38047L2.11847 3.67647H17.8815ZM1.17647 4.42241L8.02682 10.1471L1.17647 15.8717V4.42241ZM2.47041 16.3235L8.94418 10.9136L10 11.796L11.0558 10.9136L17.5295 16.3235H2.47041ZM18.8235 15.8717L11.9732 10.1471L18.8235 4.42241V15.8717Z" fill="#E95420"/>
                                </svg>
                                <span class="toolip-action"><?php _e('Відправити накладні на e-mail', NOVA_POSHTA_TTN_DOMAIN); ?></span>
                            </button>
                        </form>
                    </div>
                    <!-- Action remove all enabled checkboxes  -->
                    <div class="group-action group__trash icon-hover">
                        <form id="posts-filter" method="post" target="_blank">
                            <input type="hidden" name="bulklist" id="bulklist" value="">
                            <input type="hidden" name="bulklistdelete" id="bulklistdelete" value="">
                            <input type="hidden" name="bulklistnew" id="bulklistnew" value="">
                            <input type="hidden" name="action" value="trash">
                            <button type="submit">
                                <svg class="action-regular" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M10.9688 1.59375H11.125C11.0391 1.59375 10.9688 1.52344 10.9688 1.4375V1.59375H5.03125V1.4375C5.03125 1.52344 4.96094 1.59375 4.875 1.59375H5.03125V3H3.625V1.4375C3.625 0.748047 4.18555 0.1875 4.875 0.1875H11.125C11.8145 0.1875 12.375 0.748047 12.375 1.4375V3H10.9688V1.59375ZM1.125 3H14.875C15.2207 3 15.5 3.2793 15.5 3.625V4.25C15.5 4.33594 15.4297 4.40625 15.3438 4.40625H14.1641L13.6816 14.6211C13.6504 15.2871 13.0996 15.8125 12.4336 15.8125H3.56641C2.89844 15.8125 2.34961 15.2891 2.31836 14.6211L1.83594 4.40625H0.65625C0.570312 4.40625 0.5 4.33594 0.5 4.25V3.625C0.5 3.2793 0.779297 3 1.125 3ZM3.7168 14.4062H12.2832L12.7559 4.40625H3.24414L3.7168 14.4062Z" fill="#7C7C7C"/>
                                </svg>
                                <svg class="action-hover" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M10.9688 1.59375H11.125C11.0391 1.59375 10.9688 1.52344 10.9688 1.4375V1.59375H5.03125V1.4375C5.03125 1.52344 4.96094 1.59375 4.875 1.59375H5.03125V3H3.625V1.4375C3.625 0.748047 4.18555 0.1875 4.875 0.1875H11.125C11.8145 0.1875 12.375 0.748047 12.375 1.4375V3H10.9688V1.59375ZM1.125 3H14.875C15.2207 3 15.5 3.2793 15.5 3.625V4.25C15.5 4.33594 15.4297 4.40625 15.3438 4.40625H14.1641L13.6816 14.6211C13.6504 15.2871 13.0996 15.8125 12.4336 15.8125H3.56641C2.89844 15.8125 2.34961 15.2891 2.31836 14.6211L1.83594 4.40625H0.65625C0.570312 4.40625 0.5 4.33594 0.5 4.25V3.625C0.5 3.2793 0.779297 3 1.125 3ZM3.7168 14.4062H12.2832L12.7559 4.40625H3.24414L3.7168 14.4062Z" fill="#E95420"/>
                                </svg>
                                <span class="toolip-action"><?php _e('Видалити накладні', NOVA_POSHTA_TTN_DOMAIN); ?></span>
                            </button>
                        </form>
                    </div>
                </div>
                <!-- Table Body -->
                <div class="mrkvnp-table-body">
                        <table class="wp-list-table widefat fixed striped posts">
                            <thead>
                                <tr>
                                    <td id="cb" class="manage-column column-cb check-column">
                                        <label class="screen-reader-text" for="cb-select-all-form">
                                            <input id="cb-select-all-form" type="checkbox">
                                        </label>
                                    </td>
                                    <th scope="col" id="title" class="manage-column column-title column-primary sortable desc">
                                        <span>Номер накладної</span>
                                    </th>
                                    <th scope="col" id="author" class="">Номер замовлення</th>
                                    <th scope="col" id="author" class="">Дата оформлення</th>
                                    <th scope="col" id="tags" class="manage-column column-tags">Статус відправлення</th>
                                    <th class="lastrow col">Обрати дію</th>
                                </tr>
                            </thead>
                            <?php
                                $results = array();
                            ?>
                            <tbody id="the-list">
                                <?php foreach( $results as $invoice ) {
                                ?>
                                    <tr>
                                        <th scope="row" class="check-column checkbin">
                                            
                                                <label class="screen-reader-text" for="cb-select-<?php echo $invoice['order_invoice']; ?>">
                                                    <input class="checkb" id="cb-select-<?php echo $invoice['order_invoice']; ?>"
                                                type="checkbox" name="post[]" value="<?php echo $invoice['order_invoice']; ?>"
                                                valued="<?php echo $invoice['invoice_ref']; ?>">
                                                </label>
                                        </th>
                                        <?php
                                            $sqlgetref = "SELECT invoice_ref FROM `".$wpdb->prefix."novaposhta_ttn_invoices` WHERE   order_invoice='".$invoice['order_invoice']."' ";
                                            $reff = $wpdb->get_results($sqlgetref);
                                            $ref = $reff[0]->invoice_ref;
                                        ?>
                                        <td class="firstrow">
                                            <strong>
                                                <a  href="post.php?post=<?php echo $invoice['order_id']; ?>&action=edit"
                                                    class="row-title"><?php echo $invoice['order_invoice']; ?>
                                                </a>
                                            </strong>
                                        </td>
                                        <td class="idrow">
                                            <a  href="post.php?post=<?php echo $invoice['order_id']; ?>&action=edit" class="row-title"><?php echo $invoice['order_id']; ?></a>
                                        </td>
                                        <td class="daterow" ><?php echo $obj['DateCreated']; // echo substr( $obj['DateCreated'], 0, 10 ); ?></td>
                                        <td class=startcode code=<?php echo $obj['StatusCode'];?> ttn=<?php echo $obj['Number'];?>  ><?php echo $obj['Status']; ?></td>
                                        <td class="lastrow">
                                            <div class="lastrow__invoice-action">
                                                <a href="https://my.novaposhta.ua/orders/printMarkings/orders[]/<?php echo $invoice_number; ?>/type/pdf/apiKey/<?php echo $api_key; ?>" class="button icon-hover" style="margin: 5px;" target="_blank">
                                                   <svg class="action-regular" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17.5 2.5V9.16667C17.5 13.769 13.769 17.5 9.16667 17.5H2.5V2.5H17.5Z" stroke="#7C7C7C" stroke-linecap="square"/><path d="M7.5 17.5C9.72222 17.5 10.8333 16.3889 10.8333 14.1667C10.8333 14.1667 10.8333 13.0556 10.8333 10.8333H14.1667C16.3889 10.8333 17.5 9.72222 17.5 7.5" stroke="#7C7C7C" stroke-linecap="square"/></svg>
                                                   <svg class="action-hover" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17.5 2.5V9.16667C17.5 13.769 13.769 17.5 9.16667 17.5H2.5V2.5H17.5Z" stroke="#E95420" stroke-linecap="square"/><path d="M7.5 17.5C9.72222 17.5 10.8333 16.3889 10.8333 14.1667C10.8333 14.1667 10.8333 13.0556 10.8333 10.8333H14.1667C16.3889 10.8333 17.5 9.72222 17.5 7.5" stroke="#E95420" stroke-linecap="square"/></svg>
                                                   <span class="toolip-action"><?php _e('Надрукувати стікер', NOVA_POSHTA_TTN_DOMAIN); ?></span>
                                                </a>
                                                <a href="https://my.novaposhta.ua/orders/printDocument/orders[]/<?php echo $invoice_number; ?>/type/pdf/apiKey/<?php echo $api_key; ?>" class="button icon-hover" style="margin: 5px;" target="_blank">
                                                    <svg class="action-regular" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_221_5010)"><path d="M16.4062 5.00391V0.625H3.59375V5.00391C2.79333 5.04493 2.03917 5.39154 1.4868 5.97226C0.93442 6.55298 0.625951 7.32353 0.625 8.125V15.625H3.28125V14.375H1.875V8.125C1.87556 7.62789 2.07328 7.1513 2.42479 6.79979C2.7763 6.44828 3.25289 6.25056 3.75 6.25H16.25C16.7471 6.25056 17.2237 6.44828 17.5752 6.79979C17.9267 7.1513 18.1244 7.62789 18.125 8.125V14.375H16.4062V15.625H19.375V8.125C19.3741 7.32353 19.0656 6.55298 18.5132 5.97226C17.9608 5.39154 17.2067 5.04493 16.4062 5.00391ZM15.1562 5H4.84375V1.875H15.1562V5Z" fill="#7C7C7C"/><path d="M16.7188 7.8125H15.4688V9.0625H16.7188V7.8125Z" fill="#7C7C7C"/><path d="M4.53125 10.3125H2.96875V11.5625H4.53125V19.375H15.1562V11.5625H16.7188V10.3125H4.53125ZM13.9062 18.125H5.78125V11.5625H13.9062V18.125Z" fill="#7C7C7C"/></g><defs><clipPath id="clip0_221_5010"><rect width="20" height="20" fill="white"/></clipPath></defs></svg>
                                                    <svg class="action-hover" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_221_5010)"><path d="M16.4062 5.00391V0.625H3.59375V5.00391C2.79333 5.04493 2.03917 5.39154 1.4868 5.97226C0.93442 6.55298 0.625951 7.32353 0.625 8.125V15.625H3.28125V14.375H1.875V8.125C1.87556 7.62789 2.07328 7.1513 2.42479 6.79979C2.7763 6.44828 3.25289 6.25056 3.75 6.25H16.25C16.7471 6.25056 17.2237 6.44828 17.5752 6.79979C17.9267 7.1513 18.1244 7.62789 18.125 8.125V14.375H16.4062V15.625H19.375V8.125C19.3741 7.32353 19.0656 6.55298 18.5132 5.97226C17.9608 5.39154 17.2067 5.04493 16.4062 5.00391ZM15.1562 5H4.84375V1.875H15.1562V5Z" fill="#E95420"/><path d="M16.7188 7.8125H15.4688V9.0625H16.7188V7.8125Z" fill="#E95420"/><path d="M4.53125 10.3125H2.96875V11.5625H4.53125V19.375H15.1562V11.5625H16.7188V10.3125H4.53125ZM13.9062 18.125H5.78125V11.5625H13.9062V18.125Z" fill="#E95420"/></g><defs><clipPath id="clip0_221_5010"><rect width="20" height="20" fill="white"/></clipPath></defs></svg>
                                                    <span class="toolip-action"><?php _e('Надрукувати накладну', NOVA_POSHTA_TTN_DOMAIN); ?></span>
                                                </a>
                                                <form action="admin.php?page=morkvanp_invoices" method="POST">
                                                    <input type="text" name="send_mail_order_id" value="<?php echo $invoice['order_id']; ?>" style="display: none" />
                                                    <input type="text" name="send_mail_date_created" value="<?php // echo $obj['DateCreated']; ?>" style="display: none"; />
                                                    <input type="text" name="send_mail_invoice_number" value="<?php echo $obj['Number']; ?>" style="display: none;" />
                                                    <input type="text" name="send_mail" value="ON" style="display: none"; />
                                                    <button type="submit" class="icon-hover">
                                                        <svg class="action-regular" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0 2.5V17.5H20V2.5H0ZM17.8815 3.67647L11.0558 9.38047L10 10.2628L8.94418 9.38047L2.11847 3.67647H17.8815ZM1.17647 4.42241L8.02682 10.1471L1.17647 15.8717V4.42241ZM2.47041 16.3235L8.94418 10.9136L10 11.796L11.0558 10.9136L17.5295 16.3235H2.47041ZM18.8235 15.8717L11.9732 10.1471L18.8235 4.42241V15.8717Z" fill="#7C7C7C"/></svg>
                                                        <svg class="action-hover" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0 2.5V17.5H20V2.5H0ZM17.8815 3.67647L11.0558 9.38047L10 10.2628L8.94418 9.38047L2.11847 3.67647H17.8815ZM1.17647 4.42241L8.02682 10.1471L1.17647 15.8717V4.42241ZM2.47041 16.3235L8.94418 10.9136L10 11.796L11.0558 10.9136L17.5295 16.3235H2.47041ZM18.8235 15.8717L11.9732 10.1471L18.8235 4.42241V15.8717Z" fill="#E95420"/></svg>
                                                        <span class="toolip-action"><?php _e('Відправити накладну на e-mail', NOVA_POSHTA_TTN_DOMAIN); ?></span>
                                                    </button>
                                                </form>
                                                <form action="admin.php?page=morkvanp_invoices" method="POST">
                                                    <input type="text" name="delete_invoice" value="<?php echo $invoice['invoice_ref']; ?>" style="display: none;" />
                                                    <input tyoe="text" name="delete_invoice_on" value="ON" style="display: none;" />
                                                    <button type="submit" class="icon-hover">
                                                       <svg class="action-regular" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12.9688 3.59375H13.125C13.0391 3.59375 12.9688 3.52344 12.9688 3.4375V3.59375H7.03125V3.4375C7.03125 3.52344 6.96094 3.59375 6.875 3.59375H7.03125V5H5.625V3.4375C5.625 2.74805 6.18555 2.1875 6.875 2.1875H13.125C13.8145 2.1875 14.375 2.74805 14.375 3.4375V5H12.9688V3.59375ZM3.125 5H16.875C17.2207 5 17.5 5.2793 17.5 5.625V6.25C17.5 6.33594 17.4297 6.40625 17.3438 6.40625H16.1641L15.6816 16.6211C15.6504 17.2871 15.0996 17.8125 14.4336 17.8125H5.56641C4.89844 17.8125 4.34961 17.2891 4.31836 16.6211L3.83594 6.40625H2.65625C2.57031 6.40625 2.5 6.33594 2.5 6.25V5.625C2.5 5.2793 2.7793 5 3.125 5ZM5.7168 16.4062H14.2832L14.7559 6.40625H5.24414L5.7168 16.4062Z" fill="#7C7C7C"/></svg>
                                                       <svg class="action-hover" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12.9688 3.59375H13.125C13.0391 3.59375 12.9688 3.52344 12.9688 3.4375V3.59375H7.03125V3.4375C7.03125 3.52344 6.96094 3.59375 6.875 3.59375H7.03125V5H5.625V3.4375C5.625 2.74805 6.18555 2.1875 6.875 2.1875H13.125C13.8145 2.1875 14.375 2.74805 14.375 3.4375V5H12.9688V3.59375ZM3.125 5H16.875C17.2207 5 17.5 5.2793 17.5 5.625V6.25C17.5 6.33594 17.4297 6.40625 17.3438 6.40625H16.1641L15.6816 16.6211C15.6504 17.2871 15.0996 17.8125 14.4336 17.8125H5.56641C4.89844 17.8125 4.34961 17.2891 4.31836 16.6211L3.83594 6.40625H2.65625C2.57031 6.40625 2.5 6.33594 2.5 6.25V5.625C2.5 5.2793 2.7793 5 3.125 5ZM5.7168 16.4062H14.2832L14.7559 6.40625H5.24414L5.7168 16.4062Z" fill="#E95420"/></svg>
                                                       <span class="toolip-action"><?php _e('Видалити накладну', NOVA_POSHTA_TTN_DOMAIN); ?></span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } // <?php foreach( $results as $invoice ) ?>
                            </tbody>
                        </table>
                        <div id="ajax-response"></div>
                        <br class="clear">
                        <div class="container">
                            <div class="padding">
                                <?php // 'Видалити ЕН' from row actions
                                    if ( isset( $_POST['delete_invoice_on'] ) ) {
                                        $document_ref = $_POST['delete_invoice']; // Get invoice Ref
                                        $document_number = $_POST['delete_invoice_number']; // Get invice number
                                        $invoiceModel->runMyInvoicesDeleteRowAction( $document_ref, $document_number );
                                    }
                                ?>
                                <div class="invoices">
                                    <?php
                                        if ( isset( $_POST['send_mail'] ) ) {
                                            $send_mail_order_id = $_POST['send_mail_order_id'];
                                            $selected_order = wc_get_order( $send_mail_order_id );
                                            $order = $selected_order->get_data();
                                            $invoice_email = $order['billing']['email'];
                                            $invoice_number = $wpdb->get_results( "SELECT order_invoice FROM {$wpdb->prefix}novaposhta_ttn_invoices WHERE order_id = '$send_mail_order_id' ", ARRAY_A );
                                            $linkd = 'https://novaposhta.ua/tracking/?cargo_number='.$invoice_number[0]['order_invoice'];
                                            $message = esc_attr( get_option( 'mrkvnp_email_template' ) );
                                            $message = str_replace("[NOVAPOSHTA_TTN]", $invoice_number[0]['order_invoice'], $message);
                                            $message = str_replace("[NOVAPOSHTA_ORDER]", $send_mail_order_id, $message);
                                            $message = str_replace("[LINK]", $linkd, $message);
                                            $message = str_replace("[NOVAPOSHTA_DATE]", " ", $message);
                                            $woocommerce_email_from = get_option( 'woocommerce_email_from_address' );
                                            $message_full = $message . ' ' . $woocommerce_email_from;
                                            $subject = get_option( 'mrkvnp_email_subject' );
                                            $subject = str_replace("[NOVAPOSHTA_TTN]", $invoice_number[0]['order_invoice'], $subject);
                                            $subject = str_replace("[NOVAPOSHTA_ORDER]", $send_mail_order_id, $subject);
                                            $subject = str_replace("[NOVAPOSHTA_DATE]", " ", $subject);
                                            $order = wc_get_order( $_POST['send_mail_order_id'] );
                                            $note = "Відправлено email з номером накладної.";
                                        $order->add_order_note( $note );
                                        $order->save();
                                            wp_mail( $invoice_email, $subject, $message_full );
                                        }
                                        if ( isset( $_POST['email'] ) ) {
                                            $date_arr = explode( ' ', $_POST['date'] );
                                            $linkd = 'https://novaposhta.ua/tracking/?cargo_number='.$_POST['number'];

                                            $message_ajax = esc_attr( get_option( 'mrkvnp_email_template' ) );
                                            $message_ajax = str_replace("[NOVAPOSHTA_TTN]", $_POST['number'], $message_ajax);
                                            $message_ajax = str_replace("[NOVAPOSHTA_ORDER]", $_POST['order'], $message_ajax);

                                            $message_ajax = str_replace("[LINK]", $linkd, $message_ajax);
                                            $message = str_replace("[LINK]", $linkd, $message);
                                            $message_ajax = str_replace("[NOVAPOSHTA_DATE]", "", $message_ajax);
                                            $subject = get_option( 'mrkvnp_email_subject' );
                                            $order = wc_get_order($_POST['order']);
                                            $note = "Відправлено email з номером накладної.";
                                            $order->add_order_note( $note );
                                            wp_mail( $_POST['email'], $subject, $message_ajax );
                                        }
                                    ?>
                                </div>
                            </div><!-- .padding -->
                        </div><!-- .container -->
                </div>
                <div class="mrkvnp-table-footer">
                    <div class="mrkvnp-table-footer__pagination">
                        <?php 
                            $total = $invoiceModel->runMyInvoicesGetCountInvoicesFromDB();

                            // Set data for pagination
                            $post_per_page = isset( $_GET['limit'] ) ? abs( (int) $_GET['limit'] ) : 10;
                            $page = isset( $_GET['cpage'] ) ? abs( (int) $_GET['cpage'] ) : 1;
                            $offset = ( $page * $post_per_page ) - $post_per_page;

                            // Create pagination html
                            echo '<div class="pagination">';
                            echo paginate_links( array(
                            'base' => add_query_arg( 'cpage', '%#%' ),
                            'format' => '',
                            'prev_text' => '<svg width="7" height="14" viewBox="0 0 7 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.78017 13.4224C6.63952 13.5786 6.44879 13.6664 6.24992 13.6664C6.05104 13.6664 5.86031 13.5786 5.71967 13.4224L0.469666 7.58876C0.329062 7.43248 0.250076 7.22054 0.250076 6.99956C0.250076 6.77858 0.329062 6.56665 0.469666 6.41037L5.71967 0.576745C5.86112 0.424939 6.05057 0.340939 6.24722 0.342838C6.44387 0.344737 6.63197 0.432383 6.77103 0.586898C6.91009 0.741412 6.98896 0.950433 6.99067 1.16894C6.99238 1.38745 6.91679 1.59796 6.78017 1.75514L2.06042 6.99956L6.78017 12.244C6.92077 12.4003 6.99976 12.6122 6.99976 12.8332C6.99976 13.0542 6.92077 13.2661 6.78017 13.4224Z" fill="#7C7C7C"/></svg>',
                            'next_text' => ' <svg width="7" height="14" viewBox="0 0 7 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0.219834 0.577619C0.36048 0.421386 0.551211 0.333618 0.750084 0.333618C0.948957 0.333618 1.13969 0.421386 1.28033 0.577619L6.53034 6.41124C6.67094 6.56752 6.74992 6.77946 6.74992 7.00044C6.74992 7.22142 6.67094 7.43335 6.53034 7.58963L1.28033 13.4233C1.13888 13.5751 0.949429 13.6591 0.752781 13.6572C0.556134 13.6553 0.368024 13.5676 0.228968 13.4131C0.0899119 13.2586 0.0110354 13.0496 0.00932663 12.8311C0.00761785 12.6125 0.0832137 12.402 0.219832 12.2449L4.93958 7.00044L0.219834 1.75601C0.0792306 1.59973 0.000243974 1.3878 0.000244009 1.16681C0.000244044 0.945834 0.0792308 0.7339 0.219834 0.577619Z" fill="#7C7C7C"/></svg>',
                            'total' => ceil($total / $post_per_page),
                            'current' => $page,
                            'type' => 'list'
                            ));
                            echo '</div>';
                        ?>
                    </div>
                    <div class="mrkvnp-table-footer__total-page">
                        <p class="total-page__title"><?php _e('Кількість накладних на сторінці: ', NOVA_POSHTA_TTN_DOMAIN); ?></p>
                        <?php 
                            // Default invoices on page
                            $total_on_page = 10;
                            // List of possible invoices on page
                            $arr_totals = array('10', '20', '50', '100');

                            // Check isset limit data in query get
                            if(isset($_GET['limit'])){
                                $total_on_page = $_GET['limit'];
                            }

                        ?>
                        <ul>
                            <?php 
                                // Loop all limit
                                foreach($arr_totals as $total){
                                    ?>
                                        <li>
                                            <?php 
                                            #//Check current total on page
                                            if($total == $total_on_page){
                                                ?>
                                                    <span><?php echo $total; ?></span>
                                                <?php
                                            }
                                            else{
                                                ?>  
                                                    <form method=get>
                                                        <input type="hidden" name="page" value="morkvanp_invoices">
                                                        <input type="hidden" name="limit" value="<?php echo $total; ?>">
                                                        <button type="submit"><?php echo $total; ?></button>
                                                    </form>
                                                <?php
                                            }
                                            ?>
                                        </li>
                                    <?php
                                }
                            ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="col large-3">
            <div class="mrkvnp_my_invoices__stats mrkvnp_my_invoices__side">
                <h3><?php _e('Статистика відправлень', NOVA_POSHTA_TTN_DOMAIN); ?></h3>
                <ul>
                    <li class="invoices__stats__line">
                        <p><?php _e('Очікує прибуття на пошту', NOVA_POSHTA_TTN_DOMAIN); ?></p>
                        <span>12</span>
                    </li>
                    <li class="invoices__stats__line">
                        <p><?php _e('У відділенні', NOVA_POSHTA_TTN_DOMAIN); ?></p>
                        <span>0</span>
                    </li>
                    <li class="invoices__stats__line">
                        <p><?php _e('Виконано', NOVA_POSHTA_TTN_DOMAIN); ?></p>
                        <span>203</span>
                    </li>
                    <li class="invoices__stats__line">
                        <p><?php _e('Всі', NOVA_POSHTA_TTN_DOMAIN); ?></p>
                        <span>20302</span>
                    </li>
                </ul>
            </div>
            <div class="mrkvnp_my_invoices__status-update mrkvnp_my_invoices__side">
                <h3><?php _e('Останнє оновлення статусів:', NOVA_POSHTA_TTN_DOMAIN); ?></h3>
                <p>2022-05-17 06:50:02 (UTC)</p>
                <a href="#"><?php _e('Оновити зараз', NOVA_POSHTA_TTN_DOMAIN); ?></a>
            </div>
            <?php require 'card.php' ; ?>
        </div>
    </div>
<?php } // if ( isset( $_GET['invoice'] ) ) { include 'edit-ttn.php'; } else { // Рядок 113
