<?php

namespace SalesCommission\Pro\Filament\Resources\CommissionPlanResource\Pages;

use Filament\Resources\Pages\ListRecords;
use SalesCommission\Pro\Filament\Resources\CommissionPlanResource;

class ListCommissionPlans extends ListRecords
{
    protected static string $resource = CommissionPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
