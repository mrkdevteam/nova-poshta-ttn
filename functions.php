<?php

if ( ! function_exists( 'is_woocommerce_activated_np' ) ) {
        function is_woocommerce_activated_np() {
          if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ){
            return true;
          }
          return false;
        }
}

if(!is_woocommerce_activated_np()){

  /* extend wpcf7 */
  add_filter( 'wpcf7_form_elements', 'do_shortcode' );


  /* add shorctcode function + shortcode */
  function vue_np_func( $atts ){
  	 return "<div id=\"vue-nova-poshta\"><formgroup /></div>
     <script src=\"https://unpkg.com/axios/dist/axios.min.js\"></script>
     <script type=\"module\" src=\"".NOVA_POSHTA_TTN_SHIPPING_PLUGIN_URL."assets/js/no_wc_frontend.js?v=".time()."\"></script>
     <link rel=stylesheet href=".NOVA_POSHTA_TTN_SHIPPING_PLUGIN_URL."assets/css/no_wc_frontend.css?v=".time()."\" />";
  }
  add_shortcode('vue_nova_poshta', 'vue_np_func');

  /* WP REST API callbacks + actions */
  function wp_rest_get_np_cities(){
    global $wpdb;
    $sql = "SELECT ref,description  FROM `".$wpdb->prefix."nova_poshta_city` ORDER BY `description` ASC";
    $resultarray = $wpdb->get_results($sql);
    return json_encode($resultarray);
  }

  add_action('rest_api_init', function (){
      register_rest_route('mrk/v1', '/cities',[
          'methods' => 'GET',
          'callback' => 'wp_rest_get_np_cities',
      ]);
  });

  function wp_rest_get_city_warehouses($data)
  {

      global $wpdb;
      $id = $data['id'];
      $sql = "SELECT `description`  FROM `".$wpdb->prefix."nova_poshta_warehouse` WHERE `parent_ref`='".$id."'";
      $sqlarray = $wpdb->get_results($sql);

      return json_encode($sqlarray);
  }

  add_action('rest_api_init', function ()
  {
      register_rest_route('mrk/v1', '/get_city_warehouses', [
          'methods' => 'POST',
          'callback' => 'wp_rest_get_city_warehouses',
      ]);
  });
}

?>
