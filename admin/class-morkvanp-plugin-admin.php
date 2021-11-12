<?php

class MNP_Plugin_Admin {

	private $plugin_name;

	private $version;

	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		add_action( 'admin_enqueue_scripts', array($this, 'enqueue_styles') );
	}

	public function enqueue_styles() {
 		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/mystyle.css', array(), $this->version, 'all' );
	}


	public function enqueue_scripts() {

		wp_enqueue_script( 'np-script-admin', PLUGIN_URL .'public/js/script.js', array(), MNP_PLUGIN_VERSION , true );

	}

}
