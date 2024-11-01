=== WC Bulk Buyer Discounts ===
Contributors: Michael Hall
Donate link: http://goo.gl/Dq0Akw
Tags: WooCommerce, Coupons, Custom Discounts, Bulk Buyer Discount
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Requires at least: 3.5
Tested up to: 3.5
Stable tag: 1.0

== Description ==

WC Bulk Buyer Discounts is a simple discount plugin for Woocommerce using automatic one use only coupons.

The plugin creates automatic WooCommerce coupons for customers when their cart matches the plugin conditions set on the options page. If the cart changes and the contents do not match the options, the coupons will be removed automatically.

== Prerequisites ==

 - WooCommerce

== Installation ==

To setup the plugin please use the following instructions.

1. Download and extract wc-bulk-buyer-discounts.zip. Make sure WooCommerce is installed and activated.
2. Copy the "wc-bulk-buyer-discounts" folder to your wordpress plugins directory (/wp-content/plugins/)
3. Open your Wordpress admin interface and go to Plugins, find WC Bulk Buyer Discounts and click Activate.
4. Now go to Settings --> WCBBD Settings and set the following options:

 - Discount Percentage - Enter as a whole number the discount percentage (0-100).
 - Cart Quantity Qualification    - Enter the number of discountable products that must be in a users cart to qualify for the discount.
 - Discountable Products - Enter product ID's separated by commas, i.e. 102,132,140.
 - Expired Coupon Cleanup - Check this checkbox to automatically schedule a daily deletion of any bulk buyer coupons that are expired.

Click Save Changes and you are done! Have a look and see how the coupons are applied when adding products to the shopping cart.

== Frequently Asked Questions ==

Q. Can you make it so the discounts can be cash value instead of percentage?

A. The next release will feature an options setting to change the type of coupon the plugin uses, in the meantime if you want me to I can tell you where to make the changes for this, just drop me an email: michael@hallnet.com.au.

Q. Why are there lots of wierd 'bulk-buyer-discount...' coupons in my woocommerce coupons page now?!

A. The WC Bulk Buyer Discounts plugin creates unique coupons for each user as they create and modify their shopping cart, this ensures no coupons are abused again and again, each coupon can only be used once, expires after 1 day and will be removed if the cart items do not meet the required quantity and allowed products. The coupons will build up BUT as long as the "Expired Coupon Cleanup" checkbox is ticked the plugin will look for and delete any coupons it created that are expired.

== Screenshots ==

For screenshots and an online readme see: http://goo.gl/Dq0Akw

== Upgrade Notice ==



== Changelog ==

= 0.1 =
	- First build, untested.
= 1.0 =
	- Second iteration, tested and working.
