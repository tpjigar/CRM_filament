<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use App\Models\PipelineStage;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $slug = 'customers';

    protected static ?string $recordTitleAttribute = 'email';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('first_name')
                ->required()->maxLength(255),

            TextInput::make('last_name')->maxLength(255),

            TextInput::make('email')->maxLength(100),

            TextInput::make('phone_number')->maxLength(50),

            Textarea::make('description')->maxLength(500)->columnSpanFull(),

            Select::make('lead_source_id')
                ->relationship('leadSource', 'name'),

            Select::make('tags')
                ->relationship('tags', 'name')
                ->multiple(),

            Select::make('pipeline_stage_id')
                ->relationship('pipelineStage', 'name', function ($query){
                    $query->orderBy('position', 'ASC');
                })
                ->default(PipelineStage::where('is_default', true)->first()?->id),

            Placeholder::make('created_at')
                ->label('Created Date')
                ->content(fn(?Customer $record): string => $record?->created_at?->diffForHumans() ?? '-'),

            Placeholder::make('updated_at')
                ->label('Last Modified Date')
                ->content(fn(?Customer $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('first_name')
                ->label('Name')
                ->formatStateUsing(function ($record) {
                    $tagsList = view('customer.tagsList', ['tags' => $record->tags])->render();
                    return $record->first_name . ' ' . $record->last_name . ' ' . $tagsList;
                })
                ->html()
                ->searchable(['first_name', 'last_name'])
                ->sortable(),

            TextColumn::make('email')
                ->searchable()
                ->sortable(),

            TextColumn::make('phone_number')
                ->searchable(),

            TextColumn::make('leadSource.name'),
            TextColumn::make('pipelineStage.name'),


            TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('updated_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('deleted_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make()->hidden(fn($record) => $record->trashed()),
                DeleteAction::make(),
                RestoreAction::make(),
                Action::make('Move to Stage')
                    ->hidden(fn($record) => $record->trashed())
                    ->icon('heroicon-m-pencil-square')
                    ->form([
                        Select::make('pipeline_stage_id')
                            ->label('Status')
                            ->options(PipelineStage::pluck('name', 'id')->toArray())
                            ->default(function (Customer $record) {
                                $currentPosition = $record->pipelineStage->position;
                                return PipelineStage::where('position', '>', $currentPosition)->first()?->id;
                            }),
                        Textarea::make('notes')
                            ->label('Notes')
                    ])
                    ->action(function (Customer $customer, array $data): void {
                        $customer->pipeline_stage_id = $data['pipeline_stage_id'];
                        $customer->save();

                        $customer->pipelineStageLogs()->create([
                            'pipeline_stage_id' => $data['pipeline_stage_id'],
                            'notes' => $data['notes'],
                            'user_id' => auth()->id()
                        ]);

                        Notification::make()
                            ->title('Customer Pipeline Updated')
                            ->success()
                            ->send();
                    }),
            ])
            ->recordUrl(function ($record) {
                // If the record is trashed, return null
                if ($record->trashed()) {
                    // Null will disable the row click
                    return null;
                }

                // Otherwise, return the edit page URL
                return Pages\ViewCustomer::getUrl([$record->id]);
//                return Pages\EditCustomer::getUrl([$record->id]);
            })
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
            'view' => Pages\ViewCustomer::route('/{record}'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['email'];
    }

    public static function infoList(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Personal Information')
                    ->schema([
                        TextEntry::make('first_name'),
                        TextEntry::make('last_name'),
                    ])
                    ->columns(),
                Section::make('Contact Information')
                    ->schema([
                        TextEntry::make('email'),
                        TextEntry::make('phone_number'),
                    ])
                    ->columns(),
                Section::make('Additional Details')
                    ->schema([
                        TextEntry::make('description'),
                    ]),
                Section::make('Lead and Stage Information')
                    ->schema([
                        TextEntry::make('leadSource.name'),
                        TextEntry::make('pipelineStage.name'),
                    ])
                    ->columns(),
                Section::make('Pipeline Stage History and Notes')
                    ->schema([
                        ViewEntry::make('pipelineStageLogs')
                            ->label('')
                            ->view('infolists.components.pipeline-stage-history-list')
                    ])
                    ->collapsible()
            ]);
    }
}
