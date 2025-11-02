<?php

/**
 * Handle all AJAX requests for the plugin.
 *
 * @since      1.0.0
 * @package     DirectOrderPopupCheckout
 * @subpackage  DirectOrderPopupCheckout/includes
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Handle all AJAX requests for the plugin.
 */
class DOPW_Ajax
{
    /**
     * Initialize the class.
     *
     * @return void
     */
    public function init()
    {
        add_action('wp_ajax_DOPW_process_order', array($this, 'process_order'));
        add_action('wp_ajax_nopriv_DOPW_process_order', array($this, 'process_order'));
        add_action('wp_ajax_DOPW_get_variations', array($this, 'get_variations'));
        add_action('wp_ajax_nopriv_DOPW_get_variations', array($this, 'get_variations'));
    }

    /**
     * Return variation data for a variable product.
     *
     * @return void
     */
    public function get_variations()
    {
        // Verify nonce
        $nonce = filter_input(INPUT_POST, 'nonce', FILTER_SANITIZE_STRING);
        if (!$nonce || !wp_verify_nonce(wp_unslash($nonce), 'DOPW_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'direct-order-popup-for-woocommerce')));
        }

        $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT, array('options' => array('default' => 0)));
        if (!$product_id) {
            wp_send_json_error(array('message' => __('Invalid product ID', 'direct-order-popup-for-woocommerce')));
        }

        $product = wc_get_product($product_id);
        if (!$product || !$product->is_type('variable')) {
            wp_send_json_error(array('message' => __('Not a variable product', 'direct-order-popup-for-woocommerce')));
        }

        /** @var WC_Product_Variable $product */
        $variations = $product->get_available_variations();
        $raw_attributes = $product->get_variation_attributes();

        // Normalize attributes for dropdowns
        $attributes = array();
        foreach ($raw_attributes as $attr_key => $opts) {
            $normalized = array();
            foreach ($opts as $opt) {
                if (empty($opt)) {
                    continue;
                }

                $slug = '';
                $label = $opt;

                // Handle taxonomy-based attributes (pa_)
                if (strpos($attr_key, 'pa_') === 0) {
                    $taxonomy = $attr_key;
                    $term = get_term_by('slug', $opt, $taxonomy);
                    if (!$term || is_wp_error($term)) {
                        $term = get_term_by('name', $opt, $taxonomy);
                    }
                    if ($term && !is_wp_error($term)) {
                        $slug = $term->slug;
                        $label = $term->name;
                    } else {
                        $slug = sanitize_title($opt);
                        $label = $opt;
                    }
                } else {
                    // Custom attributes
                    $slug = sanitize_title($opt);
                    $label = $opt;
                }

                $normalized[] = array('slug' => $slug, 'label' => $label);
            }

            // Remove duplicates based on slug
            $attributes[$attr_key] = array_values(array_unique($normalized, SORT_REGULAR));
        }

        // Prepare variations with consistent attribute structure
        $data_variations = array();
        foreach ($variations as $v) {
            $variation_id = isset($v['variation_id']) ? intval($v['variation_id']) : 0;
            if (!$variation_id) {
                continue;
            }

            $variation_obj = wc_get_product($variation_id);
            if (!$variation_obj) {
                continue;
            }

            // Build variation attributes ensuring consistent slugs
            $variation_attributes = array();

            foreach ($raw_attributes as $attr_key => $possible_values) {
                // Get the attribute value for this variation
                $value = '';

                // Try to get from variation's attributes first
                if (isset($v['attributes']) && isset($v['attributes']['attribute_' . $attr_key])) {
                    $value = $v['attributes']['attribute_' . $attr_key];
                } else {
                    // Fallback to getting from variation object
                    $value = $variation_obj->get_attribute($attr_key);
                }

                // Normalize the value to match dropdown options
                if ($value !== '') {
                    if (strpos($attr_key, 'pa_') === 0) {
                        // Taxonomy attribute - ensure we use slug
                        $term = get_term_by('slug', $value, $attr_key);
                        if (!$term || is_wp_error($term)) {
                            $term = get_term_by('name', $value, $attr_key);
                        }
                        if ($term && !is_wp_error($term)) {
                            $value = $term->slug;
                        } else {
                            $value = sanitize_title($value);
                        }
                    } else {
                        // Custom attribute - sanitize consistently
                        $value = sanitize_title($value);
                    }
                }

                // Store with 'attribute_' prefix to match WooCommerce standard
                $variation_attributes['attribute_' . $attr_key] = $value;
            }

            // Get prices
            $price = $variation_obj->get_price();
            $regular_price = $variation_obj->get_regular_price();
            $sale_price = $variation_obj->get_sale_price();
            $display_price = !empty($sale_price) ? $sale_price : ($regular_price ?: $price);

            // Get image
            $image_src = '';
            if ($variation_obj->get_image_id()) {
                $image_src = wp_get_attachment_image_url($variation_obj->get_image_id(), 'woocommerce_thumbnail');
            } elseif ($product->get_image_id()) {
                $image_src = wp_get_attachment_image_url($product->get_image_id(), 'woocommerce_thumbnail');
            }

            $data_variations[] = array(
                'variation_id'   => $variation_id,
                'attributes'     => $variation_attributes,
                'price'          => floatval($price),
                'display_price'  => floatval($display_price),
                'regular_price'  => floatval($regular_price ?: 0),
                'sale_price'     => floatval($sale_price ?: 0),
                'price_html'     => $variation_obj->get_price_html(),
                'is_in_stock'    => $variation_obj->is_in_stock(),
                'is_purchasable' => $variation_obj->is_purchasable(),
                'title'          => $variation_obj->get_name(),
                'image'          => array('src' => $image_src),
                'sku'            => $variation_obj->get_sku(),
            );
        }

        wp_send_json_success(array(
            'attributes' => $attributes,
            'variations' => $data_variations,
            'product_title' => $product->get_name(),
            'product_id' => $product_id,
        ));
    }

    /**
     * Process the quick order.
     *
     * @return void
     */
    public function process_order()
    {
        // Verify nonce
        $nonce = filter_input(INPUT_POST, 'nonce', FILTER_SANITIZE_STRING);
        if (!$nonce || !wp_verify_nonce(wp_unslash($nonce), 'DOPW_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'direct-order-popup-for-woocommerce')));
        }

        // Sanitize customer data
        $customer = array();
        $raw_customer = filter_input(INPUT_POST, 'customer', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        if (is_array($raw_customer)) {
            $customer = array_map('sanitize_text_field', wp_unslash($raw_customer));
        }

        // Sanitize order data
        $order_data = array();
        $raw_order_data = filter_input(INPUT_POST, 'order', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        if (is_array($raw_order_data)) {
            $order_data = $this->sanitize_recursive(wp_unslash($raw_order_data));
        }

        // Sanitize order items array
        if (!empty($order_data['items']) && is_array($order_data['items'])) {
            foreach ($order_data['items'] as &$item) {
                $item['id'] = isset($item['id']) ? intval($item['id']) : 0;
                $item['quantity'] = isset($item['quantity']) ? intval($item['quantity']) : 1;
                $item['variation_id'] = isset($item['variation_id']) ? intval($item['variation_id']) : 0;
                $item['price'] = isset($item['price']) ? floatval($item['price']) : 0;
                if (isset($item['variation_attributes']) && is_array($item['variation_attributes'])) {
                    $item['variation_attributes'] = array_map('sanitize_text_field', $item['variation_attributes']);
                }
                if (isset($item['product_type'])) {
                    $item['product_type'] = sanitize_text_field($item['product_type']);
                }
            }
            unset($item);
        }

        // Sanitize other order fields
        $order_data['shipping_method'] = isset($order_data['shipping_method']) ? sanitize_text_field($order_data['shipping_method']) : '';
        $order_data['payment_method'] = isset($order_data['payment_method']) ? sanitize_text_field($order_data['payment_method']) : '';
        $order_data['subtotal'] = isset($order_data['subtotal']) ? floatval($order_data['subtotal']) : 0;
        $order_data['shipping'] = isset($order_data['shipping']) ? floatval($order_data['shipping']) : 0;
        $order_data['total'] = isset($order_data['total']) ? floatval($order_data['total']) : 0;

        // Validate input
        if (empty($customer['name']) || empty($customer['phone']) || empty($customer['address']) || empty($order_data['items']) || !is_array($order_data['items'])) {
            wp_send_json_error(array('message' => __('Missing required customer or order data', 'direct-order-popup-for-woocommerce')));
        }

        // Validate email if provided
        if (!empty($customer['email']) && !is_email($customer['email'])) {
            wp_send_json_error(array('message' => __('Invalid email address', 'direct-order-popup-for-woocommerce')));
        }

        // Validate items
        foreach ($order_data['items'] as $item) {
            if ($item['id'] <= 0) {
                wp_send_json_error(array('message' => sprintf(
                    /* translators: %d: Product ID */
                    __('Invalid product ID: %d', 'direct-order-popup-for-woocommerce'),
                    $item['id']
                )));
            }
            if ($item['product_type'] === 'variable' && $item['variation_id'] <= 0) {
                wp_send_json_error(array('message' => __('Missing variation ID for variable product', 'direct-order-popup-for-woocommerce')));
            }
        }

        // Create WooCommerce order
        try {
            $order = wc_create_order();
            if (is_wp_error($order)) {
                wp_send_json_error(array('message' => sprintf(
                    /* translators: %s: Error message */
                    __('Failed to create order: %s', 'direct-order-popup-for-woocommerce'),
                    $order->get_error_message()
                )));
            }

            // Add customer details
            $order->set_billing_first_name($customer['name']);
            $order->set_billing_phone($customer['phone']);
            $order->set_billing_email(!empty($customer['email']) ? $customer['email'] : '');
            $order->set_billing_address_1($customer['address']);
            $order->set_shipping_address_1($customer['address']);

            // Add products to order
            foreach ($order_data['items'] as $item) {
                $product = wc_get_product($item['id']);
                if (!$product) {
                    wp_send_json_error(array('message' => sprintf(
                        /* translators: %d: Product ID */
                        __('Invalid product: %d', 'direct-order-popup-for-woocommerce'),
                        $item['id']
                    )));
                }

                if ($item['product_type'] === 'variable') {
                    $variation_id = $item['variation_id'];
                    $variation = wc_get_product($variation_id);
                    if (!$variation || !$variation->is_in_stock() || !$variation->is_purchasable()) {
                        wp_send_json_error(array('message' => sprintf(
                            /* translators: %d: Variation ID */
                            __('Invalid or out-of-stock variation: %d', 'direct-order-popup-for-woocommerce'),
                            $variation_id
                        )));
                    }
                    $order->add_product($variation, $item['quantity'], array(
                        'variation' => $item['variation_attributes'],
                        'subtotal' => $item['price'] * $item['quantity'],
                        'total' => $item['price'] * $item['quantity'],
                    ));
                } else {
                    if (!$product->is_in_stock() || !$product->is_purchasable()) {
                        wp_send_json_error(array('message' => sprintf(
                            /* translators: %d: Product ID */
                            __('Product out of stock or not purchasable: %d', 'direct-order-popup-for-woocommerce'),
                            $item['id']
                        )));
                    }
                    $order->add_product($product, $item['quantity'], array(
                        'subtotal' => $item['price'] * $item['quantity'],
                        'total' => $item['price'] * $item['quantity'],
                    ));
                }
            }

            // Set shipping and payment methods
            if (!empty($order_data['shipping_method'])) {
                $shipping_item = new WC_Order_Item_Shipping();
                $shipping_item->set_method_id($order_data['shipping_method']);
                $shipping_item->set_total($order_data['shipping']);
                $order->add_item($shipping_item);
            }

            $order->set_payment_method($order_data['payment_method']);
            $order->set_status('pending');
            $order->calculate_totals();

            // Save the order
            $order_id = $order->save();
            if (!$order_id) {
                wp_send_json_error(array('message' => __('Failed to save order', 'direct-order-popup-for-woocommerce')));
            }

            wp_send_json_success(array(
                'order_id' => $order_id,
                'message' => __('Order created successfully', 'direct-order-popup-for-woocommerce'),
            ));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => sprintf(
                /* translators: %s: Error message */
                __('Error processing order: %s', 'direct-order-popup-for-woocommerce'),
                $e->getMessage()
            )));
        }
    }

    /**
     * Recursively sanitize arrays and strings.
     *
     * @param mixed $data The data to sanitize.
     * @return mixed The sanitized data.
     */
    private function sanitize_recursive($data)
    {
        if (is_array($data)) {
            return array_map(array($this, 'sanitize_recursive'), $data);
        }
        return is_string($data) ? sanitize_text_field($data) : $data;
    }
}
