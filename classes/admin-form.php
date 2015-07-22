<?php 
/**
 * @author 		: Saravana Kumar K
 * @copyright	: sarkware.com
 * @todo		: One of the core module, which renders the wcpb related tabs & fields on the product admin page.
 * 
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class wcpb_product_interface {
	
	function __construct() {
		add_filter( 'product_type_selector', array( $this, 'wcpb_add_product_bundle_type' ), 1, 2 );
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'wcpb_add_product_bundle_tab' ) );
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'wcpb_add_bundle_pricing_fields' ), 10, 1 );
		add_action( 'woocommerce_product_data_panels', array( $this, 'wcpb_add_product_bundle_tab_panel' ), 10, 1 );		
	}
	
	function wcpb_add_product_bundle_type( $ptypes, $ptype ) {
		$ptypes['wcpb'] = __( 'Bundled', 'wc-product-bundles' );
		return $ptypes;
	}
	
	function wcpb_add_product_bundle_tab( $tabs ) {
		$tabs['wcpb'] = array (
			'label'  => __( 'Products Bundled', 'wc-product-bundles' ),
			'target' => 'wcpb_data',
			'class'  => array( 'hide_if_virtual', 'hide_if_grouped', 'hide_if_external', 'hide_if_simple', 'hide_if_variable', 'show_if_wcpb' ),
		);
		return $tabs;
	}
	
	function wcpb_add_bundle_pricing_fields() {
		global $post;
		
		echo '<div class="options_group pricing hide_if_virtual hide_if_grouped hide_if_external hide_if_simple hide_if_variable show_if_wcpb">';
	
		$sprice = wcpb_utils::get_wcpb_meta( $post->ID, '_wcpb_product_sale_price' );		
		woocommerce_wp_text_input( array( 'id' => '_wcpb_product_sale_price', 'value' => esc_html( $sprice ), 'data_type' => 'price', 'label' => __( 'Bundle Price', 'wc-product-bundles' ) . ' ('.get_woocommerce_currency_symbol().')', 'desc_tip' => 'true', 'description' => __( 'Enter your discounted price for this bundle, otherwise the price will be the sum of included products.', 'wc-product-bundles' ) ) );
		
		do_action( 'woocommerce_product_options_pricing' );
		
		echo '</div>';
		
		echo '<div class="options_group pricing hide_if_virtual hide_if_grouped hide_if_external hide_if_simple hide_if_variable show_if_wcpb">';
				
		$on_product = wcpb_utils::get_wcpb_meta( $post->ID, '_wcpb_show_bundle_on_product', 'yes' );
		$on_cart = wcpb_utils::get_wcpb_meta( $post->ID, '_wcpb_show_bundle_on_cart', 'yes' );
		$on_order = wcpb_utils::get_wcpb_meta( $post->ID, '_wcpb_show_bundle_on_order', 'yes' );
		
		woocommerce_wp_checkbox( array( 'id' => '_wcpb_show_bundle_on_product', 'label' => __( 'Show on Product Page', 'wc-product-bundles' ), 'value' => 'yes', 'cbvalue' => $on_product, 'desc_tip' => 'true', 'description' => __( 'Un check if you want to hide the bundle on product page.', 'wc-product-bundles' ) ) );
		woocommerce_wp_checkbox( array( 'id' => '_wcpb_show_bundle_on_cart', 'label' => __( 'Show on Cart Page', 'wc-product-bundles' ), 'value' => 'yes', 'cbvalue' => $on_cart, 'desc_tip' => 'true', 'description' => __( 'Un check if you want to hide the bundle on cart.', 'wc-product-bundles' ) ) );
		woocommerce_wp_checkbox( array( 'id' => '_wcpb_show_bundle_on_order', 'label' => __( 'Show on Order', 'wc-product-bundles' ), 'value' => 'yes', 'cbvalue' => $on_order, 'desc_tip' => 'true', 'description' => __( 'Un check if you want to hide the bundle on order.', 'wc-product-bundles' ) ) );
		
		echo '</div>';
	}
	
	function wcpb_add_product_bundle_tab_panel() { ?>		
		<div id="wcpb_data" class="panel woocommerce_options_panel hide_if_virtual hide_if_grouped hide_if_external hide_if_simple hide_if_variable show_if_wc_bundled_product">					
			<ul class="wcpb-product-search-container-ul wc-metaboxes-wrapper">  
				<li>
					<div class="wcpb-product-search-txt-wrapper">
						<input type="text" id="wcpb-product-search-txt" placeholder="<?php _e( 'Search Products', 'wc-product-bundles' ); ?>" />
						<img alt="spinner" src="<?php echo wcpb()->settings['dir'] ?>/assets/images/spinner.gif" id="wcpb-ajax-spinner" />
						<div id="wcpb-product-search-result-holder">
						
						</div>
					</div>												      
				</li>
				<li>						
					<a href="#" class="wcpb_close_all"><?php _e( 'Close all', 'wc-product-bundles' ); ?></a>
					<a href="#" class="wcpb_expand_all"><?php _e( 'Expand all', 'wc-product-bundles' ); ?></a>
					<a href="#" class="button button-primary button-large" id="wcpb-add-product"><?php _e( 'Add Products', 'wc-product-bundles' ); ?></a>
				</li>
			</ul>			
			
			<div class="wcpb-products-container wc-metaboxes ui-sortable" id="wcpb-products-container">
				<?php global $post; echo apply_filters( 'wcpb/build/included_products', $post->ID ); ?>
			</div>	
			<input type="hidden" id="wcpb-bundles-array" name="wcpb-bundles-array" value="" />				
		</div>		
	<?php 
		$this->wcpb_admin_head();
	}

	function wcpb_admin_head() {
		global $post; ?>
<script type="text/javascript">
var wcpb_var = {
	post_id : <?php echo $post->ID; ?>,
	nonce  : "<?php echo wp_create_nonce( 'wcpb_nonce' ); ?>",
	admin_url : "<?php echo admin_url(); ?>",
	ajaxurl : "<?php echo admin_url( 'admin-ajax.php' ); ?>"	 
};		
</script>
<?php
	}
}

new wcpb_product_interface();

?>