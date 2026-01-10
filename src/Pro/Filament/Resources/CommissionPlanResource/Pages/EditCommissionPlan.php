<?php

namespace SalesCommission\Pro\Filament\Resources\CommissionPlanResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use SalesCommission\Pro\Filament\Resources\CommissionPlanResource;

class EditCommissionPlan extends EditRecord
{
    protected static string $resource = CommissionPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
