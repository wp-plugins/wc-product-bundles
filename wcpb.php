<?php 
/*
 Plugin Name: WC Product Bundles
 Plugin URI: http://sarkware.com/wc-product-bundle-bundle-products-together-and-sell-them-with-a-discounted-rate/
 Description: Bundle two or more woocommerce products together and sell them at a discounted rate. 
 Version: 1.0.3
 Author: Saravana Kumar K
 Author URI: http://www.iamsark.com/
 License: GPL
 Copyright: sarkware
*/

if ( ! defined( 'ABSPATH' ) ) { exit; }

if( !class_exists('wc_product_bundles') ):

class wc_product_bundles {
	
	var $settings,
	$request,
	$response;
	
	public function __construct() {
	
		$this->settings = array(
				'path'				=> plugin_dir_path( __FILE__ ),
				'dir'				=> plugin_dir_url( __FILE__ ),
				'version'			=> '1.0.3'
		);
	
		add_action( 'init', array( $this, 'init' ), 1 );
		add_action( 'plugins_loaded', array( $this, 'setup_front_end_env' ), 1 );
		add_filter( 'wcpb/get_info', array( $this, 'wccpf_get_info' ), 1, 1 );	
	
	}
	
	function init() {
		if( is_admin() ) {
			wp_register_script( 'wcpb-script', $this->settings['dir'] . "assets/js/wcpb.js", 'jquery', $this->settings['version'] );
			wp_register_style( 'wcpb-style', $this->settings['dir'] . 'assets/css/wcpb-admin.css' );
			wp_enqueue_style( 'wcpb-style' );
			wp_enqueue_script( 'wcpb-script' );
		} else {
			wp_register_style( 'wcpb-style', $this->settings['dir'] . 'assets/css/wcpb-front-end.css' );
			wp_enqueue_style( 'wcpb-style' );
		}
		
		$this->wcpb_includes();
	}
	
	function wcpb_includes() {		
		include_once('classes/dao.php');
		include_once('classes/request.php');
		include_once('classes/response.php');		
		include_once('classes/listener.php');		
		include_once('classes/admin-form.php');
		include_once('classes/builder.php');	
		include_once('classes/utils.php');
	}
	
	function setup_front_end_env() {
		include_once('classes/wc_bundled_product.php');
		include_once('classes/product-form.php');
	}
	
	function wccpf_get_info( $i ) {
		$return = false;
	
		if( isset($this->settings[ $i ]) ) {
			$return = $this->settings[ $i ];
		}
	
		if( $i == 'all' ) {
			$return = $this->settings;
		}
	
		return $return;
	}
	
}

function wcpb() {

	global $wcpb;

	if( !isset( $wcpb ) ) {
		$wcpb = new wc_product_bundles();
	}

	return $wcpb;

}

wcpb();

endif;

?>