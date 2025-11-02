<?php

/**
 * Direct Order Popup Checkout
 *
 * @package   DirectOrderPopupCheckout
 * @author    MD Najmus Shadat
 * @license   GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: Direct Order Popup for WooCommerce
 * Plugin URI:  https://github.com/devnajmus/quick-order-popup-checkout
 * Description: A WooCommerce extension for quick order and checkout via popup.
 * Version:     1.0.0
 * Author:      MD Najmus Shadat
 * Author URI:  https://github.com/devnajmus
 * Text Domain: direct-order-popup-for-woocommerce
 * Domain Path: /languages
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', function () {
        echo '<div class="error"><p>';
        esc_html_e('Direct Order Popup for WooCommerce requires WooCommerce to be installed and activated.', 'direct-order-popup-for-woocommerce');
        echo '</p></div>';
    });
    return;
}

// Define plugin constants
define('DOPW_VERSION', '1.0.0');
define('DOPW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DOPW_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DOPW_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Require the main loader class
require_once DOPW_PLUGIN_DIR . 'includes/class-dopw-loader.php';

/**
 * Run the plugin.
 *
 * @since 1.0.0
 */
function run_direct_order_popup_checkout()
{
    $plugin = DOPW_Loader::get_instance();
    $plugin->run();
}

/**
 * Plugin activation hook.
 *
 * @since 1.0.0
 */
function activate_direct_order_popup_checkout()
{
    DOPW_Loader::activate();
}

/**
 * Plugin deactivation hook.
 *
 * @since 1.0.0
 */
function deactivate_direct_order_popup_checkout()
{
    DOPW_Loader::deactivate();
}

// Register activation & deactivation hooks
register_activation_hook(__FILE__, 'activate_direct_order_popup_checkout');
register_deactivation_hook(__FILE__, 'deactivate_direct_order_popup_checkout');

// Initialize the plugin
run_direct_order_popup_checkout();
