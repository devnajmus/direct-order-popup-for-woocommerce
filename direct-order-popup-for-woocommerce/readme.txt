=== Direct Order Popup for WooCommerce ===
Contributors: devnajmus
Tags: woocommerce, quick checkout, popup checkout, variable products, ajax checkout
Requires at least: 5.5
Tested up to: 6.8
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Direct Order Popup for WooCommerce enables fast WooCommerce orders via a popup with variable product support.

== Short Description ==
Fast WooCommerce popup checkout with support for variable products, multiple items, and AJAX cart updates.

== Description ==
Direct Order Popup for WooCommerce streamlines WooCommerce checkout with a fast, user-friendly popup interface. Customers can select product variations, enter their details, and complete orders directly from the popup.

Key Features:
* Quick Order button on product loops and single product pages.
* Popup checkout with Name, Email, Phone, Shipping & Payment options.
* Supports variable products with real-time attribute selection.
* Add multiple products to the popup before ordering.
* AJAX-powered cart updates and checkout.
* Fully responsive for desktop and mobile.
* Seamless WooCommerce integration.

Getting Started:
1. Upload the `direct-order-popup-for-woocommerce` folder to `/wp-content/plugins/`, or install via the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' menu.
3. Ensure WooCommerce is installed and active.
4. Add the Quick Order button using the class `DOPW-quick-order-btn` with `data-product-id` and `data-product-type` attributes in your theme templates.
5. Optional plugin settings can be configured under **WooCommerce → Direct Order Popup**.

== Installation ==
1. Upload the `direct-order-popup-for-woocommerce` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu.
3. Ensure WooCommerce is installed and active.
4. Add the Quick Order button using class `DOPW-quick-order-btn` with `data-product-id` and `data-product-type`.
5. Configure optional settings in WooCommerce → Direct Order Popup.

== Frequently Asked Questions ==
= Does this plugin require WooCommerce? =
Yes, WooCommerce must be installed and active.

= Can I use it with variable products? =
Yes, it fully supports variable products with attribute selection in the popup.

= Can I add multiple products in one popup? =
Yes, customers can add multiple products before checkout.

= Is the popup mobile-friendly? =
Yes, fully responsive for all devices.

= How do I add the Quick Order button? =
Add a button with class `DOPW-quick-order-btn` and `data-product-id`, `data-product-type` attributes in your theme.

= What happens if a customer does not select a variation? =
The plugin prompts customers to select required variations before placing the order.

= Where can I get support? =
* Support: https://github.com/devnajmus/direct-order-popup-for-woocommerce

== Screenshots ==
1. Quick Order button in admin or frontend.
2. “Buy Now” button on product loop.
3. Popup opens with form fields (Name, Email, Phone, Shipping, Payment).
4. Single product page showing Quick Order button.
5. Variable product attribute selection inside popup.
6. Multiple products added to popup before checkout.
7. Order confirmation / success message after checkout.

== Changelog ==
= 1.0.0 =
* Initial release with popup quick-order, simple & variable product support, multiple product selection, and AJAX checkout.

== Additional Information ==
* **Support**: https://github.com/devnajmus/direct-order-popup-for-woocommerce
* **License**: GPLv2 or later

