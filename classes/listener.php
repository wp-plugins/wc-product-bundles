<?php 
/**
 * @author		: Saravana Kumar K
 * @copyright	: sarkware.com  
 * @todo		: Wcpb core Ajax handler. common hub for all ajax related actions of wcpb
 *  
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class wcpb_ajax {
	
	function __construct() {		
		add_action("wp_ajax_wcpb_ajax", array( $this, "listen" ) );		
	}
	
	function listen() {
		/* Parse the incoming request */
		wcpb()->request = apply_filters( 'wcpb/request', array() );		
		/* Handle the request */
		$this->handleRequest();
		/* Respond the request */
		echo wcpb()->response;
		/* end the request - response cycle */
		die();
	}
	
	function handleRequest() {				
		$message = "";
		$products = array();
		if( wcpb()->request["context"] == "search" ) {
			if( wcpb()->request["type"] == "GET" ) {
				$result = apply_filters( 'wcpb/build/products_search', array() );
				wcpb()->response = apply_filters( 'wcpb/response', true, "Success", $result );
			}
		} else if( wcpb()->request["context"] == "add-to-bundle" ) {			
			$res = apply_filters( 'wcpb/add_to_bundle/products', array() );
			if( $res ) {
				$message = "Successfully Added to Bundled";				
				$products = apply_filters( 'wcpb/build/included_products', wcpb()->request['post'], wcpb()->request['payload'] );
			} else {
				$message = "Failed to Add to Bundled";
			}
			wcpb()->response = apply_filters( 'wcpb/response', $res, $message, $products );
		} else if( wcpb()->request["context"] == "remove-from-bundle" ) {
			$res = apply_filters( 'wcpb/remove_from_bundle/products', array() );
			if( $res ) {
				$message = "Successfully Removed from Bundle";				
			} else {
				$message = "Failed to Remove from Bundle";
			}
			wcpb()->response = apply_filters( 'wcpb/response', $res, $message, $products );
		}
	}
	
}

/* Init wccpf ajax object */
new wcpb_ajax();

?>