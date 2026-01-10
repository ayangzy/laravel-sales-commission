<?php

namespace SalesCommission\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CommissionSplit extends Model
{
    use HasUlids;

    protected $fillable = [
        'earning_id',
        'agent_type',
        'agent_id',
        'split_percentage',
        'split_amount',
        'role',
        'metadata',
    ];

    protected $casts = [
        'split_percentage' => 'decimal:2',
        'split_amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function getTable(): string
    {
        return config('sales-commission.tables.commission_splits', 'commission_splits');
    }

    /**
     * Get the parent earning.
     */
    public function earning(): BelongsTo
    {
        return $this->belongsTo(CommissionEarning::class, 'earning_id');
    }

    /**
     * Get the agent receiving this split.
     */
    public function agent(): MorphTo
    {
        return $this->morphTo();
    }
}
