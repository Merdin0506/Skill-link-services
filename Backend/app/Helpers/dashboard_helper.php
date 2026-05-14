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
        return 'â‚±' . number_format($value, 2);
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
