<?php

namespace App\Filament\Resources\PipelineStagesResource\Pages;

use App\Filament\Resources\PipelineStagesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPipelineStages extends ListRecords
{
    protected static string $resource = PipelineStagesResource::class;

    protected function getActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
