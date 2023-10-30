<?php

namespace App\Filament\Resources\PipelineStagesResource\Pages;

use App\Filament\Resources\PipelineStagesResource;
use App\Models\PipelineStage;
use Filament\Resources\Pages\CreateRecord;

class CreatePipelineStages extends CreateRecord
{
    protected static string $resource = PipelineStagesResource::class;

    protected function getActions(): array
    {
        return [

        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['position'] = PipelineStage::max('position') + 1;

        return $data;
    }
}
