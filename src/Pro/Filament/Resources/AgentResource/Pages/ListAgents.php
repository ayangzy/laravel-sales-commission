<?php

namespace SalesCommission\Pro\Filament\Resources\AgentResource\Pages;

use Filament\Resources\Pages\ListRecords;
use SalesCommission\Pro\Filament\Resources\AgentResource;

class ListAgents extends ListRecords
{
    protected static string $resource = AgentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTableRecordKey($record): string
    {
        return $record->agent_type . ':' . $record->agent_id;
    }
}
