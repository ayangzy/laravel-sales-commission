<?php

namespace SalesCommission\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use SalesCommission\Models\CommissionEarning;
use SalesCommission\Models\CommissionSplit;

class SplitCalculator
{
    protected $commissionable;
    protected CommissionCalculator $calculator;
    protected array $splits = [];
    protected ?CommissionEarning $parentEarning = null;

    public function __construct($commissionable, CommissionCalculator $calculator)
    {
        $this->commissionable = $commissionable;
        $this->calculator = $calculator;
    }

    /**
     * Define how the commission should be split between agents.
     *
     * @param  array  $splits  Array of [agent => percentage] or [agent => ['percentage' => x, 'role' => y]]
     */
    public function between(array $splits): self
    {
        $this->splits = $splits;
        return $this;
    }

    /**
     * Calculate and create split earnings.
     */
    public function calculate(): Collection
    {
        // Validate splits total 100%
        $totalPercentage = $this->getTotalPercentage();
        if (abs($totalPercentage - 100) > 0.01) {
            throw new \InvalidArgumentException("Split percentages must total 100%, got {$totalPercentage}%");
        }

        // Calculate the main earning first (use primary agent)
        $primaryAgent = $this->getPrimaryAgent();
        $this->parentEarning = $this->calculator->calculate($this->commissionable, $primaryAgent);

        if (!$this->parentEarning) {
            return collect();
        }

        $totalCommission = $this->parentEarning->commission_amount;
        $splitRecords = collect();

        foreach ($this->splits as $agent => $config) {
            $percentage = is_array($config) ? $config['percentage'] : $config;
            $role = is_array($config) ? ($config['role'] ?? null) : null;

            if (!($agent instanceof Model)) {
                continue;
            }

            $splitAmount = round($totalCommission * ($percentage / 100), 2);

            $split = CommissionSplit::create([
                'earning_id' => $this->parentEarning->id,
                'agent_type' => get_class($agent),
                'agent_id' => $agent->getKey(),
                'split_percentage' => $percentage,
                'split_amount' => $splitAmount,
                'role' => $role,
            ]);

            $splitRecords->push($split);
        }

        return $splitRecords;
    }

    /**
     * Get total percentage from splits configuration.
     */
    protected function getTotalPercentage(): float
    {
        $total = 0;

        foreach ($this->splits as $agent => $config) {
            $percentage = is_array($config) ? $config['percentage'] : $config;
            $total += $percentage;
        }

        return $total;
    }

    /**
     * Get the primary agent (highest percentage).
     */
    protected function getPrimaryAgent(): ?Model
    {
        $primary = null;
        $highestPercentage = 0;

        foreach ($this->splits as $agent => $config) {
            $percentage = is_array($config) ? $config['percentage'] : $config;
            $role = is_array($config) ? ($config['role'] ?? null) : null;

            // Prefer explicit primary role
            if ($role === 'primary') {
                return $agent;
            }

            if ($percentage > $highestPercentage && $agent instanceof Model) {
                $highestPercentage = $percentage;
                $primary = $agent;
            }
        }

        return $primary;
    }

    /**
     * Get the parent earning record.
     */
    public function getParentEarning(): ?CommissionEarning
    {
        return $this->parentEarning;
    }
}
