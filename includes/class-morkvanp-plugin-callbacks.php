<?php
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
     public function morkvanpTypeExample()
     {
         $value = esc_attr(get_option('type_example'));
         $values= array( 'Parcel',  'Cargo',  'Documents', 'TiresWheels', 'Pallet' );
         $volues= array( 'Посилка', 'Вантаж','Документи', 'Шини-диски',  'Палета' );
         $vilues= array(' ',' ',' ', ' ', '  ');
         for ($i=0; $i<sizeof($values); $i++) {
             if ($values[$i] == $value) {
                 $vilues[$i] = 'selected';
             }
         }
         echo '<select '.$value.' id="type_example" name="type_example">';

         for ($i=0; $i<sizeof($values); $i++) {
             echo '<option '.
              $vilues[$i] .'
		       value="'.$values[$i].'">
		       '.$volues[$i].'</option>';
         }
         echo '</select>';
     }

     public function morkvanpParcelTerminal() {
        echo '<select name="parcel_terminals" id="parcel_terminals">
                <option selected value="middle_parcel_terminal">Середня комірка (23х40х58см, max вага 30кг)</option>
            </select>';
     }

     public function morkvanpTextExample()
     {
        $value = $this->setOldOptionInNewOne( 'mrkvnp_sender_api_key', 'text_example' );
        echo '<input type="text"  id="npttnapikey" class="regular-text" name="mrkvnp_sender_api_key" value="' . $value . '" placeholder="API ключ">';
        echo '<small><span>Якщо у вас немає API ключа, то можете отримати його за посиланням <a href="http://my.novaposhta.ua/settings/index#apikeys">my.novaposhta.ua/settings/index#apikeys</a></span></small>';
     }

     public function morkvanpSelectRegion()
     {
        $region = $this->setOldOptionInNewOne( 'mrkvnp_invoice_sender_region_name', 'woocommerce_nova_poshta_shipping_method_area_name' );
        $shipping_settings = get_option( 'woocommerce_nova_poshta_shipping_method_settings' ); //1.6.x support
        $region = ( null !== $shipping_settings["area_name"] ) ? $shipping_settings["area_name"] : ''; //1.6.x support
        if ( get_option( 'mrkvnp_invoice_sender_region_name' ) ) {
            $region = get_option( 'mrkvnp_invoice_sender_region_name' );
        }
        echo '<input type="text" class="input-text regular-input  ui-autocomplete-input" name="mrkvnp_invoice_sender_region_name"
            id="mrkvnp_invoice_sender_region_name" value="' . $region . '" placeholder=" " readonlyd>';
        echo '<small><span>Підказка. Введіть перші 2-3 літери і дочекайтеся підвантаження даних з бази.<small><span>';
        $regionid = get_option('woocommerce_nova_poshta_shipping_method_area');
        echo '<input class="input-text regular-input jjs-hide-nova-poshta-option" type="hidden"
            name="woocommerce_nova_poshta_shipping_method_area" id="woocommerce_nova_poshta_shipping_method_area" style=""
            value="'.$regionid.'" placeholder="">';
     }

     public function morkvanpSelectCity()
     {
         $value1 = $this->setOldOptionInNewOne( 'mrkvnp_invoice_sender_city_name', 'woocommerce_nova_poshta_shipping_method_city_name' );
         // Getting settings of WooShipping plugin
         $shipping_settings = get_option('woocommerce_nova_poshta_shipping_method_settings');
         $value1 = ( null !== $shipping_settings["city_name"] ) ? $shipping_settings["city_name"] : '';
         if ( get_option( 'mrkvnp_invoice_sender_city_name' ) ) {
             $value1 = get_option( 'mrkvnp_invoice_sender_city_name' );
         }
         echo '<input type="text" class="input-text regular-input  ui-autocomplete-input"
             name="mrkvnp_invoice_sender_city_name" id="mrkvnp_invoice_sender_city_name"
             value="' . $value1 . '" placeholder=" " readonlyd>';
         echo '<small><span>Підказка. Введіть перші 2-3 літери і дочекайтеся підвантаження даних з бази.</small></span>';
         $city = get_option('woocommerce_nova_poshta_shipping_method_city') ?? '';
         echo '<input class="input-text regular-input" type="hidden" name="woocommerce_nova_poshta_shipping_method_city"
             id="woocommerce_nova_poshta_shipping_method_city" style="" value="'.$city.'" placeholder="">';
     }

     public function morkvanpPhone()
     {
         $phone = $this->setOldOptionInNewOne( 'mrkvnp_sender_phone', 'phone' );
         echo '<input type="text" class="regular-text" name="mrkvnp_sender_phone" value="' . $phone . '" placeholder="380901234567">';
         echo '<small><span>Підказка. Вводьте телефон у такому форматі: 380901234567</span></small>';
     }

     public function morkvanpNames()
     {
         $names = $this->setOldOptionInNewOne( 'mrkvnp_invoice_sender_names', 'names' );
         echo '<input type="text" class="regular-text" name="mrkvnp_invoice_sender_names" value="' . $names . '" placeholder="Петронко Петро Петрович">';
     }

     public function morkvanpCheckoutExample()
     {
         $value = esc_attr( get_option('morkvanp_checkout_count' ) );
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
         echo '<select '.$value.' id="morkvanp_checkout_count" name="morkvanp_checkout_count">';
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

     public function morkvanpWarehouseAddress()
     {
        $shipping_settings = get_option( 'woocommerce_nova_poshta_shipping_method_settings' );
        $warehouse = ( null !== $shipping_settings["warehouse_name"] ) ? $shipping_settings["warehouse_name"] : '';
        $warehouse = $this->setOldOptionInNewOne(
            'mrkvnp_invoice_sender_warehouse_name',
            'woocommerce_nova_poshta_shipping_method_warehouse_name'
        );

        $address_type = get_option( 'woocommerce_nova_poshta_sender_address_type' ) ? 'unchecked' : 'checked';
        echo '<input type=radio name=woocommerce_nova_poshta_sender_address_type value=0 '.$address_type.'>
            <input type="text" class="uai input-text regular-input  ui-autocomplete-input"
            id="mrkvnp_invoice_sender_warehouse_name" name="mrkvnp_invoice_sender_warehouse_name"
            value="' .htmlspecialchars($warehouse) . '" placeholder="" readonlyd>';
        echo '<small><span style="margin-left: 30px;">Підказка. Введіть перші 2-3 літери і дочекайтеся підвантаження даних з бази.</small></span>';


        if ( get_option( 'woocommerce_nova_poshta_shipping_method_warehouse' ) ) {
         $warehouseid = get_option( 'woocommerce_nova_poshta_shipping_method_warehouse' );
         echo '<input class="input-text regular-input jjs-hide-nova-poshta-option" type="hidden"
             name="woocommerce_nova_poshta_shipping_method_warehouse" id="woocommerce_nova_poshta_shipping_method_warehouse"
             style="" value="'.$warehouseid.'" placeholder="">';
        } else {
         echo '<input class="input-text regular-input jjs-hide-nova-poshta-option" type="hidden"
             name="woocommerce_nova_poshta_shipping_method_warehouse" id="woocommerce_nova_poshta_shipping_method_warehouse"
             style="" value="" placeholder="">';
        }
     }

     public function morkvanpWarehouseAddress2()
     {
         // $warehouse = esc_attr( get_option( 'warehouse' ) );
         //$shipping_settings = get_option('woocommerce_nova_poshta_shipping_method_settings');
         // $shipping_settings["warehouse_name"];
         //$warehouse = $shipping_settings["warehouse_name"];


         $warehouse = get_option('woocommerce_nova_poshta_shipping_method_address_name');
         $warehouseid = get_option('woocommerce_nova_poshta_shipping_method_address');

         $sender_building  = get_option('woocommerce_nova_poshta_sender_building');
         $sender_flat  = get_option('woocommerce_nova_poshta_sender_flat');

         $address_type = get_option('woocommerce_nova_poshta_sender_address_type') ? 'checked' : 'unchecked';

         echo '<input type=radio name=woocommerce_nova_poshta_sender_address_type value=1 '.$address_type.'><p class=afterradio>Якщо обрати, поле \'Відділення\' не братиметься до уваги.<br><strong>Після створення накладної до кінця робочого дня вам зателефонують кур\'єри Нової Пошти для уточнення деталей щодо забирання відправлення.</strong><br><small><span>У випадаючому списку будуть адреси, з яких Нова Пошта приймає, та адреси на які доставляє відправлення.</small></span></p>
    <table class="addressformnpttn" ><tbody><tr>
    <td class="child">
        Вулиця/проспект/мікрорайон
    </td>
    <td>
    <input type="text" placeholder="" class=" input-text regular-input  ui-autocomplete-input" id="woocommerce_nova_poshta_shipping_method_address_name" name="woocommerce_nova_poshta_shipping_method_address_name" value="' . $warehouse . '" placeholder="" readonlyd>
    </td>
    </tr>
    <tr>
    <td><label>Будинок</label>
    </td>
    <td><input type="text" name="woocommerce_nova_poshta_sender_building" value="'.$sender_building.'">
    </td>
    </tr>
    <tr>
    <td>
    <label>Квартира/офіс</label>
    </td>
    <td>
    <input type="text"  name="woocommerce_nova_poshta_sender_flat" value="'.$sender_flat.'">
    </td>
    </tr>
    </tbody>
    </table>';
         echo '<input class="input-text regular-input jjs-hide-nova-poshta-option" type="hidden" name="woocommerce_nova_poshta_shipping_method_address" id="woocommerce_nova_poshta_shipping_method_address" style="" value="'.$warehouseid.'" placeholder="">';
     }

     public function morkvanpload()
     {
         $activate = get_option('invoice_load');
         $checked = $activate;
         $current = 1;
         $echo = false;
         echo '<input type="checkbox" class="regular-text" name="invoice_load" value="1" ' . checked($checked, $current, $echo) . ' /><br>
      <small>Примітка. Якщо у вас one-step checkout, кастомна сторінка чекауту або оформлення замовлення у спливаючому вікні доступне на всіх сторінках сайту, скрипти і стилі доведеться підключати окремо.</small>';
     }

     public function morkvanpInvoiceDescription()
     {
         $invoice_description = $this->setOldOptionInNewOne( 'mrkvnp_invoice_description', 'invoice_description' );

         echo '<textarea  id=td45 name="mrkvnp_invoice_description" rows="5" cols="54">' . $invoice_description . '</textarea>
		<span id=sp1 class=shortspan>+ Вартість</span>
		<select class=shortspan id=shortselect>
			<option value="0" disabled selected style="display:none"> + Перелік</option>
			<option value="list" > + Перелік товарів (з кількістю)</option>
			<option value="list_qa"> + Перелік товарів ( з артикулами та кількістю)</option>
      <option value="list_a"> + Перелік артикулів з кількістю</option>
		</select>
		<select class=shortspan id=shortselect2>
			<option value="0" disabled selected style="display:none"> + Кількість</option>
			<option value="qa"> + Кількість позицій</option>
			<option value="q"> + кількість товарів</option>
		</select>
		<p>значення шорткодів, при натисненні кнопок додаються в кінець текстового поля</p>
		';
     }

     public function morkvanpInvoiceWeight()
     {
         $activate = get_option('invoice_weight');
         $checked = $activate;
         $current = 1;
         $echo = false;
         echo '<input type="checkbox" class="regular-text" name="invoice_weight" value="1" ' . checked($checked, $current, $echo) . ' />';
     }

     public function morkvanpInvoiceupdatebases()
     {
         $activate = get_option('update_bases');

         $checked = $activate;
         $current = 1;
         $echo = false;
         // echo '<input '. $activate .' type="checkbox" class="regular-text" name="update_bases" value="1" ' . checked($checked, $current, $echo) . ' /><br><small>Примітка. Якщо обрати цей пункт, оновлення баз відбуватиметься автоматично кожні 7 днів. </small>';
         echo '<input '. $activate .' type="checkbox" class="regular-text" name="update_bases" value="1" ' . checked($checked, $current, $echo) . ' /><br><small>Примітка. <span style="color: #dc3232;"> Якщо обрати цей пункт, оновлення таблиць БД плагіну відбуватиметься автоматично щодня в зв\'зку з воєнним станом в Україні для забезпечення актуальними даними.</span></small>';
     }

     public function spinnercolorcb()
     {
         $spinnercolor = get_option('spinnercolor');
         $current = 1;
         $echo = false;
         echo '<input value="'. $spinnercolor .'" type="text" class="regular-text" name="spinnercolor" id="spinnercolor" data-default-color="#808080"/>';
         echo '<small><br>Тут можна визначити колір спінера, який буде з\'являтися на сторінці Chekout.</small>';
     }

     public function morkvanpcalc()
     {
         $activate = get_option('show_calc');

         $checked = $activate;
         $current = 1;
         $echo = false;
         echo '<input '. $activate .' type="checkbox" class="regular-text" name="show_calc" value="1" ' . checked($checked, $current, $echo) . ' /> Вартість доставки буде показана біля способу доставки<br><small> Примітка. Сума доставки не включається у замовлення за замовчуванням.</small></p>';
     }

     public function morkvanpcalcplus()
     {
         $activate = get_option('plus_calc');

         $checked = $activate;
         $current = 1;
         $echo = false;
         echo '<input '. $activate .' type="checkbox" class="regular-text" name="plus_calc" value="1" ' . checked($checked, $current, $echo) . ' /> Вартість доставки буде додана до загальної суми замовлення<br><small>Примітка. Можуть виникнути колізії з наложеним платежем.</small></p>';
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

         ///	$crontime = intval($invoice_dpay);

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
         $content = get_option('morkvanp_email_template');
         $editor_id = 'morkvanp_email_editor_id';
         wp_editor($content, $editor_id, array( 'textarea_name' => 'morkvanp_email_template', 'tinymce' => 0, 'media_buttons' => 0 ));

         echo '<span id=standarttext title="щоб встановити шаблонний текст, натисніть">Шаблон email</span>';
     }

     public function morkvanpEmailSubject()
     {
         $subject = get_option('morkvanp_email_subject');
         echo '<input type="text" name="morkvanp_email_subject" class="regular-text" value="' . $subject . '" />';
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
            'warehouse' => ( null !== get_option('woocommerce_nova_poshta_shipping_method_warehouse') ) ? get_option('woocommerce_nova_poshta_shipping_method_warehouse') : ''
        );

        update_option( 'woocommerce_nova_poshta_shipping_method_settings', $settings_array );
     }

     public function setOldOptionInNewOne($newOptionName, $oldOptionName)
     {
         $value = sanitize_text_field( get_option( $newOptionName ) );
         $old_value = sanitize_text_field( get_option( $oldOptionName ) );
         if ( ! $value ) {
             $value = $old_value;
             update_option( $newOptionName, $value );
         }
         return $value;
     }
 }
