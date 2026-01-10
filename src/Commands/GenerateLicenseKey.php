<?php

namespace SalesCommission\Commands;

use Illuminate\Console\Command;
use SalesCommission\Pro\LicenseManager;

class GenerateLicenseKey extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'commission:generate-license 
                            {--count=1 : Number of license keys to generate}
                            {--prefix=SCPRO : License key prefix}';

    /**
     * The console command description.
     */
    protected $description = 'Generate Pro license key(s) for the Sales Commission package';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = (int) $this->option('count');
        $prefix = $this->option('prefix');

        $this->info("Generating {$count} license key(s)...");
        $this->newLine();

        $keys = [];

        for ($i = 0; $i < $count; $i++) {
            $key = $this->generateKey($prefix);
            $keys[] = $key;
            $this->line("  <fg=green>✓</> {$key}");
        }

        $this->newLine();
        $this->info('License keys generated successfully!');
        $this->newLine();

        $this->line('<fg=yellow>Add to your .env file:</>');
        $this->newLine();
        $this->line("SALES_COMMISSION_PRO_KEY={$keys[0]}");
        $this->newLine();

        if ($count > 1) {
            $this->line('<fg=yellow>All generated keys:</>');
            foreach ($keys as $key) {
                $this->line("  {$key}");
            }
        }

        return self::SUCCESS;
    }

    /**
     * Generate a single license key.
     */
    protected function generateKey(string $prefix): string
    {
        $segments = [];
        for ($i = 0; $i < 4; $i++) {
            $segments[] = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
        }

        return $prefix . '-' . implode('-', $segments);
    }
}
