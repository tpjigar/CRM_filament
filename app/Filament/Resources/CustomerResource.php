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
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
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
                EditAction::make(),
                DeleteAction::make(),
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
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['email'];
    }
}
