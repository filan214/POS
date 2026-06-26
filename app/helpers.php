<?php

use Illuminate\Support\Carbon;

if (! function_exists('rupiah')) {
    /**
     * Format a number as Indonesian Rupiah. Currency always displays in IDR
     * regardless of the active interface locale (PRD 5.8).
     */
    function rupiah(int|float|string|null $value, bool $symbol = true): string
    {
        $formatted = number_format((float) $value, 0, ',', '.');

        return $symbol ? 'Rp '.$formatted : $formatted;
    }
}

if (! function_exists('locale_date')) {
    /**
     * Locale-aware date formatting, e.g. "20 Juni 2026" / "June 20, 2026".
     */
    function locale_date(Carbon|string|null $date, string $format = 'd MMMM yyyy'): string
    {
        if ($date === null) {
            return '';
        }

        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        return $carbon->locale(app()->getLocale())->isoFormat(
            app()->getLocale() === 'en' ? 'MMMM D, YYYY' : 'D MMMM YYYY'
        );
    }
}
