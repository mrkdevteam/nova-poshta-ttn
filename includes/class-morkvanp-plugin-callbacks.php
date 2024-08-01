<?php

use plugins\NovaPoshta\classes\Database;
use plugins\NovaPoshta\classes\DatabasePM;
use plugins\NovaPoshta\classes\DatabaseSync;
use plugins\NovaPoshta\classes\invoice\Sender;

/**
 * Registering callbacks for settings admin page
 */
 class MNP_Plugin_Callbacks
 {
     public function adminDashboard()
     {
         return require_once("$this->plugin_path/templates/admin.php");
     }

     public function adminInvoice()
     {
         return require_once("$this->plugin_path/templates/invoice.php");
     }

     public function adminSettings()
     {
         return require_once("$this->plugin_path/templates/taxonomy.php");
     }

     public function morkvanpOptionsGroup($input)
     {
         return $input;
     }

     public function morkvanpAdminSection()
     {
         echo 'Введіть свій API ключ для початку щоб плагін міг працювати.';
     }

     // *** Base Settings
     public function setSenderAPIkey()
     {
         $value = esc_attr(get_option('mrkvnp_sender_api_key'));
         echo '<input type="text"  id="npttnapikey" class="regular-text" name="mrkvnp_sender_api_key" value="' . $value . '" placeholder="Введіть ключ...">';
         echo '<small><div class="mrkvnpsmall">Не знаєш, де взяти ключ? Подивись <a href="http://my.novaposhta.ua/settings/index#apikeys">це відео</a>
            </div></small><hr style="margin-right:40px">';
     }

     public function setAutoDetectLang()
     {
         $activate = \get_option( 'mrkvnp_auto_detect_lang' );
         $checked = $activate;
         $current = 1;
         $echo = false;
        echo '<label class="form-switch">
          <input disabled type="checkbox" class="regular-text" name="mrkvnp_auto_detect_lang" value="1" ' .
            checked( $checked, $current, $echo ) . ' />
          <i></i>
        </label>';
     }

     public function mrkvnpInvoicePatranomicRequired()
     {
        $activate = \get_option( 'mrkvnp_invoice_patranomic_required' );
         $checked = $activate;
         $current = 1;
         $echo = false;
        echo '<label class="form-switch">
          <input type="checkbox" class="regular-text" name="mrkvnp_invoice_patranomic_required" value="1" ' .
            checked( $checked, $current, $echo ) . ' />
          <i></i>
        </label>';
     }

     public function mrkvnpInvoicePatranomicDisabled()
     {
        $activate = \get_option( 'mrkvnp_invoice_patranomic_disable' );
         $checked = $activate;
         $current = 1;
         $echo = false;
        echo '<label class="form-switch">
          <input type="checkbox" class="regular-text" name="mrkvnp_invoice_patranomic_disable" value="1" ' .
            checked( $checked, $current, $echo ) . ' />
          <i></i>
        </label>';
     }

     public function setIndividualCheckoutText()
     {
         $activate = \get_option( 'mrkvnp_individual_checkout_text' );
         $checked = $activate;
         $current = 1;
         $echo = false;
        echo '<label class="form-switch">
          <input disabled type="checkbox" class="regular-text" name="mrkvnp_individual_checkout_text" value="1" ' .
            checked( $checked, $current, $echo ) . ' />
          <i></i>
        </label>';
     }

     public function setCheckoutSpinnerColor()
     {
         $spinnercolor = \get_option( 'mrkvnp_checkout_spinner_color' );
         $current = 1;
         $echo = false;
         echo '<input value="' . $spinnercolor .
            '" type="text" class="regular-text" name="mrkvnp_checkout_spinner_color"
            id="mrkvnp_checkout_spinner_color" data-default-color="#808080"/>';
     }

     public function mrkvnpIsAutoUpdateDB()
     {
        echo '<label class="form-switch">
            <input type="checkbox" class="regular-text" value="1"  / disabled>
            <i></i>
        </label>';
     }

     public function mrkvnpIsByHandUpdateDB()
     {
        \mrkvnp_show_status_dbtables();
        echo '<br><span style="display:inline-block;padding-bottom:15px;">
                    <a style="cursor:pointer;" type="button" id="mrkvnpupdatedb" class="updatedbtables">Оновити базу відділень зараз</a>
                    <span id="mrkvupdatedbloader" class=""></span>
                    <span id="mrkvnpwhupdated"></span>
                </span>';
     }

     // *** Sender
     public function mrkvnpGetSendersByAPIKey()
     {
        $is_api_key = get_option('mrkvnp_sender_api_key') ?: '';
        if ( ! $is_api_key ) {
            echo '<p style="color:#dc3232;"><span class="dashicons dashicons-warning"></span> API ключ не введений або не дійсний.<br>
                Перевірте <em>Базові налаштування</em> плагіну <b>' . MNP_PLUGIN_NAME . '</b>.</p>';
            echo '<hr style="margin-right:40px;margin-top:20px;">';
            return;
        }
        $sender = Sender::getInstance() ?: '';
        $senders = $sender->getSendersContactsRef();
        if ( is_array( $senders ) && ! empty( $senders['errors']) ) {
            echo '<p style="color:#dc3232;"> ' . $senders['errors'][0] . '</p>';
        } ?>
        <select class="custom-select" v-model="selected"  id="mrkvnp_invoice_sender_ref"
            name="mrkvnp_invoice_sender_ref" style="width:350px" >
            <?php
                $makeYourChoice = __( 'Зробіть свій вибір', 'nova-poshta-ttn');
                echo '<option value=0 >' . $makeYourChoice . '</option>';
                for ( $s = 0; $s < sizeof( $senders['data'] ); $s++ ) {
                    echo '<option '. 'value="'. $senders['data'][$s]['Ref'] . '" ' .
                        selected( get_option('mrkvnp_invoice_sender_ref'), $senders['data'][$s]['Ref'] ) . '>' .
                        $senders['data'][$s]['Description'] . '</option>';
                }
            ?>
        </select>
        <hr style="margin-right:40px">
        <?php
     }

    public function morkvanpGetSenderNames()
    {
        $names = \get_option( 'mrkvnp_invoice_sender_names' );
        $sender_ref = \get_option( 'mrkvnp_invoice_sender_ref' );
        $sender = Sender::getInstance() ?: '';
        $senders = $sender->getSendersContactsRef();
        for ( $s = 0; $s < sizeof( $senders['data'] ); $s++ ) {
            if ( $senders['data'][$s]['Ref'] == $sender_ref ) {
                echo '<input type="hidden" name="mrkvnp_invoice_sender_names" value="' . $senders['data'][$s]['Description'] . '"></input>';
            }
        }
        // echo '<hr style="margin-right:40px">';
    }

     public function mrkvnpSenderRegion()
     {
         $region = esc_attr( get_option( 'region' ) );

         $shipping_settings = get_option( 'woocommerce_nova_poshta_shipping_method_settings' ); //1.6.x support
         if(isset($shipping_settings["area_name"])){
            $region = ( null !== $shipping_settings["area_name"] ) ? $shipping_settings["area_name"] : ''; //1.6.x support
         }
         else{
            $region = ''; //1.6.x support
         }

        if ( get_option( 'mrkvnp_invoice_sender_region_name' ) ) {
            $region = get_option('mrkvnp_invoice_sender_region_name');
        }

         echo '<input style="width:350px;" type="text" class="input-text regular-input  ui-autocomplete-input"
            name="mrkvnp_invoice_sender_region_name" id="mrkvnp_invoice_sender_region_name"
            value="' . $region . '" placeholder="Область" readonlyd >';

         $regionid = get_option('woocommerce_nova_poshta_shipping_method_area');

         echo '<input class="input-text regular-input jjs-hide-nova-poshta-option" type="hidden"
            name="woocommerce_nova_poshta_shipping_method_area" id="woocommerce_nova_poshta_shipping_method_area"
            style="" value="'.$regionid.'" placeholder="" >';
     }

     public function morkvanpSenderCity()
     {
         $value1 = esc_attr( get_option( 'city' ) );

         /**
          * Get settings of WooShipping plugin
          */
         $shipping_settings = get_option( 'woocommerce_nova_poshta_shipping_method_settings' );
         if(isset($shipping_settings["city_name"])){
            $value1 = ( null !== $shipping_settings["city_name"] ) ? $shipping_settings["city_name"] : '';
         }
         else{
            $value1 = '';
         }

         if ( get_option( 'mrkvnp_invoice_sender_city_name' ) ) {
             $value1 = get_option( 'mrkvnp_invoice_sender_city_name' );
         }

         echo '<input style="width:350px;" type="text" class="input-text regular-input  ui-autocomplete-input"
            name="mrkvnp_invoice_sender_city_name" id="mrkvnp_invoice_sender_city_name"
            value="' . $value1 . '" placeholder="Населений пункт" readonlyd>';

         $city = get_option('woocommerce_nova_poshta_shipping_method_city') ?? '';

         echo '<input class="input-text regular-input" type="hidden" name="woocommerce_nova_poshta_shipping_method_city"
            id="woocommerce_nova_poshta_shipping_method_city" style="" value="' . $city . '" placeholder="">';
     }

     public function morkvanpWarehouseAddress()
     {
         $shipping_settings = get_option('woocommerce_nova_poshta_shipping_method_settings');
         // $shipping_settings["warehouse_name"];
         if(isset($shipping_settings["warehouse_name"])){
            $warehouse = ( null !== $shipping_settings["warehouse_name"] ) ? $shipping_settings["warehouse_name"] : '';
         }
         else{
            $warehouse = '';
         }


         if (get_option('mrkvnp_invoice_sender_warehouse_name')) {
             $warehouse = get_option('mrkvnp_invoice_sender_warehouse_name');
         }

         $address_type = get_option('mrkvnp_invoice_sender_warehouse_type') ? 'unchecked' : 'checked';
         echo '<input style="margin-top:6px;position: relative;top:-40px;left:-35px;" id="mrkvnpfromwh"
                type=radio name=mrkvnp_invoice_sender_warehouse_type value=0 ' . $address_type . '>

            <input style="margin-left:-21px;width:350px;" type="text" class="uai input-text regular-input  ui-autocomplete-input"
            id="mrkvnp_invoice_sender_warehouse_name" name="mrkvnp_invoice_sender_warehouse_name"
            value="' .htmlspecialchars($warehouse) . '" placeholder="Номер / Назва відділення" readonlyd >';
         // echo '<small><span style="margin-left: 30px;">Підказка. Введіть перші 2-3 літери і дочекайтеся підвантаження даних з бази.</small></span>';
            echo '<p id="messagefromwh" style="width:400px;"><small>
                    <span>Введіть перші 2-3 літери і дочекайтеся підвантаження даних з бази.</span>
                </small></p>';

         if (get_option('mrkvnp_invoice_sender_warehouse_ref')) {
             $warehouseid = get_option('mrkvnp_invoice_sender_warehouse_ref');
             echo '<input class="input-text regular-input jjs-hide-nova-poshta-option" type="hidden"
                name="mrkvnp_invoice_sender_warehouse_ref" id="mrkvnp_invoice_sender_warehouse_ref" style=""
                value="'.$warehouseid.'" placeholder="">';
         } else {
             echo '<input class="input-text regular-input jjs-hide-nova-poshta-option" type="hidden"
                name="mrkvnp_invoice_sender_warehouse_ref" id="mrkvnp_invoice_sender_warehouse_ref" style=""
                value="" placeholder="">';
         }
         // echo '<hr style="margin-right:40px;margin-top:20px;">';
     }

     public function morkvanpWarehouseAddress2()
     {
         $warehouse = get_option('mrkvnp_invoice_sender_address_name');
         $warehouseid = get_option('woocommerce_nova_poshta_shipping_method_address');

         $sender_building  = get_option('mrkvnp_invoice_sender_building_number');
         $sender_flat  = get_option('mrkvnp_invoice_sender_flat_number');

         $address_type = get_option('mrkvnp_invoice_sender_warehouse_type') ? 'checked' : 'unchecked';

         echo '<input style="display:inline-block;position:relative;top:-43px;left:-35px;" id="mrkvnpfromaddr"
                type=radio name=mrkvnp_invoice_sender_warehouse_type value=1 ' . $address_type . '>';

         echo '<table style="display:inline;" class="addressformnpttn" ><tbody><tr>
            <td style="padding: 5px 10px;">
            <input style="margin-left:-72px;width:350px;" type="text" placeholder="Вулиця / Проспект / Мікрорайон" class=" input-text regular-input  ui-autocomplete-input"
                id="mrkvnp_invoice_sender_address_name" name="mrkvnp_invoice_sender_address_name"
                value="' . $warehouse . '" readonlyd>
            </td>
            </tr>
            <tr>
            <td><input style="margin-left:-72px;margin-right:50px;width:160px;" type="text" placeholder="Будинок"
                    name="mrkvnp_invoice_sender_building_number" id="mrkvnp_invoice_sender_building_number" value="' . $sender_building . '">
                <input style="margin-left:-23px;width:160px;" type="text"  placeholder="Квартира / Офіс"
                    name="mrkvnp_invoice_sender_flat_number" id="mrkvnp_invoice_sender_flat_number" value="' . $sender_flat . '">
            </td>
            </tr>
            </tbody>
            </table>';
         echo '<input class="input-text regular-input jjs-hide-nova-poshta-option" type="hidden" name="woocommerce_nova_poshta_shipping_method_address" id="woocommerce_nova_poshta_shipping_method_address" style="" value="'.$warehouseid.'" placeholder="">';
         echo '<p id="messagefromaddr"><small>
            <span style="display:inline-block;padding-bottom:20px;">Введіть перші 2-3 літери і дочекайтеся підвантаження даних з бази.</span>
            </small></p>';
     }

     // *** Default settings
     public function mrkvnpInvoiceCargoType() {
        $option = \sanitize_text_field( \get_option( 'mrkvnp_invoice_cargo_type' ) );
        $values = array( 'Parcel', 'Pallet',  'Documents', 'TiresWheels' );
        $labels = array( 'Посилки', 'Палети','Документи', 'Шини-диски' );
        $addattrs = array();
        for ( $i = 0; $i < sizeof( $values ); $i++ ) {
            $addattrs[$i] = '';
            if ( 'Documents' == $values[$i] || 'TiresWheels' == $values[$i] ) {
                $addattrs[$i] .= ' disabled';
            } else $addattrs[$i] = ' ';
            if ( $option == $values[$i] ) {
                $addattrs[$i] .= ' checked="checked"';
            }
        }
        for( $i = 0; $i < sizeof( $values ); $i++ ) {
            echo '<p><input type="radio" id="' . $values[$i] . '" name="mrkvnp_invoice_cargo_type" value="' .
                $values[$i] . '" ' . $addattrs[$i] . ' required />';
            echo '<label for="' . $values[$i] . '" ' . $addattrs[$i] . ' > ' . $labels[$i] . ' </label></p>';
        }
     }

    public function mrkvnpInvoicePayer()
    {
        $option =  \get_option( 'mrkvnp_invoice_payer' );
        echo '<p style="width:max-content;"><input type="radio" id="recipient" name="mrkvnp_invoice_payer" value="Recipient" required';
        if ( $option == 'Recipient' ) echo ' checked="checked" />';
        echo '<label for="recipient" > Одержувач </label></p>';
        echo '<p style="width:max-content;"><input type="radio" id="sender" name="mrkvnp_invoice_payer" value="Sender" required';
        if ( $option == 'Sender' ) echo ' checked="checked" />';
        echo '<label for="sender" > Відправник </label><p>';
        return $option;
     }

     public function mrkvnpInvoicePaymentType()
     {
        $option =  \get_option( 'mrkvnp_invoice_payment_type' ) ;
        $values = array( 'Cash', 'NonCash' );
        $labels = array( 'Готівка', 'Безготівка' );
        $addattrs = array( '', '' );
        for ( $i = 0; $i < sizeof( $values ); $i++ ) {
            if ( $values[$i] == $option ) {
                $addattrs[$i] = ' checked="checked"';
            }
        }
        for ( $i = 0; $i < sizeof( $values ); $i++ ) {
            $radioandlabel = '<p><input type="radio" id="' . $values[$i] . '" name="mrkvnp_invoice_payment_type" value="' .
                $values[$i] . '"' . $addattrs[$i] . ' /><label for="' . $values[$i] . '" required >' . ' ' . $labels[$i] . ' ' . '</label>';
            if ( 1 == $i ) {
                echo $radioandlabel;
                echo '<span class="tooltip"><span style="color: #9b9b9b;" class="dashicons dashicons-editor-help"></span>
                    <span class="tooltip-text">Безготівковий розрахунок для відправника доступний лише за умови підписання договору.</span>
                </span></p>';
            } else {
                echo $radioandlabel;
                echo '</p>';
            }
        }
        return $option;
     }

     public function mrkvnpInvoiceRedeliveryPayer()
     {
        $option =  'Recipient';
        echo '<p><input type="radio" id="recipientredlvr"
            value="Recipient" ';
        if ( $option == 'Recipient' ) echo ' checked="checked" disabled />';
        echo '<label for="recipientredlvr" > Одержувач </label></p>';
        echo '<p><input type="radio" id="senderredlvr" disabled
            value="Sender" ';
        if ( $option == 'Sender' ) echo ' checked="checked" />';
        echo '<label for="senderredlvr" > Відправник </label><p>';
        return $option;
     }

     public function mrkvnpInvoiceDescription()
     {

         echo '<textarea disabled readonly placeholder="Наприклад, товари для дітей..."  onkeyup="mrkvnpCountChar(this)"
            id=td45  rows="5" cols="60"></textarea>
            <div class="mrkvnpsymbolmaxqty">Максимальна кількість символів: <span id="mrkvnpcharNum"></span></div>
            <div class="textarea-caption">
                <span style="font-size:12px;">Вставте шорт-код:</span>
                <span class=shortspan>Номер замовлення</span>
                <select disabled style="width:125px;" class=shortspan id=shortselect>
                    <option value="0" disabled selected style="display:none">Перелік товарів</option>
                    <option value="list" > Перелік товарів (з кількістю)</option>
                    <option value="list_qa"> Перелік товарів ( з артикулами та кількістю)</option>
                    <option value="list_a"> Перелік артикулів з кількістю</option>
                </select>
                <select disabled class=shortspan id=shortselect2>
                    <option value="0" disabled selected style="display:none">Кількість товарів</option>
                    <option value="qa"> Кількість позицій</option>
                    <option value="q"> Кількість товарів</option>
                </select>
            </div>';
     }

     public function mrkvnpInvoiceDefaultWeight()
     {
        $invoice_weight = get_option( 'mrkvnp_invoice_weight' );
        echo '<div>
                <input style="width:60px;" type="text" id="mrkvnp_invoice_weight" class="regular-text" required
                    name="mrkvnp_invoice_weight" value="' . $invoice_weight . '">
             </div>';
     }

    public function mrkvnpInvoiceDefaultLength()
     {
        $invoice_length = \get_option( 'mrkvnp_invoice_length' );
        echo '<div>
                <span style="padding-right:15px;width:60px;"><input style="width:60px;" type="text" id="mrkvnp_invoice_length"
                    name="mrkvnp_invoice_length" value="'. $invoice_length . '" required /> (д)</span>
            </div>';
     }

    public function mrkvnpInvoiceDefaultWidth()
     {
        $invoice_width = \get_option( 'mrkvnp_invoice_width' );
        echo '<div>
                <span style="padding-right:15px;width:60px;"><input
                    style="width:60px;position:relative;top:-40px;left:72px;visibility:visible;" type="text" id="mrkvnp_invoice_width"
                    name="mrkvnp_invoice_width" value="' . $invoice_width . '"  required /> (ш)</span>
            </div>';
     }

    public function mrkvnpInvoiceDefaultHeight()
     {
        $invoice_height = \get_option( 'mrkvnp_invoice_height' );
        echo '<div>
                <span style="width:60px;position:relative;top:-80px;left:144px;display:flex;align-items:center;visibility:visible;">
                    <input style="width:60px;margin-right:4px;" type="text" id="mrkvnp_invoice_height"
                    name="mrkvnp_invoice_height" value="' . $invoice_height . '"  required /> (в)</span>
            </div>';
     }

    public function mrkvnpInvoiceDefaultVolume()
     {
        $invoice_volume = \get_option( 'mrkvnp_invoice_volume' );
        echo '<div>
                <span style="width:70px;">
                    <input style="width:70px;" type="text" id="mrkvnp_invoice_volume" readonly
                    name="mrkvnp_invoice_volume" value="' . $invoice_volume . '" /></span>
            </div>';
     }

    public function mrkvnpIsCalcShipCostOderParms()
     {
        echo '<label class="form-switch">
          <input type="checkbox" class="regular-text" disabled value="1" />
          <i></i>
        </label>';
     }

     // *** Automation
     public function mrkvnpInvoiceIsPaymentControlOn() // Контроль оплати
     {
        
         echo '<label class="form-switch">
          <input type="checkbox" class="regular-text" value="1" disabled />
          <i></i>
        </label>';
        echo '<span class="tooltip"><span style="color: #9b9b9b;" class="dashicons dashicons-editor-help"></span>
                <span class="tooltip-text">Послуга передбачає контроль оплати готівкою за отримане Одержувачем відправлення. Кошти перераховуються Відправнику на його поточний рахунок (наступного робочого дня) на підставі укладеного договору на переказ коштів з ТОВ "НоваПей".</span>
            </span>';
     }

     public function mrkvnpInvoiceIsAutoCODOn()
     {
         $activated = \get_option( 'mrkvnp_is_auto_cash_on_delivery_on' );
         $checked = $activated;
         $current = 1;
         $echo = false;
         echo '<label class="form-switch">
          <input type="checkbox" class="regular-text" name="mrkvnp_is_auto_cash_on_delivery_on" value="1" ' .
            checked( $checked, $current, $echo ) . ' />
          <i></i>
        </label>';
     }

     public function mrkvnpInvoiceIsAutoCreating() // Автоматично створювати накладні
     {
        echo '<label class="form-switch">
            <input type="checkbox" class="regular-text" id="mrkvnp_is_auto_invoice_creating_chk"
             value="1" disabled />
            <i></i>
        </label>';
     }

     public function mrkvnpInvoiceIsAutoStatusOrderChanging()
     {
         echo '<label class="form-switch">
            <input type="checkbox" class="regular-text" id="mrkvnp_is_order_auto_status_changing_chk"
             value="1" disabled />
          <i></i>
        </label>';
     }

     public function getTrackingNPStatuses()
     {
        return array(
            9 => 'Відправлення отримано',
            11 => 'Грошовий переказ видано одержувачу',
            103 => 'Відмова одержувача від отримання',
            111 => 'Невдала спроба доставки через відсутність Одержувача на адресі або зв\'язку з ним'
        );
     }




     public function morkvanpParcelTerminal() {
        echo '<select name="parcel_terminals" id="parcel_terminals">
                <option selected value="middle_parcel_terminal">Середня комірка (23х40х58см, max вага 30кг)</option>
            </select>';
     }

     public function morkvanpPhone()
     {
         $phone = esc_attr( \get_option('mrkvnp_sender_phone' ) );
         echo '<input type="text" class="regular-text" name="mrkvnp_sender_phone" value="' . $phone . '" placeholder="380901234567">';
         echo '<small><span>Підказка. Вводьте телефон у такому форматі: 380901234567</span></small>';
     }

     public function mrkvnpCheckoutFieldsCount()
     {
         $value = esc_attr( get_option( 'morkvanp_checkout_count', '3fields' ) );
         $values = array( '3fields', '2fields', '2fieldsdb' );
         $volues = array( 'Область + Місто + Відділення', 'Місто + Відділення (select3)', 'Місто + Відділення (search in DB)' );
         $vilues = array(' ', ' ', ' ');
         for ( $i = 0; $i < sizeof( $values ); $i++) {
             if (  $value == $values[$i] ) {
                 $vilues[$i] = 'selected';
             } elseif ( '3fields' != $values[$i] ) {
                $vilues[$i] = 'disabled';
             }
         }
         for ( $i = 0; $i < sizeof( $values ); $i++) {
             if (  '3fields' != $values[$i] ) {
                 $vilues[$i] .= ' style="color: gray"';
             }
         }

         echo '<select ' . $value . ' id="morkvanp_checkout_count" name="morkvanp_checkout_count">';

         for ( $i = 0; $i < sizeof( $values ); $i++ ) {
             echo '<option ' . $vilues[$i] . ' value="' . $values[$i].'">' . $volues[$i] . '</option>';
         }

         echo '</select>';
     }

     public function morkvanpFlat()
     {
         $flat = esc_attr(get_option('flat'));
         echo '<input type="text" class="regular-text" name="flat" value="' . $flat . '" placeholder="номер">';
     }

     public function emptyfunccalbask()
     {
         echo '';
     }

     public function morkvanpInvoiceWeight()
     {
         $activate = get_option('invoice_weight');
         $checked = $activate;
         $current = 1;
         $echo = false;
         echo '<input type="checkbox" class="regular-text" name="invoice_weight" value="1" ' . checked($checked, $current, $echo) . ' />';
     }

     public function morkvanpcalc()
     {
         $activate = \get_option('mrkvnp_is_show_delivery_price');

         $checked = $activate;
         $current = 1;
         $echo = false;
         echo '<input '. $activate .' type="checkbox" class="regular-text" name="mrkvnp_is_show_delivery_price" value="1" ' . checked( $checked, $current, $echo ) . ' /> Вартість доставки буде показана біля способу доставки<br><small> Примітка. Сума доставки не включається у замовлення за замовчуванням.</small></p>';
     }

     public function morkvanpcalcplus()
     {
         $activate = \get_option( 'mrkvnp_is_add_delivery_price' );
         $checked = $activate;
         $current = 1;
         $echo = false;
         \update_option( 'mrkvnp_is_add_delivery_price', false, false ); // Off 'Додати розрахунок вартості доставки до замовлення' wp-option
         echo '<input '. $activate .' type="checkbox" class="regular-text" id="mrkvnp_is_add_delivery_price" name="mrkvnp_is_add_delivery_price"
            value="1" ' . checked( $checked, $current, $echo ) . ' /> Вартість доставки буде додана до загальної суми замовлення<br>
            <small>Примітка. Налаштування більше не підтримується.</small></p>';
     }

     public function morkvanpInvoiceshort()
     {
         $activate = get_option('invoice_short');

         $checked = $activate;
         $current = 1;
         $echo = false;
         echo '<input '. $activate .' type="checkbox" class="regular-text" name="invoice_short" value="1" ' . checked($checked, $current, $echo) . ' /><p>якщо увімкнено, функціонал плагіна розширюється можливістю використовувати шорткоди</p>';
     }

     public function morkvanpInvoicecron()
     {
         $invoice_dpay = get_option('invoice_cron');

         ///    $crontime = intval($invoice_dpay);

         $textt = '';

         if ($invoice_dpay) {
             $textt = 'Крон вимкнуто. Якщо не бажаєте оновлювати статуси автоматично, позначте пункт';
         } else {
             $textt = 'Крон завдання відбуватиметься щогодинно.';
         }

         $echo = false;
         echo '<input value="'. $invoice_dpay .'" type="checkbox" class="regular-text" name="invoice_cron" value="55"  /><p>';
     }

     public function morkvanpEmailTemplate()
     {
         $content = get_option('mrkvnp_email_template');
         $editor_id = 'morkvanp_email_editor_id';
         wp_editor($content, $editor_id, array( 'textarea_name' => 'mrkvnp_email_template', 'tinymce' => 0, 'media_buttons' => 0 ));

         echo '<span id=standarttext title="щоб встановити шаблонний текст, натисніть">Шаблон email</span>';
     }

     public function morkvanpEmailSubject()
     {
         $subject = \get_option( 'mrkvnp_email_subject' );
         echo '<input type="text" name="mrkvnp_email_subject" class="regular-text" value="' . $subject . '" />';
     }

     public function morkvanpShippingMethodSettings()
     {
        require_once NOVA_POSHTA_TTN_SHIPPING_PLUGIN_DIR . 'classes/WC_NovaPoshta_Shipping_Method.php';
        require_once NOVA_POSHTA_TTN_SHIPPING_PLUGIN_DIR . 'classes/WC_NovaPoshta_Shipping_Method_Poshtomat.php';
        require_once NOVA_POSHTA_TTN_SHIPPING_PLUGIN_DIR . 'classes/WC_NovaPoshtaAddress_Shipping_Method.php';

        $settings_array = array(
            "api_key" => ( null !== get_option( 'mrkvnp_sender_api_key' ) ) ? get_option( 'mrkvnp_sender_api_key' ) : '',
            'area_name' => ( null !== get_option('mrkvnp_invoice_sender_region_name') ) ? get_option('mrkvnp_invoice_sender_region_name') : '',
            'area' => ( null !== get_option('woocommerce_nova_poshta_shipping_method_area') ) ? get_option('woocommerce_nova_poshta_shipping_method_area') : '',
            'city_name' => ( null !== get_option('mrkvnp_invoice_sender_city_name') ) ? get_option('mrkvnp_invoice_sender_city_name') : '',
            'city' => ( null !== get_option('woocommerce_nova_poshta_shipping_method_city') ) ? get_option('woocommerce_nova_poshta_shipping_method_city') : '',
            'warehouse_name' => ( null !== get_option('mrkvnp_invoice_sender_warehouse_name') ) ? get_option('mrkvnp_invoice_sender_warehouse_name') : '',
            'warehouse' => ( null !== get_option('mrkvnp_invoice_sender_warehouse_ref') ) ? get_option('mrkvnp_invoice_sender_warehouse_ref') : ''
        );

        update_option( 'woocommerce_nova_poshta_shipping_method_settings', $settings_array );
     }
 }
