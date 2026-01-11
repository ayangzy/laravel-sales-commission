<?php

namespace SalesCommission\Support;

class CurrencyHelper
{
    /**
     * Currency symbols mapping.
     */
    protected static array $symbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'NGN' => '₦',
        'JPY' => '¥',
        'CNY' => '¥',
        'INR' => '₹',
        'KRW' => '₩',
        'BRL' => 'R$',
        'ZAR' => 'R',
        'AUD' => 'A$',
        'CAD' => 'C$',
        'CHF' => 'CHF',
        'MXN' => '$',
        'SGD' => 'S$',
        'HKD' => 'HK$',
        'SEK' => 'kr',
        'NOK' => 'kr',
        'DKK' => 'kr',
        'PLN' => 'zł',
        'RUB' => '₽',
        'TRY' => '₺',
        'THB' => '฿',
        'IDR' => 'Rp',
        'MYR' => 'RM',
        'PHP' => '₱',
        'VND' => '₫',
        'AED' => 'د.إ',
        'SAR' => '﷼',
        'EGP' => 'E£',
        'KES' => 'KSh',
        'GHS' => 'GH₵',
        'XOF' => 'CFA',
        'XAF' => 'FCFA',
    ];

    /**
     * Get the symbol for a currency code.
     */
    public static function getSymbol(string $currencyCode): string
    {
        return static::$symbols[strtoupper($currencyCode)] ?? $currencyCode;
    }

    /**
     * Format an amount with the currency symbol.
     */
    public static function format(float $amount, ?string $currencyCode = null, int $decimals = 2): string
    {
        $currencyCode = $currencyCode ?? config('sales-commission.currency', 'USD');
        $symbol = static::getSymbol($currencyCode);
        
        return $symbol . number_format($amount, $decimals);
    }

    /**
     * Get the configured currency.
     */
    public static function getConfiguredCurrency(): string
    {
        return config('sales-commission.currency', 'USD');
    }

    /**
     * Get the configured currency symbol.
     */
    public static function getConfiguredSymbol(): string
    {
        return static::getSymbol(static::getConfiguredCurrency());
    }

    /**
     * Get all supported currencies with their symbols.
     */
    public static function getSupportedCurrencies(): array
    {
        return static::$symbols;
    }
}
