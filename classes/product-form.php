<?php 
/**
 * @author 		: Saravana Kumar K
 * @copyright	: sarkware.com
 * @todo		: One of the core module, which renders the actual wcpb bundle on the product, cart and checkout pages.
 * 				  also it manages stock synchronization and cart validation
 * 
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class wcpb_product_form {
	
	var $product_type = "wcpb";
	
	function __construct() {		
		add_action( 'woocommerce_wcpb_add_to_cart', array( $this, 'wcpb_product_form' ), 10 );
		add_action( 'woocommerce_add_to_cart_handler_'.$this->product_type, array( $this, 'wcpb_add_to_cart' ) );
		add_filter( 'woocommerce_cart_item_name', array( $this, 'wcpb_render_bundle_on_cart' ), 1, 3 );
		add_filter( 'woocommerce_checkout_cart_item_quantity', array( $this, 'wcpb_render_bundle_on_order_review' ), 1, 3 );	
		add_action( 'woocommerce_add_order_item_meta', array( $this, 'wcpb_add_bundle_as_order_meta' ), 1, 3 );
		add_action( 'woocommerce_reduce_order_stock', array( $this, 'wcpb_sync_bundle_stocks' ) );
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'wcpb_re_sync_bundle_stocks' ) );
		add_filter( 'woocommerce_sale_flash', array( $this, 'wcpb_add_combo_pack_label' ), 10, 2 );		
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'wcpb_add_to_cart_bundle_validation' ), 10, 3 );
	}
	
	function wcpb_product_form() { 
		global $product;				
		if( wcpb_utils::get_wcpb_meta( $product->id, '_wcpb_show_bundle_on_product', 'yes' ) == "yes" ) {		
	?>
	
		<div class="wcpb-bundled-products-container">
			<?php $bundles = apply_filters( 'wcpb/load/bundle', $product->id ); ?>
			
			<?php if( has_action( 'wcpb/bundle/rendering' ) ) { 

				do_action( 'wcpb/bundle/rendering', $bundles );
				
			} else { ?>
			
			<?php do_action( 'wcpb/bundle/before/main/content/rendering' ); ?>		
			
			<?php foreach ( $bundles as $key => $value ) : ?>
				<?php $bundle = wc_get_product( $key ); 
					$product_url = "";
					if ( get_post_type( $key ) == 'product_variation' ) {
						$product_url = get_the_permalink( wp_get_post_parent_id( $key ) );
					} else {
						$product_url = get_the_permalink( $key );
					}
				?>
				
				<?php do_action( 'wcpb/bundle/before/product/content/rendering', $bundle ); ?>
				
				<table class="wcpb-bundled-product">
					<tr>
						<!-- bundled product's thumbnail section -->
						<?php if( $value["thumbnail"] == "yes" ) : ?>				
						<td class="wcpb-thumbnail-td">				
							<a href="<?php echo esc_url( $product_url ); ?>" title="<?php echo esc_attr( $value['title'] ); ?>" class="wcpb-featured"><?php echo $bundle->get_image( 'thumbnail' ); ?></a>					
						</td>
						<?php endif; ?>
						<!-- bundled product's summary section -->
						<td>
							<a href="<?php echo esc_url( $product_url ); ?>" class="wcpb-bundled-product-title"><h1><?php echo esc_html( $value['quantity'] ) ." x ". esc_html( $value['title'] ); ?></h1></a>
							
							<?php 
								$desc = "";								
								if( $value['desc'] != "" && $value['desc'] != null ) {
									$desc = $value['desc'];
								} else {
									if( $bundle->post->post_excerpt != "" && $bundle->post->post_excerpt != null ) {
										$desc = $bundle->post->post_excerpt;
									} else {
										$desc = wp_trim_words( $bundle->post->post_content, apply_filters( 'excerpt_length', 55 ), "..." );
									}
								}							
							?>
							
							<p class="wcpb-bundled-product-desc"><?php echo esc_html( $desc ); ?></p>
							<p class="wcpb-bundled-product-stock">
								<?php 
								if( $bundle->has_enough_stock( $value['quantity'] ) ) {
									echo '<span class="wcpb-in-stock-label">'. apply_filters( 'wcpb/bundle/instock/label', __( 'instock', 'wc-product-bundles' ) ) .'</span>';
								} else {
									echo '<span class="wcpb-out-of-stock-label">'.  apply_filters( 'wcpb/bundle/outofstock/label', __( 'out of stock', 'wc-product-bundles' ) )  .'</span>'; 
								}
								?>
							</p>
						</td>
					</tr>
				</table>
				
				<?php do_action( 'wcpb/bundle/after/product/content/rendering', $bundle ); ?>
				
			<?php endforeach; ?>
			
			<?php do_action( 'wcpb/bundle/after/main/content/rendering' ); ?>
			
			<?php 
			
			}
			
			?>
			
		</div>
		
		<?php 
		
		}
		
		?>
		
		<form class="cart" method="post" enctype='multipart/form-data'>
		 	<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>
	
		 	<?php
		 		if ( ! $product->is_sold_individually() )
		 			woocommerce_quantity_input( array(
		 				'min_value' => apply_filters( 'woocommerce_quantity_input_min', 1, $product ),
		 				'max_value' => apply_filters( 'woocommerce_quantity_input_max', $product->backorders_allowed() ? '' : $product->get_stock_quantity(), $product )
		 			) );
		 	?>
	
		 	<input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product->id ); ?>" />
	
		 	<button type="submit" class="single_add_to_cart_button button alt"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>
	
			<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
		</form>
	
	<?php 
	}
	
	function wcpb_add_to_cart() { 
		$was_added_to_cart   = false;
		$product_id         = apply_filters( 'woocommerce_add_to_cart_product_id', absint( $_REQUEST['add-to-cart'] ) );
		$quantity 			= empty( $_REQUEST['quantity'] ) ? 1 : wc_stock_amount( $_REQUEST['quantity'] );		
		$passed_validation 	= apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity );
		
		if ( $passed_validation ) {
			if ( WC()->cart->add_to_cart( $product_id, $quantity ) ) {
				$was_added_to_cart = true;
				$added_to_cart[] = $product_id;
			}
		}
		
		if ( $was_added_to_cart ) {
			wc_add_to_cart_message( $added_to_cart );
		}
		
		if ( ! $was_added_to_cart ) {			
			return;
		}
		
		// If we added the product to the cart we can now optionally do a redirect.
		if ( $was_added_to_cart && wc_notice_count( 'error' ) == 0 ) {
		
			$url = apply_filters( 'woocommerce_add_to_cart_redirect', $url );
		
			// If has custom URL redirect there
			if ( $url ) {
				wp_safe_redirect( $url );
				exit;
			}
		
			// Redirect to cart option
			elseif ( get_option('woocommerce_cart_redirect_after_add') == 'yes' ) {
				wp_safe_redirect( WC()->cart->get_cart_url() );
				exit;
			}		
		}
	}
	
	function wcpb_render_bundle_on_cart( $title = null, $cart_item = null, $cart_item_key = null ) {				
		if( is_cart() ) {
			return $this->wcpb_render_bundle_item( $title, $cart_item );
		}
		return $title;		
	}
	
	function wcpb_render_bundle_on_order_review( $quantity = null, $cart_item = null, $cart_item_key = null ) {		
		return $this->wcpb_render_bundle_item( $quantity, $cart_item );										
	}
	
	function wcpb_render_bundle_item( $html, $cart_item ) {
		if( isset( $cart_item['product_id'] ) ) {
			$terms        = get_the_terms( $cart_item['product_id'], 'product_type' );
			$product_type = ! empty( $terms ) ? sanitize_title( current( $terms )->name ) : 'simple';
			if( $product_type == "wcpb" ) {				
				if( wcpb_utils::get_wcpb_meta( $cart_item['product_id'], '_wcpb_show_bundle_on_cart', 'yes' ) == "yes" ) {				
					$bundles =  json_decode( get_post_meta( $cart_item['product_id'], "wcpb_bundle_products", true ), true );
					if( has_filter( 'wcpb/bundle/item/rendering' ) ) {
						$html .= apply_filters( 'wcpb/bundle/item/rendering', $bundles );
					} else {
						$html .= '<dl class="wcpb-cart-item-container">';
						$html .= '<dt>'. apply_filters( 'wcpb/bundle/item/title', __( 'Bundle Includes', 'wc-product-bundles' ) ) .'</dt>';
						$html .= '<dd>';
						foreach ( $bundles as $key => $bundle ) {
							if ( get_post_type( $key ) == 'product_variation' ) {							
								$html .= '<div>'. $bundle['quantity'] .' x <a href="'. get_permalink( wp_get_post_parent_id( $key ) ) .'">'. esc_html( $bundle['title'] ) .'</a></div>';
							} else {
								$html .= '<div>'. $bundle['quantity'] .' x <a href="'. get_permalink( $key ) .'">'. esc_html( $bundle['title'] ) .'</a></div>';
							}												
						}
						$html .= '</dd>';
						$html .= '</dl>';
					}
					return $html;				
				}
			}
		}
		return $html;		
	}
	
	function wcpb_add_bundle_as_order_meta( $item_id, $values, $cart_item_key ) {
		if( isset( $values['product_id'] ) ) {
			$terms        = get_the_terms( $values['product_id'], 'product_type' );
			$product_type = ! empty( $terms ) ? sanitize_title( current( $terms )->name ) : 'simple';
			if( $product_type == "wcpb" ) {				
				if( wcpb_utils::get_wcpb_meta( $values['product_id'], '_wcpb_hide_bundle_on_order', 'yes' ) == "yes" ) {
					$index = 0;
					$btitle = '';
					$bundles =  json_decode( get_post_meta( $values['product_id'], "wcpb_bundle_products", true ), true );
					foreach ( $bundles as $key => $bundle ) {
						if( $index == 0 ) {
							$btitle .= $bundle['quantity'] .'x'. esc_html( $bundle['title'] );
						} else {
							$btitle .= ', '. $bundle['quantity'] .'x'. esc_html( $bundle['title'] );
						}
						$index++;
					}
					wc_add_order_item_meta( $item_id, "Bundle Includes", $btitle );
				}
			}
		}
	}
	
	function wcpb_sync_bundle_stocks( $order_id ) {
		$order = new WC_Order( $order_id );
		if ( get_option('woocommerce_manage_stock') == 'yes' ) {
			foreach ( $order->get_items() as $item ) {				
				if ( $item['product_id'] > 0 ) {
					if ( ! empty( $item['variation_id'] ) && 'product_variation' === get_post_type( $item['variation_id'] ) ) {
						$pid = $item['variation_id'];
					} else {
						$pid = $item['product_id'];
					}	
					
					$terms        = get_the_terms( $pid, 'product_type' );
					$product_type = ! empty( $terms ) ? sanitize_title( current( $terms )->name ) : 'simple';
										
					if( $product_type == "wcpb" ) {
						$bundles =  json_decode( get_post_meta( $pid, "wcpb_bundle_products", true ), true );
						foreach ( $bundles as $key => $bundle ) {
							$_product = wc_get_product( $key );						
							if ( $_product && $_product->exists() && $_product->managing_stock() ) {
								$new_stock = $_product->reduce_stock( intval( $item['qty'] ) * intval( $bundle['quantity'] ) );
								$order->send_stock_notifications( $_product, $new_stock, $bundle['quantity'] );
								do_action( 'wcpb_reduce_order_bundle_stock', $_product, $new_stock, $bundle['quantity'] );
							}
						}
					}
				}
			}
		}
	}
	
	function wcpb_re_sync_bundle_stocks( $order_id ) {
		$order = new WC_Order( $order_id );
		if ( get_option('woocommerce_manage_stock') == 'yes' ) {
			foreach ( $order->get_items() as $item ) {
				if ( $item['product_id'] > 0 ) {
					if ( ! empty( $item['variation_id'] ) && 'product_variation' === get_post_type( $item['variation_id'] ) ) {
						$pid = $item['variation_id'];
					} else {
						$pid = $item['product_id'];
					}
						
					$terms        = get_the_terms( $pid, 'product_type' );
					$product_type = ! empty( $terms ) ? sanitize_title( current( $terms )->name ) : 'simple';
		
					if( $product_type == "wcpb" ) {
						$bundles =  json_decode( get_post_meta( $pid, "wcpb_bundle_products", true ), true );
						foreach ( $bundles as $key => $bundle ) {
							$_product = wc_get_product( $key );
							if ( $_product && $_product->exists() && $_product->managing_stock() ) {
								$new_stock = $_product->increase_stock( intval( $item['qty'] ) * intval( $bundle['quantity'] ) );								
								do_action( 'wcpb_increase_order_bundle_stock', $_product, $new_stock, $bundle['quantity'] );
							}
						}
					}
				}
			}
		}
	}	
	
	function wcpb_add_combo_pack_label( $label, $post ) {
		$terms        = get_the_terms( $post->ID, 'product_type' );
		$product_type = ! empty( $terms ) ? sanitize_title( current( $terms )->name ) : 'simple';
		
		if( $product_type == "wcpb" ) {
			return apply_filters( 'wcpb_combo_pack_label', '<span class="onsale">'. __( 'Combo', 'wc-product-bundles' ) .'</span>' );
		} else {
			return $label;
		}
	}
	
	function wcpb_add_to_cart_bundle_validation( $unknown, $pid = null, $quantity ) {
		$terms        = get_the_terms( $pid, 'product_type' );
		$product_type = ! empty( $terms ) ? sanitize_title( current( $terms )->name ) : 'simple';
		
		if( $product_type == "wcpb" ) {
			if ( get_option('woocommerce_manage_stock') == 'yes' ) {
				$bundles = apply_filters( 'wcpb/load/bundle', $pid );
				foreach ( $bundles as $key => $value ) {
					$bundle = wc_get_product( $key );
					if( !$bundle->has_enough_stock( intval( $quantity ) * intval( $value['quantity'] ) ) ) {
						wc_add_notice( __( 'You cannot add that amount of quantity, because there is not enough stock', 'wc-product-bundles' ), 'error' );
						return false;
					}
				}
			}
		}
		return true;		
	}
}

new wcpb_product_form();

?>