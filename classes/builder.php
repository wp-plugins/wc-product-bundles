<?php 

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
					$html .= '<li class="wcpb-product"><a href="#" product_id="'. $product->ID .'" product_type="simple" title="'.$product->post_title.'">#'. $product->ID .' - '. $product->post_title .'</a></li>';
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
							$attributes = $this->get_attributes( $variation );
							foreach ( $attributes as $attr ) {
								if( $index == 0 ) {
									$title .= 'with '. $attr["option"];
								} else {
									$title .= ', '. $attr["option"];
								}
								$index++;
							}
							$html .= '<li class="wcpb-product"><a href="#" product_id="'. $variation->get_variation_id() .'" product_type="simple" title="'. trim( $title ) .'">#'. $variation->get_variation_id() .' - '. $product->post_title .' '. trim( $title ) .'</a></li>';
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
							<strong>#<?php echo $key; ?> <?php echo $value['title'];?> - <?php echo $p->product_type; ?></strong>
							<a href="#" title="Remove <?php echo $value['title'];?> from bundle" product_id="<?php echo $key; ?>" class="button wcpb-remove-bundle-btn">Remove</a>
						</h3>
						<div class="wcpb-wc-metabox-content">
							<?php woocommerce_wp_checkbox( array( 'id' => 'wcpb_bundle_product_'. $key .'_thumbnail', 'label' => __( 'Show Thumbnail', 'woocommerce' ), 'value' => 'yes', 'cbvalue' => $value['thumbnail'], 'desc_tip' => 'true', 'description' => __( 'Check if you want to show the thumbnail of this product.', 'woocommerce' ) ) ); ?>
							<?php woocommerce_wp_checkbox( array( 'id' => 'wcpb_bundle_product_'. $key .'_tax_included', 'label' => __( 'Tax Included', 'woocommerce' ), 'value' => 'yes', 'cbvalue' => $value['tax_included'], 'desc_tip' => 'true', 'description' => __( 'Check if you want to include the tax with price.', 'woocommerce' ) ) ); ?>							
							<?php //woocommerce_wp_text_input( array( 'id' => 'wcpb_bundle_product_'. $key .'_price', 'label' => __( 'Price', 'woocommerce' ), 'value' => $value['price'], 'desc_tip' => 'true', 'description' => __( 'Give a special price. If you leave it blank, product\'s regular price will be used instead', 'woocommerce' ) ) ); ?>	
							<?php woocommerce_wp_text_input( array( 'id' => 'wcpb_bundle_product_'. $key .'_quantity', 'label' => __( 'Quantity', 'woocommerce' ), 'value' => $value['quantity'], 'desc_tip' => 'true', 'description' => __( 'How many quantity should be added to the bundle. If you leave it blank, by default it will be 1', 'woocommerce' ) ) ); ?>		
							<?php woocommerce_wp_text_input( array( 'id' => 'wcpb_bundle_product_'. $key .'_title', 'label' => __( 'Title', 'woocommerce' ), 'value' => $value['title'], 'desc_tip' => 'true', 'description' => __( 'Give a special title, otherwise original title will be used instead.', 'woocommerce' ) ) ); ?>
							<?php woocommerce_wp_textarea_input( array( 'id' => 'wcpb_bundle_product_'. $key .'_desc', 'label' => __( 'Short Description', 'woocommerce' ), 'value' => $value['desc'], 'desc_tip' => 'true', 'description' => __( 'Give short description, otherwise original product\'s excerpt will be used instead.', 'woocommerce' ) ) ); ?>						
						</div>
					</div>
					<?php	
				}	
			}	
		} else { ?>
			<div class="wcpb-empty-msg">
				<p>Search for products, select as many as product you want and add those to bundle using "Add Products". Only "Simple" or "variable" product are allowed to add. You can also drag drop to re arrange the order of bundled products in product page.!</p>
			</div>
		<?php
		}
		return ob_get_clean();		
	}
	
	function get_attributes( $vari ) {
		$attributes = array();
		foreach ( $vari->get_variation_attributes() as $attribute_name => $attribute ) {
			// taxonomy-based attributes are prefixed with `pa_`, otherwise simply `attribute_`
			$attributes[] = array(
					'name'   => esc_attr( sanitize_title( ucwords( str_replace( 'attribute_', '', str_replace( 'pa_', '', $attribute_name ) ) ) ) ),
					'option' => esc_attr( sanitize_title( $attribute ) ),
			);
		}
		return $attributes;
	}
}

new wcpb_builder();

?>