<?php 
/**
 * @author 		: Saravana Kumar K
 * @copyright	: sarkware.com
 * @todo		: Wrapper module for all wccpf related Ajax request.
 * 				  All Ajax request target for wccpf will be converted to "wcpb_request" object and
 * 				  made available to the context through "wcpb()->request".
 * 
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class wcpb_request {
	
	function __construct() {
		add_filter( 'wcpb/request', array( $this, 'prepare_request' ) );
	}
	
	function prepare_request() {
		if( isset( $_REQUEST["WCPB_AJAX_PARAM"] ) ) {	
			$payload = json_decode( str_replace('\"','"', $_REQUEST["WCPB_AJAX_PARAM"] ), true );			
			return array (
				"type" => $payload["request"],
				"context" => $payload["context"],
				"post" => $payload["post"],
				"payload" => $payload["payload"]
			);
		}
	}
	
}

new wcpb_request();

?>