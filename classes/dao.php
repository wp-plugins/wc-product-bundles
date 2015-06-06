<?php 

if ( ! defined( 'ABSPATH' ) ) { exit; }

class wcpb_dao {
	
	function __construct() {
		add_filter( 'wcpb/search/products', array( $this, 'search_products' ) );
		add_filter( 'wcpb/add_to_bundle/products', array( $this, 'add_to_bundle' ) );
		add_filter( 'wcpb/remove_from_bundle/products', array( $this, 'remove_from_bundle' ) );
		add_filter( 'wcpb/load/bundle', array( $this, 'load_products_bundle' ), 10, 2 );
		add_action( 'save_post', array( $this, 'update_bundle_products' ), 1, 3 );
	}
	
	function search_products() {
		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'wcpb_product_search' => wcpb()->request['payload'],
			'fields'         => 'ids, title'
		);		
		add_filter( 'posts_where', array( $this, 'wcpb_product_search' ), 10, 2 );
		$wp_query = new WP_Query($args);
		remove_filter( 'posts_where', array( $this, 'wcpb_product_search' ), 10, 2 );		
		return $wp_query->posts;		
	}
	
	function add_to_bundle() {	
		$is_new = true;	
		$bundles =  json_decode( get_post_meta( wcpb()->request['post'], "wcpb_bundle_products", true ), true );		
		if( is_array( $bundles ) ) {
			$is_new = false;				
		} else {
			$bundles = array();
		}										
		foreach ( wcpb()->request['payload'] as $bundle ) {				
			$product = get_post( $bundle );	
			if( get_post_type( $bundle) === 'product_variation' ) {
				$index = 0;
				$variation = wc_get_product( $bundle );		
				$parent_post_id = wp_get_post_parent_id( $bundle );		
				$parent_product = wc_get_product( $parent_post_id );
				$title = $parent_product->get_title() ." - ";
				$attributes = $this->get_attributes( $variation );
				foreach ( $attributes as $attr ) {					
					if( $index == 0 ) {
						$title .= 'with '. $attr["option"];
					} else {
						$title .= ", ". $attr["option"];
					}
					$index++;					
				}
			} else {
				$title = $product->post_title;
			}	
			
			$bundles[ $bundle ] = array(
				"quantity" => 1,
				"price" => get_post_meta( $bundle, '_regular_price', true ),
				"thumbnail" => "yes",
				"tax_included" => "yes",
				"title" => trim( $title ),
				"desc" => $product->post_excerpt
			);
		}			
		if( $is_new ) {
			add_post_meta( wcpb()->request['post'], 'wcpb_bundle_products', wp_slash( json_encode( $bundles ) ) );
		} else {
			update_post_meta( wcpb()->request['post'], 'wcpb_bundle_products', wp_slash( json_encode( $bundles ) ) );
		}		
		return true;
	}
	
	function update_bundle_products( $post_id, $post, $update ) {		
		if( $post->post_type != "product" ) {
			return;
		}	
		$product = wc_setup_product_data( $post );

		$terms        = get_the_terms( $post_id, 'product_type' );
		$product_type = ! empty( $terms ) ? sanitize_title( current( $terms )->name ) : 'simple';
				
		if( $product_type == "wcpb" ) {
			/* Update sale price meta */
			delete_post_meta( $post_id, '_wcpb_product_sale_price' );
			add_post_meta( $post_id, '_wcpb_product_sale_price', $_REQUEST['_wcpb_product_sale_price'] );
			/* Update the bundles */
			$bundles =  json_decode( get_post_meta( $post_id, "wcpb_bundle_products", true ), true );			
			if( is_array( $bundles ) ) {
				update_post_meta( $post_id, 'wcpb_bundle_products', $_REQUEST['wcpb-bundles-array'] );
			}				
		}		
	}
	
	function remove_from_bundle() {
		$bundle = wcpb()->request['payload'];		
		$bundles = json_decode( get_post_meta( wcpb()->request['post'], "wcpb_bundle_products", true ), true );		
		if( is_array( $bundles ) ) {
			if( isset( $bundles[ $bundle ] ) ) {
				unset( $bundles[ $bundle ] );
				return update_post_meta( wcpb()->request['post'], 'wcpb_bundle_products', json_encode( $bundles ) );
			}				
		}		
		return false;
	}
	
	function load_products_bundle( $pid ) {		
		$products = array();
		$bundles =  json_decode( get_post_meta( $pid, "wcpb_bundle_products", true ), true );		
		if( is_array( $bundles ) ) {
			$products = $bundles;
		}		
		return $products;
	}
	
	function wcpb_product_search( $where, &$wp_query ) {
		global $wpdb;
		if ( $search_term = $wp_query->get( 'wcpb_product_search' ) ) {
			$where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . esc_sql( like_escape( $search_term ) ) . '%\'';
		}
		return $where;
	}
	
	function get_attributes( $vari ) {
		$attributes = array();
		foreach ( $vari->get_variation_attributes() as $attribute_name => $attribute ) {			
			$attributes[] = array(
				'name'   => esc_attr( sanitize_title( ucwords( str_replace( 'attribute_', '', str_replace( 'pa_', '', $attribute_name ) ) ) ) ),
				'option' => esc_attr( sanitize_title( $attribute ) )
			);
		}
		return $attributes;
	}
}

new wcpb_dao();

?>