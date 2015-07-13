<?php 
/**
 * @author 		: Saravana Kumar K
 * @copyright	: sarkware.com
 * @todo		: HTML generator module, which wil uses "wcpb_dao" module to get data and render HTML skeletons.
 *
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class wcpb_builder {
	function __construct() {
		add_filter( 'wcpb/build/products_search', array( $this, 'build_products_search_list' ) );		
		add_filter( 'wcpb/build/included_products', array( $this, 'build_wcpb_included_products' ), 10, 2 );
	}
	
	function build_products_search_list( $search ) {
		$bundles = array();
		$bmeta = get_post_meta( wcpb()->request['post'], "wcpb_bundle_products", true );
		if( $bmeta ) {
			$bmeta = json_decode( $bmeta, true );
			if( is_array( $bundles ) ) {
				foreach ( $bmeta as $key => $value ) {
					$bundles[] = $key;
				}				
			} 
		}				
		$products = apply_filters( "wcpb/search/products", $search );
		$html = '<div class="wcpb-auto-complete-results-box">';
		$html .= '<ul class="wcpb-auto-complete-ul">';		
		foreach ( $products as $product ) {
			if( !in_array( $product->ID, $bundles ) ) {
				$p = wc_setup_product_data( $product );
				if( $p->product_type == "simple" ) {
					$html .= '<li class="wcpb-product"><a href="#" product_id="'. $product->ID .'" product_type="simple" title="'. esc_attr( $product->post_title ) .'">#'. $product->ID .' - '. esc_html( $product->post_title ) .'</a></li>';
				} else if( $p->product_type == "variable" ) {
					$variations = array();						
					$args = array(
							'post_parent'  => $product->ID,
							'post_type'   => 'product_variation',
							'orderby'     => 'menu_order',
							'order'       => 'ASC',
							'fields'      => 'ids, title',
							'post_status' => 'publish',
							'numberposts' => -1
					);
					$childposts = get_posts( $args );						
					foreach ( $childposts as $child ) {
						if( !in_array( $child->ID, $bundles ) ) {
							$variation = wc_get_product( $child );
							if ( ! $variation->exists() ) {
								continue;
							}
							$index = 0;
							$title = "";
							$attributes = wcpb_utils::get_variable_attributes( $variation );
							foreach ( $attributes as $attr ) {
								if( $index == 0 ) {
									$title .= 'with '. $attr["option"];
								} else {
									$title .= ', '. $attr["option"];
								}
								$index++;
							}
							$html .= '<li class="wcpb-product"><a href="#" product_id="'. $variation->get_variation_id() .'" product_type="simple" title="'. esc_attr( trim( $title ) ) .'">#'. $variation->get_variation_id() .' - '. esc_html( $product->post_title ) .' '. esc_html( trim( $title ) ) .'</a></li>';
						}						
					}
				}	
			}				
		}
		$html .= '</ul>';
		$html .= '</div>';
		return $html;		
	}
	
	function build_wcpb_included_products( $product, $bundles = null ) {
		$html = '';
		$newly_added = ( is_array( $bundles ) ) ? $bundles : array();
		$bundles_product = apply_filters( 'wcpb/load/bundle', $product );		
		ob_start();
		if( count( $bundles_product ) > 0 ) {
			foreach ( $bundles_product as $key => $value ) { 
				if( ( $bundles == null ) || in_array( $key, $newly_added ) ) {			
					$p = wc_setup_product_data( $key );
					?>			
					<div class="wcpb-product-bundle-row wc-metabox" product_id="<?php echo $key; ?>">
						<h3 class="wcpb-product-bundle-row-header">
							<strong>#<?php echo esc_html( $key ); ?> <?php echo esc_html( $value['title'] );?> - <?php echo esc_html( $p->product_type ); ?></strong>
							<a href="#" title="<?php _e( 'Remove', 'wc-product-bundles' ); ?> <?php echo esc_attr( $value['title'] ); ?> <?php _e( 'from bundle', 'wc-product-bundles' ); ?>" product_id="<?php echo esc_attr( $key ); ?>" class="button wcpb-remove-bundle-btn"><?php _e( 'Remove', 'wc-product-bundles' ); ?></a>
						</h3>
						<div class="wcpb-wc-metabox-content">
							<?php woocommerce_wp_checkbox( array( 'id' => 'wcpb_bundle_product_'. $key .'_thumbnail', 'label' => __( 'Show Thumbnail', 'wc-product-bundles' ), 'value' => 'yes', 'cbvalue' => $value['thumbnail'], 'desc_tip' => 'true', 'description' => __( 'Check if you want to show the thumbnail of this product.', 'wc-product-bundles' ) ) ); ?>
							<?php woocommerce_wp_checkbox( array( 'id' => 'wcpb_bundle_product_'. $key .'_tax_included', 'label' => __( 'Include Tax', 'wc-product-bundles' ), 'value' => 'yes', 'cbvalue' => $value['tax_included'], 'desc_tip' => 'true', 'description' => __( 'Check if you want to include the tax with price.', 'wc-product-bundles' ) ) ); ?>							
							<?php //woocommerce_wp_text_input( array( 'id' => 'wcpb_bundle_product_'. $key .'_price', 'label' => __( 'Price', 'wc-product-bundles' ), 'value' => $value['price'], 'desc_tip' => 'true', 'description' => __( 'Give a special price. If you leave it blank, product\'s regular price will be used instead', 'woocommerce' ) ) ); ?>	
							<?php woocommerce_wp_text_input( array( 'id' => 'wcpb_bundle_product_'. $key .'_quantity', 'label' => __( 'Quantity', 'wc-product-bundles' ), 'value' => $value['quantity'], 'desc_tip' => 'true', 'description' => __( 'How many quantity should be added to the bundle. If you leave it blank, by default it will be 1', 'wc-product-bundles' ) ) ); ?>		
							<?php woocommerce_wp_text_input( array( 'id' => 'wcpb_bundle_product_'. $key .'_title', 'label' => __( 'Title', 'wc-product-bundles' ), 'value' => $value['title'], 'desc_tip' => 'true', 'description' => __( 'Give a special title, otherwise original title will be used instead.', 'wc-product-bundles' ) ) ); ?>
							<?php woocommerce_wp_textarea_input( array( 'id' => 'wcpb_bundle_product_'. $key .'_desc', 'label' => __( 'Short Description', 'wc-product-bundles' ), 'value' => $value['desc'], 'desc_tip' => 'true', 'description' => __( 'Give short description, otherwise original product\'s excerpt will be used instead.', 'wc-product-bundles' ) ) ); ?>						
						</div>
					</div>
					<?php	
				}	
			}	
		} else { ?>
			<div class="wcpb-empty-msg">
				<p><?php _e( 'Search for products, select as many as product you want and add those to bundle using "Add Products". Only "Simple" or "variable" product are allowed to add. You can also drag drop to re arrange the order of bundled products in product page.!', 'wc-product-bundles' ); ?></p>
			</div>
		<?php
		}
		return ob_get_clean();		
	}
}

new wcpb_builder();

?>