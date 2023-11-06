        <!-- <hr style="color:#f0f0f0;margin:auto 30px;">
        <div class="create-invoice-params"> -->
        <table class="form-table invoice-params full-width-input">
            <tbody>
                <?php $invoiceModel->showFormBlockTitle( '3. Параметри відправлення' ); ?>
                <?php
                    $recipient_city_ref = $invoiceModel->getRecipientCityByNameRef( $shipping_city_name );
                    $mrkvnp_invoice_packing_weight = floatval( \get_option( 'mrkvnp_invoice_packing_weight' ) );
                    $mrkvnp_invoice_volume_with_packing = get_option( 'mrkvnp_invoice_volume_with_packing' );
                ?>
                <tr>
                    <th scope=row>
                        <label for=sender_cargo><?php _e( 'Тип відправлення:', NOVA_POSHTA_TTN_DOMAIN ) ?></label>
                    </th>
                    <td>
                        <?php $invoiceModel->showGetCargoType( $recipient_city_ref, $shipping_warehouse_name ); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="mrkvnp_invoice_payer"><?php _e( 'Платник доставки:', NOVA_POSHTA_TTN_DOMAIN ) ?></label>
                    </th>
                    <td>
                        <?php $invoice_payer = $invoiceModel->showGetInvoicePayer( $order_data ); ?>
                    </td>
                </tr>
                <!-- <tr>
                    <th scope="row">
                        <label for="mrkvnp_invoice_payment_type"><?php _e( 'Тип оплати Відправника:', NOVA_POSHTA_TTN_DOMAIN ) ?></label>
                    </th>
                    <td>
                        <?php $invoice_payer = $invoiceModel->showGetInvoicePaymentType( $order_data ); ?>
                    </td>
                </tr> -->
                <tr>
                    <th scope="row">
                        <label for="mrkvnp_invoice_description"><?php _e( 'Опис відправлення:', NOVA_POSHTA_TTN_DOMAIN ) ?></label>
                    </th>
                    <td class="pb7">
                        <textarea id="mrkvnp_invoice_description" name="mrkvnp_invoice_description" rows="1"
                            class="input" minlength="1" maxlength="100" required /><?php echo $descriptionarea ?></textarea>
                        <p id="error_dec"></p>
                    </td>
                </tr>
            </tbody>
        </table>
        <table style="margin-top: 33px;" class="form-table invoice-params full-width-input">
            <tbody>
                <tr>
                    <th scope="row">
                        <label class="light" for="mrkvnp_invoice_cargo_weight">
                            <?php _e( 'Вага:', NOVA_POSHTA_TTN_DOMAIN ) ?>
                        </label>
                    </th>
                <td>
                    <?php 
                        $weight_unit_new = get_option('woocommerce_weight_unit');
                        switch($weight_unit_new){
                            case 'kg':
                            $weight_unit_new = 'кг';
                            break;
                            case 'g':
                            $weight_unit_new = 'г';
                            break;
                            default:
                            break;
                        }
                    ?>
                    <input type="text" name="mrkvnp_invoice_cargo_weight" id="mrkvnp_invoice_cargo_weight"
                        value="<?php echo $invoiceModel->getCargoWeight( $order_obj ); ?>" /><span> <?php echo $weight_unit_new; ?></span>
                </td>
                </tr>
                <tr>
                    <?php
                        $wc_dimention_unit = get_option('woocommerce_dimension_unit');
                        if ('cm' == $wc_dimention_unit) $site_dimention_unit = 'см';
                        if ('m' == $wc_dimention_unit) $site_dimention_unit = 'м';
                        $dimentions = $invoiceModel->calcOrderDimensions( $order_data );
                        if ( \get_option('mrkvnp_invoice_length') ) $dimentions[0] = \get_option('mrkvnp_invoice_length');
                        if ( \get_option('mrkvnp_invoice_width') ) $dimentions[1] = \get_option('mrkvnp_invoice_width');
                        if ( \get_option('mrkvnp_invoice_height') ) $dimentions[2] = \get_option('mrkvnp_invoice_height');
                    ?>
                <tr>
                    <th scope="row">
                        <label for="order-dimentions"><?php _e( 'Розміри:', NOVA_POSHTA_TTN_DOMAIN ) ?></label>
                    </th>
                    <td style="padding:0;margin-bottom:0;">
                        <table width="100%">
                            <tr style="padding-left:0;">
                                <td style="padding-bottom:0;margin-bottom:0;display:flex;flex-direction:row;">
                                    <input style="width:60px;" type="text" id="mrkvnp_invoice_cargo_length"
                                        name="mrkvnp_invoice_cargo_length" value="<?php echo $dimentions[0]; ?>" />
                                    <input style="width:60px;" type="text" id="mrkvnp_invoice_cargo_width"
                                        name="mrkvnp_invoice_cargo_width" value="<?php echo $dimentions[1]; ?>" />
                                    <input style="width:60px;" type="text" id="mrkvnp_invoice_cargo_height"
                                        name="mrkvnp_invoice_cargo_height" value="<?php echo $dimentions[2]; ?>" />
                                    <span style="margin-top:12px;margin-left:3px;"> см</span>
                                </td>
                                <td style="padding:0 0 0 10px;color: #9b9b9b;">
                                    <?php echo '<div>Довжина x Ширина x Висота</div>'; ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <th scope="row" class="pb0">
                        <label class="light" for="mrkvnp_invoice_cargo_volume" ><?php _e( 'Об\'ємна вага:', NOVA_POSHTA_TTN_DOMAIN ) ?></label>
                    </th>
                    <td class="pb0">
                        <input style="width:70px;" type="text" id="mrkvnp_invoice_cargo_volume" name="mrkvnp_invoice_cargo_volume" readonly
                            value="<?php echo \get_option('mrkvnp_invoice_volume'); ?>" />
                        <span style="margin-top:8px;margin-left:3px;"></span>
                    </td>
                </tr>
                <tr>
                    <td colspan=2>
                        <p><?php //echo $volumemessage; ?></p>
                    </td>
                </tr>
                <input type=hidden name=InfoRegClientBarcodes value="<?php echo $order_id; ?>" >
                <!-- <tr>
                    <th scope=row>
                        <label for=invoice_priceid>Оголошена вартість</label>
                    </th>
                    <td>
                        <input id="invoice_priceid" type="text" name="mrkvnp_invoice_price" required
                            value="<?php //echo $invoiceModel->getInvoicePrice( $order_obj ); ?>" />
                    </td>
                </tr> -->
                <!-- <tr>
                    <th colspan="2">
                        <p class=light>Якщо поле залишити порожнім, буде використана вартість замовлення<br><?php //echo $weighte; ?></p>
                    </th>
                </tr> -->
                <!-- <tr>
                    <th scope="row">
                        <label for="mrkvnp_is_invoice_cod">Післяплата</label>
                    </th>
                    <td>
                        <?php
                            $cod =  \get_option( 'mrkvnp_is_auto_cod' );
                            $orderTotalLimit = \sanitize_text_field( \get_option( 'invoice_dpay' ) );
                            $codChecked = '';
                            if ( 'cod' == $invoiceModel->getPaymentMethod( $order_obj ) && ! $cod ) :
                                $codChecked = ' checked';
                            endif;
                        ?>
                        <input class="w24" type="checkbox" id="mrkvnp_is_invoice_cod" name="mrkvnp_is_invoice_cod"
                            value="1"<?php checked( isset( $_POST['mrkvnp_is_invoice_cod'] ) ); ?>
                            <?php echo $codChecked; ?> />
                    </td>
                </tr> -->
                <!-- <tr>
                    <th scope="row">
                        <label for="mrkvnp_invoice_redelivery_payer">Платник зворотньої доставки</label>
                    </th>
                    <td>
                        <?php if ( $orderTotalLimit > 0 ):
                                echo '<select id="mrkvnp_invoice_redelivery_payer" name="mrkvnp_invoice_redelivery_payer" ><option value="Recipient"';
                                if ( $invoiceModel->getInvoicePrice( $order_obj ) < $orderTotalLimit ) echo ' selected ';
                                echo '>Одержувач</option><option value="Sender" ';
                                if ( $invoiceModel->getInvoicePrice( $order_obj ) > $orderTotalLimit ) echo ' selected ';
                                echo '>Відправник</option></select>';
                            else :
                                echo '<select id="mrkvnp_invoice_redelivery_payer" name="mrkvnp_invoice_redelivery_payer" ><option value="Recipient" ';
                                if ( $invoice_payer == 0 ) echo ' selected ';
                                echo '>Одержувач</option><option value="Sender" ';
                                if ( $invoice_payer == 1 ) echo ' selected ';
                                echo '>Відправник</option></select>';
                            endif;
                        ?>
                    </td>
                </tr> -->
                <!-- <tr>
                    <th scope="row" >
                        <label for="mrkvnp_invoice_descriptionred">Штрихкод RedBoxBarcode</label>
                    </th>
                    <td>
                        <input id="mrkvnp_invoice_descriptionred" type="text" name="mrkvnp_invoice_descriptionred" placeholder="Наприклад: 0105QD26L" />
                    </td>
                </tr>
                <tr>
                    <th colspan="2">
                        <p class="light">Штрихкод RedBoxBarcode - не обов'язкове поле.</p>
                    </th>
                </tr> -->
            </tbody>
        </table>
    </div><!-- .create-invoice-params -->
    <div class="create-invoice-additional-services">
        <hr style="color:#f0f0f0;margin:auto 30px;">
        <table class="form-table-chk invoice-additional-services full-width-input">
            <tbody>
                <?php $invoiceModel->showFormBlockTitle( __( '4. Додаткові послуги', NOVA_POSHTA_TTN_DOMAIN ) ); ?>
                <tr id="mrkvnp_cash_transfer" class="mrkvnp-th-colorgray">
                    <th scope="row" style="width:170px;text-align:start;">
                        <label for=mrkvnp_is_cod_payment_chk><?php _e( 'Грошовий переказ ', NOVA_POSHTA_TTN_DOMAIN ) ?><br>
                    <small style="font-weight:bold;color:#dc3232;">Лише у Про-версії</small></label>
                    </th>
                    <td>
                        <label for="mrkvnp_is_cod_payment_chk" class="form-switch">
                            <?php
                                $checkedCod = '';
                                if ( 'cod' == $order_data['payment_method'] ) $checkedCod = 'checked="checked"'
                            ?>
                            <input type="checkbox" class="regular-text" 
                                 disabled="" >
                            <i></i>
                        </label>
                    </td>
                </tr>
            </tbody>
        </table>
    </div><!-- .create-invoice-additional-services -->
        <!-- <table class="form-table full-width-input">
            <tbody>
                <tr>
                    <td> -->
                        <!-- <input type="submit" value="Створити" name="create_invoice" class="checkforminputs button button-primary" id="submit"/> -->
                    <!-- </td>
                </tr>
            </tbody>
        </table> -->
        <!-- </div> .tablecontainer -->

    <!-- </form> -->
<!-- </div> .container -->
<!-- </div> .tablecontainer -->
<!-- </form> -->
<!-- </div> .create-invoice-all -->
<script>
    jQuery(function($) {
        function validatePhoneNumber(inputTxt) {
             var phoneNum = /^\d{12}$/;
             if (inputTxt.value.match(phoneNum)) {
                 return true;
             } else {
                 alert("В полі 'Телефон' повинні бути лише числа від 0 до 9 (без пробілів і спецсимволів).");
                 return false;
             }
         }
         var inputPhoneField = $('#sender_phone, #recipient_phone');
         // inputPhoneField.change(function() {
         inputPhoneField.on('change', function() {
             if (!validatePhoneNumber(this)) {
                 var inputVal = $(this).val();
                 $(this).val(inputVal);
                 // $(this).focus();
                 $(this).trigger('focus');
             }
         });

         // Shows 'Помилки з API Нова Пошта': Notieces and there code numbers.
         var errnonpID = document.getElementById("errnonp");
         var msgboxNP = jQuery("#messageboxnp");
         if (errnonpID) {
            setTimeout(function(){
              jQuery("#errnonp").appendTo('#messageboxnp');
              msgboxNP.addClass('error');
            }, 1000);
            jQuery( "#submit" ).on( "click", function() {
              msgboxNP.fadeIn();
            });
         }
    });
</script>
