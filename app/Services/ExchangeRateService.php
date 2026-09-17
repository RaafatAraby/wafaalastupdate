<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches USD-quoted exchange rates from a free public API (open.er-api.com).
 *
 * All rates are expressed as "1 unit of source currency = X USD" so that
 * `original_amount * exchange_rate = USD amount`. Results are cached for
 * a few hours to avoid hammering the free API on every form interaction.
 * If the upstream call fails the service falls back to a small built-in
 * table of approximate rates so the form remains usable offline.
 */
class ExchangeRateService
{
    public const SUPPORTED_CURRENCIES = ['USD', 'TRY', 'EUR'];

    private const CACHE_TTL_SECONDS = 6 * 60 * 60;

    /**
     * Approximate fallback rates: 1 unit of <currency> in USD.
     * Used only when the live API is unreachable. Keep these reasonably
     * fresh — Turkish lira in particular drifts fast.
     */
    private const FALLBACK_RATES = [
        'USD' => 1.0,
        'TRY' => 0.026,   // ~38 TRY per USD (mid-2026 baseline)
        'EUR' => 1.08,    // ~0.93 EUR per USD
    ];

    /**
     * How many units of <currency> equal 1 USD (inverse of rateToUsd).
     * Useful for displaying "1 USD = X TRY" instead of "1 TRY = 0.026 USD",
     * which is the way humans normally quote TRY/EUR rates.
     */
    public function unitsPerUsd(string $currency): float
    {
        $rate = $this->rateToUsd($currency);

        return $rate > 0 ? round(1 / $rate, 4) : 0.0;
    }

    /**
     * Return how many USD 1 unit of $currency is worth.
     */
    public function rateToUsd(string $currency): float
    {
        $currency = strtoupper(trim($currency));

        if ($currency === 'USD' || $currency === '') {
            return 1.0;
        }

        $cacheKey = 'exchange_rate.usd_per.'.$currency;

        return (float) Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($currency) {
            try {
                $response = Http::timeout(5)
                    ->retry(2, 200)
                    ->get('https://open.er-api.com/v6/latest/USD');

                if ($response->successful()) {
                    $rates = (array) $response->json('rates', []);
                    $perUsd = (float) ($rates[$currency] ?? 0);

                    if ($perUsd > 0) {
                        // The API returns "1 USD = X <currency>", invert to
                        // get "1 <currency> = Y USD" for the form formula.
                        return round(1 / $perUsd, 6);
                    }
                }
            } catch (\Throwable $exception) {
                Log::warning('ExchangeRateService: live rate fetch failed', [
                    'currency' => $currency,
                    'error' => $exception->getMessage(),
                ]);
            }

            return self::FALLBACK_RATES[$currency] ?? 1.0;
        });
    }

    /**
     * Convert an arbitrary amount in $currency to USD using the live rate.
     */
    public function convertToUsd(float $amount, string $currency): float
    {
        return round($amount * $this->rateToUsd($currency), 2);
    }

    /**
     * Localised currency labels for forms / tables.
     *
     * @return array<string, string>
     */
    public function currencyOptions(): array
    {
        return [
            'USD' => 'دولار أمريكي (USD)',
            'TRY' => 'ليرة تركية (TRY)',
            'EUR' => 'يورو (EUR)',
        ];
    }
}
