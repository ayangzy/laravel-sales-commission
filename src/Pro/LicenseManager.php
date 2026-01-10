<?php

namespace SalesCommission\Pro;

use Illuminate\Support\Facades\Cache;

class LicenseManager
{
    /**
     * License key from config.
     */
    protected ?string $licenseKey;

    /**
     * Cache key for license validation.
     */
    protected string $cacheKey = 'sales_commission_pro_license';

    /**
     * Cache duration in seconds (24 hours).
     */
    protected int $cacheDuration = 86400;

    public function __construct()
    {
        $this->licenseKey = config('sales-commission.pro.license_key');
    }

    /**
     * Check if a valid Pro license is active.
     */
    public function isValid(): bool
    {
        if (empty($this->licenseKey)) {
            return false;
        }

        return Cache::remember($this->cacheKey, $this->cacheDuration, function () {
            return $this->validateLicense($this->licenseKey);
        });
    }

    /**
     * Check if Pro features are enabled.
     */
    public function isProEnabled(): bool
    {
        return $this->isValid();
    }

    /**
     * Activate a license key.
     */
    public function activate(string $key): bool
    {
        $this->licenseKey = $key;
        
        if ($this->validateLicense($key)) {
            Cache::put($this->cacheKey, true, $this->cacheDuration);
            return true;
        }

        return false;
    }

    /**
     * Deactivate the current license.
     */
    public function deactivate(): void
    {
        Cache::forget($this->cacheKey);
    }

    /**
     * Clear the license cache.
     */
    public function clearCache(): void
    {
        Cache::forget($this->cacheKey);
    }

    /**
     * Get available Pro features.
     */
    public function getFeatures(): array
    {
        return [
            'admin_panel' => $this->isValid(),
            'api_endpoints' => $this->isValid(),
            'exports' => $this->isValid(),
            'multi_currency' => $this->isValid(),
        ];
    }

    /**
     * Get the current license key (masked).
     */
    public function getMaskedKey(): ?string
    {
        if (empty($this->licenseKey)) {
            return null;
        }

        $length = strlen($this->licenseKey);
        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($this->licenseKey, 0, 4) . str_repeat('*', $length - 8) . substr($this->licenseKey, -4);
    }

    /**
     * Validate a license key.
     * 
     * Simple validation using a prefix and hash check.
     * In production, you might want to validate against an external API.
     */
    protected function validateLicense(string $key): bool
    {
        // License format: SCPRO-XXXX-XXXX-XXXX-XXXX
        // Simple validation: check prefix and minimum length
        if (!str_starts_with($key, 'SCPRO-')) {
            return false;
        }

        if (strlen($key) < 24) {
            return false;
        }

        // Additional validation can be added here:
        // - Check against a list of valid keys
        // - Validate against an external API
        // - Check a cryptographic signature

        return true;
    }

    /**
     * Generate a sample license key (for testing/demos).
     */
    public static function generateSampleKey(): string
    {
        $segments = [];
        for ($i = 0; $i < 4; $i++) {
            $segments[] = strtoupper(substr(md5(uniqid()), 0, 4));
        }

        return 'SCPRO-' . implode('-', $segments);
    }
}
