=== WC Product Bundles ===
Contributors: mycholan
Tags: Woocommerce product bundle
Requires at least: 3.5
Tested up to: 4.2.4
Stable tag: 1.0.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Bundle two or more woocommerce products together and sell them at a discounted rate.

== Description ==

WC Product Bundle allows you to bundle two or more woocommerce products together and sell them at a discounted rate. No complex configurations are required, just few steps you can setup and sell Bundled Products. 

= Features =
* Creating bundled products with ease
* Flexible pricing methods
* Allows you to override bundle products Title and Short Description
* Automatic stock management.
* Powerfull API to customize many UI aspects behaviour

= Documentation =
* [Getting Started](http://sarkware.com/wc-product-bundle-bundle-products-together-and-sell-them-with-a-discounted-rate/)
* [Customize Bundle Rendering Behavior - Product](http://sarkware.com/changing-bundles-rendering-behaviour-wc-product-bundles/)
* [Customize Bundle Rendering Behavior - Cart](http://sarkware.com/changing-rendering-behavior-bundle-item-on-cart-wc-product-bundles/)

== Installation ==
1. Ensure you have latest version of WooCommerce plugin installed ( 2.2 or above )
2. Unzip and upload contents of the plugin to your /wp-content/plugins/ directory
3. Activate the plugin through the 'Plugins' menu in WordPress

== Screenshots ==
1. Wcpb 'Bundled' product type
2. Wcpb search and add products to the bundle
3. Wcpb individual bundle product's options
4. Wcpb bundled product page

== Changelog ==

= 1.0.0 =
* First Public Release.

= 1.0.1 =
* Option Label text updates
* 'wcpb/bundle/before/product/content/rendering' and 'wcpb/bundle/after/product/content/rendering' action hook introduced.

= 1.0.2 =
* Global visibility option for bundles

= 1.0.3 =
* Bundle re order issue fixed
* introduced filter for 'instock' & 'out of stock' labels. 'wcpb/bundle/instock/label' and 'wcpb/bundle/outofstock/label'

= 1.0.4 =
* wp-admin 'Screen Option' tab issue fixed

= 1.0.5 =
* Internationalization ( i18n ) support added
* Text domain is 'wc-product-bundles'

= 1.0.6 =
* Bundles inventory sync issue fixed

= 1.0.7 =
* Bundled items description issue fixed
* Shipping weight sync added ( The weight will be the sum of bundled items, if no weight is specified )