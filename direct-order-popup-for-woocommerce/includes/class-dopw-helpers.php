<?php

/**
 * Helper functions for the plugin.
 *
 * @since      1.0.0
 * @package    DirectOrderPopupCheckout
 * @subpackage DirectOrderPopupCheckout/includes
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Helper functions for the plugin.
 */
class DOPW_Helpers
{

    /**
     * Validate product ID.
     *
     * @param int $product_id The product ID to validate.
     * @return bool
     */
    public static function is_valid_product($product_id)
    {
        if (!function_exists('wc_get_product')) {
            return false;
        }

        $product = wc_get_product($product_id);
        return $product && $product->is_purchasable();
    }

    /**
     * Format price with currency symbol.
     *
     * @param float $price The price to format.
     * @return string
     */
    public static function format_price($price)
    {
        if (!function_exists('wc_price')) {
            return number_format($price, 2);
        }

        return wc_price($price);
    }
}
