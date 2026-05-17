<?php

if (!function_exists('rupiah')) {
    /**
     * Format a number as Indonesian Rupiah.
     *
     * @param  float|int  $amount
     * @param  bool  $withPrefix  Include "Rp" prefix
     * @return string
     */
    function rupiah($amount, bool $withPrefix = true): string
    {
        $formatted = number_format(abs((float) $amount), 0, ',', '.');
        return ($withPrefix ? 'Rp' : '') . $formatted;
    }
}

if (!function_exists('rupiah_signed')) {
    /**
     * Format a number as Rupiah with +/- sign.
     *
     * @param  float|int  $amount
     * @return string
     */
    function rupiah_signed($amount): string
    {
        $sign = $amount >= 0 ? '+' : '-';
        return $sign . 'Rp' . number_format(abs((float) $amount), 0, ',', '.');
    }
}
