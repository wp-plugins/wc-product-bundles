<?php
/**
 * @author 		: Saravana Kumar K
 * @copyright	: sarkware.com
 * @todo		: Wcpb Product Bundle Class which extends WC_Product
 * 				  It mainly responsible for registering 'wcpb' product type and managing bundle pricing.
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Product_Wcpb extends WC_Product {
	
	public function __construct( $product ) {
		$this->product_type = 'wcpb';
		parent::__construct( $product );
	}
	
	/**
	 * Calculate and return the price for the bundle
	 *
	 * @return string [ price ]
	 *
	 */
	public function get_price() {
		$price = 0;
		$sprice = $this->get_sale_price();
		if( $sprice && $sprice != "" ) {
			/* Sale price has been set by user */
			$this->price = $sprice;
		} else {
			/* Sale price has not been set,
			 * So here we are going to sum
			 **/
			$bundles =  json_decode( get_post_meta( $this->id, "wcpb_bundle_products", true ), true );
			if( is_array( $bundles ) ) {
				foreach ( $bundles as $key => $value ) {
					$bundle = new WC_Product( $key );					
					if( $value["tax_included"] == "yes" ) {
						$price += apply_filters( 'wcpb_bundle_product_price', $bundle->get_price_including_tax( $value["quantity"] ), $bundle, $value["quantity"] );
					} else {
						$price += apply_filters( 'wcpb_bundle_product_price', $bundle->get_price_excluding_tax( $value["quantity"] ), $bundle, $value["quantity"] );
					}					
				}
			}
			$this->price = apply_filters( 'wcpb_bundle_regular_price', $price, $this );
		}
		return $this->price;
	}
	
	/**
	 * Return the product's sale price
	 *
	 * @return string [ _wcpb_product_sale_price ]
	 *
	 */
	public function get_sale_price() {
		$this->sale_price = get_post_meta( $this->id, '_wcpb_product_sale_price', true );
		return $this->sale_price;
	}
	
	/**
	 * Return the product's regular price
	 *
	 * @return string [ _wcpb_product_regular_price ]
	 *
	 */
	public function get_regular_price() {
		$bundles =  json_decode( get_post_meta( $this->id, "wcpb_bundle_products", true ), true );
		if( is_array( $bundles ) ) {
			foreach ( $bundles as $key => $value ) {
				$bundle = new WC_Product( $key );					
				if( $value["tax_included"] == "yes" ) {
					$price += apply_filters( 'wcpb_bundle_product_price', $bundle->get_price_including_tax( $value["quantity"] ), $bundle, $value["quantity"] );
				} else {
					$price += apply_filters( 'wcpb_bundle_product_price', $bundle->get_price_excluding_tax( $value["quantity"] ), $bundle, $value["quantity"] );
				}					
			}
		}
		$this->regular_price = apply_filters( 'wcpb_bundle_regular_price', $price, $this );
		return $this->regular_price;
	}
	
	/**
	 * Returns the product weight - in this case sum of bundles items weight.
	 *
	 * @return decimal
	 */
	public function get_weight() {
		$weight = 0;		
		if( parent::get_weight() ) {
			$weight = parent::get_weight();
		} else {		
			$bundles =  json_decode( get_post_meta( $this->id, "wcpb_bundle_products", true ), true );
			if( is_array( $bundles ) ) {
				foreach ( $bundles as $key => $value ) {
					$bundle = new WC_Product( $key );
					if( $bundle->has_weight() ) {
						$weight += ( floatval( $bundle->get_weight() ) * intval( $value["quantity"] ) );
					}				
				}
			}
		}		
		return $weight;
	}
	
	/**
	 * Returns whether or not the product has weight set.
	 *
	 * @return bool
	 */
	public function has_weight() {
		return $this->get_weight() ? true : false;
	}

}

?>