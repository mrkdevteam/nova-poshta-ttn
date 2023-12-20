<div class="wrap">
 <?php
 global $wpdb;

 if(class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled())
{
  $order = wc_get_order( $_GET['invoice'] );

  $ttn = $order->get_meta('novaposhta_ttn');

  $order->save();
}
else
{
  $ttn = get_post_meta( $_GET['invoice'], 'novaposhta_ttn', true ); 
}
 


  $date = strtotime('-14 days');
$dd =  date('d.m.Y', $date);
// print_r($dd);
print_r(date('d.m.Y'));

  $methodProperties = array("Type"=>"csv","DateTime" => date('d.m.Y') ,"DocumentRefs" => array($ttn));
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


    $obj2 = (array) $response_json;

    // print_r($obj2);

   }


   $obj2data =  $obj2['data'];
   //print_r($obj2data);
   for($i=0; $i< sizeof($obj2data);$i++){
     $element = $obj2data[$i];
     if($element['IntDocNumber'] == $ttn){
       $obj2 =$element;
     }


    }
 ?>
 <div class="gridttn h3cont" class="" >
   <div>
   <h3 class="wp-heading-inline">ЕКСПРЕС-НАКЛАДНА <br> № <?php echo $ttn; ?></h3>
   </div>
 <div class=""><br>Статус: <br>
   <?php echo $obj['Status'];?>
<br>Час: <br>
   <?php echo $obj2['DateTime'];?>
 </div>
 </div>
 <form method=post action="admin.php?page=morkvanp_invoices&invoice=<?php echo $_GET['invoice']; ?>" class=gridttn class="" >
   <input type="hidden" name="ref" value="<?php echo $obj2['Ref'];?>">
   <div class="">
     <div class="">
       <h4>Інформація про відправника</h4>
       <label for="">Місто відправника</label>
       <input type="text" name="city" readonly value="<?php echo $obj2["CitySenderDescription"];?>">
       <input type="hidden" name="cityid" value="<?php echo $obj2["CitySender"];?>">
       <br>
       <!--label for="">Адреса (звідки забирається відправлення)</label>
       <input type="text" name="" value="<?php echo $obj["CitySender"];?>">
       <br-->
       <!--label for="">Телефон</label><br-->
       <input type="hidden" name="sender_phone" value="<?php echo $obj2['SendersPhone']; ?>">
       <label for="">Контактна особа</label>
       <?php

       $senders = getsenders();

       echo "<select class=custom-select v-model=selected  id=mrkvnp_invoice_sender_ref name=mrkvnp_invoice_sender_ref>";
   for($s=0; $s<sizeof($senders); $s++){
     echo '<option namero="'.$senders[$s]->Description.'" phone="'.$senders[$s]->Phones.'" value='.$senders[$s]->Ref.'>'.$senders[$s]->Description.' '.$senders[$s]->Phones.'</option>';

   }

   echo "</select>
        <script></script>";
   echo "<input style=display:none type=\"text\" id=\"sender_name\" name=\"mrkvnp_invoice_sender_names\" class=\"input sender_name\" value=\""
   .$senders[0]->Description."\" />";
       ?>

       <!--input type="text" name="" value="<?php echo $obj2['SenderContactPerson'];?>"-->
       <br>
       <hr>
     </div>
     <h4>Інформація про одержувача</h4>
     <label for="">Місто одержувача</label>
     <input type="text" id=woocommerce_nova_poshta_shipping_method_city_all_name class="input-text regular-input  ui-autocomplete-input" name="" value="<?php echo $obj2["CityRecipientDescription"];?>">
     <input type="hidden" name="woocommerce_nova_poshta_shipping_method_city" id="woocommerce_nova_poshta_shipping_method_city" value="<?php echo $obj2['CityRecipient']; ?>">
     <br>
     <label for="">Відділення</label>
     <input class="uai input-text regular-input  ui-autocomplete-input" id="mrkvnp_invoice_sender_warehouse_name" type="text" name="mrkvnp_invoice_sender_warehouse_name" value="<?php echo $obj2["RecipientAddressDescription"];?>">
     <input class="input-text regular-input jjs-hide-nova-poshta-option" type="hidden" name="mrkvnp_invoice_sender_warehouse_ref" id="mrkvnp_invoice_sender_warehouse_ref" style="" value="<?php echo $obj2["RecipientAddress"];?>" placeholder="">
     <br>
 <label for="">АБО Адреса </label>

 <table>
   <tbody>
     <tr>
       <td>Вулиця</td>
       <td>
         <input type="text" placeholder="" class="input-text regular-input  ui-autocomplete-input" id="mrkvnp_invoice_sender_address_name" name="mrkvnp_invoice_sender_address_name" value="" readonlyd="" autocomplete="off">
         <input class="" type="hidden" name="woocommerce_nova_poshta_shipping_method_address" id="woocommerce_nova_poshta_shipping_method_address" style="" value="8ceab873-4146-11dd-9198-001d60451983" placeholder="">
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
     <input type="text" name="recipient_phone" value="<?php echo $obj2['RecipientsPhone']; ?>"><br>
     <label for="">П.І.Б. контактної особи (повністю)</label>
     <input readonly type="text" name="recipient_name" value="<?php echo $obj2['RecipientContactPerson'];?>">
     <input type="hidden" name="ContactRecipient" value="<?php echo $obj2['ContactRecipient']; ?>">
     <input type="hidden" name="Recipient" value="<?php echo $obj2['Recipient']; ?>">
     <br>
     <hr>
   </div>
   <div class="">
     <h4>Інформація про відправлення</h4>
     <label for="">Фактична вага</label><br>
     <input type="text" name="Weight" value="<?php echo $obj2["Weight"];?>">
     <br>
     <label for="">Об'єм'</label><br>
     <input type="text" name="Volume" value="<?php echo $obj2["Volume"];?>">
     <br>
     <label for="">Оголошена вартість</label><br>
     <input type="text" name="cost" value="<?php echo $obj2["Cost"];?>">
     <label for="">Зворотня доставка (наложений платіж)</label><br>
     <input type="text" name="backward" value="<?php echo $obj2["BackwardDeliverySum"];?>">
     <label for="">Повний опис відправлення</label><br>
     <textarea name=invoicedescription><?php echo $obj2["Description"];?></textarea>
     <br>
   </div>
   <?php  include 'card.php'; ?>
   <div>
   </div>
   <div>
   </div>
   <div>
     <button type="submit" name="updatettn">Оновити</button>
   </div>
 </form>
</div>
<?php

    echo "<pre>";
    //print_r($obj2);
    echo "</pre>"; ?>
