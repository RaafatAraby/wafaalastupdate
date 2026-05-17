<?php

namespace App\Support;

/**
 * Helpers that defensively normalize user-entered numeric strings before
 * they are cast and persisted. The admin panel runs in Arabic, which
 * means some browser / OS / keyboard combinations can submit Arabic-Indic
 * (٠-٩) or Eastern Arabic-Indic (۰-۹) digits, with Arabic thousand
 * separators ("٬" U+066C) or decimal marks ("٫" U+066B). PHP's float
 * cast does not understand any of that and silently truncates or
 * returns the prefix of the value, which caused user-visible bugs like
 * "entered 600, got stored as 598".
 */
final class NumericNormalizer
{
    /**
     * Normalize a user-entered string to a canonical decimal format
     * using ASCII digits, a dot decimal separator, and no thousand
     * separators. Returns null when the input cannot be interpreted.
     */
    public static function normalize(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        // Short-circuit: if the input is already a valid ASCII numeric
        // string (including scientific notation like "1e5"), pass it
        // through untouched. The character-stripping pipeline below
        // would otherwise corrupt "1e5" into "15".
        if (is_numeric($value)) {
            return $value;
        }

        // Map Arabic-Indic (U+0660-0669) and Eastern Arabic-Indic
        // (U+06F0-06F9) digits to ASCII 0-9.
        $arabic  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $ascii   = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $value = str_replace($arabic, $ascii, $value);
        $value = str_replace($persian, $ascii, $value);

        // Normalize Arabic decimal mark (٫ U+066B) to "."
        // and Arabic thousand separator (٬ U+066C) to "".
        $value = str_replace(["\u{066B}", "\u{066C}"], ['.', ''], $value);

        // Drop common thousand / whitespace separators.
        $value = str_replace([',', ' ', "\u{00A0}", "\u{2009}"], '', $value);

        // Keep only leading sign + digits + one decimal point.
        $value = preg_replace('/[^0-9.\-]/u', '', $value) ?? '';

        if ($value === '' || $value === '-' || $value === '.') {
            return null;
        }

        // Guard: reject mixed-notation inputs that produced multiple dots
        // or multiple signs (e.g. "12.34٫56" → "12.34.56"), which PHP
        // would silently truncate when cast to float.
        if (! is_numeric($value)) {
            return null;
        }

        return $value;
    }

    /**
     * Normalize the value in $data[$key] in place so that casting /
     * decimal column persistence receives a clean ASCII string.
     */
    public static function apply(array &$data, string $key): void
    {
        if (! array_key_exists($key, $data)) {
            return;
        }

        $normalized = self::normalize($data[$key]);
        if ($normalized !== null) {
            $data[$key] = $normalized;
        }
    }
}
