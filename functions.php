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
}

?>
