<?php

/**
 * Template for the quick order popup.
 *
 * @since      1.0.0
 * @package    DirectOrderPopupCheckout
 */

defined('ABSPATH') || exit;
?>

<div id="DOPW-popup" class="DOPW-popup" style="display: none;" role="dialog" aria-modal="true">
    <div class="DOPW-popup-overlay"></div>
    <div class="DOPW-popup-content">
        <div class="DOPW-popup-header">
            <h3><?php esc_html_e('Quick Order', 'direct-order-popup-for-woocommerce'); ?></h3>
            <button type="button" class="DOPW-close">&times;</button>
        </div>

        <form id="DOPW-order-form" class="DOPW-form">
            <!-- Customer Information -->
            <div class="DOPW-customer-info">
                <h4><?php esc_html_e('Customer Information', 'direct-order-popup-for-woocommerce'); ?></h4>
                <div class="DOPW-form-row">
                    <div class="DOPW-form-group">
                        <label for="DOPW-name"><?php esc_html_e('Name', 'direct-order-popup-for-woocommerce'); ?> *</label>
                        <input type="text" id="DOPW-name" name="customer_name" required>
                    </div>
                    <div class="DOPW-form-group">
                        <label for="DOPW-phone"><?php esc_html_e('Phone', 'direct-order-popup-for-woocommerce'); ?> *</label>
                        <input type="tel" id="DOPW-phone" name="customer_phone" required>
                    </div>
                </div>
                <div class="DOPW-form-row">
                    <div class="DOPW-form-group">
                        <label for="DOPW-email"><?php esc_html_e('Email', 'direct-order-popup-for-woocommerce'); ?></label>
                        <input type="email" id="DOPW-email" name="customer_email">
                    </div>
                    <div class="DOPW-form-group">
                        <label for="DOPW-address"><?php esc_html_e('Address', 'direct-order-popup-for-woocommerce'); ?> *</label>
                        <textarea id="DOPW-address" name="customer_address" required></textarea>
                    </div>
                </div>
            </div>

            <!-- Product Information -->
            <div class="DOPW-products">
                <h4><?php esc_html_e('Product Information', 'direct-order-popup-for-woocommerce'); ?></h4>
                <div id="DOPW-product-list">
                    <!-- Product rows will be dynamically added here -->
                </div>
                <!-- Variation selectors (for variable products) -->
                <div id="DOPW-variation-selectors" aria-live="polite"></div>
                <div class="DOPW-subtotal">
                    <span><?php esc_html_e('Subtotal:', 'direct-order-popup-for-woocommerce'); ?></span>
                    <span class="DOPW-subtotal-amount">0.00</span>
                </div>
            </div>

            <!-- Shipping Method -->
            <div class="DOPW-shipping">
                <h4><?php esc_html_e('Shipping Method', 'direct-order-popup-for-woocommerce'); ?></h4>
                <select id="DOPW-shipping-method" name="shipping_method[0]" required>
                    <option value=""><?php esc_html_e('Select shipping method', 'direct-order-popup-for-woocommerce'); ?></option>
                    <?php
                    if (function_exists('WC') && (class_exists('WC_Shipping') || WC()->shipping)) {
                        $packages = [];
                        if (function_exists('WC') && isset(WC()->cart) && method_exists(WC()->cart, 'get_shipping_packages')) {
                            $packages = WC()->cart->get_shipping_packages();
                        } elseif (function_exists('WC') && WC()->shipping && method_exists(WC()->shipping(), 'get_packages')) {
                            $packages = WC()->shipping()->get_packages();
                        }

                        $package = !empty($packages) ? reset($packages) : [];
                        $rates = !empty($package['rates']) ? $package['rates'] : [];

                        if (!empty($rates)) {
                            $first = true;
                            foreach ($rates as $rate_id => $rate) {
                                $value = $rate->get_id();
                                $label = $rate->get_label();
                                $cost = (float)$rate->get_cost();
                                $cost_raw = number_format($cost, wc_get_price_decimals(), '.', '');
                                $cost_html = wc_price($cost);
                                $selected_attr = $first ? ' selected="selected"' : '';
                                printf(
                                    '<option value="%s" data-cost="%s"%s>%s - %s</option>',
                                    esc_attr($value),
                                    esc_attr($cost_raw),
                                    esc_attr($selected_attr),
                                    esc_html($label),
                                    wp_kses_post($cost_html)
                                );
                                $first = false;
                            }
                        } else {
                            $zones = WC_Shipping_Zones::get_zones();
                            $found = false;
                            foreach ($zones as $zone) {
                                $zone_obj = new WC_Shipping_Zone($zone['zone_id']);
                                $methods = $zone_obj->get_shipping_methods(true);
                                if (!empty($methods)) {
                                    foreach ($methods as $method) {
                                        if (isset($method->enabled) && $method->enabled === 'yes') {
                                            $found = true;
                                            $method_id = $method->id;
                                            $instance_id = isset($method->instance_id) ? $method->instance_id : '';
                                            $value = $instance_id ? $method_id . ':' . $instance_id : $method_id;
                                            $label = $method->get_method_title ? $method->get_method_title() : ($method->title ?? $method->id);
                                            $cost_opt = $method->get_option('cost', '');
                                            $cost_num = is_string($cost_opt) && $cost_opt !== '' ? floatval(preg_replace('/[^0-9\.\-]/', '', $cost_opt)) : 0;
                                            $cost_raw = number_format($cost_num, wc_get_price_decimals(), '.', '');
                                            $cost_html = wc_price($cost_num);
                                            printf(
                                                '<option value="%s" data-cost="%s">%s - %s</option>',
                                                esc_attr($value),
                                                esc_attr($cost_raw),
                                                esc_html($label),
                                                wp_kses_post($cost_html)
                                            );
                                        }
                                    }
                                }
                            }
                            if (!$found) {
                                echo '<option value="">' . esc_html__('No shipping methods available', 'direct-order-popup-for-woocommerce') . '</option>';
                            }
                        }
                    } else {
                        echo '<option value="">' . esc_html__('WooCommerce shipping not available', 'direct-order-popup-for-woocommerce') . '</option>';
                    }
                    ?>
                </select>
                <div class="DOPW-shipping-cost">
                    <span><?php esc_html_e('Shipping Cost:', 'direct-order-popup-for-woocommerce'); ?></span>
                    <span class="DOPW-shipping-amount">0.00</span>
                </div>

                <!-- Payment Method -->
                <?php
                if (function_exists('WC') && class_exists('WC_Payment_Gateways')) {
                    $gateways = WC()->payment_gateways()->get_available_payment_gateways();
                    if (empty($gateways)) {
                        $all = WC()->payment_gateways()->payment_gateways();
                        $gateways = array_filter($all, function ($g) {
                            return isset($g->enabled) && 'yes' === $g->enabled;
                        });
                    }

                    if (!empty($gateways)) {
                        echo '<fieldset class="DOPW-payment-methods"><legend>' . esc_html__('Payment Method', 'direct-order-popup-for-woocommerce') . '</legend>';
                        $first = true;
                        foreach ($gateways as $gateway_id => $gateway) {
                            $id = esc_attr($gateway->id);
                            $title = esc_html($gateway->get_title());
                            $desc = wp_kses_post($gateway->get_description());
                            $checked = $first ? ' checked="checked"' : '';
                            printf(
                                '<label class="DOPW-payment-method"><input type="radio" name="DOPW_payment_method" value="%s" %s> <span class="DOPW-payment-title">%s</span> <span class="DOPW-payment-desc">%s</span></label>',
                                esc_attr($id),       // value attribute
                                esc_attr($checked),  // checked attribute
                                esc_html($title),    // title text
                                wp_kses_post($desc)  // description (HTML allowed)
                            );

                            $first = false;
                        }
                        echo '</fieldset>';
                    } else {
                        echo '<div class="DOPW-payment-methods"><p>' . esc_html__('No payment methods available.', 'direct-order-popup-for-woocommerce') . '</p></div>';
                    }
                } else {
                    echo '<div class="DOPW-payment-methods"><p>' . esc_html__('WooCommerce payment system not available.', 'direct-order-popup-for-woocommerce') . '</p></div>';
                }
                ?>
            </div>

            <!-- Total -->
            <div class="DOPW-total">
                <strong><?php esc_html_e('Total:', 'direct-order-popup-for-woocommerce'); ?></strong>
                <strong class="DOPW-total-amount">0.00</strong>
            </div>

            <!-- Footer Actions -->
            <div class="DOPW-footer">
                <button type="button" class="DOPW-cancel"><?php esc_html_e('Cancel', 'direct-order-popup-for-woocommerce'); ?></button>
                <button type="submit" class="DOPW-place-order"><?php esc_html_e('Place Order', 'direct-order-popup-for-woocommerce'); ?></button>
            </div>
        </form>
    </div>
</div>