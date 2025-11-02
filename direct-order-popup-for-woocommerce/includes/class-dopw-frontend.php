<?php

/**
 * The frontend-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    DirectOrderPopupCheckout
 * @subpackage  DirectOrderPopupCheckout/includes
 */

if (!defined('WPINC')) {
    die;
}

/**
 * The frontend-specific functionality of the plugin.
 */
class DOPW_Frontend
{

    /**
     * Initialize the class.
     *
     * @return void
     */
    public function init()
    {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'render_popup_template'));

        // Add Quick Order buttons
        add_action('woocommerce_after_shop_loop_item', array($this, 'add_shop_quick_order_button'), 15);
        add_action('woocommerce_after_add_to_cart_button', array($this, 'add_single_quick_order_button'), 15);
    }

    /**
     * Register the stylesheets for the frontend.
     *
     * @return void
     */
    public function enqueue_styles()
    {
        // Only enqueue on WooCommerce pages
        if (!is_woocommerce() && !is_cart() && !is_checkout() && !is_product()) {
            return;
        }

        wp_enqueue_style(
            'direct-order-popup-for-woocommerce',
            DOPW_PLUGIN_URL . 'assets/css/quickorder.css',
            array(),
            DOPW_VERSION,
            'all'
        );
    }

    /**
     * Register the JavaScript for the frontend.
     *
     * @return void
     */
    public function enqueue_scripts()
    {
        global $post;

        // Load scripts on any page that might have products
        if (!is_woocommerce() && !is_shop() && !is_product() && !is_product_category() && !is_product_tag() && !has_shortcode($post->post_content, 'products')) {
            return;
        }

        // Core scripts
        wp_enqueue_script('jquery');
        wp_enqueue_script('woocommerce');
        wp_enqueue_script('accounting');

        // Plugin script
        wp_enqueue_script(
            'direct-order-popup-for-woocommerce-frontend',
            DOPW_PLUGIN_URL . 'assets/js/quickorder.js',
            array('jquery'),
            DOPW_VERSION . '.' . time(),
            true
        );

        // Pass data to JavaScript
        wp_localize_script(
            'direct-order-popup-for-woocommerce-frontend',
            'DOPWData',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('DOPW_nonce'),
                'currency_symbol' => get_woocommerce_currency_symbol(),
                'currency_pos' => get_option('woocommerce_currency_pos'),
                'decimal_separator' => wc_get_price_decimal_separator(),
                'thousand_separator' => wc_get_price_thousand_separator(),
                'decimals' => wc_get_price_decimals(),
                'debug' => WP_DEBUG
            )
        );

        wp_localize_script(
            'direct-order-popup-for-woocommerce-frontend',
            'DOPWAjax',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('DOPW_nonce'),
            )
        );
    }

    /**
     * Render the popup template.
     *
     * @return void
     */
    public function render_popup_template()
    {
        // Only render on pages where we might need it
        if (is_woocommerce() || is_product() || is_shop() || is_product_category() || is_product_tag()) {
            include DOPW_PLUGIN_DIR . 'templates/quickorder-popup.php';
        }
    }

    /**
     * Add Quick Order button to shop/archive pages.
     *
     * @return void
     */
    public function add_shop_quick_order_button()
    {
        global $product;
        if (!$product || !$product->is_purchasable()) {
            return;
        }
        $this->render_quick_order_button($product);
    }

    /**
     * Add Quick Order button to single product page.
     *
     * @return void
     */
    public function add_single_quick_order_button()
    {
        global $product;
        if (!$product || !$product->is_purchasable()) {
            return;
        }
        $this->render_quick_order_button($product);
    }

    /**
     * Render the Quick Order button.
     *
     * @param WC_Product $product The product object.
     * @return void
     */
    private function render_quick_order_button($product)
    {
        $button_text = get_option('DOPW_button_text', __('Order Now', 'direct-order-popup-for-woocommerce'));
        $image_url = wp_get_attachment_image_url($product->get_image_id(), 'woocommerce_thumbnail');
        if (!$image_url) {
            $image_url = wc_placeholder_img_src('woocommerce_thumbnail');
        }
?>
        <button type="button"
            class="button DOPW-quick-order-btn"
            data-product-id="<?php echo esc_attr($product->get_id()); ?>"
            data-product-type="<?php echo esc_attr($product->get_type()); ?>"
            data-product-title="<?php echo esc_attr($product->get_name()); ?>"
            data-product-price="<?php echo esc_attr($product->get_price()); ?>"
            data-product-price-formatted="<?php echo esc_attr(wp_strip_all_tags(wc_price($product->get_price()))); ?>"
            data-product-image-url="<?php echo esc_attr($image_url); ?>">
            <?php echo esc_html($button_text); ?>
        </button>
<?php
    }
}
