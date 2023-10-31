            <table class="form-table recipient full-width-input">
                <tbody>
                    <?php $invoiceModel->showFormBlockTitle( '2. Одержувач' ); ?>
                    <?php
                    $shippingMethodId = $invoiceModel->getShippingMethod($order_obj);
                    // Show recipient form block when nova poshta address shipping method chosen - цей блок не працює, можна видалити.
                    if ( 'npttn_address_shipping_method_false' == $shippingMethodId ) : ?>
<!--                         <tr>
                            <th scope="row">
                                <label for="recipient_name"><?php _e( 'Прізвище', NOVA_POSHTA_TTN_DOMAIN ) ?></label>
                            </th>
                            <td>
                                <input type="text" name="mrkvnp_invoice_recipient_last_name" id="recipient_name" class="input recipient_last_name"
                                    value="<?php echo $shipping_last_name; ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="recipient_name"><?php _e( 'Ім\'я', NOVA_POSHTA_TTN_DOMAIN ) ?></label>
                            </th>
                            <td>
                                <input type="text" name="mrkvnp_invoice_recipient_first_name" id="recipient_name" class="input recipient_first_name"
                                    value="<?php echo $shipping_first_name; ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="recipient_name"><?php _e( 'По батькові', NOVA_POSHTA_TTN_DOMAIN ) ?></label>
                            </th>
                            <td>
                                <input type="text" name="mrkvnp_invoice_recipient_middle_name" id="recipient_name" class="input recipient_middle_name"
                                    value="<?php echo $shipping_middle_name; ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="recipient_city">Місто одержувача</label>
                            </th>
                            <td>
                                <input type="text" name="mrkvnp_invoice_recipient_city_name" id="recipient_city" class="recipient_city"
                                    value="<?php echo esc_html( $shipping_city_name ); ?>"  readonly />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="RecipientAddressName">Адреса:</label>
                            </th>
                            <td>
                                <textarea name="addresstext"><?php echo esc_html( $shipping_warehouse_name . ', кв. ' . $shipping_flat ); ?></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="recipient_phone">Телефон</label>
                            </th>
                            <td>
                                <input type="text" name="mrkvnp_invoice_recipient_phone" class="input recipient_phone" id="recipient_phone" value="<?php echo esc_html( $shipping_phone ); ?>" />
                            </td>
                        </tr> -->
                    <?php endif;
                    // Show recipient form block when nova poshta warehouse/poshtomat shipping method chosen
                    if ( 'nova_poshta_shipping_method' == $shippingMethodId ||
                        'nova_poshta_shipping_method_poshtomat' == $shippingMethodId ||
                        'npttn_address_shipping_method' == $shippingMethodId ) : ?>
                        <tr>
                            <td style="margin-bottom:0;">
                                <!-- <input type="text" name="mrkvnp_invoice_recipient_name" id="recipient_name" class="input recipient_name"
                                    value="<?php //echo esc_html( $shipping_first_name ) . ' ' .  esc_html( $shipping_last_name ); ?>" /> -->
                                <div class="dflex">
                                    <input type="text" name="mrkvnp_invoice_recipient_last_name" id="recipient_name" class="input recipient_last_name"
                                        value="<?php echo $shipping_last_name; ?>" placeholder="Прізвище" />
                                    <input type="text" name="mrkvnp_invoice_recipient_first_name" id="recipient_name" class="input recipient_first_name"
                                        value="<?php echo $shipping_first_name; ?>" placeholder="Ім'я" />
                                </div>
                                <input type="text" name="mrkvnp_invoice_recipient_middle_name" id="recipient_name" class="input recipient_middle_name"
                                    value="<?php echo $shipping_middle_name; ?>" placeholder="По батькові" />
                                <div class="dflex">
                                    <!-- <input type="text" name="mrkvnp_invoice_recipient_email" class="input recipient_email" id="recipient_email"
                                        value="<?php //echo esc_html( $shipping_email ); ?>" placeholder="E-mail" required /> -->
                                    <input type="text" name="mrkvnp_invoice_recipient_phone" class="input recipient_phone" id="recipient_phone"
                                        value="<?php echo esc_html( $shipping_phone ); ?>" placeholder="Телефон" required />
                                </div>
                                <div>
                                    <?php if ( 'nova_poshta_shipping_method' == $shippingMethodId ) {
                                        _e( 'Відділення', NOVA_POSHTA_TTN_DOMAIN );
                                        $mrkvnp_wh = __( 'Номер відділення', NOVA_POSHTA_TTN_DOMAIN );
                                    }
                                    if ( 'nova_poshta_shipping_method_poshtomat' == $shippingMethodId ) {
                                        _e( 'Поштомат', NOVA_POSHTA_TTN_DOMAIN );
                                        $mrkvnp_wh = __( 'Номер поштомату', NOVA_POSHTA_TTN_DOMAIN );
                                    }
                                    if ( 'npttn_address_shipping_method' == $shippingMethodId ) _e( 'Адреса', NOVA_POSHTA_TTN_DOMAIN ); ?>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="margin-bottom:0;">
                                <div class="dflexcol">
                                    <input style="margin-bottom:0;" type="text" name="mrkvnp_invoice_recipient_city_name" id="recipient_city"
                                        class="recipient_city" value="<?php echo stripslashes( esc_html( $shipping_city_name ) ); ?>" readonly
                                            placeholder="<?php _e( 'Населений пункт', NOVA_POSHTA_TTN_DOMAIN ); ?>" />
                                    <input type="hidden" name="mrkvnp_invoice_recipient_city_ref" id="mrkvnp_invoice_recipient_city_ref"
                                        value="<?php echo $invoiceModel->getRecipientCityByNameRef(esc_html( $shipping_city_name )); ?>">
                                </div>
                            </td>
                        </tr>
                        <?php if ( 'nova_poshta_shipping_method' == $shippingMethodId || 'nova_poshta_shipping_method_poshtomat' == $shippingMethodId ) : ?>
                        <tr>
                            <td style="margin-bottom:0;">
                                <div class="dflexcol">
                                    <!-- <input type="text" name="mrkvnp_invoice_recipient_city_name" id="recipient_city"
                                        class="recipient_city" value="<?php echo esc_html( $shipping_city_name ); ?>"
                                            placeholder="<?php _e( 'Населений пункт', NOVA_POSHTA_TTN_DOMAIN ); ?>" /> -->
                                    <input type="text" name="mrkvnp_invoice_recipient_warehouse_name"
                                        class="input recipient_region regular-input" value="<?php echo esc_attr( $shipping_warehouse_name ) ?>"
                                        placeholder="<?php echo $mrkvnp_wh; ?>" readonly />
                                    <input type="hidden" class="input-text regular-input jjs-hide-nova-poshta-option"
                                        name="invoice_no_order_np_shipping_method_warehouse"
                                        id="invoice_no_order_np_shipping_method_warehouse" style="" value="" placeholder="">
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php if ( 'npttn_address_shipping_method' == $shippingMethodId ) : ?>
                            <tr>
                            <td style="margin-bottom:0;padding-bottom:0;">
                                <div class="dflexcol">
                                    <!-- <input type="text" name="mrkvnp_invoice_recipient_city_name" id="recipient_city" class="recipient_city" value="<?php echo esc_html( $shipping_city_name ); ?>"  /> -->
                                    <input type="text" name="mrkvnp_invoice_recipient_warehouse_name"
                                        id="mrkvnp_invoice_recipient_warehouse_name"
                                        class="input recipient_region regular-input" value="<?php echo $streetName; ?>"
                                        placeholder="<?php _e( 'Вулиця / проспект / мікрорайон', NOVA_POSHTA_TTN_DOMAIN ); ?>" />
                                    <?php echo '<small style="margin-top:-10px;margin-left:10px;">Введіть перші три літери..</small>'; ?>
                                </div>
                                <div class="dflex">
                                    <input type="text" name="mrkvnp_invoice_sender_building_number"
                                        class="input recipient_building_number" id="recipient_building_number"
                                        value="<?php echo esc_html( $shippingBuldingNumber ); ?>"
                                        placeholder="<?php _e( 'Будинок', NOVA_POSHTA_TTN_DOMAIN ); ?>" required />
                                    <input type="text" name="mrkvnp_invoice_flat" class="input recipient_flat" id="recipient_flat"
                                        value="<?php echo esc_html( $shipping_flat ); ?>"
                                        placeholder="<?php _e( 'Квартира / офіс', NOVA_POSHTA_TTN_DOMAIN ); ?>" />
                                </div>
                                <input type="hidden" class="input-text regular-input jjs-hide-nova-poshta-option"
                                    name="invoice_no_order_np_shipping_method_warehouse"
                                    id="invoice_no_order_np_shipping_method_warehouse" style="" value="" placeholder="">
                                <!-- </div> -->
                            </td>
                        </tr>
                        <?php endif; ?>
                        <!-- <tr>
                            <th scope="row">
                                <label for="recipient_phone">Телефон</label>
                            </th>
                            <td>
                                <input type="text" name="mrkvnp_invoice_recipient_phone" class="input recipient_phone"
                                    id="recipient_phone" value="<?php echo esc_html( $shipping_phone ); ?>" required />
                                <p>Вводьте телефон у такому форматі 380901234567</p>
                            </td>
                        </tr> -->
                    <?php endif; ?>
                <!-- </tbody>
            </table> -->
            <!-- <table class="form-table full-width-input"> -->
                <!-- <tbody> -->
                    <?php if ( ! empty( $invoiceModel->getRecipientNote( $order_obj ) ) ) : ?>
                        <tr>
                            <th scope="row">Примітка від користувача</th>
                            <td><?php echo $invoiceModel->getRecipientNote( $order_obj ); ?></td>
                        </tr>
                    <?php endif; ?>
                <?php if ( isset( $_GET['post'] ) ) : ?>
                <!-- <tr>
                    <th scope="row">Довідка адреси</th>
                    <td>
                        <?php echo $shipping_city_name . ', ';
                              echo $shipping_warehouse_name . ', '; 
                              if ( isset( $shipping_flat ) ) echo 'кв. ' . $shipping_flat . ', '; else echo '';
                              echo $shipping_phone;
                        ?>
                    </td>
                </tr> -->
                <?php endif; ?>
                </tbody>
            </table>
        <!-- </div> --><!-- .tablecontainer -->
