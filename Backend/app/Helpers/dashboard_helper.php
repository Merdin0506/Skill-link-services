<?php

/**
 * Dashboard view helper functions.
 */

if (!function_exists('formatCurrency')) {
    /**
     * Format currency value.
     *
     * @param float $value
     */
    function formatCurrency($value): string
    {
        return '&#8369;' . number_format((float) $value, 2);
    }
}

if (!function_exists('formatNumber')) {
    /**
     * Format number value.
     *
     * @param int $value
     */
    function formatNumber($value): string
    {
        return number_format($value);
    }
}
