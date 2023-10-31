    <!-- <form class="form-invoice" action="admin.php?page=morkvanp_invoice<?php //if ( ! empty( $order_id ) ) echo "&post=$order_id" ?>" method="post" name="invoice"> -->
        <!-- <div class="tablecontainer"> -->
            <table class="form-table sender full-width-input">
                <tbody id="tbody-invoice-sender">
                    <?php $invoiceModel->showFormBlockTitle( '1. Відправник' ); ?>
                    <input type=hidden name=servicetype value="<?php echo $invoiceModel->getServiceType($order_id); ?>">
                    <?php
                        $descriptionarea = $invoiceModel->decodeInvoiceDescription(
                            '',
                            $list3, $list2, $list, $prod_quantity, $prod_quantity2, $order_data['id'] );
                        $senders = $sender->getSendersContactsRef();
                        if ( $senders['errors'] ) {
							$apinp_errors = implode('<br>', $senders['errors'] );
							echo '<script>alert('. '"API Нова Пошта: ' . 'Перевірте дані Відправника - ' . $apinp_errors . '."' . '); </script>';
						}
                    ?>
                    <tr>
                        <th scope="row">
                        </th>
                        <td style="margin-bottom:0;">
                            <?php $sender_ref = get_option( 'mrkvnp_invoice_sender_ref' ); ?>
                            <select class="custom-select" v-model="selected"  id="mrkvnp_invoice_sender_ref" name="mrkvnp_invoice_sender_ref" style="width:100%" >
                            <?php
                                for ( $s = 0; $s < sizeof( $senders['data'] ); $s++ ) {
                                    $selected = '';
                                    if($senders['data'][$s]['Ref'] == $sender_ref){
                                        $selected = 'selected';
                                    }
                                  echo '<option namero="' . $senders['data'][$s]['Description'] . '" phone="'.$senders['data'][$s]['Phones'] .
                                      '" value=' . $senders['data'][$s]['Ref'] .' ' . $selected . '>' . $senders['data'][$s]['Description'] . ' ' . $senders['data'][$s]['Phones'] . '</option>';
                                }
                            ?>
                            </select>
                            <input type="hidden" id="sender_name" name="mrkvnp_invoice_sender_names" class="input sender_name"
                                value="<?php echo $senders['data'][0]['Description']; ?>" />
                            <input type="hidden" id="sender_phone" name="mrkvnp_invoice_sender_phones" class="input sender_phones"
                                value="<?php echo $senders['data'][0]['Phones']; ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <!-- <label for="sender_namecity">Місто</label> -->
                        </th>
                        <td>
                            <?php // echo '<pre>'; print_r($sender); echo '</pre>'; ?>
                            <input id="sender_address_name" type="text" value="<?php echo $sender->sender_address_name ?>" readonly name="mrkvnp_invoice_sender_address_name" />
                            <input id="sender_namecity" type="hidden" value="<?php echo $shipping_city_name ?>" readonly name="mrkvnp_invoice_sender_city" />
                        </td>
                    </tr>
                    <!-- <tr>
                        <th scope="row">
                            <label for="sender_phone">Телефон</label>
                        </th>
                        <td>
                            <input type="text" id="sender_phone" name="mrkvnp_invoice_sender_phone" class="input sender_phone" value="<?php echo $senders['data'][0]['Phones'] ?>" required />
                            <p>Вводьте телефон у такому форматі 380901234567</p>
                        </td>
                    </tr> -->
                    <!-- <tr>
                        <th scope="row">
                            <label for="mrkvnp_invoice_description">Опис відправлення</label>
                        </th>
                        <td class="pb7">
                            <textarea id="mrkvnp_invoice_description" name="mrkvnp_invoice_description"
                                class="input" minlength="1" maxlength="100" required /><?php echo $descriptionarea ?></textarea>
                            <p id="error_dec"></p>
                        </td>
                    </tr> -->
                </tbody>
            </table>
        <!-- </div> -->  <!-- .tablecontainer -->
