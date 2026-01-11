<?php

namespace SalesCommission\Pro\Filament\Resources\CommissionSplitResource\Pages;

use Filament\Resources\Pages\ListRecords;
use SalesCommission\Pro\Filament\Resources\CommissionSplitResource;

class ListCommissionSplits extends ListRecords
{
    protected static string $resource = CommissionSplitResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
