<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    DirectOrderPopupCheckout
 * @subpackage DirectOrderPopupCheckout/includes
 */

if (!defined('WPINC')) {
    die;
}

/**
 * The admin-specific functionality of the plugin.
 */
class DOPW_Admin
{
    /**
     * Initialize the class.
     *
     * @return void
     */
    public function init()
    {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_menu', array($this, 'add_menu_pages'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Register plugin settings.
     *
     * @return void
     */
    public function register_settings()
    {
        register_setting(
            'DOPW_settings',
            'DOPW_button_text',
            array(
                'type' => 'string',
                'default' => __('Order Now', 'direct-order-popup-for-woocommerce'),
                'sanitize_callback' => 'sanitize_text_field',
            )
        );
        register_setting(
            'DOPW_settings',
            'DOPW_place_order_color',
            array(
                'type' => 'string',
                'default' => '#1f7a8c',
                'sanitize_callback' => 'sanitize_hex_color',
            )
        );
        register_setting(
            'DOPW_settings',
            'DOPW_total_color',
            array(
                'type' => 'string',
                'default' => '#1f7a8c',
                'sanitize_callback' => 'sanitize_hex_color',
            )
        );
        register_setting(
            'DOPW_settings',
            'DOPW_product_price_color',
            array(
                'type' => 'string',
                'default' => '#6b7280',
                'sanitize_callback' => 'sanitize_hex_color',
            )
        );
    }

    /**
     * Enqueue styles for the frontend popup.
     *
     * @return void
     */
    public function enqueue_frontend_styles()
    {
        wp_enqueue_style(
            'direct-order-popup-for-woocommerce',
            DOPW_PLUGIN_URL . 'assets/css/quickorder.css',
            array(),
            DOPW_VERSION,
            'all'
        );

        // Add inline styles for dynamic colors
        $place_order_color = get_option('DOPW_place_order_color', '#1f7a8c');
        $total_color = get_option('DOPW_total_color', '#1f7a8c');
        $product_price_color = get_option('DOPW_product_price_color', '#6b7280');

        $custom_css = sprintf(
            '#DOPW-popup {
                --DOPW-accent: %s;
                --DOPW-total-bg: %s;
                --DOPW-product-price: %s;
            }',
            esc_attr($place_order_color),
            esc_attr($total_color),
            esc_attr($product_price_color)
        );

        wp_add_inline_style('direct-order-popup-for-woocommerce', $custom_css);
    }

    /**
     * Enqueue styles and scripts for the admin area.
     *
     * @param string $hook The current admin page.
     * @return void
     */
    public function enqueue_admin_assets($hook)
    {
        // Load only on the plugin's settings page
        if ($hook !== 'toplevel_page_direct-order-popup-for-woocommerce') {
            return;
        }

        // Enqueue admin styles
        wp_enqueue_style(
            'direct-order-popup-for-woocommerce-admin',
            DOPW_PLUGIN_URL . 'assets/css/admin.css',
            array('wp-color-picker'),
            DOPW_VERSION,
            'all'
        );

        // Enqueue admin scripts
        wp_enqueue_script(
            'direct-order-popup-for-woocommerce-admin',
            DOPW_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker'),
            DOPW_VERSION,
            true
        );

        // Initialize color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        $init_color_picker = "jQuery(document).ready(function($){
            $('.DOPW-color-field').wpColorPicker();
        });";

        wp_add_inline_script('direct-order-popup-for-woocommerce-admin', $init_color_picker);
    }

    /**
     * Add menu pages for the plugin.
     *
     * @return void
     */
    public function add_menu_pages()
    {
        add_menu_page(
            __('Direct Order Popup for WooCommerce', 'direct-order-popup-for-woocommerce'),
            __('Direct Order', 'direct-order-popup-for-woocommerce'),
            'manage_options',
            'direct-order-popup-for-woocommerce',
            array($this, 'render_settings_page'),
            'dashicons-cart',
            56
        );
    }

    /**
     * Render the settings page.
     *
     * @return void
     */
    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('DOPW_settings');
                do_settings_sections('DOPW_settings');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="DOPW_button_text">
                                <?php esc_html_e('Button Text', 'direct-order-popup-for-woocommerce'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text"
                                id="DOPW_button_text"
                                name="DOPW_button_text"
                                value="<?php echo esc_attr(get_option('DOPW_button_text', __('Order Now', 'direct-order-popup-for-woocommerce'))); ?>"
                                class="regular-text">
                            <p class="description"><?php esc_html_e('Text for the quick order button on product pages.', 'direct-order-popup-for-woocommerce'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="DOPW_place_order_color">
                                <?php esc_html_e('Place Order Button Color', 'direct-order-popup-for-woocommerce'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text"
                                id="DOPW_place_order_color"
                                name="DOPW_place_order_color"
                                value="<?php echo esc_attr(get_option('DOPW_place_order_color', '#1f7a8c')); ?>"
                                class="DOPW-color-field"
                                data-default-color="#1f7a8c">
                            <p class="description"><?php esc_html_e('Background color for the Place Order button in the popup.', 'direct-order-popup-for-woocommerce'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="DOPW_total_color">
                                <?php esc_html_e('Total Box Color', 'direct-order-popup-for-woocommerce'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text"
                                id="DOPW_total_color"
                                name="DOPW_total_color"
                                value="<?php echo esc_attr(get_option('DOPW_total_color', '#1f7a8c')); ?>"
                                class="DOPW-color-field"
                                data-default-color="#1f7a8c">
                            <p class="description"><?php esc_html_e('Background color for the total box in the popup.', 'direct-order-popup-for-woocommerce'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="DOPW_product_price_color">
                                <?php esc_html_e('Product Price Color', 'direct-order-popup-for-woocommerce'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text"
                                id="DOPW_product_price_color"
                                name="DOPW_product_price_color"
                                value="<?php echo esc_attr(get_option('DOPW_product_price_color', '#6b7280')); ?>"
                                class="DOPW-color-field"
                                data-default-color="#6b7280">
                            <p class="description"><?php esc_html_e('Text color for product prices in the popup.', 'direct-order-popup-for-woocommerce'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Save Settings', 'direct-order-popup-for-woocommerce')); ?>
            </form>
        </div>
<?php
    }
}
