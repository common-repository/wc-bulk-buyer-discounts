<?php
	/**
	 * Plugin Name: WC Bulk Buyer Discount
	 * Plugin URI: http://michaelhalls.net/articles/11/woocommerce-bulk-buyer-discounts-wordpress-plugin
	 * Description: WC Bulk Buyer Discounts is a simple discount plugin for Woocommerce using automatic one use only coupons.
	 * Version: 1.0.0
	 * Author: Michael Hall
	 * Author URI: http://michaelhalls.net
	 * License: GPL2
	 */

	/*  Copyright 2014  MICHAEL HALL  (email : michael@hallnet.com.au)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
	*/

	load_plugin_textdomain('wc-bulk-buyer-discount', false, basename( dirname( __FILE__ ) ) . '/languages' );

	// create custom plugin settings menu
	add_action('admin_menu', 'wcbbd_menu');	

	function wcbbd_menu() {

		// create wcbbd options page under settings->WCBBD Settings in the menu
		add_submenu_page( 'options-general.php', 'WC Bulk Buyer Discounts', 'WCBBD Settings', 'administrator', __FILE__, 'wcbbd_settings_page' ); 

		//call register settings function
		add_action( 'admin_init', 'register_wcbbdsettings' );
	}

	function register_wcbbdsettings() {
		//register our settings
		register_setting( 'wcbbdoption-group', 'wcbbd_discount_percentage', 'intval' );
		register_setting( 'wcbbdoption-group', 'wcbbd_discount_products');
		register_setting( 'wcbbdoption-group', 'wcbbd_discount_minimum', 'intval' );
		register_setting( 'wcbbdoption-group', 'wcbbd_auto_coupon_cleanup', 'intval' );
	}

	function wcbbd_settings_page() {
	// Options page HTML
?>
	<style type="text/css">
		.wbbd-settings-input{text-align: right;}
	</style>
	<div class="wrap">
		<h2>WC Bulk Buyer Discounts</h2>
		<form method="post" action="options.php">
		    <?php settings_fields( 'wcbbdoption-group' ); ?>
		    <?php do_settings_sections( 'wcbbdoption-group' ); ?>
		    <table class="form-table">
		        <tr valign="top">
		        	<th scope="row">Discount Percentage</th>
		        	<td><input type="text" name="wcbbd_discount_percentage" class="wbbd-settings-input" value="<?php echo get_option('wcbbd_discount_percentage', 10); ?>" />%<p class="description">Enter as a whole number the discount percentage (0-100)</p></td>
		        </tr>
		        <tr valign="top">
		        	<th scope="row">Cart Quantity Qualification</th>
		        	<td><input type="text" name="wcbbd_discount_minimum" class="wbbd-settings-input" value="<?php echo get_option('wcbbd_discount_minimum', 2); ?>" /><p class="description">Enter the number of discountable products that must be in a users cart to qualify for the discount</p></td>
		        </tr>
		        <tr valign="top">
			        <th scope="row">Discountable Products</th>
			        <td><input type="text" name="wcbbd_discount_products" class="wbbd-settings-input" value="<?php echo get_option('wcbbd_discount_products'); ?>" /><p class="description">Enter product ID's separated by commas, i.e. 102,132,140</p></td>
		        </tr>
		        <tr valign="top">
		        	<th scope="row">Expired Coupon Cleanup</th>
		        	<td><input type="checkbox" name="wcbbd_auto_coupon_cleanup" class="wbbd-settings-input" value="1" <?php if (get_option('wcbbd_auto_coupon_cleanup', 1) == 1){echo 'checked';} ?> /><p class="description">Check this checkbox to automatically schedule a daily deletion of any bulk buyer coupons that are expired.</p></td>
		        </tr> 
		    </table>
		    <?php submit_button(); ?>
		</form>
	</div>

<?php }

	register_activation_hook( __FILE__, 'wcbbd_activation' );
	/**
	 * On activation, set a time, frequency and name of an action hook to be scheduled.
	 */
	function wcbbd_activation() {
		wp_schedule_event( time(), 'daily', 'wcbbd_daily_event_hook' );
	}

	add_action( 'wcbbd_daily_event_hook', 'wcbbd_daily_coupon_cleanup' );
	/**
	 * On the scheduled action hook, run the function.
	 */
	function wcbbd_daily_coupon_cleanup() {
		if (get_option('wcbbd_auto_coupon_cleanup') == 1) {
			global $wpdb;
			global $woocommerce;
			// Get all coupons.
			$args = array('post_type' => 'shop_coupon','posts_per_page' => -1);
			$coupon_array = get_posts($args);
			// For each coupon check if it is a bulk-buyer-discount coupon, if so delete it if it is expired
			foreach ($coupon_array as $key => $value) {
				if (substr($value->post_title, 0, 20 ) === "bulk-buyer-discount-") {
					$res = $wpdb->get_results("SELECT * FROM wp_postmeta WHERE post_id='".$value->ID."'");
					foreach ($res as $key => $values) {
						if ($values->meta_key == 'expiry_date') {
							if (strtotime($values->meta_value) < time()){
								wp_delete_post( $values->post_id );
							}
						}				
					}
				}
			}
		}
	}

	register_deactivation_hook( __FILE__, 'wcbbd_deactivation' );
	/**
	 * On deactivation, remove all functions from the scheduled action hook.
	 */
	function wcbbd_deactivation() {
		wp_clear_scheduled_hook( 'wcbbd_daily_event_hook' );
	}


	// add actions for main plugin code to fire on cart add and cart update
	add_action('woocommerce_before_cart_table', 'wcbbd_add');

	function wcbbd_add() {
	  wcbbd_add_discount();
	}
	// Add discount, plugins main function. 
	function wcbbd_add_discount(){
		  	global $woocommerce;
		  	// load discountable product string
			$setProducts = get_option('wcbbd_discount_products');
			// convert products string to array
			$wcbbd_products = explode(",", $setProducts);
			// set base quantity for discount options
			$discountableQty = 0;
			//create blank array to add found products to
			$qualifiedProductIds = array();
		  	// Check each item in the cart to see if it is a discountable product then add it and its qty to variables for processing
		  	foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
		  		if (in_array($values['product_id'], $wcbbd_products)) {
		  			$discountableQty += $values['quantity'];
		  			array_push($qualifiedProductIds, $values['product_id']);
		  		}
		  	}
		  	// If statement to check if the cart item quantity is equal or more than the plugin option for discount minimum qty 
		  	if ($discountableQty >= get_option('wcbbd_discount_minimum')) {
		  		// Process the cart again this time looking for the discountable products, for each matching product and qty apply a new coupon
		  		foreach ($woocommerce->cart->get_cart() as $cart_item_key => $values ) {
		  			if (in_array($values['product_id'], $qualifiedProductIds)) {
		  				// Check for existing bulk buyer coupons, remove any if found to reprocess
		  				$existingCoupons = $woocommerce->cart->get_applied_coupons();
		  				foreach ($existingCoupons as $key => $value) {
		  					if (substr( $value, 0, 20 ) === "bulk-buyer-discount-") {
								$woocommerce->cart->remove_coupon($value);
							}
		  				}
		  				// Create a new coupon for this processing loop, use discount percentage from plugin options
		  				$time = time();
		  				$coupon_code = 'bulk-buyer-discount-' . $time; // Code - generated with string + timestamp
		  				$coupon_date = date('Y-m-d', strtotime('+1 day'));
						$amount = get_option('wcbbd_discount_percentage'); // Amount
						$discount_type = 'percent_product'; // Type: fixed_cart, percent, fixed_product, percent_product
						$coupon = array(
						    'post_title'	=> $coupon_code,
						    'post_content' 	=> '',
						    'post_status'	=> 'publish',
						    'post_author'	=> 1,
						    'post_type'		=> 'shop_coupon'
						);    

						$new_coupon_id = wp_insert_post( $coupon );
						// Add meta
						update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
						update_post_meta( $new_coupon_id, 'coupon_amount', $amount );
						update_post_meta( $new_coupon_id, 'individual_use', 'no' );
						update_post_meta( $new_coupon_id, 'product_ids', $setProducts );
						update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
						update_post_meta( $new_coupon_id, 'usage_limit', '1' );
						update_post_meta( $new_coupon_id, 'expiry_date', $coupon_date );
						update_post_meta( $new_coupon_id, 'apply_before_tax', 'no' );
						update_post_meta( $new_coupon_id, 'free_shipping', 'no' );
						// apply coupon and recalculate cart totals
		  				$woocommerce->cart->add_discount( $coupon_code );
		  				$woocommerce->cart->calculate_totals();

		  			}
		  		}
		  	// If discountable quantity does not match plugin options then remove any bulk buyer coupons and recalculate cart total.
			} elseif ($discountableQty <= get_option('wcbbd_discount_minimum')) {
				$existingCoupons = $woocommerce->cart->get_applied_coupons();
				foreach ($existingCoupons as $key => $value) {
					if (substr( $value, 0, 20 ) === "bulk-buyer-discount-") {
						$woocommerce->cart->remove_coupon($value);
						$woocommerce->cart->calculate_totals();
					}
				}
			}
		// Print the woocommerce notices for coupons etc, not sure if this should be here or in the function at all...
		if(!wc_print_notices()){
			wc_print_notices();
		}
	}

?>