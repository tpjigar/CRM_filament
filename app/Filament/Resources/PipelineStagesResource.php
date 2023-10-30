<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PipelineStagesResource\Pages;
use App\Models\PipelineStage;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PipelineStagesResource extends Resource
{
    protected static ?string $model = PipelineStage::class;

    protected static ?string $slug = 'pipeline-stages';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->required(),

            Placeholder::make('created_at')
                ->label('Created Date')
                ->content(fn(?PipelineStage $record): string => $record?->created_at?->diffForHumans() ?? '-'),

            Placeholder::make('updated_at')
                ->label('Last Modified Date')
                ->content(fn(?PipelineStage $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                IconColumn::make('is_default')->boolean(),
                TextColumn::make('created_at')->sortable()->dateTime(),
                ])
            ->defaultSort('position')
            ->reorderable('position')
            ->actions([
                Action::make('Set Default')
                    ->icon('heroicon-o-star')
                    ->hidden(fn($record) => $record->is_default)
                    ->requiresConfirmation(function (Action $action, $record) {
                        $action->modalDescription('Are you sure you want to set this as the default pipeline stage?');
                        $action->modalHeading('Set "' . $record->name . '" as Default');
                        return $action;
                    })
                    ->action(function (PipelineStage $record) {
                        PipelineStage::where('is_default', true)->update(['is_default' => false]);
                        $record->is_default = true;
                        $record->save();
                    }),
                EditAction::make(),
                DeleteAction::make()
                    ->action(function ($data, $record) {
                        if ($record->customers()->count() > 0) {
                            Notification::make()
                                ->danger()
                                ->title('Pipeline Stage is in use')
                                ->body('Pipeline Stage is in use by customers.')
                                ->send();
                            return;
                        }

                        Notification::make()
                            ->success()
                            ->title('Pipeline Stage deleted')
                            ->body('Pipeline Stage has been deleted.')
                            ->send();

                        $record->delete();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPipelineStages::route('/'),
            'create' => Pages\CreatePipelineStages::route('/create'),
            'edit' => Pages\EditPipelineStages::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }
}
